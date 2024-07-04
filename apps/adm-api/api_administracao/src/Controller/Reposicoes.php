<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutoModel;
use MobileStock\model\Reposicao;
use MobileStock\model\ReposicaoGrade;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ReposicoesService;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\ProdutoService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Reposicoes
{
    public function buscaListaReposicoes()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'itens' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        if (!Gate::allows('ADMIN')) {
            $dados['id_fornecedor'] = Auth::user()->id_colaborador;
        }

        $retorno = ReposicoesService::consultaListaReposicoes($dados);

        return $retorno;
    }

    public function verificarEntradasAppInterno(int $idProduto)
    {
        $resultado = ReposicoesService::listaReposicoesEmAbertoAppInterno($idProduto);

        if (empty($resultado)) {
            throw new \DomainException('Não há entradas em aberto para este produto');
        }

        $reposicoes = array_merge(...array_column($resultado['reposicoes_em_aberto'], 'produtos'));
        $reposicoes = array_column($reposicoes, 'qtd_falta_entrar');
        $totalProdutosParaEntrar = array_sum($reposicoes);

        $resposta = [
            'id_produto' => $idProduto,
            'nome_fornecedor' => $resultado['nome_fornecedor'],
            'localizacao' => $resultado['localizacao'],
            'foto' => $resultado['foto'],
            'referencia' => $resultado['referencia'],
            'total_produtos_para_entrar' => $totalProdutosParaEntrar,
            'reposicoes_em_aberto' => $resultado['reposicoes_em_aberto'],
        ];

        return $resposta;
    }

    public function buscaFornecedorPeloNome()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'nome' => [Validador::OBRIGATORIO, Validador::SANIZAR],
        ]);

        $resposta = ColaboradoresService::consultaFornecedoresPorNome($dados['nome']);
        return $resposta;
    }

    public function buscaProdutosParaReposicaoInterna()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pesquisa' => [],
        ]);

        $resposta = ReposicoesService::buscaProdutosCadastradosPorFornecedor(
            $dados['id_fornecedor'],
            $dados['pesquisa'],
            $dados['pagina']
        );
        return $resposta;
    }

    public function buscaReposicao(int $idReposicao)
    {
        $dados = ReposicoesService::buscaReposicao($idReposicao);
        return $dados;
    }

    public function salvaReposicao(int $idReposicao = null)
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'data_previsao' => [Validador::OBRIGATORIO, Validador::DATA],
            'id_fornecedor' => [Validador::SE(Gate::allows('ADMIN'), Validador::OBRIGATORIO), Validador::NUMERO],
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        if (!Gate::allows('ADMIN')) {
            $dados['id_fornecedor'] = Auth::user()->id_colaborador;
        }

        foreach ($dados['produtos'] as $produto) {
            Validador::validar($produto, [
                'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            foreach ($produto['grades'] as $grade) {
                Validador::validar($grade, [
                    'falta_entregar' => [Validador::NAO_NULO, Validador::NUMERO],
                    'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
                    'quantidade_total' => [Validador::NAO_NULO, Validador::NUMERO],
                    'id_grade' => [Validador::SE(!empty($idReposicao), Validador::OBRIGATORIO), Validador::NUMERO],
                ]);
            }
        }

        DB::beginTransaction();
        $produtos = ProdutoModel::buscaProdutosSalvaReposicao(array_column($dados['produtos'], 'id_produto'));

        $reposicao = new Reposicao();
        $situacao = 'EM_ABERTO';

        if (!empty($idReposicao)) {
            $reposicao->exists = true;
            $reposicao->id = $idReposicao;

            $totalProdutosPrometidos = 0;
            $totalProdutosNaoBipados = 0;

            foreach ($dados['produtos'] as $produto) {
                if (isset($produto['grades'])) {
                    $totalProdutosPrometidos += array_sum(array_column($produto['grades'], 'quantidade_total'));
                    $totalProdutosNaoBipados += array_sum(array_column($produto['grades'], 'falta_entregar'));
                }
            }

            if ($totalProdutosNaoBipados === 0) {
                $situacao = 'ENTREGUE';
            } elseif ($totalProdutosNaoBipados !== $totalProdutosPrometidos && $totalProdutosNaoBipados > 0) {
                $situacao = 'PARCIALMENTE_ENTREGUE';
            }
        }

        $reposicao->id_fornecedor = $dados['id_fornecedor'];
        $reposicao->data_previsao = $dados['data_previsao'];
        $reposicao->id_usuario = Auth::id();
        $reposicao->situacao = $situacao;
        $reposicao->save();

        foreach ($dados['produtos'] as $dadosProduto) {
            foreach ($dadosProduto['grades'] as $grade) {
                $reposicaoGrade = new ReposicaoGrade();

                if (!empty($grade['id_grade'])) {
                    $reposicaoGrade->exists = true;
                    $reposicaoGrade->id = $grade['id_grade'];
                    $reposicaoGrade->data_alteracao = date('Y-m-d H:i:s');
                }

                $reposicaoGrade->id_reposicao = $reposicao->id;
                $reposicaoGrade->id_produto = $dadosProduto['id_produto'];
                $reposicaoGrade->nome_tamanho = $grade['nome_tamanho'];
                $reposicaoGrade->id_usuario = Auth::id();
                $reposicaoGrade->preco_custo_produto = current(
                    array_filter($produtos, fn(array $produto): bool => $dadosProduto['id_produto'] === $produto['id'])
                )['preco_custo'];
                $reposicaoGrade->quantidade_entrada = $grade['quantidade_entrada'] ?? 0;
                $reposicaoGrade->quantidade_total = $grade['quantidade_total'];

                $reposicaoGrade->save();
            }
        }

        DB::commit();
    }

    public function finalizarEntradasEmReposicoes()
    {
        $dados = Request::all();
        $idUsuario = Auth::id();
        Validador::validar($dados, [
            'id_reposicao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        DB::beginTransaction();
        $dados['grades'] = array_filter($dados['grades'], fn($grade) => $grade['qtd_entrada'] > 0);

        $localizacaoVerificada = ProdutoService::verificaLocalizacao($dados['id_produto']);
        $qtdTotal = array_sum(array_column($dados['grades'], 'qtd_entrada'));

        if ($localizacaoVerificada !== null && $localizacaoVerificada !== $dados['localizacao']) {
            throw new BadRequestHttpException(
                "Este produto não pertence a localização {$dados['localizacao']}, bipe a localização correta para prosseguir."
            );
        }

        if ($localizacaoVerificada === null) {
            EstoqueService::atualizaLocalizacaoProduto(
                DB::getPdo(),
                $dados['id_produto'],
                '0',
                $dados['localizacao'],
                $idUsuario,
                $qtdTotal
            );
        }

        ReposicoesService::atualizaEntradaGrades($dados['grades']);
        ReposicoesService::atualizaSituacaoReposicao($dados['id_reposicao']);
        ReposicoesService::atualizaAguardandoEntrada(
            $dados['id_produto'],
            $dados['localizacao'],
            $dados['id_reposicao'],
            $dados['grades']
        );

        $estoque = new EstoqueGradeService();
        $estoque->id_produto = $dados['id_produto'];
        $estoque->id_responsavel = 1;
        $estoque->tipo_movimentacao = 'E';
        $estoque->descricao = "Entrada de produtos da reposição: {$dados['id_reposicao']} usuário: {$idUsuario}";
        foreach ($dados['grades'] as $grade) {
            $estoque->nome_tamanho = $grade['nome_tamanho'];
            $estoque->alteracao_estoque = $grade['qtd_entrada'];
            $estoque->movimentaEstoque(DB::getPdo(), $idUsuario);
        }

        DB::commit();

        return $qtdTotal;
    }

    public function buscaHistoricoEntradas()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'data_inicio' => [Validador::OBRIGATORIO],
            'data_fim' => [Validador::OBRIGATORIO],
        ]);

        if (!empty($dados['id_produto'])) {
            if (!ProdutoModel::verificaExistenciaProduto($dados['id_produto'], null)) {
                throw new BadRequestHttpException('Produto não encontrado');
            }
        }

        $resposta = EstoqueService::buscaHistoricoEntradas(
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['id_produto'] ?? null
        );

        return $resposta;
    }
}

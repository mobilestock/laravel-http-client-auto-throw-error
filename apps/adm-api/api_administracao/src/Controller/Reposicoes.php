<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\jobs\NotificaEntradaEstoque;
use MobileStock\model\ProdutoModel;
use MobileStock\model\Reposicao;
use MobileStock\model\ReposicaoGrade;
use MobileStock\service\ReposicoesService;
use MobileStock\service\Estoque\EstoqueService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $reposicoesEmAberto = Reposicao::reposicoesEmAbertoDoProduto($idProduto);
        if (empty($reposicoesEmAberto)) {
            throw new NotFoundHttpException('Nenhuma reposicao em aberto encontrada para este produto');
        }
        $produtoReferencias = ProdutoModel::obtemReferencias($idProduto);

        $reposicoes = array_merge(...array_column($reposicoesEmAberto, 'produtos'));
        $reposicoes = array_column($reposicoes, 'quantidade_falta_entrar');
        $totalProdutosParaEntrar = array_sum($reposicoes);

        $resposta = [
            'id_produto' => $idProduto,
            'nome_fornecedor' => $produtoReferencias['nome_fornecedor'],
            'localizacao' => $produtoReferencias['localizacao'],
            'foto' => $produtoReferencias['foto'],
            'referencia' => $produtoReferencias['referencia'],
            'quantidade_total_produtos_para_entrar' => $totalProdutosParaEntrar,
            'reposicoes_em_aberto' => $reposicoesEmAberto,
        ];

        return $resposta;
    }

    public function buscaProdutosParaReposicaoInterna()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pesquisa' => [Validador::NAO_NULO],
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
                    'quantidade_falta_entregar' => [
                        Validador::SE(!empty($idReposicao), [Validador::NAO_NULO, Validador::NUMERO]),
                    ],
                    'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
                    'quantidade_total' => [Validador::NAO_NULO, Validador::NUMERO],
                    'id_grade' => [Validador::SE(!empty($idReposicao), [Validador::OBRIGATORIO, Validador::NUMERO])],
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
                $totalProdutosPrometidos += array_sum(array_column($produto['grades'], 'quantidade_total'));
                $totalProdutosNaoBipados += array_sum(array_column($produto['grades'], 'quantidade_falta_entregar'));
            }

            if ($totalProdutosNaoBipados === 0) {
                $situacao = 'ENTREGUE';
            } elseif ($totalProdutosNaoBipados !== $totalProdutosPrometidos) {
                $situacao = 'PARCIALMENTE_ENTREGUE';
            }
        }

        $reposicao->id_fornecedor = $dados['id_fornecedor'];
        $reposicao->data_previsao = $dados['data_previsao'];
        $reposicao->situacao = $situacao;
        $reposicao->save();

        foreach ($dados['produtos'] as $dadosProduto) {
            foreach ($dadosProduto['grades'] as $grade) {
                $reposicaoGrade = new ReposicaoGrade();

                $reposicaoGrade->id_reposicao = $reposicao->id;
                $reposicaoGrade->id_produto = $dadosProduto['id_produto'];
                $reposicaoGrade->nome_tamanho = $grade['nome_tamanho'];
                $reposicaoGrade->preco_custo_produto = current(
                    array_filter($produtos, fn(array $produto): bool => $dadosProduto['id_produto'] === $produto['id'])
                )['preco_custo'];
                $reposicaoGrade->quantidade_total = $grade['quantidade_total'];

                if (!empty($grade['id_grade'])) {
                    $reposicaoGrade->exists = true;
                    $reposicaoGrade->id = $grade['id_grade'];
                }

                $reposicaoGrade->save();
            }
        }

        DB::commit();
    }

    public function finalizarEntradasEmReposicoes()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'id_reposicao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        foreach ($dados['grades'] as $grade) {
            Validador::validar($grade, [
                'id_grade' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'qtd_entrada' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);
        }

        DB::getLock();
        DB::beginTransaction();

        ReposicaoGrade::atualizaEntradaGrades($dados['id_reposicao'], $dados['grades']);
        $idsInseridos = ReposicoesService::preparaProdutosParaEntrada(
            $dados['id_produto'],
            $dados['localizacao'],
            $dados['id_reposicao'],
            $dados['grades']
        );

        $numeracoes = implode(',', $idsInseridos);
        EstoqueService::defineLocalizacaoProduto(
            DB::getPdo(),
            $dados['id_produto'],
            $dados['localizacao'],
            Auth::id(),
            $numeracoes
        );

        DB::commit();

        dispatch(new NotificaEntradaEstoque($dados['id_produto'], $dados['grades']));

        $qtdTotal = array_sum(array_column($dados['grades'], 'qtd_entrada'));
        return $qtdTotal;
    }

    public function buscaHistoricoEntradas()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'data_inicio' => [Validador::OBRIGATORIO, Validador::DATA],
            'data_fim' => [Validador::OBRIGATORIO, Validador::DATA],
            'id_produto' => [Validador::NAO_NULO, Validador::NUMERO],
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

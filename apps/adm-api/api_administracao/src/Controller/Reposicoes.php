<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\Compras\ReposicoesService;
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

        if (Gate::allows('fornecedor')) {
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
            'fornecedor' => $resultado['fornecedor'],
            'localizacao' => $resultado['localizacao'],
            'caminho_foto' => $resultado['caminho_foto'],
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

    public function adicionarReposicao()
    {
        $dados = Request::all();
        DB::beginTransaction();
        Validador::validar($dados, [
            'data_previsao' => [Validador::OBRIGATORIO, Validador::DATA],
            'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
            'situacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'valor_total' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        foreach ($dados['produtos'] as $produto) {
            Validador::validar($produto, [
                'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'valor_unitario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            ReposicoesService::verificaSePermitido($produto['id_produto']);
        }

        $idReposicao = ReposicoesService::insereNovaReposicao(
            $dados['id_fornecedor'],
            $dados['data_previsao'],
            $dados['valor_total']
        );

        foreach ($dados['produtos'] as $produto) {
            foreach ($produto['grades'] as $grade) {
                ReposicoesService::insereNovaReposicaoGrade(
                    $idReposicao,
                    $produto['id_produto'],
                    $grade['nome_tamanho'],
                    $produto['valor_unitario'],
                    $grade['quantidade_total']
                );
            }
        }
        DB::commit();
    }

    public function atualizaReposicao(int $idReposicao)
    {
        $dados = Request::all();
        Validador::validar(
            ['id_reposicao' => $idReposicao],
            ['id_reposicao' => [Validador::OBRIGATORIO, Validador::NUMERO]]
        );
        Validador::validar($dados, [
            'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'data_previsao' => [Validador::OBRIGATORIO],
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
            'valor_total' => [Validador::NAO_NULO, Validador::NUMERO],
        ]);

        foreach ($dados['produtos'] as $produto) {
            Validador::validar($produto, [
                'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'valor_unitario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            ReposicoesService::verificaSePermitido($produto['id_produto']);
        }

        ReposicoesService::atualizaReposicao(
            $idReposicao,
            $dados['id_fornecedor'],
            $dados['data_previsao'],
            $dados['valor_total'],
            $dados['produtos']
        );
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
            if (!ProdutoService::verificaExistenciaProduto($dados['id_produto'], null)) {
                throw new BadRequestHttpException('Produto não encontrado');
            }
        }

        $resposta = ReposicoesService::buscaHistoricoEntradas(
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['id_produto'] ?? null
        );
        return $resposta;
    }
}

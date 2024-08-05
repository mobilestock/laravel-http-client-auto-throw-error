<?php

namespace api_administracao\Controller;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\jobs\NotificaEntradaEstoque;
use MobileStock\model\Produto;
use MobileStock\model\Reposicao;
use MobileStock\model\ReposicaoGrade;
use MobileStock\service\Estoque\EstoqueService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Reposicoes
{
    public function verificarEntradasAppInterno(int $idProduto)
    {
        $reposicoesEmAberto = Reposicao::reposicoesEmAbertoProduto($idProduto);
        if (empty($reposicoesEmAberto)) {
            throw new NotFoundHttpException('Nenhuma reposicao em aberto encontrada para este produto');
        }
        $produtoReferencias = Produto::obtemReferencias($idProduto);

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

        DB::getLock($dados['id_reposicao'], $dados['id_produto']);
        DB::beginTransaction();

        ReposicaoGrade::atualizaEntradaGrades($dados['grades']);
        $idsInseridos = EstoqueService::preparaProdutosParaEntrada(
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

        $grades = [];

        foreach ($dados['grades'] as $grade) {
            $grades[] = Arr::only($grade, ['nome_tamanho', 'qtd_entrada']);
        }

        dispatch(new NotificaEntradaEstoque($dados['id_produto'], $grades));
    }

    public function buscaHistoricoEntradas()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'data_inicio' => [Validador::OBRIGATORIO, Validador::DATA],
            'data_fim' => [Validador::OBRIGATORIO, Validador::DATA],
            'id_produto' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
        ]);

        if (!empty($dados['id_produto'])) {
            Produto::verificaExistenciaProduto($dados['id_produto'], null);
        }

        $resposta = EstoqueService::buscaHistoricoEntradas(
            $dados['data_inicio'],
            $dados['data_fim'],
            $dados['id_produto'] ?? null
        );

        return $resposta;
    }
}

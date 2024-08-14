<?php

namespace api_estoque\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use MobileStock\helper\Validador;
use MobileStock\jobs\NotificaEntradaEstoque;
use MobileStock\model\Produto;
use MobileStock\model\ProdutoLogistica;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\service\Estoque\EstoqueGradeService;

class ProdutosLogistica
{
    public function buscarProdutosReposicaoFulfillment()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pesquisa' => [Validador::NAO_NULO],
        ]);

        $produtos = Produto::buscaCadastrados($dados['pesquisa'], $dados['pagina']);

        return $produtos;
    }

    public function gerarEtiquetasSku()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        $produto = Produto::buscarProdutoPorId($dados['id_produto']);
        if (!Gate::allows('ADMIN') && $produto->id_fornecedor !== Auth::user()->id_colaborador) {
            throw new Exception('Você não tem permissão para gerar essas etiquetas');
        }

        $etiquetas = [];
        DB::beginTransaction();
        foreach ($dados['grades'] as $grade) {
            Validador::validar($grade, [
                'nome_tamanho' => [Validador::OBRIGATORIO],
                'quantidade_impressao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            for ($i = 0; $i < $grade['quantidade_impressao']; $i++) {
                $produtoSku = new ProdutoLogistica([
                    'id_produto' => $dados['id_produto'],
                    'nome_tamanho' => $grade['nome_tamanho'],
                    'origem' => 'REPOSICAO',
                ]);

                $produtoSku->criarSkuPorTentativas();
                $etiquetas[] = [
                    'id_produto' => $produtoSku->id_produto,
                    'nome_tamanho' => $produtoSku->nome_tamanho,
                    'referencia' => $produto->descricao . ' ' . $produto->cores,
                    'qrcode_sku' => 'SKU' . $produtoSku->sku,
                    'sku_formatado' => 'SKU:' . implode('-', mb_str_split($produtoSku->sku, 4)),
                ];
            }
        }
        DB::commit();

        return $etiquetas;
    }

    public function buscarAguardandoEntrada(string $sku)
    {
        $listaProdutos = ProdutoLogistica::buscarAguardandoEntrada($sku);
        return $listaProdutos;
    }

    public function guardarProdutos()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'localizacao' => [Validador::TAMANHO_MINIMO(4), Validador::TAMANHO_MAXIMO(4)],
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        $localizacao = Produto::buscarLocalizacao(array_column($dados['produtos'], 'id_produto'));
        if ($localizacao && $localizacao !== $dados['localizacao']) {
            throw new Exception('Localização inválida');
        }

        $idUsuario = Auth::id();
        DB::beginTransaction();
        foreach ($dados['produtos'] as $produto) {
            Validador::validar($produto, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO],
                'sku' => [Validador::OBRIGATORIO],
            ]);

            $produtoLogistica = new ProdutoLogistica();
            $produtoLogistica->exists = true;
            $produtoLogistica->sku = $produto['sku'];
            $produtoLogistica->id_produto = $produto['id_produto'];
            $produtoLogistica->nome_tamanho = $produto['nome_tamanho'];
            $produtoLogistica->situacao = 'EM_ESTOQUE';
            $produtoLogistica->update();

            $estoque = new EstoqueGradeService();
            $estoque->id_produto = $produtoLogistica->id_produto;
            $estoque->nome_tamanho = $produtoLogistica->nome_tamanho;
            $estoque->alteracao_estoque = 1;
            $estoque->tipo_movimentacao = 'E';
            $estoque->descricao = "SKU:{$produtoLogistica->sku} - Usuario $idUsuario guardou produto no estoque por reposição";
            $estoque->id_responsavel = 1;
            $estoque->movimentaEstoque();

            $produto = Produto::buscarProdutoPorId($produtoLogistica->id_produto);
            if ($produto->data_primeira_entrada === null || $produto->localizacao !== $dados['localizacao']) {
                $produto->data_primeira_entrada =
                    $produto->data_primeira_entrada ?? Carbon::now()->format('Y-m-d H:i:s');
                $produto->localizacao = $dados['localizacao'];
                $produto->update();
            }
        }
        DB::commit();

        $grades = Collection::make($dados['produtos'])
            ->groupBy(fn(array $produto) => "{$produto['id_produto']}-{$produto['nome_tamanho']}")
            ->map(
                fn(Collection $grupo) => [
                    'id_produto' => $grupo->first()['id_produto'],
                    'nome_tamanho' => $grupo->first()['nome_tamanho'],
                    'qtd_movimentado' => $grupo->count(),
                ]
            )
            ->values()
            ->toArray();

        dispatch(new NotificaEntradaEstoque($grades));
    }
}

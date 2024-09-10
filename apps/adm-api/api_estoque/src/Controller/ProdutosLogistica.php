<?php

namespace api_estoque\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaSku;
use MobileStock\helper\Validador;
use MobileStock\jobs\NotificaEntradaEstoque;
use MobileStock\model\Produto;
use MobileStock\model\ProdutoLogistica;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\Estoque\EstoqueService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
            'formato_saida' => [Validador::OBRIGATORIO, Validador::ENUM('ZPL', 'JSON')],
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

                $etiquetaSku = new ImagemEtiquetaSku(
                    $produtoSku->id_produto,
                    $produtoSku->nome_tamanho,
                    $produto->descricao . ' ' . $produto->cores,
                    $produtoSku->sku
                );

                $etiquetas[] = $etiquetaSku->criarZpl();
            }
        }
        DB::commit();

        return $etiquetas;
    }

    public function buscarAguardandoEntrada(string $sku)
    {
        $informacoesProduto = ProdutoLogistica::buscarAguardandoEntrada($sku);
        return $informacoesProduto;
    }

    public function guardarProdutos()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'localizacao' => [Validador::LOCALIZACAO()],
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        ProdutoLogistica::verificaPodeGuardarCodigosSku(array_column($dados['produtos'], 'sku'));
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
            if (!empty($produto->localizacao) && $produto->localizacao !== $dados['localizacao']) {
                throw new BadRequestHttpException('Localização inválida');
            }
            $produto->data_primeira_entrada ??= Carbon::now()->format('Y-m-d H:i:s');
            if ($produto->localizacao !== $dados['localizacao']) {
                $produto->localizacao = $dados['localizacao'];
                $produto->update();
            }
        }
        DB::commit();

        $grades = [];
        foreach ($dados['produtos'] as $produto) {
            $key = $produto['id_produto'] . '-' . $produto['nome_tamanho'];
            if (!isset($grades[$key])) {
                $grades[$key] = [
                    'id_produto' => $produto['id_produto'],
                    'nome_tamanho' => $produto['nome_tamanho'],
                    'qtd_entrada' => 0,
                ];
            }
            $grades[$key]['qtd_entrada']++;
        }
        $grades = array_values($grades);

        dispatch(new NotificaEntradaEstoque($grades));
    }

    public function imprimirEtiquetasSkuPorLocalizacao(string $localizacao)
    {
        Validador::validar(
            ['localizacao' => $localizacao],
            [
                'localizacao' => [Validador::LOCALIZACAO()],
            ]
        );

        DB::beginTransaction();
        $produtosEstoque = EstoqueService::buscarEstoquePorLocalizacao($localizacao);
        $codigosSkuValidos = ProdutoLogistica::filtraCodigosSkuPorProdutos($produtosEstoque);

        $codigosZpl = [];
        foreach ($produtosEstoque as $dadosEstoque) {
            $produtoComSku = current(
                array_filter($codigosSkuValidos, function (array $dadosSku) use ($dadosEstoque) {
                    return $dadosSku['id_produto'] === $dadosEstoque['id_produto'] &&
                        $dadosSku['nome_tamanho'] === $dadosEstoque['nome_tamanho'];
                })
            );

            $dadosEstoque['codigos_sku'] = $produtoComSku['codigos_sku'] ?? [];
            $codigosSkuFaltantes = $dadosEstoque['estoque'] - count($dadosEstoque['codigos_sku']);

            if ($codigosSkuFaltantes > 0) {
                for ($i = 0; $i < $codigosSkuFaltantes; $i++) {
                    $produtoSku = new ProdutoLogistica([
                        'id_produto' => $dadosEstoque['id_produto'],
                        'nome_tamanho' => $dadosEstoque['nome_tamanho'],
                        'origem' => 'REPOSICAO',
                        'situacao' => 'EM_ESTOQUE',
                    ]);
                    $produtoSku->criarSkuPorTentativas();

                    $etiquetaSku = new ImagemEtiquetaSku(
                        $produtoSku->id_produto,
                        $produtoSku->nome_tamanho,
                        $dadosEstoque['referencia'],
                        $produtoSku->sku
                    );
                    $codigosZpl[] = $etiquetaSku->criarZpl();
                }
            }
        }
        DB::commit();

        return $codigosZpl;
    }
}

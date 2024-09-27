<?php

namespace api_estoque\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaSku;
use MobileStock\helper\Validador;
use MobileStock\jobs\NotificaEntradaEstoque;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Produto;
use MobileStock\model\ProdutoLogistica;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\Estoque\EstoqueService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
                    'sku_formatado' => Str::formatarSku($produtoSku->sku),
                ];
            }
        }
        DB::commit();

        return $etiquetas;
    }

    public function buscarProdutosGuardar(string $codigo)
    {
        if (preg_match(LogisticaItemModel::REGEX_ETIQUETA_UUID_PRODUTO_CLIENTE, $codigo)) {
            $codigo = LogisticaItemModel::buscaInformacoesLogisticaItem($codigo)->sku;
        }

        $produto = ProdutoLogistica::buscarPorSku($codigo);

        $dadosProdutos = [];
        $codigosSkuGrades = [];
        if ($produto->origem === 'REPOSICAO' && $produto->situacao === 'AGUARDANDO_ENTRADA') {
            $dadosLogistica = ProdutoLogistica::buscarReposicoesAguardandoEntrada($produto->id_produto);
            $localizacao = $dadosLogistica['localizacao'];
            $dadosProdutos = $dadosLogistica['produtos'];
            $origem = 'REPOSICAO';
        } elseif ($produto->situacao === 'EM_ESTOQUE') {
            $localizacao = Produto::buscarProdutoPorId($produto->id_produto)->localizacao;
            $produtosGrades = EstoqueService::buscarEstoqueGradePorLocalizacao($localizacao, $produto->id_produto);
            $codigosSkuGrades = ProdutoLogistica::filtraCodigosSkuPorGrades($produtosGrades);

            foreach ($produtosGrades as $grade) {
                $produtoComSku = current(
                    array_filter($codigosSkuGrades, function (array $dadosSku) use ($grade): bool {
                        return $dadosSku['id_produto'] === $grade['id_produto'] &&
                            $dadosSku['nome_tamanho'] === $grade['nome_tamanho'];
                    })
                );

                if (empty($produtoComSku)) {
                    continue;
                }

                if ($grade['estoque'] > count($produtoComSku['unidades_produtos'])) {
                    throw new ConflictHttpException($localizacao);
                }

                for ($i = 0; $i < $grade['estoque']; $i++) {
                    $uuidProduto = $produtoComSku['unidades_produtos'][$i]['uuid_produto'] ?? null;
                    $dadosProdutos[] = [
                        'id_produto' => $grade['id_produto'],
                        'nome_tamanho' => $grade['nome_tamanho'],
                        'referencia' => $grade['referencia'],
                        'foto' => $grade['foto'],
                        'localizacao' => $grade['localizacao'],
                        'sku' => $uuidProduto ? $produtoComSku['unidades_produtos'][$i]['sku'] : null,
                        'uuid_produto' => $uuidProduto,
                    ];
                }
            }

            $origem = 'ESTOQUE';
        } else {
            throw new BadRequestHttpException('Produto não suportado');
        }

        return [
            'origem' => $origem,
            'localizacao' => $localizacao,
            'produtos' => $dadosProdutos,
            'grades_sku' => $codigosSkuGrades,
        ];
    }

    public function guardarProdutos()
    {
        $dados = Request::all();
        Validador::validar($dados, [
            'localizacao' => [Validador::LOCALIZACAO()],
            'produtos_alterar_localizacao' => [Validador::ARRAY],
            'produtos_reposicao' => [Validador::ARRAY],
        ]);

        DB::beginTransaction();
        if (!empty($dados['produtos_alterar_localizacao'])) {
            $idsUnicos = [];
            foreach ($dados['produtos_alterar_localizacao'] as $produto) {
                Validador::validar($produto, [
                    'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'nome_tamanho' => [Validador::OBRIGATORIO],
                    'sku' => [Validador::OBRIGATORIO],
                ]);

                if (!in_array($produto['id_produto'], $idsUnicos)) {
                    $idsUnicos[] = $produto['id_produto'];
                }
            }

            EstoqueService::verificaPodeAlterarLocalizacao($dados['produtos_alterar_localizacao']);
            foreach ($idsUnicos as $idProduto) {
                $produto = new Produto();
                $produto->exists = true;
                $produto->id = $idProduto;
                $produto->localizacao = $dados['localizacao'];
                $produto->update();
            }
        }

        if (!empty($dados['produtos_reposicao'])) {
            foreach ($dados['produtos_reposicao'] as $produto) {
                Validador::validar($produto, [
                    'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'nome_tamanho' => [Validador::OBRIGATORIO],
                    'sku' => [Validador::OBRIGATORIO],
                ]);
            }

            ProdutoLogistica::verificaPodeGuardarCodigosSku(array_column($dados['produtos_reposicao'], 'sku'));
            $idUsuario = Auth::id();
            foreach ($dados['produtos_reposicao'] as $produto) {
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

            $grades = [];
            foreach ($dados['produtos_reposicao'] as $produto) {
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

            $job = new NotificaEntradaEstoque($grades);
            dispatch($job->afterCommit());
        }

        DB::commit();
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
        $produtosEstoque = EstoqueService::buscarEstoqueGradePorLocalizacao($localizacao);
        $codigosSkuValidos = ProdutoLogistica::filtraCodigosSkuPorGrades($produtosEstoque);

        $codigosZpl = [];
        foreach ($produtosEstoque as $dadosEstoque) {
            $produtoComSku = current(
                array_filter($codigosSkuValidos, function (array $dadosSku) use ($dadosEstoque) {
                    return $dadosSku['id_produto'] === $dadosEstoque['id_produto'] &&
                        $dadosSku['nome_tamanho'] === $dadosEstoque['nome_tamanho'];
                })
            );

            if (empty($produtoComSku)) {
                continue;
            }

            $dadosEstoque['codigos_sku'] = array_column($produtoComSku['unidades_produtos'], 'sku') ?? [];
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

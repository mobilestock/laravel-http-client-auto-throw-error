<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class Produto extends Model
{
    protected $fillable = ['permitido_reposicao', 'eh_moda'];
    protected $casts = [
        'eh_moda' => 'boolean',
    ];
    public $timestamps = false;

    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE = 82044;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_EXPRESSO = 82042;

    public static function buscarProdutoPorId(int $idProduto): self
    {
        $produto = self::fromQuery(
            "SELECT
                produtos.id,
                produtos.eh_moda,
                produtos.permitido_reposicao,
                produtos.descricao,
                produtos.id_fornecedor,
                produtos.id_linha,
                produtos.valor_custo_produto,
                produtos.nome_comercial,
                produtos.tipo_grade,
                produtos.sexo,
                produtos.cores,
                produtos.bloqueado,
                produtos.forma,
                produtos.fora_de_linha,
                produtos.embalagem,
                produtos.outras_informacoes
            FROM produtos
            WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }

    public static function desativaPromocaoMantemValores(int $idProduto): void
    {
        DB::beginTransaction();
        $produto = self::buscarProdutoPorId($idProduto);
        $valorCustoProduto = $produto->valor_custo_produto;
        $produto->preco_promocao = 0;
        $produto->save();

        $produto->refresh();

        $produto->valor_custo_produto = $valorCustoProduto;
        $produto->save();
        DB::commit();
    }

    public static function salvaPromocao(array $produtos)
    {
        foreach ($produtos as $produto) {

            $produtoModel = self::buscarProdutoPorId($produto['id']);

            if (Gate::allows('FORNECEDOR') && $produto['promocao'] === 100) {
                if ($produto['promocao'] === 100) {
                    throw new UnauthorizedException(
                        "Você não tem permissão para alterar o produto $produtoModel->descricao para promoção 100%"
                    );
                }
            }

            DB::beginTransaction();
            $produtoModel->preco_promocao = $produto['promocao'];
            $produtoModel->id_usuario = Auth::user()->id;
            $produtoModel->data_entrada = $produtoModel->promocao === '1' ? $produtoModel->data_entrada : now();
            $produtoModel->save();
            DB::commit();
        }
    }
}

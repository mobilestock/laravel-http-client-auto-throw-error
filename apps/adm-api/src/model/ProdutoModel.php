<?php

namespace MobileStock\model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 *
 * @property int $id
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class ProdutoModel extends Model
{
    protected $table = 'produtos';
    protected $fillable = ['permitido_reposicao', 'eh_moda'];
    protected $casts = [
        'eh_moda' => 'bool',
    ];
    public $timestamps = false;

    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE = 82044;

    public static function buscarProdutoPorId(int $idProduto): self
    {
        $produto = self::fromQuery(
            "SELECT
                produtos.id,
                produtos.eh_moda,
                produtos.permitido_reposicao
            FROM produtos WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }
}

<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class Produto extends Model
{
    protected $fillable = [
        'descricao',
        'id_forncedor',
        'bloqueado',
        'id_linha',
        'data_entrada',
        'outras_informacoes',
        'forma',
        'embalagem',
        'nome_comercial',
        'preco_promocao',
        'valor_custo_produto',
        'id_usuario',
        'tipo_grade',
        'sexo',
        'cores',
        'fora_de_linha',
        'permitido_reposicao',
        'eh_moda',
    ];
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
                produtos.eh_moda,
                produtos.permitido_reposicao,
                produtos.descricao,
                produtos.valor_custo_produto,
                produtos.preco_promocao,
                produtos.data_entrada
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
            if ($produto['promocao'] === 100) {
                throw new BadRequestHttpException(
                    "Uma promoção de 100% não é permitida."
                );
            }

            DB::beginTransaction();
            $produtoModel = self::buscarProdutoPorId($produto['id']);
            $produtoModel->preco_promocao = $produto['promocao'];
            $produtoModel->data_entrada = $produtoModel->promocao === '1' ? $produtoModel->data_entrada : now();
            $produtoModel->save();
            DB::commit();
        }
    }
}

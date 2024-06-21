<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $valorCustoProduto = DB::selectOneColumn(
            "SELECT produtos.valor_custo_produto
            FROM produtos
            WHERE produtos.id = :id_produto;",
            ['id_produto' => $idProduto]
        );

        $linhasAlteradas = DB::update(
            "UPDATE produtos
            SET produtos.preco_promocao = 0,
                produtos.id_usuario = :id_usuario
            WHERE produtos.id = :id_produto;",
            ['id_produto' => $idProduto, 'id_usuario' => Auth::id()]
        );
        if ($linhasAlteradas !== 1) {
            throw new Exception('Não foi possível desativar a promoção');
        }

        $linhasAlteradas = DB::update(
            "UPDATE produtos
            SET produtos.valor_custo_produto = :valor_custo_produto
            WHERE produtos.id = :id_produto;",
            ['id_produto' => $idProduto, 'valor_custo_produto' => $valorCustoProduto]
        );
        if ($linhasAlteradas !== 1) {
            throw new Exception('Não foi possível atualizar o custo do produto');
        }
    }
}

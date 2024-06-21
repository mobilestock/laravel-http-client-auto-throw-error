<?php

namespace MobileStock\model;

use Error;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

            if (Gate::allows('FORNECEDOR') && $produto['promocao'] == 100) {
                if ($produto['promocao'] == 100) {
                    $produtoQuery = DB::selectOneColumn("SELECT produtos.descricao FROM produtos WHERE produtos.id = {$produto['id']};");
                    throw new Error(
                        "Você não tem permissão para alterar o produto '{$produtoQuery['descricao']}' para promoção 100%",
                        401
                    );
                }
                throw new Error('Você não tem permissão para alterar este produto', 401);
            }

            if ($produto['promocao'] == 100 && $produto['pontos'] < 100) {
                $produtoQuery = DB::selectOneColumn("SELECT produtos.descricao FROM produtos WHERE produtos.id = {$produto['id']};");
                throw new Error(
                    "Para definir '{$produtoQuery['descricao']}' como promoção, defina primeiro o valor em pontos.",
                    401
                );
            }

            $linhasAlteradas = DB::update(
                "UPDATE produtos SET
                    produtos.preco_promocao = :promocao,
                    produtos.premio_pontos = :pontos,
                    produtos.id_usuario = :usuario,
                    produtos.data_entrada = CASE WHEN  produtos.promocao = '1' THEN produtos.data_entrada ELSE now() END
                WHERE produtos.id = :id",
                [
                    ':promocao' => $produto['promocao'],
                    ':pontos' => $produto['pontos'],
                    ':usuario' => Auth::user()->id,
                    ':id' => $produto['id'],
                ]
            );

            if ($linhasAlteradas < 1) {
                throw new Error('Erro ao salvar dados da promoção', 500);
            }
        }
    }
}

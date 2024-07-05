<?php

namespace MobileStock\model;

use DomainException;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
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
                produtos.permitido_reposicao
            FROM produtos
            WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }

    public static function verificaExistenciaProduto(int $idProduto, ?string $nomeTamanho): bool
    {
        $innerJoin = '';
        $bindings = ['id_produto' => $idProduto];
        if ($nomeTamanho) {
            $innerJoin = 'INNER JOIN produtos_grade ON produtos_grade.id_produto = produtos.id
                AND produtos_grade.nome_tamanho = :nome_tamanho';
            $bindings['nome_tamanho'] = $nomeTamanho;
        }

        $ehValido = DB::selectOneColumn(
            "SELECT EXISTS (
                SELECT 1
                FROM produtos
                $innerJoin
                WHERE produtos.id = :id_produto
            ) AS eh_valido",
            $bindings
        );

        return $ehValido;
    }

    /**
     * @param array<int> $idsProdutos
     */
    public static function buscaProdutosSalvaReposicao(array $idsProdutos): array
    {
        [$referenciaSql, $binds] = ConversorArray::criaBindValues($idsProdutos, 'id_produto');
        $produtos = DB::select(
            "SELECT
                produtos.id,
                produtos.valor_custo_produto AS `preco_custo`
            FROM produtos
            WHERE produtos.id IN ($referenciaSql)
            AND produtos.permitido_reposicao = 1",
            $binds
        );

        if (count($produtos) !== count($idsProdutos)) {
            throw new DomainException('Pelo menos um dos produtos não tem permissão para repor no Mobile Stock');
        }

        return $produtos;
    }

    public static function obtemReferencias(int $idProduto): array
    {
        $resultadoReferencias = DB::selectOne(
            "SELECT
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ), '{$_ENV['URL_MOBILE']}images/img-placeholder.png'
                ) AS `foto`,
                GROUP_CONCAT(DISTINCT CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, ''))) AS `referencia`,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) AS `nome_fornecedor`,
                produtos.localizacao
            FROM produtos
            WHERE produtos.id = :id_produto",
            ['id_produto' => $idProduto]
        );

        return $resultadoReferencias;
    }
}

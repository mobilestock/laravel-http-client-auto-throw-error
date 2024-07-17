<?php

namespace MobileStock\model;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $id_fornecedor
 * @property string $situacao
 */
class Reposicao extends Model
{
    protected $table = 'reposicoes';
    protected $fillable = ['id_fornecedor', 'id_usuario', 'situacao'];

    public static function reposicoesEmAbertoProduto(int $idProduto): array
    {
        $sqlCalculoPrecoTotal = ReposicaoGrade::sqlCalculoPrecoTotalReposicao();
        $listaReposicoes = DB::select(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                DATE_FORMAT(reposicoes.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                reposicoes.situacao,
                $sqlCalculoPrecoTotal,
                CONCAT(
                    '[',
                        GROUP_CONCAT(DISTINCT
                            JSON_OBJECT(
                                'id_reposicao', reposicoes.id,
                                'id_grade', reposicoes_grades.id,
                                'id_produto', reposicoes_grades.id_produto,
                                'cod_barras', produtos_grade.cod_barras,
                                'referencia', (
                                    SELECT CONCAT(produtos.descricao, ' ', produtos.cores)
                                    FROM produtos
                                    WHERE produtos.id = reposicoes_grades.id_produto
                                    LIMIT 1
                                ),
                                'quantidade_falta_entrar', reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada,
                                'nome_tamanho', reposicoes_grades.nome_tamanho
                            ) ORDER BY produtos_grade.sequencia ASC
                        ),
                    ']'
                ) AS `json_produtos`
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            INNER JOIN produtos_grade ON produtos_grade.id_produto = reposicoes_grades.id_produto
                AND produtos_grade.nome_tamanho = reposicoes_grades.nome_tamanho
            WHERE reposicoes_grades.id_produto = :id_produto
                AND reposicoes.situacao IN ('EM_ABERTO', 'PARCIALMENTE_ENTREGUE')
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC",
            ['id_produto' => $idProduto]
        );

        return $listaReposicoes;
    }

    public static function consultaListaReposicoes(array $filtros): array
    {
        $where = '';
        $bindings = [];

        if ($filtros['itens'] < 0) {
            $itens = PHP_INT_MAX;
            $offset = 0;
        } else {
            $itens = $filtros['itens'];
            $offset = ($filtros['pagina'] - 1) * $itens;
        }

        if (!empty($filtros['id_reposicao'])) {
            $where .= ' AND reposicoes.id =  :id_reposicao';
            $bindings[':id_reposicao'] = $filtros['id_reposicao'];
        }

        if (!empty($filtros['id_fornecedor'])) {
            $where .= ' AND reposicoes.id_fornecedor = :id_fornecedor';
            $bindings[':id_fornecedor'] = $filtros['id_fornecedor'];
        }

        if (!empty($filtros['referencia'])) {
            $where .= " AND EXISTS(
                SELECT 1
                FROM produtos
                WHERE produtos.id = reposicoes_grades.id_produto
                    AND CONCAT_WS(' - ', produtos.id, produtos.descricao, produtos.cores) LIKE :referencia
            )";
            $bindings[':referencia'] = '%' . $filtros['referencia'] . '%';
        }

        if (!empty($filtros['nome_tamanho'])) {
            $where .= ' AND reposicoes_grades.nome_tamanho = :nome_tamanho';
            $bindings[':nome_tamanho'] = $filtros['nome_tamanho'];
        }

        if (!empty($filtros['situacao'])) {
            $where .= ' AND reposicoes.situacao = :situacao';
            $bindings[':situacao'] = $filtros['situacao'];
        }

        if (!empty($filtros['data_inicial_emissao']) && !empty($filtros['data_fim_emissao'])) {
            $where .=
                ' AND DATE(reposicoes.data_criacao) BETWEEN DATE(:data_emissao_inicial) AND DATE(:data_emissao_final)';
            $bindings[':data_emissao_inicial'] = $filtros['data_inicial_emissao'];
            $bindings[':data_emissao_final'] = $filtros['data_fim_emissao'];
        }

        $sqlCalculoPrecoTotal = ReposicaoGrade::sqlCalculoPrecoTotalReposicao();

        $reposicoes = DB::select(
            "SELECT
                reposicoes.id,
                reposicoes.data_criacao,
                reposicoes.situacao,
                $sqlCalculoPrecoTotal,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = reposicoes.id_fornecedor
                ) AS `fornecedor`
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE 1 = 1 $where
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC
            LIMIT $itens OFFSET $offset",
            $bindings
        );

        return $reposicoes;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/438
     */
    public static function buscaReposicao(int $idReposicao): array
    {
        $dadosReposicao = DB::selectOne(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes.id_fornecedor,
                reposicoes.situacao
            FROM reposicoes
            WHERE reposicoes.id = :id_reposicao",
            ['id_reposicao' => $idReposicao]
        );

        $produtos = DB::select(
            "SELECT
                reposicoes_grades.id_produto,
                SUM(reposicoes_grades.quantidade_total) AS `quantidade_total_grade`,
                (
                    SELECT
                        produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = reposicoes_grades.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`,
                reposicoes_grades.preco_custo_produto,
                CONCAT(
                    '[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'id_grade', reposicoes_grades.id,
                                'nome_tamanho', reposicoes_grades.nome_tamanho,
                                'quantidade_em_estoque', COALESCE(estoque_grade.estoque, 0),
                                'quantidade_falta_entregar', reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada,
                                'quantidade_total', reposicoes_grades.quantidade_total
                            ) ORDER BY reposicoes_grades.nome_tamanho
                        ),
                    ']'
                ) AS `json_grades`
            FROM reposicoes_grades
            LEFT JOIN estoque_grade ON estoque_grade.id_produto = reposicoes_grades.id_produto
                AND estoque_grade.id_responsavel = 1
                AND estoque_grade.nome_tamanho = reposicoes_grades.nome_tamanho
            WHERE reposicoes_grades.id_reposicao = :id_reposicao
            GROUP BY reposicoes_grades.id_produto;",
            ['id_reposicao' => $idReposicao]
        );

        $dadosReposicao['produtos'] = array_map(function (array $produto): array {
            $produto['preco_total_grade'] = $produto['preco_custo_produto'] * $produto['quantidade_total_grade'];
            $totaisGrades = [
                'falta_entregar' => array_sum(array_column($produto['grades'], 'quantidade_falta_entregar')),
                'total' => array_sum(array_column($produto['grades'], 'quantidade_total')),
            ];
            if ($totaisGrades['falta_entregar'] === 0) {
                $produto['situacao_grade'] = 'Entregue';
            } elseif (
                $totaisGrades['falta_entregar'] > 0 &&
                $totaisGrades['falta_entregar'] !== $totaisGrades['total']
            ) {
                $produto['situacao_grade'] = 'Parcialmente Entregue';
            } else {
                $produto['situacao_grade'] = 'Em Aberto';
            }
            return $produto;
        }, $produtos);

        $dadosReposicao['quantidade_total'] = array_sum(
            array_column($dadosReposicao['produtos'], 'quantidade_total_grade')
        );

        return $dadosReposicao;
    }

    public static function buscaReposicoesDoProduto(int $idProduto, bool $verApenasPendentes): array
    {
        $where = '';
        if ($verApenasPendentes) {
            $where = ' AND reposicoes.situacao IN ("EM_ABERTO", "PARCIALMENTE_ENTREGUE") ';
        }

        $reposicoes = DB::select(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes_grades.id_produto,
                reposicoes.id_fornecedor,
                DATE_FORMAT(reposicoes.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                reposicoes.id_usuario,
                reposicoes.situacao
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE reposicoes_grades.id_produto = :id_produto
                $where
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC",
            [':id_produto' => $idProduto]
        );

        return $reposicoes;
    }

    public static function buscaEtiquetasUnitarias(int $idReposicao): array
    {
        $grades = DB::select(
            "SELECT
                reposicoes_grades.id_produto,
                CONCAT(produtos.descricao, ' ', produtos.cores) AS `referencia`,
                reposicoes_grades.nome_tamanho,
                reposicoes_grades.quantidade_total,
                CONCAT('SKU_', produtos.id, '_', produtos_grade.cod_barras) AS `sku`
            FROM reposicoes_grades
            INNER JOIN produtos_grade ON produtos_grade.id_produto = reposicoes_grades.id_produto
                AND produtos_grade.nome_tamanho = reposicoes_grades.nome_tamanho
            INNER JOIN produtos ON produtos.id = reposicoes_grades.id_produto
            WHERE reposicoes_grades.id_reposicao = :id_reposicao
                AND reposicoes_grades.quantidade_total > 0
            GROUP BY reposicoes_grades.id
            ORDER BY reposicoes_grades.id_produto, reposicoes_grades.nome_tamanho",
            ['id_reposicao' => $idReposicao]
        );

        $etiquetas = array_map(function (array $grade): array {
            $etiquetas = array_fill(
                0,
                $grade['quantidade_total'],
                Arr::only($grade, ['referencia', 'nome_tamanho', 'id_produto', 'sku'])
            );

            return $etiquetas;
        }, $grades);
        $etiquetas = array_merge(...$etiquetas);

        return $etiquetas;
    }
}

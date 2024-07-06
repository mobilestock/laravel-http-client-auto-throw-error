<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $id_fornecedor
 * @property string $data_previsao
 * @property string $situacao
 */
class Reposicao extends Model
{
    protected $table = 'reposicoes';
    protected $fillable = ['id_fornecedor', 'data_previsao', 'id_usuario', 'situacao'];

    public static function reposicoesEmAbertoDoProduto(int $idProduto): array
    {
        $sqlCalculoPrecoTotal = ReposicaoGrade::sqlCalculoPrecoTotalReposicao();
        $listaReposicoes = DB::select(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes.data_criacao AS `data_emissao`,
                reposicoes.data_previsao,
                reposicoes.situacao,
                $sqlCalculoPrecoTotal,
                CONCAT(
                    '[',
                        GROUP_CONCAT(DISTINCT
                            JSON_OBJECT(
                                'id_reposicao', reposicoes.id,
                                'id_grade', reposicoes_grades.id,
                                'id_produto', reposicoes_grades.id_produto,
                                'cod_barras', (
                                    SELECT produtos_grade.cod_barras
                                    FROM produtos_grade
                                    WHERE produtos_grade.id_produto = reposicoes_grades.id_produto
                                        AND produtos_grade.nome_tamanho = reposicoes_grades.nome_tamanho
                                    LIMIT 1
                                ),
                                'referencia', (
                                    SELECT CONCAT(produtos.descricao, ' ', produtos.cores)
                                    FROM produtos
                                    WHERE produtos.id = reposicoes_grades.id_produto
                                    LIMIT 1
                                ),
                                'quantidade_falta_entrar', reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada,
                                'nome_tamanho', reposicoes_grades.nome_tamanho
                            ) ORDER BY reposicoes_grades.nome_tamanho ASC
                        ),
                    ']'
                ) AS `json_produtos`
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE reposicoes_grades.id_produto = :id_produto
                AND reposicoes.situacao IN ('EM_ABERTO', 'PARCIALMENTE_ENTREGUE')
                AND (reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada) > 0
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC",
            ['id_produto' => $idProduto]
        );

        return $listaReposicoes;
    }
}

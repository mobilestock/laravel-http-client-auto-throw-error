<?php

namespace MobileStock\model;

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
}

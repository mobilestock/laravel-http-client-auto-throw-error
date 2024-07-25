<?php

namespace MobileStock\repository;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\Validador;

class EstoqueRepository
{
    /**
     *  @issue https://github.com/mobilestock/backend/issues/401
     */
    public static function insereGrade(array $grades, int $idProduto, int $idFornecedor): void
    {
        foreach ($grades as $grade) {
            Validador::validar($grade, [
                'sequencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            $sequencia = (int) $grade['sequencia'];
            $nomeTamanho = $grade['nome_tamanho'];

            $existeReposicao = DB::selectOneColumn(
                "SELECT EXISTS(
                    SELECT 1
                    FROM reposicoes_grades
                    WHERE reposicoes_grades.id_produto = :id_produto
                        AND reposicoes_grades.nome_tamanho = :nome_tamanho
                ) AS `existe_reposicao`;",
                [':id_produto' => $idProduto, ':nome_tamanho' => $nomeTamanho]
            );

            if ($existeReposicao) {
                continue;
            }

            DB::delete(
                "DELETE FROM produtos_grade
                WHERE produtos_grade.id_produto = :id_produto
                    AND produtos_grade.nome_tamanho = :nome_tamanho;",
                [':id_produto' => $idProduto, ':nome_tamanho' => $nomeTamanho]
            );

            DB::insert(
                "INSERT INTO produtos_grade(
                    produtos_grade.id_produto,
                    produtos_grade.sequencia,
                    produtos_grade.nome_tamanho,
                    produtos_grade.cod_barras
                ) VALUES (
                    :id_produto,
                    :sequencia,
                    :nome_tamanho,
                    CONCAT(:id_fornecedor, :id_produto, :sequencia)
                );",
                [
                    ':id_produto' => $idProduto,
                    ':sequencia' => $sequencia,
                    ':nome_tamanho' => $nomeTamanho,
                    ':id_fornecedor' => $idFornecedor,
                ]
            );
        }
    }
}

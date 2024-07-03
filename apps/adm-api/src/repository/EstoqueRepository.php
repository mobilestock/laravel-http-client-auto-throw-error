<?php

namespace MobileStock\repository;

use Exception;
use Illuminate\Support\Facades\DB as FacadesDB;
use MobileStock\helper\Validador;
use PDO;

class EstoqueRepository
{
    public static function foraDeLinhaZeraEstoque(PDO $conexao, int $idProduto): void
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM estoque_grade
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $ehExterno = (bool) $sql->fetchColumn();

        if (!$ehExterno) {
            return;
        }
        $query = $conexao->prepare(
            "UPDATE estoque_grade SET
                estoque_grade.estoque = 0,
                estoque_grade.tipo_movimentacao = 'X',
                estoque_grade.descricao = 'Estoque zerado porque o produto foi colocado como fora de linha'
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;"
        );
        $query->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() < 1) {
            throw new Exception('Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.');
        }
    }

    public static function insereGrade(array $grades, int $idProduto, int $idFornecedor)
    {
        foreach ($grades as $grade) {
            Validador::validar($grade, [
                'sequencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            $sequencia = (int) $grade['sequencia'];
            $nomeTamanho = (string) $grade['nome_tamanho'];

            $sqlExistis = "SELECT 1
                FROM produtos_grade
                WHERE id_produto = :id_produto
                AND nome_tamanho = :nome_tamanho;";
            $bindsExistis = [
                ':id_produto' => $idProduto,
                ':nome_tamanho' => $nomeTamanho,
            ];
            $existeReposicao = FacadesDB::select($sqlExistis, $bindsExistis);

            if ($existeReposicao) {
                continue;
            }

            $sqlDelete = "DELETE FROM produtos_grade
                WHERE id_produto = :id_produto
                AND nome_tamanho = :nome_tamanho";
            $bindsDelete = [
                ':id_produto' => $idProduto,
                ':nome_tamanho' => $nomeTamanho,
            ];
            FacadesDB::delete($sqlDelete, $bindsDelete);

            $sqlInsert = "INSERT INTO produtos_grade (id_produto, sequencia, nome_tamanho, cod_barras)
                VALUES (:id_produto, :sequencia, :nome_tamanho, :cod_barras);
            ";
            $bindsInsert = [
                ':id_produto' => $idProduto,
                ':sequencia' => $sequencia,
                ':nome_tamanho' => $nomeTamanho,
                ':cod_barras' => $idFornecedor . $idProduto . $sequencia,
            ];
            FacadesDB::insert($sqlInsert, $bindsInsert);
        }
    }
}

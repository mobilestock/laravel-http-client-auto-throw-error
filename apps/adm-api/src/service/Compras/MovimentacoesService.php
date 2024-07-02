<?php

namespace MobileStock\service\Compras;

use PDO;

class MovimentacoesService
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function insereHistoricoDeMovimentacao(int $id, int $usuario): string
    {
        $statement = "INSERT INTO movimentacao_estoque 
                        (id,usuario,tipo,data,origem) 
                        VALUES ({$id},{$usuario},'E', NOW(),'Compras Mobile');";
        return $statement;
        $this->conexao->exec(
            $statement);
    }

    public function getIdLancamento(): int
    {
        $sql = "SELECT max(id + 1) id FROM lancamento_financeiro_seller;";
        $resultado = $this->conexao->query($sql);
        $resultado = $resultado->fetch();
        return $resultado ? $resultado['id'] : 0;
    }

    public function insereHistoricoDeMovimentacaoEstoque(int $idUsuario, string $origem, string $tipo): int
    {
        $statement = "INSERT INTO movimentacao_estoque 
                        (usuario,tipo,data,origem) 
                        VALUES ($idUsuario,'$tipo',NOW(),'$origem');";
        $this->conexao->exec($statement);

        return $this->conexao->lastInsertId();
    }

    public function insereHistoricoDeMovimentacaoItemEstoque(PDO $conexao, int $idMov, int $idProduto, string $nomeTamanho, int $seq, int $idResponsavelEstoque, int $quantidade): bool
    {
        $sql = $conexao->prepare(
            "INSERT INTO movimentacao_estoque_item(
                movimentacao_estoque_item.id_mov,
                movimentacao_estoque_item.id_produto,
                movimentacao_estoque_item.id_responsavel_estoque,
                movimentacao_estoque_item.nome_tamanho,
                movimentacao_estoque_item.sequencia,
                movimentacao_estoque_item.quantidade,
                movimentacao_estoque_item.compra,
                movimentacao_estoque_item.sequencia_compra,
                movimentacao_estoque_item.volume,
                movimentacao_estoque_item.preco_unit
            ) VALUES (
                :id_movimentacao,
                :id_produto,
                :id_responsavel_estoque,
                :nome_tamanho,
                :sequencia,
                :quantidade,
                0,
                0,
                0,
                0
            );"
        );
        $sql->bindValue(":id_movimentacao", $idMov, PDO::PARAM_INT);
        $sql->bindValue(":id_produto", $idProduto, PDO::PARAM_INT);
        $sql->bindValue(":id_responsavel_estoque", $idResponsavelEstoque, PDO::PARAM_INT);
        $sql->bindValue(":nome_tamanho", $nomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(":sequencia", $seq, PDO::PARAM_INT);
        $sql->bindValue(":quantidade", $quantidade, PDO::PARAM_INT);

        return $sql->execute();
    }

    // public function atualizaHistoricoDeMovimentacaoItemEstoque(int $idMov, int $idProduto, int $tamanho, int $seq)
    // {
    //     $query = "UPDATE movimentacao_estoque_item set quantidade=quantidade+1 
    //     WHERE id_mov = $idMov AND id_produto = $idProduto AND tamanho = $tamanho AND sequencia = $seq AND volume=0;";
    //     return $this->conexao->exec($query);
    // }

    public function ehPrimeiraEntradaEstoque(PDO $conexao, int $idProduto, int $idResponsavelEstoque): bool
    {
        return empty($conexao->query(
            "SELECT 1 FROM movimentacao_estoque_item WHERE movimentacao_estoque_item.id_produto = $idProduto AND movimentacao_estoque_item.id_responsavel_estoque = $idResponsavelEstoque LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC));
    }
}
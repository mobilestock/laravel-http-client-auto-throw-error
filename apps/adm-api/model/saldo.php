<?php

class Saldo{

    // busca a linha de saldo do pedido que esta em aberto
    public function verificaSaldoTrocaDia(PDO $conexao, $cliente){
        $query = "SELECT * FROM saldo_troca WHERE id_cliente ={$cliente} AND faturado = 0 and tipo='E'";
        if($resultado = $conexao->query($query))
        {
            return $resultado->fetch();
        } else {
            throw new PDOException($conexao->errorInfo());
        }
    }

    public function buscaTotalSaldoCompra(PDO $conexao, $id_cliente){
        $query = "SELECT saldo from saldo_troca WHERE id_cliente = {$id_cliente}
        ORDER BY sequencia DESC LIMIT 1";
        if($resultado = $conexao->query($query))
        {
            $linha = $resultado->fetch();
            return $linha['saldo'];
        } else {
            throw new PDOException($conexao->errorInfo());
        }
    }

    public function buscaUltimaSequenciaSaldoCliente(PDO $conexao, $cliente){
        $query = "SELECT MAX(sequencia)sequencia from saldo_troca
        WHERE id_cliente = {$cliente}";
        if($resultado = $conexao->query($query))
        {
            $linha = $resultado->fetch();
            return $linha['sequencia'];
        } else {
            throw new PDOException($conexao->errorInfo());
        }
    }
}
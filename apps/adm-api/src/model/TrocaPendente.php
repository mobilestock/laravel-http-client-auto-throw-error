<?php /*

class TrocaPendente
{
    private $dataAtual;
    
    public function __construct() {
        date_default_timezone_set('America/Sao_Paulo');
        $this->dataAtual = date('Y-m-d H:i:s');
    }

    public function buscaUltimaSequenciaProdutoTroca(PDO $conexao, int $cliente)
    {
        $query = "SELECT MAX(sequencia) seq FROM troca_pendente_item WHERE id_cliente={$cliente};";
        $resultado = $conexao->query($query);
        $linha = $resultado->fetch();
        return $linha['seq'];
    }

    public function retornaProdutoTrocaPendenteDoPedido(PDO $conexao, int $cliente, array $devolucoes)
    {
        $sequencia = $this->buscaUltimaSequenciaProdutoTroca($conexao, $cliente);
        $sequencia++;
        $query = "";
        foreach ($devolucoes as $key => $d) {
            $query .= "INSERT INTO troca_pendente_item (id_cliente,id_produto,sequencia,tamanho,
                tipo_cobranca,id_tabela,id_vendedor,preco,data_hora,uuid,defeito,descricao_defeito)
                VALUES ({$cliente},{$d['id_produto']},{$sequencia},{$d['tamanho']},
                {$d['tipo_cobranca']},{$d['id_tabela']},{$d['id_vendedor']},{$d['preco']},'{$d['data_hora']}','{$d['uuid']}',{$d['defeito']},'{$d['decricao_defeito']}');";
        }
        return $conexao->exec($query);
    }

    public function removeSaldoClienteFaturado(PDO $conexao, int $id_cliente, int $id_faturamento)
    {
        $query = "DELETE FROM saldo_troca WHERE id_cliente={$id_cliente} AND faturado = 1 AND num_fatura={$id_faturamento}";
        $stmt = $conexao->prepare($query);
        return $stmt->execute();
    }
}*/
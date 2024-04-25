<?php /*


namespace MobileStock\service\NotaFiscal;
use Error;
use MobileStock\model\NotaFiscalSaida\NotaFiscalSaida;
use PDO;

class NotaFiscalService extends NotaFiscalSaida
{
    public function criaNotaFiscalSaida(PDO $conexao): int
    {
        $sql = 'INSERT INTO nota_fiscal_saida (' . implode(',', array_keys(array_filter(get_object_vars($this)))) . ') VALUES (';
        foreach ($this as $key => $value) {
            if (!$value) continue;

            $sql .= ":{$key},";
        }

        $sql = substr($sql, 0, strlen($sql) - 1) . ')';
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);

        $this->id = $conexao->lastInsertId();
        return $this->id;
    }

    public function atualizaNotaFiscalSaida(PDO $conexao)
    {
        $dados = [];
        $sql = "UPDATE nota_fiscal_saida SET ";

        foreach ($this as $key => $valor) {
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            array_push($dados, $key . " = " . $valor);
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser atualizada');
        }

        $sql .= " " . implode(',', $dados) . " WHERE nota_fiscal_saida.id = '" . $this->id . "'";

        return $conexao->exec($sql);
    }
    public function retornaIdNotaFiscalSaida(PDO $conexao)
    {
        $sql = "SELECT nota_fiscal_saida.id 
                    FROM nota_fiscal_saida 
                        WHERE nota_fiscal_saida.id = " . $this->id;

        $resultado = $conexao->query($sql);
        return $resultado->fetch(PDO::FETCH_ASSOC)['id'];
    }
    public function TransacaoNotaFiscalSaida(PDO $conexao, int $faturamento){
        $consulta = "SELECT transacao_financeiras.id, 
                            MAX(transacao_financeiras.valor_liquido) valor_liquido,
                            transacao_financeiras.data_criacao 
                                FROM transacao_financeiras 
                                    WHERE transacao_financeiras.id 
                                                                    IN (
                                                                            SELECT tf.id_transacao 
                                                                                FROM transacao_financeiras_faturamento tf 
                                                                                    WHERE tf.id_faturamento = {$faturamento}
                                                                        ) LIMIT 1";
        $resultado = $conexao->query($consulta);
        $nota = $resultado->fetch(PDO::FETCH_ASSOC);
        return $nota;
    }
    public function existeOrdemGeradaFiscal(PDO $conexao, int $id_faturamento)
    {
        $query = "SELECT nota_fiscal_saida.id FROM nota_fiscal_saida WHERE id_faturamento={$id_faturamento};";
        $resultado = $conexao->query($query);
        return $resultado->fetch();
    }

}

?>*/
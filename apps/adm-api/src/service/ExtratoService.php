<?php

namespace MobileStock\service;

use Error;
use Illuminate\Support\Facades\DB;
use MobileStock\model\Extrato;
use PDO;

class ExtratoService extends Extrato
{
    public function inserir()
    {
        DB::insert('INSERT INTO extrato_saldo_dia (extrato_saldo_dia.extrato) values (?)', [$this->extrato]);
    }

    public function atualizar(PDO $conexao)
    {
        $dados = [];
        $sql = 'UPDATE extrato_saldo_dia SET ';

        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            $dados[] = $key . ' = ' . $valor;
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser atualizada');
        }

        $sql .= ' ' . implode(',', $dados) . " WHERE extrato_saldo_dia.id = '" . $this->id . "'";

        return $conexao->exec($sql);
    }
}
?>

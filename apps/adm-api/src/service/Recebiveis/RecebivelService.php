<?php

namespace MobileStock\service\Recebiveis;

use Exception;
use MobileStock\model\Recebivel;
use PDO;

class RecebivelService extends Recebivel
{
    public function recebivel_adiciona(pdo $conexao)
    {
        $dados = [];
        $value = [];
        $parametro = false;
        $sql = 'INSERT INTO lancamentos_financeiros_recebiveis ';

        $paramento_in = $this->id_zoop_recebivel ? 'id_zoop_recebivel' : 'id_lancamento';
        foreach ($this as $key => $valor) {
            if (!$valor && $key !== 'valor_pago') {
                continue;
            }
            $dados[] = $key;
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            $value[] = $valor;
            if (in_array($key, [$paramento_in, 'num_parcela'])) {
                !$parametro ? ($parametro = $key . ' = ' . $valor) : ($parametro .= ' AND ' . $key . ' = ' . $valor);
            }
        }
        if (sizeof($dados) === 0) {
            throw new Exception('Não Existem informações para adiconar na tabela');
        }

        $sql .= '(' . implode(',', $dados) . ') SELECT ' . implode(',', $value);
        if ($parametro) {
            $sql .=
                " FROM DUAL
                        WHERE NOT EXISTS
                        (SELECT 1 FROM lancamentos_financeiros_recebiveis
                        WHERE " .
                $parametro .
                ')';
        }

        return $conexao->exec($sql);
    }

    public function recebivel_atualiza(pdo $conexao)
    {
        $dados = [];
        $sql = 'UPDATE lancamentos_financeiros_recebiveis SET ';

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
            throw new Exception('Não Existe informações para ser atualizada');
        }

        $sql .=
            ' ' .
            implode(',', $dados) .
            " WHERE lancamentos_financeiros_recebiveis.id_zoop_recebivel = '" .
            $this->id_zoop_recebivel .
            "'";

        return $conexao->query($sql);
    }

    public function recebivel_atualiza_id(pdo $conexao)
    {
        $dados = [];
        $sql = 'UPDATE lancamentos_financeiros_recebiveis SET ';

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
            throw new Exception('Não Existe informações para ser atualizada');
        }

        $sql .=
            ' ' .
            implode(',', $dados) .
            " WHERE lancamentos_financeiros_recebiveis.id_zoop_recebivel = '" .
            $this->id .
            "'";

        return $conexao->exec($sql);
    }
    public function recebivel_atualiza_idLancamento(pdo $conexao)
    {
        $dados = [];
        $sql = 'UPDATE lancamentos_financeiros_recebiveis SET ';

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
            throw new Exception('Não Existe informações para ser atualizada');
        }

        $sql .=
            ' ' .
            implode(',', $dados) .
            " WHERE lancamentos_financeiros_recebiveis.id_lancamento = '" .
            $this->id_lancamento .
            "'";

        return $conexao->exec($sql);
    }

    public static function buscaRecebivel(PDO $conexao, string $splitRule)
    {
        $query = "SELECT * FROM lancamentos_financeiros_recebiveis WHERE id_zoop_split = '{$splitRule}';";
        $stm = $conexao->prepare($query);
        $stm->execute();
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
        if (sizeof($resultado) > 0) {
            return true;
        }
        return false;
    }

    public static function insereRecebivel(PDO $conexao, array $recebivel)
    {
        $situacao = '';
        if ($recebivel['status'] == 'paid') {
            $situacao = 'PA';
        } elseif ($recebivel['status'] == 'pending') {
            $situacao = 'PE';
        }

        $lanc = LancamentoService::buscaLancamentoFinanceiroPorSplit($conexao, $recebivel['split_rule']);
        $query = "INSERT INTO lancamentos_financeiros_recebiveis
        (id_lancamento, id_zoop_recebivel, situacao, id_zoop_split, id_recebedor, num_parcela, valor_pago, valor,
        data_pagamento, data_vencimento, data_gerado, id_faturamento) VALUES
        ({$lanc['id']}, {$recebivel['id']}, '{$situacao}', '{$recebivel['split_rule']}', {$recebivel['id_colaborador']},
        {$recebivel['installment']}, {$recebivel['amount']}, {$recebivel['amount']}, '{$recebivel['paid_at']}',
        '{$recebivel['expected_on']}', '{$recebivel['created_at']}', {$lanc['pedido_origem']});";
        $stm = $conexao->prepare($query);
        $stm->execute();
    }
}

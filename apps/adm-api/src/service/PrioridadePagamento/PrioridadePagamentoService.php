<?php

namespace MobileStock\service\PrioridadePagamento;

use Error;
use MobileStock\model\RegraPagamentoSeller\ColaboradorePrioridaePagamento;
use PDO;

/**
 * @deprecated
 * Usar @\MobileStock\service\TransferenciasService.php
 */
class PrioridadePagamentoService extends ColaboradorePrioridaePagamento
{
    public function criaPrioridadePagamento(PDO $conexao): int
    {
        $sql =
            'INSERT INTO colaboradores_prioridade_pagamento (' .
            implode(',', array_keys(array_filter(get_object_vars($this)))) .
            ') VALUES (';
        foreach ($this as $key => $value) {
            if (!$value) {
                continue;
            }

            $sql .= ":{$key},";
        }

        $sql = substr($sql, 0, strlen($sql) - 1) . ')';
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);

        $this->id = $conexao->lastInsertId();
        return $this->id;
    }

    // public function atualizaPrioridadePagamento(pdo $conexao)
    // {
    //     $dados = [];
    //     $sql = "UPDATE colaboradores_prioridade_pagamento SET ";

    //     foreach ($this as $key => $valor) {
    //         if (!$valor) {
    //             continue;
    //         }
    //         if (gettype($valor) == 'string') {
    //             $valor = "'" . $valor . "'";
    //         }
    //         array_push($dados, $key . " = " . $valor);
    //     }
    //     if (sizeof($dados) === 0) {
    //         throw new Error('Não Existe informações para ser atualizada');
    //     }

    //     $sql .= " " . implode(',', $dados) . " WHERE colaboradores_prioridade_pagamento.id = '" . $this->id. "'";

    //     return $conexao->exec($sql);
    // }

    public function CarregaPrioridadePagamento(pdo $conexao)
    {
        $sql =
            'SELECT transacao_financeiras.id FROM transacao_financeiras WHERE transacao_financeiras.cod_transacao = ' .
            $this->cod_transacao;

        $resultado = $conexao->query($sql);
        return $resultado->fetch(PDO::FETCH_ASSOC)['id'];
    }

    public function retornaPrioridadePagamento(PDO $conexao): void
    {
        $dados = [];
        $sql = "SELECT
            colaboradores_prioridade_pagamento.id,
            colaboradores_prioridade_pagamento.id_colaborador,
            colaboradores_prioridade_pagamento.valor_pagamento,
            colaboradores_prioridade_pagamento.valor_pago,
            colaboradores_prioridade_pagamento.data_criacao,
            colaboradores_prioridade_pagamento.data_atualizacao,
            colaboradores_prioridade_pagamento.usuario,
            colaboradores_prioridade_pagamento.pago
        FROM colaboradores_prioridade_pagamento
        WHERE colaboradores_prioridade_pagamento.id";

        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if ($key) {
                array_push($dados, 'transacao_financeiras.' . $key . ' = ' . $valor);
            }
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser consultada');
        }

        $sql .= implode(' AND ', $dados);
        $dados = $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);

        $this->id = $dados['id'];
        $this->id_colaborador = $dados['id_colaborador'];
        $this->valor_pagamento = $dados['valor_pagamento'];
        $this->valor_pago = $dados['valor_pago'];
        $this->data_criacao = $dados['data_criacao'];
        $this->data_atualizacao = $dados['data_atualizacao'];
        $this->usuario = $dados['usuario'];
        $this->situacao = $dados['pago'];
    }

    public function retornaTransacoes(PDO $conexao): array
    {
        $dados = [];
        $sql = "SELECT
            colaboradores_prioridade_pagamento.id,
            colaboradores_prioridade_pagamento.id_colaborador,
            colaboradores_prioridade_pagamento.valor_pagamento,
            colaboradores_prioridade_pagamento.valor_pago,
            DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao,'%d/%m/%Y')data_criacao,
            DATE_FORMAT(colaboradores_prioridade_pagamento.data_atualizacao,'%d/%m/%Y')data_atualizacao,
            colaboradores_prioridade_pagamento.usuario,
            colaboradores_prioridade_pagamento.situacao
        FROM colaboradores_prioridade_pagamento
        WHERE ";

        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if ($key) {
                array_push($dados, ' colaboradores_prioridade_pagamento.' . $key . ' = ' . $valor);
            }
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser consultada');
        }

        $sql .= implode(' AND ', $dados);
        $dados = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return $dados;
    }

    // public function removerPrioridadePagamento(PDO $conexao)
    // {
    //     return $conexao->query("DELETE FROM  colaboradores_prioridade_pagamento WHERE colaboradores_prioridade_pagamento.id = ".$this->id)->rowCount();

    // }

    public function buscaMontanteTransacao(PDO $conexao)
    {
        $dados = [];
        $sql = "SELECT
            colaboradores_prioridade_pagamento.id,
            colaboradores_prioridade_pagamento.id_colaborador,
            SUM(colaboradores_prioridade_pagamento.valor_pagamento) total_receber,
            SUM(colaboradores_prioridade_pagamento.valor_pago) total_pago,
            DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao,'%d/%m/%Y')data_criacao,
            DATE_FORMAT(colaboradores_prioridade_pagamento.data_atualizacao,'%d/%m/%Y')data_atualizacao,
            colaboradores_prioridade_pagamento.usuario,
            colaboradores_prioridade_pagamento.situacao
        FROM colaboradores_prioridade_pagamento
        WHERE ";
        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if ($key) {
                array_push($dados, ' colaboradores_prioridade_pagamento.' . $key . ' = ' . $valor);
            }
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser consultada');
        }

        $sql .= implode(' AND ', $dados);
        $dados = $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $dados;
    }

    // public function incrementaValorPago(\PDO $conexao, float $valor): void
    // {
    //     $conexao->exec(
    //         "UPDATE colaboradores_prioridade_pagamento
    //         SET colaboradores_prioridade_pagamento.valor_pago = colaboradores_prioridade_pagamento.valor_pago + $valor
    //         WHERE colaboradores_prioridade_pagamento.id = {$this->id};"
    //     );
    // }
}

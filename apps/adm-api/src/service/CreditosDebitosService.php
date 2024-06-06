<?php

namespace MobileStock\service;

use Exception;
use PDO;

/**
 * @deprecated
 */
class CreditosDebitosService
{
    public static function saldoCliente(PDO $conexao, int $id_colaborador, string $data, string $data_ate): array
    {
        if ($data == '' && $data_ate == '') {
            $data = 'DATE(NOW())';
            $data_ate = 'DATE(NOW())';
        }
        $sql = "SELECT
                        'periodo' periodo,
                                COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo
                                FROM lancamento_financeiro
                                WHERE lancamento_financeiro.situacao = 1
                                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
                                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
                                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
                                UNION ALL
                                SELECT
                                'anterior' periodo,
                                COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo
                                FROM lancamento_financeiro
                                WHERE lancamento_financeiro.situacao = 1
                                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
                                    AND DATE(lancamento_financeiro.data_emissao) < '{$data}'
                                UNION ALL
                                SELECT
                                'posterior' periodo,
                                COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo
                                FROM lancamento_financeiro
                                WHERE lancamento_financeiro.situacao = 1
                                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
                                    AND DATE(lancamento_financeiro.data_emissao) > '{$data_ate}' ";

        $stm = $conexao->prepare($sql);
        $stm->execute();
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public static function buscaExtratoClienteDetalhes(
        PDO $conexao,
        int $idColaborador,
        string $dataDe,
        string $dataAte
    ): array {
        $bind[':id_colaborador'] = $idColaborador;
        $filtro = 'AND lancamento_financeiro.data_emissao BETWEEN NOW() - INTERVAL 30 DAY AND NOW()';

        if ($dataDe !== '' || $dataAte !== '') {
            $filtro = 'AND lancamento_financeiro.data_emissao BETWEEN DATE(:de) AND DATE_ADD(:ate, INTERVAL 1 DAY)';
            $bind[':de'] = $dataDe;
            $bind[':ate'] = $dataAte;
        }

        $stm = $conexao->prepare(
            "SELECT
                GROUP_CONCAT(transacao_financeiras_produtos_itens.id SEPARATOR ', ') AS `id_comissao`,
                colaboradores.id id_colaborador,
                COALESCE(lancamento_financeiro.id, '') AS `id`,
                lancamento_financeiro.id_usuario,
                DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y %H:%i:%s') data_emissao,
                COALESCE((lancamento_financeiro.origem),'SI')origem,
                COALESCE((lancamento_financeiro.valor),'0,00')valor,
                lancamento_financeiro.faturamento_criado_pago,
                lancamento_financeiro.situacao,
                lancamento_financeiro.tipo,
                lancamento_financeiro.pedido_origem,
                COALESCE((lancamento_financeiro.transacao_origem),0)transacao_origem,
                IF(
                lancamento_financeiro.origem = 'MA',
                COALESCE(lancamento_financeiro.observacao, 'Motivo desconhecido'),
                ''
                ) motivo_lancamento,
                (
                    SELECT usuarios.nome
                    FROM usuarios
                    WHERE usuarios.id=lancamento_financeiro.id_usuario
                )nome,
                colaboradores.razao_social,
                lancamento_financeiro.faturamento_criado_pago
            FROM colaboradores
            INNER JOIN lancamento_financeiro ON colaboradores.id = lancamento_financeiro.id_colaborador
            LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = lancamento_financeiro.transacao_origem
                AND lancamento_financeiro.origem IN (transacao_financeiras_produtos_itens.sigla_lancamento, transacao_financeiras_produtos_itens.sigla_estorno)
                AND transacao_financeiras_produtos_itens.id_fornecedor = lancamento_financeiro.id_colaborador
                AND IF( lancamento_financeiro.numero_documento, transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro.numero_documento , 1)
            WHERE lancamento_financeiro.id_colaborador = :id_colaborador
                AND COALESCE(lancamento_financeiro.origem,'') <> 'AU'
                $filtro
            GROUP BY lancamento_financeiro.id
            ORDER BY lancamento_financeiro.id DESC"
        );
        $stm->execute($bind);
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
        $linha_adiciona = [];
        $linha_adiciona['datas'] = date('d/m/Y H:i:s');
        $linha_adiciona['origem'] = 'FIM';
        $linha_adiciona['pedido_origem'] = '';
        $linha_adiciona['tipo'] = 'S';
        $linha_adiciona['faturamento_criado_pago'] = 'T';
        $linha_adiciona['valor'] = '0';
        $resultado[] = $linha_adiciona;
        return $resultado;
    }

    public static function alternaBloquearContaBancaria(PDO $conexao, bool $bloquear, int $idContaBancaria): void
    {
        $bloquear = $bloquear ? 'T' : 'F';

        $sql = "UPDATE conta_bancaria_colaboradores
                SET conta_bancaria_colaboradores.pagamento_bloqueado = :valor
                WHERE conta_bancaria_colaboradores.id = :id_conta_bancaria";

        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':valor', $bloquear, PDO::PARAM_STR);
        $stmt->bindValue(':id_conta_bancaria', $idContaBancaria, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception("Erro ao bloquear a conta bancária $idContaBancaria");
        }

        if ($bloquear === 'T') {
            $sql = $conexao->prepare(
                "DELETE FROM fila_transferencia_automatica
                WHERE fila_transferencia_automatica.id_transferencia IN (
                    SELECT colaboradores_prioridade_pagamento.id
                    FROM colaboradores_prioridade_pagamento
                    WHERE colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND colaboradores_prioridade_pagamento.id_conta_bancaria = :id_conta_bancaria
                );"
            );
            $sql->bindValue(':id_conta_bancaria', $idContaBancaria, PDO::PARAM_INT);
            $sql->execute();
        }
    }

    public static function deletarTransferencia(PDO $conexao, int $idTransferencia): void
    {
        $sql = "DELETE FROM colaboradores_prioridade_pagamento
                WHERE colaboradores_prioridade_pagamento.id = :idTransferencia";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':idTransferencia', $idTransferencia, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new Exception("Erro ao deletar transferência $idTransferencia");
        }
    }
}

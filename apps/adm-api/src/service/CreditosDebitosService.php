<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
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

    public static function listaTransferencias(): array
    {
        $diasPagamento = ConfiguracaoService::buscaDiasTransferenciaColaboradores();

        $diasPagamento = array_map(fn($dias) => $dias + 1, $diasPagamento);

        $sql = "SELECT
                colaboradores_prioridade_pagamento.id AS `id_prioridade`,
                colaboradores_prioridade_pagamento.valor_pago,
                colaboradores_prioridade_pagamento.valor_pagamento,
                lancamento_financeiro.id_prioridade_saque,
                lancamento_financeiro.id AS `id_lancamento`,
                colaboradores_prioridade_pagamento.id_colaborador,
                IF(COALESCE(colaboradores_prioridade_pagamento.situacao, 'NA') = 'CR'
                    AND LENGTH(COALESCE(colaboradores_prioridade_pagamento.id_transferencia, '')) > 1,
                    'ET', colaboradores_prioridade_pagamento.situacao
                ) situacao,
                colaboradores_prioridade_pagamento.id_transferencia,
                conta_bancaria_colaboradores.nome_titular,
                conta_bancaria_colaboradores.cpf_titular,
                conta_bancaria_colaboradores.conta,
                conta_bancaria_colaboradores.agencia,
                conta_bancaria_colaboradores.id,
                COALESCE((colaboradores_prioridade_pagamento.valor_pagamento - colaboradores_prioridade_pagamento.valor_pago),0) valor_pendente,
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao, '%d/%m/%Y %H:%i:%s') AS `data_criacao`,
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_atualizacao,'%d/%m/%Y %H:%i:%s') AS `data_atualizacao`,
                colaboradores.razao_social,
                conta_bancaria_colaboradores.pagamento_bloqueado,
                reputacao_fornecedores.reputacao,
                CASE
                    WHEN reputacao_fornecedores.reputacao = 'MELHOR_FABRICANTE' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'EXCELENTE' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_EXCELENTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'REGULAR' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_REGULAR']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'RUIM' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_RUIM']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    ELSE
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_CLIENTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                END AS `proximo_pagamento`,
                saldo_cliente(colaboradores_prioridade_pagamento.id_colaborador) saldo
            FROM conta_bancaria_colaboradores
            INNER JOIN colaboradores_prioridade_pagamento ON colaboradores_prioridade_pagamento.id_conta_bancaria = conta_bancaria_colaboradores.id
            INNER JOIN colaboradores ON colaboradores.id = colaboradores_prioridade_pagamento.id_colaborador
            INNER JOIN lancamento_financeiro ON lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores_prioridade_pagamento.id_colaborador
            WHERE colaboradores_prioridade_pagamento.situacao IN ('CR','EM')
                AND colaboradores_prioridade_pagamento.id_transferencia = '0'
            GROUP BY colaboradores_prioridade_pagamento.id
            ORDER BY colaboradores_prioridade_pagamento.id DESC";
        $resultado = DB::select($sql);
        return $resultado ?: [];
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

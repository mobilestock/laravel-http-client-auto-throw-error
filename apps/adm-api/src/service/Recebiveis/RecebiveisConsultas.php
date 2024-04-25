<?php

namespace MobileStock\service\Recebiveis;

use Exception;
use MobileStock\database\Conexao;
use PDO;

class RecebiveisConsultas
{
    // public static function buscaRecebivel(PDO $conexao, int $idTransferencia): array
    // {
    //     $situacaoSelect = self::sqlSelectSituacaoSplit();

    //     $stmt = $conexao->prepare(
    //         "SELECT 
    //             transacao_financeira_split.id,
    //             DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y') data_gerado,
    //             $situacaoSelect,
    //             transacao_financeira_split.valor,
    //             SUM(COALESCE(lancamentos_financeiros_recebiveis.valor_pago, 0)) valor_pago,
    //             transacao_financeira_split.id_transacao
    //         FROM transacao_financeira_split
    //         INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeira_split.id_transacao
    //         LEFT JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_transacao = transacao_financeira_split.id_transacao AND lancamentos_financeiros_recebiveis.id_recebedor = transacao_financeira_split.id_colaborador
    //         WHERE transacao_financeira_split.id_transferencia = ?
    //         GROUP BY transacao_financeira_split.id
    //         ORDER BY transacao_financeira_split.id DESC"
    //     );

    //     $stmt->execute([$idTransferencia]);

    //     $resultado = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //     return $resultado;
    // }

    public static function buscaRecebiveisPendentes(PDO $conexao = null)
    {
        if (!$conexao) $conexao = Conexao::criarConexao();

        return $conexao->query("
            SELECT
                DATE_FORMAT(transacao_financeiras.data_atualizacao, '%d-%m-%Y') data,
                COUNT(DISTINCT transacao_financeiras.data_atualizacao) qtd_pendente
            FROM lancamentos_financeiros_recebiveis
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = lancamentos_financeiros_recebiveis.id_transacao
            WHERE lancamentos_financeiros_recebiveis.situacao = 'PE'
                AND transacao_financeiras.`status` = 'PA'
                AND transacao_financeiras.emissor_transacao = 'iugu'
            GROUP BY DATE(transacao_financeiras.data_atualizacao)
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    // public static function buscaIdIugu(\PDO $conexao, int $id): string
    // {
    //     $sql = $conexao->prepare(
    //         "SELECT conta_bancaria_colaboradores.id_iugu
    //         FROM conta_bancaria_colaboradores
    //         WHERE conta_bancaria_colaboradores.id = $id;"
    //     );
    //     $sql->execute();
    //     $resultado = $sql->fetch(PDO::FETCH_ASSOC)['id_iugu'];

    //     return $resultado;
    // }

    public static function sqlSelectSituacaoSplit(): string
    {
        return "CASE 
                    WHEN transacao_financeira_split.situacao = 'EX' THEN 'estornado'
                    WHEN transacao_financeira_split.situacao = 'CA' THEN 'cancelado'
                    WHEN ABS(transacao_financeira_split.valor - SUM(lancamentos_financeiros_recebiveis.valor_pago)) < 0.05 THEN 'pago'
                    ELSE 'pendente'
                END situacao";
    }

//    public static function buscaListaRecebedoresAtualizar(PDO $conexao): array
//    {
//        $situacaoSql = self::sqlSelectSituacaoSplit();
//        $consulta = $conexao->query(
//            "SELECT DISTINCT
//                        recebiveis.previsao,
//                        MIN(recebiveis.data_criacao) deve_buscar_desde,
//                        GROUP_CONCAT(recebiveis.id_transacao) transacoes,
//                        CONCAT('[', GROUP_CONCAT(DISTINCT (SELECT JSON_OBJECT(
//                            'iugu_token_live', conta_bancaria_colaboradores.iugu_token_live,
//                            'id', conta_bancaria_colaboradores.id,
//                            'nome', conta_bancaria_colaboradores.nome_titular
//                        ) FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = recebiveis.id_recebedor LIMIT 1)), ']') recebedores
//                    FROM (SELECT
//                                transacao_financeira_split.id_transacao,
//                                $situacaoSql,
//                                IF(transacao_financeiras.metodo_pagamento = 'PX', DATE(transacao_financeiras.data_atualizacao), 'qualquer dia') previsao,
//                                transacao_financeiras.data_atualizacao data_pago,
//                                transacao_financeira_split.id_colaborador id_recebedor,
//                                transacao_financeiras.metodo_pagamento,
//                                transacao_financeira_split.id_zoop,
//                                DATE(transacao_financeiras.data_criacao) data_criacao
//                            FROM transacao_financeira_split
//                            INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeira_split.id_transacao AND transacao_financeiras.status IN ('PA', 'CA')
//                            LEFT JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_transacao = transacao_financeira_split.id_transacao AND lancamentos_financeiros_recebiveis.id_recebedor = transacao_financeira_split.id_colaborador
//                            WHERE transacao_financeira_split.situacao = 'NA'
//                                AND transacao_financeira_split.id_transferencia >= COALESCE((SELECT MIN(colaboradores_prioridade_pagamento.id) FROM colaboradores_prioridade_pagamento WHERE colaboradores_prioridade_pagamento.situacao IN ('CR', 'EM') AND colaboradores_prioridade_pagamento.id_transferencia = '0' AND EXISTS(SELECT 1 FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = colaboradores_prioridade_pagamento.id_conta_bancaria AND conta_bancaria_colaboradores.conta_iugu_verificada = 'T' AND conta_bancaria_colaboradores.pagamento_bloqueado = 'F')), 4000)
//                            GROUP BY transacao_financeira_split.id
//                            HAVING situacao = 'pendente') recebiveis
//                    GROUP BY recebiveis.previsao
//                    ORDER BY recebiveis.previsao DESC"
//        )->fetchAll(\PDO::FETCH_ASSOC);
//
//        $consulta = array_reduce($consulta, function (array $total, array $dia) {
//            $dia['recebedores'] = json_decode($dia['recebedores'], true);
//
//            $dataInicio = $dataIncial = new \DateTime($dia['previsao'] === 'qualquer dia' ? '' : $dia['previsao']);
//            $qdtDiasPesquisar = $dataInicio->diff(new \DateTime($dia['deve_buscar_desde']))->days;
//
//            for ($i = 0; $i <= $qdtDiasPesquisar; $i++) {
//
//                $dia['previsao'] = $dataIncial->modify("- $i day")->format('Y-m-d');
//                $total[] = $dia;
//            }
//
//
//            return $total;
//        }, []);
//
//        return $consulta;
//    }
}

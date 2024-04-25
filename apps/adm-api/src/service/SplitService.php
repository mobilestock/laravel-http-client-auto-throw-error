<?php
namespace MobileStock\service;

use PDO;

class SplitService
{
    public static function consultaSplitsComPagamentoDuplicado(PDO $conexao, int $idPedido)
    {
        $consulta = $conexao
            ->query(
                "SELECT
            transacao_financeira_split.valor,
            (SELECT conta_bancaria_colaboradores.iugu_token_live FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id_iugu = transacao_financeira_split.id_zoop LIMIT 1) iugu_token_live,
            transacao_financeira_split.id_transferencia,
            transacao_financeira_split.id,
            IF(colaboradores_prioridade_pagamento.valor_pagamento < colaboradores_prioridade_pagamento.valor_pago + transacao_financeira_split.valor, 'estornar', 'criar_recebivel') acao,
            COALESCE((SELECT lancamento_financeiro.id FROM lancamento_financeiro WHERE lancamento_financeiro.id_prioridade_saque = transacao_financeira_split.id_transferencia LIMIT 1), 0) id_lancamento,
            transacao_financeira_split.id_transacao,
            (SELECT transacao_financeiras.cod_transacao FROM transacao_financeiras WHERE transacao_financeiras.id = transacao_financeira_split.id_transacao) cod_transacao,
            colaboradores_prioridade_pagamento.id_conta_bancaria id_recebedor
        FROM transacao_financeira_split
        INNER JOIN colaboradores_prioridade_pagamento ON colaboradores_prioridade_pagamento.id = transacao_financeira_split.id_transferencia
        WHERE transacao_financeira_split.id_transacao = $idPedido
        AND transacao_financeira_split.situacao = 'CA'"
            )
            ->fetchAll(PDO::FETCH_ASSOC);

        return $consulta;
    }

    public static function atualizaSituacaoSplit(PDO $conexao, int $idSplit, string $situacao): void
    {
        $conexao->exec(
            'UPDATE transacao_financeira_split SET transacao_financeira_split.situacao = "' .
                $situacao .
                '" WHERE transacao_financeira_split.id = ' .
                $idSplit
        );
    }
}

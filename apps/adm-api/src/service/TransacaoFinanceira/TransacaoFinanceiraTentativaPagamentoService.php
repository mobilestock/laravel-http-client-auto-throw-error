<?php

namespace MobileStock\service\TransacaoFinanceira;

use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraTentativaPagamento;
use PDO;

class TransacaoFinanceiraTentativaPagamentoService extends TransacaoFinanceiraTentativaPagamento
{
    public static function existeTentativa(PDO $conexao, string $id): bool
    {
        $stmt = $conexao->prepare(
            "SELECT
                    1
                   FROM transacao_financeiras_tentativas_pagamento
                   WHERE transacao_financeiras_tentativas_pagamento.cod_transacao = ?"
        );

        $stmt->execute([$id]);

        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);

        return !empty($resultado);
    }
}

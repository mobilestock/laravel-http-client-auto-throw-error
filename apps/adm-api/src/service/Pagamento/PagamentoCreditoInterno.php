<?php

namespace MobileStock\service\Pagamento;

use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class PagamentoCreditoInterno extends PagamentoAbstrato
{
    public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CR'];
    public static string $LOCAL_PAGAMENTO = 'Interno';

    /**
     * @inheritDoc
     */
    public function comunicaApi(): TransacaoFinanceiraService
    {
        $this->pagamentoEstaConfirmado = true;
        return $this->transacao;
    }
}

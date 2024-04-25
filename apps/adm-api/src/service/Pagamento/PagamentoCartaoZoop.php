<?php

namespace MobileStock\service\Pagamento;

use MobileStock\helper\ClienteException;
use MobileStock\service\Pagamento\Traits\ValidacaoCartaoTrait;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\Zoop\ZoopHttpClient;
use Throwable;

class PagamentoCartaoZoop extends PagamentoAbstrato
{
    use ValidacaoCartaoTrait;
    public static string $LOCAL_PAGAMENTO = 'Zoop';
    public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];

    /**
     * @inheritDoc
     * @throws ClienteException
     */
    public function comunicaApi(): TransacaoFinanceiraService
    {
        $zoop = new ZoopHttpClient();
        $zoop->listaCodigosPermitidos = [200, 201];
        try {
            $resultado = $zoop->post(
                'transactions',
                [
                    'payment_type' => 'credit',
                    'source' => [
                        'card' => [
                            'card_number' => $this->transacao->dados_cartao['cardNumber'],
                            'holder_name' => $this->transacao->dados_cartao['holderName'],
                            'expiration_month' => $this->transacao->dados_cartao['expirationMonth'],
                            'expiration_year' => $this->transacao->dados_cartao['expirationYear'],
                            'security_code' => $this->transacao->dados_cartao['secureCode']
                        ],
                        'usage' => 'single_use',
                        'amount' => round($this->transacao->valor_liquido * 100, 2),
                        'currency' => 'BRL',
                        'type' => 'card'
                    ],
                    'installment_plan' => [
                        'number_installments' => $this->transacao->numero_parcelas
                    ],
                    'on_behalf_of' => $_ENV['DADOS_PAGAMENTO_ZOOP_CONTA_MOBILE']
                ]
            );
        } catch (Throwable $exception) {
            if ($zoop->codigoRetorno === 402) {
                throw new ClienteException($exception->getMessage(), 422, $exception);
            }

            throw $exception;
        }


        $this->transacao->cod_transacao = $resultado->body['id'];
        $this->pagamentoEstaConfirmado = true;

        return $this->transacao;
    }
}
<?php

namespace MobileStock\service\Pagamento;

use MobileStock\helper\ClienteException;
use MobileStock\service\CieloService\CieloServiceApi;
use MobileStock\service\Pagamento\Traits\ValidacaoCartaoTrait;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class PagamentoCartaoCielo extends PagamentoAbstrato
{
    use ValidacaoCartaoTrait;

    public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];
    public static string $LOCAL_PAGAMENTO = 'Cielo';

    public function comunicaApi(): TransacaoFinanceiraService
    {
        $transactionService = new CieloServiceApi([
            'id_transacao' => $this->transacao->id,
            'data' => [
                'holderName' => $this->transacao->dados_cartao['holderName'],
                'creditCardNumber' => $this->transacao->dados_cartao['cardNumber'],
                'expirationDateMonth' => $this->transacao->dados_cartao['expirationMonth'],
                'expirationDateYear' => $this->transacao->dados_cartao['expirationYear'],
                'secureCode' => $this->transacao->dados_cartao['secure_code']
            ],
            'montante' => round($this->transacao->valor_liquido * 100, 2),
            'parcelas' => $this->transacao->numero_parcelas
        ]);
        $transactionService->consultaBandeiraCartao();
        $retornoCielo = $transactionService->pagamentoCielo();
        $this->transacao->cod_transacao = $retornoCielo['paymentId'];
        if($retornoCielo['status'] !== 2){
            throw new ClienteException("Erro: ".$retornoCielo['returnMessage'].", codigo da transacao ".$retornoCielo['paymentId'] . "codigo erro: " . $retornoCielo['status'], 1);
        }

        $this->pagamentoEstaConfirmado = true;
        return $this->transacao;
    }

    public function pagamentoEstaConfirmado(): bool
    {
        return $this->pagamentoEstaConfirmado;
    }
}
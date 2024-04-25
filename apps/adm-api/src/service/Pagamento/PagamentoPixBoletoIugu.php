<?php

namespace MobileStock\service\Pagamento;

use Illuminate\Support\Arr;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class PagamentoPixBoletoIugu extends PagamentoAbstrato
{
    public static string $LOCAL_PAGAMENTO = 'Iugu';
    public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX', 'BL'];

    protected function buscaValorSplitavel(float $valorSplitsUtilizado): float
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function comunicaApi(): TransacaoFinanceiraService
    {
        $iugu = new IuguHttpClient();
        $iugu->criaFatura(
            ColaboradoresService::consultaDadosPagamento($this->conexao, $this->transacao->pagador),
            Arr::only($this->transacao->jsonSerialize(), ['id', 'valor_liquido']),
            ['bank_slip', 'pix'],
            ConfiguracaoService::consultaDiasVencimento($this->conexao)
        );

        $this->transacao->cod_transacao = $iugu->body['id'];
        $this->transacao->valor_taxas = (float) '0';
        $this->transacao->qrcode_pix = $iugu->body['pix']['qrcode'];
        $this->transacao->qrcode_text_pix = $iugu->body['pix']['qrcode_text'];
        $this->transacao->url_fatura = $iugu->body['secure_url'];
        if ($this->transacao->metodo_pagamento === 'BL') {
            $this->transacao->url_boleto = str_replace('barcode/', '', $iugu->body['bank_slip']['barcode']) . '.pdf';
            $this->transacao->barcode = $iugu->body['bank_slip']['barcode_data'];
        }
        return $this->transacao;
    }
}

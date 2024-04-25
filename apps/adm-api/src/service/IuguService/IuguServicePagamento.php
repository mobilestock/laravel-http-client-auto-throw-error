<?php

namespace MobileStock\service\IuguService;

use MobileStock\model\PagamentoIugu\PagamentosIugu;

/**
 * @inheritDoc
 */
class IuguServicePagamento extends PagamentosIugu
{
    public $dados_cartao;
    protected $respostaToken;
    public function __construct(int $idPagador)
    {
        parent::__construct();
        $this->dados_cartao = [
            'holderName' => '',
            'cardNumber' => '',
            'secureCode' => '',
            'expirationMonth' => '',
            'expirationYear' => '',
        ];
        $this->idPagador = $idPagador;
    }

    public function sincronizaTransacao()
    {
        $this->method = 'GET';
        $this->url = 'https://api.iugu.com/v1/invoices/' . $this->transacao;
        $this->respostaToken = $this->requestIugu();
        if ($this->respostaToken['codigo'] !== 200) {
            $this->retornoErro();
        }
        return $this->respostaToken;
    }
}

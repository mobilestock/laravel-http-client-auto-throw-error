<?php

namespace MobileStock\service\IuguService;

use MobileStock\model\PagamentoIugu\PagamentosIugu;

/**
 * @deprecated
 * Usar @\MobileStock\service\Iugu\IuguHttpClient
 */
class IuguServiceExtrato extends PagamentosIugu
{
    protected $dataAcao;
    public function __construct()
    {
        parent::__construct();
    }

    public function sincronizaSaque(\PDO $conexao, $from, $to)
    {
        $data = explode('-', $this->dataAcao);
        //$this->apiToken = IuguServiceConsultas::dadosColaboradoresIugu($conexao, $this->idPagador)['iugu_token_live'];
        $this->apiToken = $this->apiToken;
        $this->method = 'GET';
        $this->complementoUrl = '&from=' . $from . '&to=' . $to . '&start=0&limit=1000';
        $this->url = 'https://api.iugu.com/v1/withdraw_conciliations/' . $this->transacao;
        $this->respostaToken = $this->requestIugu();
        if ($this->respostaToken['codigo'] !== 200) {
            $this->retornoErro();
        }
        return $this->respostaToken['resposta'];
    }
}

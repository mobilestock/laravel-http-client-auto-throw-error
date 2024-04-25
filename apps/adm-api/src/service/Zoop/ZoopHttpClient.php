<?php

namespace MobileStock\service\Zoop;

use MobileStock\helper\HttpClient;
use MobileStock\helper\Validador;

class ZoopHttpClient extends HttpClient
{
    public array $listaCodigosPermitidos;

    public function __construct()
    {
        $this->listaCodigosPermitidos = range(200, 399);
    }

    protected function nomeArquivoLog(): string
    {
        return 'logs_requisicoes_para_zoop.log';
    }

    protected function trataInfoParaLog(string $resposta, int $codigoRetorno, array $headers)
    {
        if (preg_match('/transactions$/', $this->url)) {
            $envio = json_decode($this->body, true);
            $envio['source']['card'] = [];
            $this->body = json_encode($envio);
        }

        return parent::trataInfoParaLog($resposta, $codigoRetorno, $headers);
    }

    protected function antesRequisicao(): HttpClient
    {
        $this->headers[] = 'Authorization: Basic ' . $_ENV['DADOS_PAGAMENTO_ZOOP_API_TOKEN'];
        $this->url = 'https://api.zoop.ws/v1/marketplaces/' . $_ENV['DADOS_PAGAMENTO_ZOOP_ID_MARKETPLACE'] . (mb_strstr($this->url, 0) === '/' ? $this->url : '/' . $this->url);

        return $this;
    }

    protected function aposRequisicao(string $response, int $statusCode, array $headers): self
    {
        if (!Validador::validacaoJson($response)) {
            parent::aposRequisicao($response, $statusCode, $headers);
            throw new \DomainException('Zoop não retornou JSON.');
        }
        $respostaJson = json_decode($response, true);

        if (
            !in_array($statusCode, $this->listaCodigosPermitidos) ||
            array_key_exists('error', $respostaJson)
        ) {

            $error = $respostaJson['error'];
            parent::aposRequisicao($response, $statusCode, $headers);
            throw new \Exception(
                implode(' ', [$error['message_display'], $error['message']]) ?: 'Erro ao comunicar a zoop'
            );
        }

        return parent::aposRequisicao($response, $statusCode, $headers);
    }
}
<?php 

namespace MobileStock\service;

use MobileStock\database\Conexao;
use MobileStock\helper\HttpClient;
use MobileStock\helper\Validador;

/**
 * @deprecated
 * https://github.com/mobilestock/web/issues/2574
 */
class RodonavesHttpClient extends HttpClient 
{
    public array $listaCodigosPermitidos;

    public function __construct()
    {
        $this->listaCodigosPermitidos = [200];
    }

    protected function nomeArquivoLog(): string
    {
        return 'logs_requisicoes_para_rodonaves.log';
    }

    protected function antesRequisicao(): HttpClient
    {
        if ($this->url !== 'token') {
            $token = ConfiguracaoService::consultaTokenRodonaves(Conexao::criarConexao());
            $this->headers = [
                'Accept: text/plain',
                'Authorization: Bearer ' . $token,
            ];
        }
        $this->url = 'https://tracking-apigateway.rte.com.br' . (mb_strstr($this->url, 0) === '/' ? $this->url : '/' . $this->url);

        return $this;
    }

    protected function aposRequisicao(string $response, int $statusCode, array $headers): HttpClient
    {
        if ($statusCode == 401) {
            $conexao = Conexao::criarConexao();
            $rodonaves = new RodonavesService;
            $rodonaves->realizaAutenticacao($conexao);

            return $this->antesRequisicao()->envia();
        }
        
        if (!Validador::validacaoJson($response)) {
            throw new \DomainException('Rodonaves não retornou JSON.');
        }
        $respostaJson = json_decode($response, true);

        if (!in_array($statusCode, $this->listaCodigosPermitidos) || array_key_exists('error', $respostaJson)) {
            $error = $respostaJson['error'];
            throw new \Exception(implode(' ', [$error['message_display'], $error['message']]) ?: 'Erro ao comunicar a Rodonaves');
        }

        return parent::aposRequisicao($response, $statusCode, $headers);
    }

    public function __call($name, $arguments)
    {
        if (in_array($name = mb_strtoupper($name), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->method = $name;
            $this->url = $arguments[0] ?? '';
            $body = $arguments[1] ?: '';
            $this->headers = $arguments[2] ?? [];


            if (in_array('Content-Type: application/x-www-form-urlencoded',$this->headers)) {
                $this->body = http_build_query($body);
            } elseif (Validador::validacaoJson($body)) {
                $this->body = json_encode($body, true);
            } else {
                $this->body = $body;
            }

            return $this->antesRequisicao()->envia();

        }
    }
}
<?php

namespace MobileStock\helper;

use stdClass;

/**
 * @method self post(string $url, \JsonSerializable|string $body = [], array $headers = [])
 * @method self get(string $url, \JsonSerializable|string $body = [], array $headers = [])
 * @method self put(string $url, \JsonSerializable|string $body = [], array $headers = [])
 * @method self patch(string $url, \JsonSerializable|string $body = [], array $headers = [])
 * @method self delete(string $url, \JsonSerializable|string $body = [], array $headers = [])
 */
class HttpClient
{
    /**
     * @var string|array
     */
    public $body;
    public array $headers;
    public string $url;
    public string $method;
    public int $timeout = 0; // 0 = infinito (padrão do cURL).

    public int $codigoRetorno;
    public ?array $certificado;

    protected function envia()
    {
        $curl = curl_init($this->url);

        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        switch ($this->method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        }

        if (Validador::validacaoJson($this->body) &&
            !in_array('Content-Type: application/json', $this->headers)
        ) {
            $this->headers[] = 'Content-Type: application/json';
        }

        if ($this->body) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->body);
        } else {
            $this->headers[] = 'Content-Length: 0';
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        if (isset($this->certificado)) {
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'P12');
            curl_setopt($curl, CURLOPT_SSLCERT, $this->certificado['caminho']);
            curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $this->certificado['senha']);
        }

        $response   = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $headerLength = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = preg_split(
            "/[\n\r]+/",
            trim(mb_substr(mb_substr($response, 0, $headerLength), mb_stripos($response, "\r\n")))
        );
        $response = substr($response, $headerLength);

        if (curl_errno($curl)) {
            $message = sprintf('cURL error[%s]: %s', curl_errno($curl), curl_error($curl));

            throw new \RuntimeException($message);
        }

        curl_close($curl);

        $arquivo = $this->nomeArquivoLog();
        if ($arquivo) {
            $logger = LoggerFactory::arquivo($arquivo);

            [
                'metodo' => $metodoStr,
                'url' => $urlStr,
                'body' => $bodyStr,
                'headers_envio'    => $headersEnvioStr,
                'codigo_retorno' => $codigoRetornoStr,
                'resposta' => $responseStr,
                'headers_resposta' => $headersRespostaStr,
            ] = $this->trataInfoParaLog($response, $statusCode, $headers);

            $logger->info(
                "$metodoStr | $urlStr Corpo: [$bodyStr, $headersEnvioStr] Resposta: $codigoRetornoStr | [$responseStr, $headersRespostaStr]"
            );
        }

        return $this->aposRequisicao($response, $statusCode, $headers);
    }

    public function __call($name, $arguments)
    {
        if (in_array($name = mb_strtoupper($name), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->method = $name;
            $this->url = $arguments[0] ?? '';
            $body = empty($arguments[1]) ? '' : $arguments[1];
            $this->headers = $arguments[2] ?? [];


            if (in_array('Content-Type: application/x-www-form-urlencoded',$this->headers)) {
                $this->body = http_build_query($body);
            } elseif (is_array($body) || $body instanceof stdClass || $body instanceof \JsonSerializable || Validador::validacaoJson($body)) {
                $this->body = json_encode($body, true);
            } else {
                $this->body = $body;
            }

            return $this->antesRequisicao()->envia();

        }
    }

    protected function antesRequisicao(): self
    {
        return $this;
    }

    protected function aposRequisicao(string $response, int $statusCode, array $headers): self
    {
        $this->body = \json_decode($response, true);
        $this->codigoRetorno = $statusCode;
        $this->headers = $headers;
        return $this;
    }

    /**
     * Se não preenchido não gravará log
     * @return string
     */
    protected function nomeArquivoLog(): string
    {
        return '';
    }

    protected function trataInfoParaLog(string $resposta, int $codigoRetorno, array $headers)
    {
        return [
            'metodo'           => $this->method,
            'url'              => $this->url,
            'body'             => $this->body,
            'headers_envio'    => json_encode($this->headers),
            'codigo_retorno'   => $codigoRetorno,
            'resposta'         => $resposta,
            'headers_resposta' => json_encode($headers)
        ];
    }
}
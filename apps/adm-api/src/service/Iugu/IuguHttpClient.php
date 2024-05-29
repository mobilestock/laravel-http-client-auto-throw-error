<?php

namespace MobileStock\service\Iugu;

use DateTime;
use Illuminate\Support\Carbon;
use MobileStock\helper\HttpClient;
use MobileStock\helper\IuguEstaIndisponivel;

class IuguHttpClient extends HttpClient
{
    public string $apiToken;
    public array $listaCodigosPermitidos;

    public function __construct()
    {
        $this->listaCodigosPermitidos = range(200, 399);
        $this->apiToken = $_ENV['DADOS_PAGAMENTO_IUGUAPITOKEN'];
    }

    public function criaFatura(
        array $consultaDadosPagamento,
        array $dadosTransacao,
        array $metodosPagamento,
        int $diasVencimento,
        ?string $orderId = null
    ): void {
        $email = $_ENV['AMBIENTE'] === 'producao' ? $consultaDadosPagamento['email'] : $_ENV['EMAIL_PAGAMENTO_TESTE'];
        $dadosPagamento = [
            'ensure_workday_due_date' => true,
            'items' => [
                0 => [
                    'description' => 'Transacao ' . $dadosTransacao['id'],
                    'quantity' => 1,
                    'price_cents' => round($dadosTransacao['valor_liquido'] * 100, 2),
                ],
            ],
            'payable_with' => $metodosPagamento,
            'payer' => [
                'address' => [
                    'zip_code' => 65635479,
                    'street' => $consultaDadosPagamento['street'] ?? '' ?: 'Praça da República',
                    'number' => $consultaDadosPagamento['number'] ?? '' ?: '301',
                    'district' => $consultaDadosPagamento['district'] ?? '' ?: 'República',
                    'city' => $consultaDadosPagamento['city'] ?? '' ?: 'São Paulo',
                    'state' => $consultaDadosPagamento['state'] ?? '' ?: 'SP',
                    'country' => 'Brasil',
                ],
                'cpf_cnpj' => 69771710818,
                'name' => $consultaDadosPagamento['nome'],
                'phone_prefix' => $consultaDadosPagamento['phone_prefix'],
                'phone' => $consultaDadosPagamento['phone'],
                'email' => $email,
            ],
            'due_date' => date('Y-m-d', strtotime('+' . $diasVencimento . ' day')),
            'email' => $email,
        ];

        if ($_ENV['AMBIENTE'] === 'producao') {
            $dadosPagamento['order_id'] = "prod_{$dadosTransacao['id']}" . $orderId;
        }

        $this->post('invoices', $dadosPagamento);
    }

    protected function nomeArquivoLog(): string
    {
        return 'logs_requisicoes_iugu.log';
    }

    protected function antesRequisicao(): HttpClient
    {
        $queryParams = [];
        if (mb_strpos($this->url, '?') !== false) {
            parse_str(explode('?', $this->url)[1], $queryParams);
        }
        $queryParams['api_token'] = $this->apiToken;
        $endpoint = $this->url[0] === '/' ? $this->url : "/{$this->url}";

        if (preg_match('/\/(request_withdraw|transfers)/', $endpoint)) {
            $agora = new Carbon();
            $requestTime = $agora->format(DateTime::RFC3339);

            $estrutura = "{$this->method}|/v1$endpoint\n";
            $estrutura .= "{$this->apiToken}|$requestTime\n";
            $estrutura .= $this->body;
            $this->headers['Request-Time'] = $requestTime;

            openssl_sign($estrutura, $assinatura, env('CHAVE_PRIVADA_IUGU'), OPENSSL_ALGO_SHA256);
            $this->headers['Signature'] = 'signature=' . base64_encode($assinatura);

            $chave = env('CHAVE_PRIVADA_IUGU');
            var_dump(
                "Teste estrutura gerada REQUEST: $estrutura\nAssinatura: {$this->headers['Signature']}\nChave: $chave"
            );
        }

        $this->url = "https://api.iugu.com/v1$endpoint?" . http_build_query($queryParams);
        return $this;
    }

    protected function aposRequisicao(string $response, int $statusCode, array $headers): HttpClient
    {
        if (in_array($statusCode, [520, 502]) || !\MobileStock\helper\Validador::validacaoJson($response)) {
            parent::aposRequisicao($response, $statusCode, $headers);
            throw new IuguEstaIndisponivel();
        }

        $respostaJson = json_decode($response, true);

        if (!in_array($statusCode, $this->listaCodigosPermitidos) || !empty($respostaJson['errors'] ?? [])) {
            $mensagemErroIugu = '';
            if (empty($respostaJson['errors'])) {
                $respostaJson['errors'] = $respostaJson['message'] ?? [];
            }
            if (is_array($respostaJson['errors'])) {
                foreach ($respostaJson['errors'] as $key => $value) {
                    $key = str_replace('payer.address.zip_code', 'CEP', $key);
                    $mensagemErroIugu .= $key . ' ' . implode('-', (array) $value) . ' ';
                }
            }

            parent::aposRequisicao($response, $statusCode, $headers);
            if ($mensagemErroIugu) {
                throw new \Exception($mensagemErroIugu);
            }

            throw new \Exception(
                $respostaJson['errors'] ?? 'Erro desconhecido Iugu' ?: $respostaJson['info_message'] ?? '',
                1
            );
        }
        return parent::aposRequisicao($response, $statusCode, $headers);
    }

    public function informacoesSubConta(?string $idSubConta = null): self
    {
        if ($idSubConta === null) {
            $idSubConta = $_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'];
        }
        $this->get("accounts/{$idSubConta}");

        return $this;
    }
}

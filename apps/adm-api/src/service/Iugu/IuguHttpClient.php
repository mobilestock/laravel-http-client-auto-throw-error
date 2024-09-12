<?php

namespace MobileStock\service\Iugu;

use DateTime;
use Exception;
use Illuminate\Support\Carbon;
use MobileStock\helper\HttpClient;
use MobileStock\helper\IuguEstaIndisponivel;
use MobileStock\helper\Validador;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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
            $this->headers[] = "Request-Time: $requestTime";

            openssl_sign($estrutura, $assinatura, env('CHAVE_PRIVADA_IUGU'), OPENSSL_ALGO_SHA256);
            $this->headers[] = 'Signature: signature=' . base64_encode($assinatura);
        }

        $this->url = "https://api.iugu.com/v1$endpoint?" . http_build_query($queryParams);
        return $this;
    }

    protected function aposRequisicao(string $response, int $statusCode, array $headers): HttpClient
    {
        $apos = parent::aposRequisicao($response, $statusCode, $headers);
        if (in_array($statusCode, [520, 502]) || !Validador::validacaoJson($response)) {
            throw new IuguEstaIndisponivel();
        }

        $respostaJson = json_decode($response, true);
        if (in_array($statusCode, [$this->listaCodigosPermitidos]) && empty($respostaJson['errors'] ?? [])) {
            return $apos;
        }

        $mensagemErroIugu = '';
        $respostaJson['errors'] = $respostaJson['errors'] ?? ($respostaJson['message'] ?? []);
        if (is_array($respostaJson['errors'])) {
            foreach ($respostaJson['errors'] as $key => $value) {
                $key = str_replace('payer.address.zip_code', 'CEP', $key);
                $valorErro = implode('-', (array) $value);
                $mensagemErroIugu .= "$key $valorErro ";
            }
        }

        if (preg_match('/amount_cents (Insufficient Balance|Saldo insuficiente)/', $mensagemErroIugu)) {
            throw new UnprocessableEntityHttpException('Saldo insuficiente na sub conta Iugu');
        } elseif ($mensagemErroIugu) {
            throw new Exception($mensagemErroIugu);
        }

        throw new Exception(
            $respostaJson['errors'] ?? 'Erro desconhecido Iugu' ?: $respostaJson['info_message'] ?? '',
            1
        );
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

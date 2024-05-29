<?php

use Illuminate\Support\Carbon;
use MobileStock\service\Iugu\IuguHttpClient;
use test\TestCase;

class AssinaturaRSATest extends TestCase
{
    public $IuguHttpClient;
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', '2001-09-11 08:46:00'));

        $_ENV['CHAVE_PRIVADA_IUGU'] = "-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCdXvGRqGZoDU10
cNKdKQK7p9Ykf0cyLltA17H9cT0RZDS7BRiojH7/zq1fpqURg9rB4DUV2M5HVt0O
mUTE9YX75hE/W4ZjsBjiLkLKTLZbarzQ6sXouMVQdV8jA6oGXZS8D0YfXB4FaALW
krzhdoyVP/jcsJl10MRutPTmGtT5zLQPGSJu7YFWKynZUFRVrnnDQDRUtknnJYw+
ZXrpfzA1oI8IDdE6CIkhzRqMu3RbISqGS+4pDDJYgO8VVBdD82uxrRNKDlyi7+mn
LqXu/wLRfvn2fYb3F4YoPXp9SArG3ycDEuQgYISqOKH5P3jATvRimvVKsz59biDj
Otfrb2nnAgMBAAECggEBAISKu5C/MYj/czXX9DszmD6uzBgvLqqgCnFheWKJJjLo
n9TIJQ2IT0pqKvF9rFdFI1DY4j0FPi8thL9P7XCpjXAsRGiFUHnTjhGpfs1dsNTr
4B1hLtCkFmN+h2M5KdF0rdl6T8gH0K0i/gj0y6plK2Bk4dgV0Ro+e8L2G7FV9fxw
zyzgUoEHyuimkokEPViGLipmqyLShuval1tfg6Q3R7ZousrHOBPnTz6XTElruR0I
laQrG831Q0YuwGm7VJA8ifvX16uvFoBUSwoUZr8t1mDCh23T13utRJYr6593OidL
86wC1FQ2impNmbJJdl1ZkD487LgvMN8vW/9ObkRSu4kCgYEAy/z+uCpXVM27lwKG
6RelVI9IN7SZ/W0FdIcHqfrbcowmKsv6JukVRXAV0vTXqhkWYPvMzA9NdCQtWjWz
KmpI8ILZFxZv1qIQ96i5cFUbXWQsi1+LgrO1WTXnkgRpURQcebG/3Gq0CC4tpM0r
fBFvKjITukkwioYElntg6snrKmUCgYEAxX8S4Z+dLEHbsTXJYCBU+0q8sWe4L3z6
3hvR7541fP3728eZhqeSNdm2ATpXt6WnBa6mhMbkioM3tZ14PWiVWFh7bkO3918A
2fx+dB4mr1hFVpYwKdxMHJiK942L73aiQN1KSmmHXuBvgJMauRJ6kGzOgtZz0KG8
EmMEPXmYeFsCgYBYm8k5zoqo98Uoz5wy3Gag2KySJg1OHHFmMNGPcLyqgV6C8J/1
DwKCazHPtTOJW+RwtHA9o9gNPznEGdd98TVF5FDQyppCLZwZOF11AkMkykLfN92u
JMn9uoCg2PG2mnnUEvY4lNEnTIffMpBVEG2tcptHLEu9oIGVrHppAtT4UQKBgC5m
JWR2oHF2Y4vlrBL2ZaDINT6ktIQLo9CszoyyKbTc4uAGq84T7mjSZk0xjMwrkerm
1l5Zb/YOz/bOMSKUQIoJ9623ITBv1H5iML9NGh+V6GxoSpZ7GDKbsAJq8dZnk8UT
eFG1K0WiCvA1H1Edw0fNGFNq2LjKVqonMybSO30DAoGBAIe3eGONvY4qM8hNzL85
8qZyYFp0uZIk3h0+UXPJLEy2t5uY9EFVoxmGmP7bT8a576J/4s/W3M69hsCQ4y6t
HlxobnSupnJ7MCm9NBGtDII3M4Gb+xU3fRQSZQLoNzlZ/gXq3XwQJnOvXbMaYfQ1
l6WcvLZSM4r/FXJM0TuU7bDN
-----END PRIVATE KEY-----
";
        $_ENV['DADOS_PAGAMENTO_IUGUAPITOKEN'] = 'API_TOKEN_CHAVE';

        $this->IuguHttpClient = new class extends IuguHttpClient {
            protected function envia()
            {
                return $this;
            }
            protected function aposRequisicao(
                string $response,
                int $statusCode,
                array $headers
            ): MobileStock\helper\HttpClient {
                return $this;
            }
        };
    }
    public function dadosCorpoAssinatura(): array
    {
        return [
            'corpo requisição' => [
                [
            'amount_cents' => 666,
            'custom_variables' => [
                [
                    'name' => 'tipo',
                    'value' => 'TESTE MOBILE ASSINATURA',
                ],
                [
                    'name' => 'id_transferencia',
                    'value' => 69,
                ],
            ],
            'receiver_id' => 'ID_RECEBEDOR',
            'account_id' => env('DADOS_PAGAMENTO_IUGUCONTAMOBILE'),
            'test' => true,
                ],
            ],
        ];
    }

        $agora = new Carbon();
        $requestTime = $agora->format(DateTime::RFC3339);
        $apiToken = env('DADOS_PAGAMENTO_IUGUAPITOKEN');

        $estrutura = "POST|/v1/transfers\n";
        $estrutura .= "$apiToken|$requestTime\n";
        $estrutura .= json_encode($corpo);

        openssl_sign($estrutura, $assinatura, env('CHAVE_PRIVADA_IUGU'), OPENSSL_ALGO_SHA256);
        $assinatura = 'signature=' . base64_encode($assinatura);

        openssl_sign($estrutura, $foo, env('CHAVE_PRIVADA_IUGU'), OPENSSL_ALGO_SHA256);
        $foo = 'signature=' . base64_encode($foo);

        $this->assertEquals($foo, '');

        // $this->IuguHttpClient->listaCodigosPermitidos = [200];
        // $retorno = $this->IuguHttpClient->post('transfers', $corpo);

        // $this->assertEquals($assinatura, $retorno->headers['Signature']);
    }
}

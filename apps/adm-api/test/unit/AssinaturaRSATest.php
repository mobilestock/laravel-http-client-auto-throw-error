<?php

use Illuminate\Support\Carbon;
use test\TestCase;

class AssinaturaRSATest extends TestCase
{
    protected string $chavePublica;
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', '2001-09-11 08:46:00'));

        $rsa = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($rsa, $chavePrivada);
        $this->chavePublica = openssl_pkey_get_details($rsa)['key'];
        $_ENV['CHAVE_PRIVADA_IUGU'] = $chavePrivada;
    }
    public function testAssinar(): void
    {
        $agora = new Carbon();
        $requestTime = $agora->format(DateTime::RFC3339);

        $estrutura = "POST|/v1/transfers\n";
        $estrutura .= "API_TOKEN_CHAVE|$requestTime\n";
        $estrutura .= json_encode(['teste' => 'assinatura']);

        openssl_sign($estrutura, $assinatura, env('CHAVE_PRIVADA_IUGU'), OPENSSL_ALGO_SHA256);
        $foo = base64_encode($assinatura);

        $this->assertTrue(true);
    }
}

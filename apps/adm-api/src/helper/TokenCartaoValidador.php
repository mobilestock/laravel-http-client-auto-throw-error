<?php

namespace MobileStock\helper;

class TokenCartaoValidador
{
    public string $cartaoEncriptado;
    private static string $metodoEncriptar = 'aes-256-cbc';

    public function __construct(string $cartaoEncriptado)
    {
        $this->cartaoEncriptado = $cartaoEncriptado;
    }

    public function buscaChavePublicaToken(): string
    {
        $tokens = explode('__DIVIDER__', base64_decode($this->cartaoEncriptado));

        // nao verifica imutabilidade
        return $tokens[1] ?? '';
    }

    public function desencriptaCartao(string $chave): array
    {
        $tokens                = explode('__DIVIDER__', base64_decode($this->cartaoEncriptado));

        $tokenBinario          = base64_decode($tokens[0]);
        $ivLength              = openssl_cipher_iv_length(self::$metodoEncriptar);

        $primeiroEncriptado    = substr($tokenBinario,$ivLength+64);
        $segundoEncriptado     = substr($tokenBinario,$ivLength,64);
        
        $iv                    = substr($tokenBinario,0,$ivLength);
        $data                  = openssl_decrypt($primeiroEncriptado,self::$metodoEncriptar,$chave,OPENSSL_RAW_DATA,$iv);

        $segundoEncriptadoNovo = hash_hmac('sha3-512', $primeiroEncriptado, $chave, true);
        
        if (hash_equals($segundoEncriptado,$segundoEncriptadoNovo)) {
            $dadosCartao = json_decode($data, true);
            return $dadosCartao;
        }

        return [];
    }

    public static function geraToken(array $dadosCartao, string $chavePrivada, string $chavePublica): string
    {
        $ivLength           = openssl_cipher_iv_length(self::$metodoEncriptar);
        $iv                 = openssl_random_pseudo_bytes($ivLength);
            
        $primeiroEncriptado = openssl_encrypt(json_encode($dadosCartao),self::$metodoEncriptar,$chavePrivada, OPENSSL_RAW_DATA ,$iv);   
        $segundoEncriptado  = hash_hmac('sha3-512', $primeiroEncriptado, $chavePrivada, true);
                
        $tokenCartao        = base64_encode($iv.$segundoEncriptado.$primeiroEncriptado);

        $tokenCartao       .= '__DIVIDER__' . $chavePublica;

        return base64_encode($tokenCartao);
    }
}

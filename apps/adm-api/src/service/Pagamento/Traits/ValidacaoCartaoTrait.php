<?php

namespace MobileStock\service\Pagamento\Traits;

use MobileStock\helper\TokenCartaoValidador;
use MobileStock\helper\Validador;
use MobileStock\service\CartoesSenhasService;

trait ValidacaoCartaoTrait
{
    public function validaTransacao(): void
    {
        if ($this->transacao->dados_cartao['tokenCartao']) {
            $validador = new TokenCartaoValidador($this->transacao->dados_cartao['tokenCartao']);
            $chavePublica = $validador->buscaChavePublicaToken();

            $chavePrivada = CartoesSenhasService::buscaChavePrivadaPorChavePublica($this->conexao, $chavePublica);
            $cartao = $validador->desencriptaCartao($chavePrivada);

            if (empty($cartao) || ($cartao['id_cliente'] ?? '') !== $this->transacao->pagador) {
                throw new \InvalidArgumentException('Cartão inválido');
            }

            $this->transacao->dados_cartao = [
                "holderName" => $cartao['proprietario'],
                "cardNumber" => $cartao['numero'],
                "secureCode" => $this->transacao->dados_cartao['secureCode'],
                "expirationMonth" => $cartao['mes'],
                "expirationYear" => $cartao['ano']
            ];
        }

        Validador::validar($this->transacao->dados_cartao, [
            'holderName' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            'cardNumber' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'secureCode' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'expirationMonth' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'expirationYear' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);
    }
}
<?php

namespace api_pagamento\Controller;

use api_pagamento\Models\Request_m;
use MobileStock\helper\TokenCartaoValidador;
use MobileStock\helper\Validador;
use MobileStock\service\CartoesSenhasService;
use PDO;

class TokenCartao extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function buscaCartoes(PDO $conexao)
    {
        $dadosGet = $this->request->query->all();
        Validador::validar($dadosGet, [
            'lista_cartoes' => [Validador::OBRIGATORIO]
        ]);

        $dadosGet['lista_cartoes'] = explode(',', $dadosGet['lista_cartoes']);
        $listaCartoes              = [];

        foreach ($dadosGet['lista_cartoes'] as $key => $cartaoEncriptado) {
            $validador          = new TokenCartaoValidador($cartaoEncriptado);

            $chavePublica       = $validador->buscaChavePublicaToken();

            try {
                $chave          = CartoesSenhasService::buscaChavePrivadaPorChavePublica($conexao, $chavePublica);
            } catch (\DomainException $erro) {
                continue;
            }

            $cartaoDesencriptado = $validador->desencriptaCartao($chave);
            $jaExisteCartao = !empty(array_filter($listaCartoes, function(array $cartao) use ($cartaoDesencriptado) {
                return $cartao['cartao_hash'] === $cartaoDesencriptado['cartao_hash'];
            }));

            if(empty($cartaoDesencriptado) || $jaExisteCartao) {
                continue;
            }

            if ($cartaoDesencriptado['id_cliente'] === $this->idCliente) {
                unset($cartaoDesencriptado['id_cliente']);
                $cartaoDesencriptado['status'] = 'ok';
                $listaCartoes[$key] = $cartaoDesencriptado;
                $listaCartoes[$key]['numero'] = '**** **** **** ' . substr($listaCartoes[$key]['numero'], 12);
            } else {
                $listaCartoes[$key] = [
                    'status' => 'unauthorized'
                ];
            }
            $listaCartoes[$key]['token'] = $cartaoEncriptado;
        }

        return array_values($listaCartoes);
    }
}
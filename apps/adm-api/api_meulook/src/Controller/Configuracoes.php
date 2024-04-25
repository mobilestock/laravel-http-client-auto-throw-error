<?php

namespace api_meulook\Controller;

class Configuracoes
{
    public function buscaConfiguracoes()
    {
        $telefones = [
            'telefoneDuvidasMeulook' => '37 99953 0450',
            'urlMobilePay' => $_ENV['URL_LOOKPAY'],
            'telefoneAtendimentoMeuLook' => '37 99103 2627',
            'telefoneSuporteEntregas' => '37 99103 2627',
            'telefoneSuporteFornecedores' => '37 99112 2302',
            'telefoneDisputaTroca' => '37 99103 2627',
            'telefoneDuvidasPontosEEntregadores' => '37 99112 5188',
        ];

        return $telefones;
    }
}

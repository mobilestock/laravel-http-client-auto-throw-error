<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\DB;
use MobileStock\service\ConfiguracaoService;
use Symfony\Component\HttpFoundation\Response;

class Configuracao
{
    public function configuracoesProdutoPago()
    {
        $resposta = ConfiguracaoService::buscaConfiguracaoProdutoPago(DB::getPdo());
        return $resposta;
    }
    public function sentry()
    {
        $resposta = ConfiguracaoService::permiteMonitoramentoSentry();
        if ($resposta) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        return new Response(null, Response::HTTP_BAD_REQUEST);
    }
}

?>

<?php

namespace MobileStock\service;

use MobileStock\helper\HttpClient;

class ZeoRoutePlannerHttpClient extends HttpClient
{
    public function __construct()
    {
        $this->listaCodigosPermitidos = [200, 201];
    }

    protected function antesRequisicao(): HttpClient
    {
        $this->url = 'https://zeorouteplanner.com' . $this->url . '?api_key=' . $_ENV['ZEO_API_TOKEN'];
        return $this;
    }
}
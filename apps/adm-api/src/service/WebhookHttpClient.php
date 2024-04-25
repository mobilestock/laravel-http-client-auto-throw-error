<?php

namespace MobileStock\service;

use MobileStock\helper\HttpClient;

class WebhookHttpClient extends HttpClient
{
    protected function nomeArquivoLog(): string
    {
        return 'logs_requisicoes_webhook.log';
    }
}
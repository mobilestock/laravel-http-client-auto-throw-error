<?php

namespace api_webhooks\Controller;

use Illuminate\Http\Request;
use MobileStock\service\Fila\FilaService;

class FilaRecebiveis
{
    public function salva(Request $request, FilaService $fila)
    {
        $arrayTransacao = $request->request->all();

        $arrayTransacao['data'] = [
            'id' => $arrayTransacao['data']['id'],
            'status' => $arrayTransacao['data']['status']
        ];
        $fila->url_fila = $_ENV['SQS_ENDPOINTS']['ATUALIZAR_PAGAMENTO_WEBHOOK'];
        $fila->conteudoArray = $arrayTransacao;
        $fila->envia();
    }
}

?>
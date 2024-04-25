<?php

namespace MobileStock\jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use MobileStock\helper\Images\ImagemEntregaMobile;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\MessageService;

class ImagemRetiradaMs implements ShouldQueue
{
    protected int $idEntrega;

    public function __construct(int $idEntrega)
    {
        $this->idEntrega = $idEntrega;
    }

    public function handle(MessageService $whatsapp)
    {
        $dadosParaImagem = EntregaServices::consultarDadosDaEntregaParaFaturaMobile($this->idEntrega);
        $produtos = array_chunk($dadosParaImagem['produtos'], 10);

        $comMiniatura = count($dadosParaImagem['produtos']) <= 50;

        foreach ($produtos as $produto) {
            $dadosParaImagem['produtos'] = $produto;
            $imagem = ImagemEntregaMobile::gerarImagem($dadosParaImagem, $comMiniatura);
            $whatsapp->sendImageBase64WhatsApp(
                $dadosParaImagem['telefone'],
                $imagem,
                'ENTREGA ' . $dadosParaImagem['id']
            );
        }
    }
}

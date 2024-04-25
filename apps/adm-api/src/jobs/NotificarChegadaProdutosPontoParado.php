<?php

namespace MobileStock\jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use MobileStock\helper\Globals;
use MobileStock\model\Entrega;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\MessageService;

class NotificarChegadaProdutosPontoParado implements ShouldQueue
{
    /**
     * @var array<string> $uuidProdutos
     */
    protected array $uuidProdutos;

    public function __construct(array $uuidProdutos)
    {
        $this->uuidProdutos = $uuidProdutos;
    }

    public function handle(EntregasFaturamentoItemService $entregaFI, MessageService $whatsapp)
    {
        $resultado = EntregasFaturamentoItemService::buscaProdutosParaNotificarChegadaPontoParado($this->uuidProdutos);
        foreach ($resultado as $entregaCliente) {
            $linkHistorico = '';

            // - enviar mensagem com texto de todos os produtos
            $mensagem = "Olá {$entregaCliente['razao_social']}!" . PHP_EOL . PHP_EOL;
            $mensagem .= 'Os produtos abaixo já podem ser retirados:' . PHP_EOL . PHP_EOL;
            $mensagem .= implode(
                "\n",
                array_map(
                    fn($item) => EntregasFaturamentoItemService::manipulaStringMensagemDeProduto($item),
                    $entregaCliente['produtos']
                )
            );

            $linkHistorico = [];
            if (in_array('MS', array_column($entregaCliente['produtos'], 'origem'))) {
                $linkHistorico[] = "Para detalhes da sua compra feita no MobileStock, acesse: {$_ENV['URL_AREA_CLIENTE']}historico_pedido";
            }
            if (in_array('ML', array_column($entregaCliente['produtos'], 'origem'))) {
                $linkHistorico[] = "Para detalhes da sua compra feita no meulook, acesse: {$_ENV['URL_MEULOOK']}usuario/historico";
            }
            $linkHistorico = implode(PHP_EOL . PHP_EOL, $linkHistorico);

            $mensagem .= PHP_EOL . PHP_EOL . $linkHistorico . PHP_EOL . PHP_EOL;

            $mensagem .=
                "Mostre o QR acima para o ponto de retirada *{$entregaCliente['ponto']['nome']}*." . PHP_EOL . PHP_EOL;
            $mensagem .= 'Endereço: *' . trim($entregaCliente['ponto']['endereco']) . '*' . PHP_EOL . PHP_EOL;
            $mensagem .=
                'Horário de Funcionamento: *' .
                trim($entregaCliente['ponto']['horario_de_funcionamento']) .
                '*' .
                PHP_EOL .
                PHP_EOL;
            $mensagem .= 'Localização do ponto de retirada:' . PHP_EOL;
            $mensagem .=
                "https://www.google.com/maps/search/{$entregaCliente['ponto']['latitude']},{$entregaCliente['ponto']['longitude']}" .
                PHP_EOL .
                PHP_EOL;
            $mensagem .= 'Contato do ponto de retirada para dúvidas sobre a entrega:' . PHP_EOL;
            $mensagem .= "https://wa.me/55{$entregaCliente['ponto']['telefone']}" . PHP_EOL . PHP_EOL;

            $qrCode = Globals::geraQRCODE(Entrega::formataEtiquetaCliente($entregaCliente['id_cliente']));

            if (sizeof($entregaCliente['produtos']) <= 4) {
                $imagemFinal = $entregaFI->criaImagemGradeDeFotosDeProdutos($entregaCliente['produtos'], $qrCode);
                $whatsapp->sendImageBase64WhatsApp($entregaCliente['telefone'], $imagemFinal, $mensagem);
            } else {
                $whatsapp->sendImageWhatsApp($entregaCliente['telefone'], $qrCode, $mensagem);
                foreach ($entregaCliente['produtos'] as $produto) {
                    $whatsapp->sendImageWhatsApp($entregaCliente['telefone'], $produto['foto']);
                }
            }
        }
    }
}

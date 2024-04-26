<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Images\ImplementacaoImagemGD\ImagemPagamentoAprovadoMobileGD;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\MessageService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\WebhookHttpClient;

class Pagamento implements ShouldQueue
{
    use Queueable;

    protected array $dadosTransacao;

    public function __construct(array $dadosTransacao)
    {
        $this->dadosTransacao = $dadosTransacao;
    }

    public function handle(
        MessageService $whatsapp,
        WebhookHttpClient $httpClient
    ) {
        $transacao = (new TransacaoFinanceiraModel())->forceFill($this->dadosTransacao);

        if ($transacao->origem_transacao === 'ML') {
            $listaProdutos = ProdutoService::dadosMensagemPagamentoAprovado($transacao->id);

            $comMiniatura = count($listaProdutos) <= 50;

            $produtos = array_chunk($listaProdutos, 10);

            foreach ($produtos as $produto) {
                $listaProdutos = $produto;
                $imagemGD = new ImagemPagamentoAprovadoMobileGD($listaProdutos, $comMiniatura);
                $imagem = $imagemGD->gerarImagemBase64();
                $whatsapp->sendImageBase64WhatsApp(
                    $listaProdutos[0]['telefone'],
                    $imagem,
                    'O pagamento do seu pedido Nº ' .
                        $listaProdutos[0]['id_transacao'] .
                        ' foi aprovado! Link para rastreio: ' .
                        $_ENV['URL_MEULOOK'] .
                        'usuario/historico'
                );
            }
        } elseif ($transacao->origem_transacao === 'ZA') {
            $url = ColaboradoresService::consultaUrlWebhook($transacao->pagador);
            $arrTransacao = TransacaoFinanceiraService::listaTransacoesApi(
                DB::getPdo(),
                $transacao->pagador,
                $transacao->id,
                1
            );
            $arrInformacoes = [
                'transacao' => $arrTransacao,
                'evento' => 'transacao.paga',
            ];

            $httpClient->post($url, $arrInformacoes);
        }
    }
}

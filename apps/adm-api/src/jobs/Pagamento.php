<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Images\ImagemPagamentoAprovadoMobile;
use MobileStock\model\Origem;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use MobileStock\model\TrocaFilaSolicitacoesModel;
use MobileStock\repository\TrocaPendenteRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\MessageService;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasProdutosTrocasService;
use MobileStock\service\TrocaFilaSolicitacoesService;
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
        WebhookHttpClient $httpClient,
        ImagemPagamentoAprovadoMobile $imagemPagamentoAprovadoMobile
    ) {
        $transacao = (new TransacaoFinanceiraModel())->forceFill($this->dadosTransacao);

        switch ($transacao->origem_transacao) {
            case 'ML':
                $listaProdutos = ProdutoService::dadosMensagemPagamentoAprovado($transacao->id);

                $comMiniatura = count($listaProdutos) <= 50;

                $produtos = array_chunk($listaProdutos, 10);

                foreach ($produtos as $produto) {
                    $listaProdutos = $produto;
                    $textoImagem = $imagemPagamentoAprovadoMobile->gerarImagem($listaProdutos, $comMiniatura);
                    $whatsapp->sendImageBase64WhatsApp(
                        $listaProdutos[0]['telefone'],
                        $textoImagem,
                        'O pagamento do seu pedido Nº ' .
                        $listaProdutos[0]['id_transacao'] .
                        ' foi aprovado! Link para rastreio: ' .
                        $_ENV['URL_MEULOOK'] .
                        'usuario/historico'
                    );
                }
                break;
            case 'ZA':
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

                $httpClient->post($url, json_encode($arrInformacoes));
                break;
            case 'ET':
                TransacaoFinanceirasProdutosTrocasService::converteDebitoPendenteParaNormalSeNecessario(
                    $transacao->pagador
                );
                TransacaoFinanceirasProdutosTrocasService::sincronizaTrocaPendenteAgendamentoSeNecessario(
                    $transacao->pagador
                );
                $uuids = (new TransacaoFinanceirasMetadadosService())->buscaUuidsMetadadoProdutosTroca($transacao->id);
                if (empty($uuids)) {
                    return;
                }

                foreach ($uuids as $uuidProduto) {
                    $produto = PedidoItemMeuLookService::buscaDadosProdutoPorUuid(
                        DB::getPdo(),
                        $uuidProduto,
                        Origem::ML
                    );
                    if ($produto['existe_agendamento']) {
                        TrocaPendenteRepository::removeTrocaAgendadadaNormalMeuLook(DB::getPdo(), $uuidProduto);
                    }
                    if ($produto['id_solicitacao']) {
                        $trocaFilaSolicitacoes = new TrocaFilaSolicitacoesModel();
                        $trocaFilaSolicitacoes->exists = true;
                        $trocaFilaSolicitacoes->id = $produto['id_solicitacao'];
                        $trocaFilaSolicitacoes->situacao = TrocaFilaSolicitacoesModel::CANCELADO_PELO_CLIENTE;
                        $trocaFilaSolicitacoes->update();
                        TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp(
                            DB::getPdo(),
                            $produto['id_solicitacao'],
                            Origem::ML
                        );
                    }
                }
                break;
        }
    }
}

<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Produto;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;

class GerenciarPrevisaoFreteConferido implements ShouldQueue
{
    use Queueable;

    protected string $uuidProduto;
    public function __construct(string $uuidProduto)
    {
        $this->uuidProduto = $uuidProduto;
    }
    public function handle(
        PontosColetaAgendaAcompanhamentoService $agenda,
        TransacaoFinanceirasMetadadosService $metadados,
        PrevisaoService $previsao
    ): void {
        $informacoes = LogisticaItemModel::buscaInformacoesProdutoPraAtualizarPrevisao($this->uuidProduto);

        $agenda->id_colaborador = $informacoes['id_colaborador_ponto_coleta'];
        $pontoColeta = $agenda->buscaPrazosPorPontoColeta();
        $informacoes['dias_processo_entrega']['dias_pedido_chegar'] = $pontoColeta['dias_pedido_chegar'];
        $mediasEnvio = $previsao->calculoDiasSeparacaoProduto(
            $informacoes['id_produto'],
            $informacoes['nome_tamanho'],
            $informacoes['id_responsavel_estoque']
        );
        $previsoes = current(
            $previsao->calculaPorMediasEDias(
                $mediasEnvio,
                $informacoes['dias_processo_entrega'],
                $pontoColeta['agenda']
            )
        );

        $metadadosExistentes = TransacaoFinanceirasMetadadosService::buscaChavesTransacao($informacoes['id_transacao']);
        $produtosJson = $metadadosExistentes['PRODUTOS_JSON'];
        $metadados->id = $produtosJson['id'];
        $metadados->id_transacao = $informacoes['id_transacao'];
        $metadados->chave = 'PRODUTOS_JSON';
        $produtosAtualizados = array_map(function (array $produto) use ($previsoes): array {
            if ($produto['uuid_produto'] === $this->uuidProduto) {
                $produto['previsao'] = $previsoes;
            }

            return $produto;
        }, $produtosJson['valor']);
        if (json_encode($produtosAtualizados) !== json_encode($produtosJson['valor'])) {
            $metadados->valor = $produtosAtualizados;
            $metadados->alterar(DB::getPdo());
        }
    }
}

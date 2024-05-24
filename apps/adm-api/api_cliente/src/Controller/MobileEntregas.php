<?php

namespace api_cliente\Controller;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Pedido\PedidoItem as PedidoItemModel;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\Frete\FreteService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class MobileEntregas
{
    public function buscaDetalhesFreteDoEndereco(int $idEndereco)
    {
        $entregador = TransportadoresRaio::buscaEntregadoresMobileEntregas($idEndereco);

        $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();
        $atendeFreteExpresso =
            $entregador['id_colaborador_ponto_coleta'] !== TipoFrete::ID_COLABORADOR_CENTRAL &&
            empty($itensNaoExpedidos);

        return [
            'eh_endereco_padrao' => $entregador['eh_endereco_padrao'],
            'pode_ser_atendido_frete_padrao' => !empty($entregador['id_tipo_frete']),
            'pode_ser_atendido_frete_expresso' => $atendeFreteExpresso,
        ];
    }

    public function buscaDetalhesPraCompra(PrevisaoService $previsao)
    {
        $nomeTamanho = 'Unico';

        $ultimoFreteEscolhido =
            ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador)->id_tipo_entrega_padrao ===
            TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA
                ? 'EXPRESSO'
                : 'PADRAO';

        $dadosTipoFrete = TransportadoresRaio::buscaEntregadoresMobileEntregas();

        if (!empty($dadosTipoFrete['id_tipo_frete'])) {
            $produtoFrete = ProdutoService::buscaPrecoEResponsavelProduto(ProdutoModel::ID_PRODUTO_FRETE, $nomeTamanho);

            $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
            $agenda->id_colaborador = $dadosTipoFrete['id_colaborador_ponto_coleta'];
            $prazosPontoColetaEntregador = $agenda->buscaPrazosPorPontoColeta();

            $previsoes = null;
            if (!empty($prazosPontoColetaEntregador['agenda'])) {
                $diasProcessoEntrega = Arr::only($dadosTipoFrete, ['dias_entregar_cliente', 'dias_margem_erro']);
                $diasProcessoEntrega['dias_pedido_chegar'] = $prazosPontoColetaEntregador['dias_pedido_chegar'];

                $mediasEnvio = $previsao->calculoDiasSeparacaoProduto(
                    ProdutoModel::ID_PRODUTO_FRETE,
                    $nomeTamanho,
                    $produtoFrete['id_responsavel']
                );
                $proximoEnvio = $previsao->calculaProximoDiaEnviarPontoColeta($prazosPontoColetaEntregador['agenda']);
                $previsoes = $previsao->calculaPorMediasEDias(
                    $mediasEnvio,
                    $diasProcessoEntrega,
                    $prazosPontoColetaEntregador['agenda']
                );
                $previsoes = current(
                    array_filter($previsoes, fn(array $item): bool => $item['responsavel'] === 'FULFILLMENT')
                );
                $dataEnvio = $proximoEnvio['data_envio']->format('d/m/Y');
                $horarioEnvio = current($proximoEnvio['horarios_disponiveis'])['horario'];
                $previsoes['data_limite'] = "$dataEnvio às $horarioEnvio";
            }

            $objetoFretePadrao = [
                'id_tipo_frete' => $dadosTipoFrete['id_tipo_frete'],
                'preco_produto_frete' => $produtoFrete['preco'],
                'preco_entregador' => $dadosTipoFrete['valor'],
                'previsao' => $previsoes,
            ];
        }

        // @issue https://github.com/mobilestock/backend/issues/282
        $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();

        if (
            $dadosTipoFrete['id_colaborador_ponto_coleta'] !== TipoFrete::ID_COLABORADOR_CENTRAL &&
            empty($itensNaoExpedidos)
        ) {
            $produtoFreteExpresso = ProdutoService::buscaPrecoEResponsavelProduto(
                ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                $nomeTamanho
            );

            $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
            $agenda->id_colaborador = $dadosTipoFrete['id_colaborador_ponto_coleta'];
            $prazosPontoColetaExpresso = $agenda->buscaPrazosPorPontoColeta();

            $previsoes = null;
            if (!empty($prazosPontoColetaExpresso['agenda'])) {
                $diasProcessoEntrega = [
                    'dias_entregar_cliente' => $dadosTipoFrete['dias_entregar_cliente'],
                    'dias_pedido_chegar' => $prazosPontoColetaExpresso['dias_pedido_chegar'],
                    'dias_margem_erro' => 0,
                ];

                $mediasEnvio = $previsao->calculoDiasSeparacaoProduto(
                    ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                    $nomeTamanho,
                    $produtoFreteExpresso['id_responsavel']
                );

                $proximoEnvio = $previsao->calculaProximoDiaEnviarPontoColeta($prazosPontoColetaExpresso['agenda']);

                $previsoes = $previsao->calculaPorMediasEDias(
                    $mediasEnvio,
                    $diasProcessoEntrega,
                    $prazosPontoColetaExpresso['agenda']
                );

                $previsoes = current(
                    array_filter($previsoes, fn(array $item): bool => $item['responsavel'] === 'FULFILLMENT')
                );
                $dataEnvio = $proximoEnvio['data_envio']->format('d/m/Y');
                $horarioEnvio = current($proximoEnvio['horarios_disponiveis'])['horario'];
                $previsoes['data_limite'] = "$dataEnvio às $horarioEnvio";
            }

            $objetoFreteExpresso = [
                'id_tipo_frete' => TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA,
                'preco_produto_frete' => $produtoFreteExpresso['preco'],
                'valor_frete' => $dadosTipoFrete['valor_frete'],
                'valor_adicional' => $dadosTipoFrete['valor_adicional'],
                'quantidade_maxima' => PedidoItemModel::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE,
                'previsao' => $previsoes,
            ];
        }

        return [
            'ultimo_frete_escolhido' => $ultimoFreteEscolhido,
            'frete_padrao' => $objetoFretePadrao ?? null,
            'frete_expresso' => $objetoFreteExpresso ?? null,
        ];
    }
    public function buscaHistoricoCompras(int $pagina)
    {
        $pedidos = TransacaoConsultasService::buscaPedidosMobileEntregas($pagina);

        return $pedidos;
    }
    public function limparCarrinho(TransacaoFinanceiraService $transacao)
    {
        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->removeTransacoesEmAberto(DB::getPdo());
        PedidoItem::limparProdutosFreteEmAbertoCarrinhoCliente();
    }

    public function calcularQuantidadesFreteExpresso()
    {
        $request = Request::all();

        Validador::validar($request, [
            'quantidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'valor_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'valor_adicional' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'valor_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $subTotal = FreteService::calculaValorFrete(
            0,
            $request['quantidade'],
            $request['valor_frete'],
            $request['valor_adicional']
        );

        $total = $subTotal + $request['valor_produto'] * $request['quantidade'];

        return $total;
    }
}

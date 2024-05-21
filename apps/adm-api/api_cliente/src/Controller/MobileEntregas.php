<?php

namespace api_cliente\Controller;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Municipio;
use MobileStock\model\Pedido\PedidoItem as PedidoItemModel;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MobileEntregas
{
    public function buscaDetalhesFreteDoEndereco(int $idEndereco)
    {
        $endereco = ColaboradorEndereco::buscarEndereco($idEndereco);
        if (empty($endereco)) {
            throw new NotFoundHttpException('Endereço não encontrado.');
        }

        $entregador = TransportadoresRaio::buscaEntregadoresMobileEntregas(
            $endereco->id_cidade,
            $endereco->latitude,
            $endereco->longitude
        );

        $idColaboradorExpresso = Municipio::buscaCidade($endereco->id_cidade)->id_colaborador_transportador;
        // @issue https://github.com/mobilestock/backend/issues/282
        $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();
        $atendeFreteExpresso =
            $idColaboradorExpresso !== TipoFrete::ID_COLABORADOR_TRANSPORTADORA && empty($itensNaoExpedidos);

        return [
            'eh_endereco_padrao' => $endereco->eh_endereco_padrao,
            'pode_ser_atendido_frete_padrao' => !empty($entregador),
            'pode_ser_atendido_frete_expresso' => $atendeFreteExpresso,
        ];
    }

    public function buscaDetalhesPraCompra(PrevisaoService $previsao)
    {
        $nomeTamanho = 'Unico';
        $objetoFreteExpresso = null;
        $objetoFretePadrao = null;

        $endereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        $ultimoFreteEscolhido =
            ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador)->id_tipo_entrega_padrao ===
            TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA
                ? 'EXPRESSO'
                : 'PADRAO';

        $dadosTipoFrete = TransportadoresRaio::buscaEntregadoresMobileEntregas(
            $endereco->id_cidade,
            $endereco->latitude,
            $endereco->longitude
        );

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

        $dadosFreteExpresso = Municipio::buscaCidade($endereco->id_cidade);
        // @issue https://github.com/mobilestock/backend/issues/282
        $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();

        if (
            $dadosFreteExpresso->id_colaborador_transportador !== TipoFrete::ID_COLABORADOR_TRANSPORTADORA &&
            empty($itensNaoExpedidos)
        ) {
            $produtoFreteExpresso = ProdutoService::buscaPrecoEResponsavelProduto(
                ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                $nomeTamanho
            );

            $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
            $agenda->id_colaborador = $dadosFreteExpresso->id_colaborador_transportador;
            $prazosPontoColetaExpresso = $agenda->buscaPrazosPorPontoColeta();

            $previsoes = null;
            if (!empty($prazosPontoColetaExpresso['agenda'])) {
                $diasProcessoEntrega = [
                    'dias_entregar_cidade' => $dadosFreteExpresso->dias_entregar_frete,
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
                'valor_frete' => $dadosFreteExpresso->valor_frete,
                'valor_adicional' => $dadosFreteExpresso->valor_adicional,
                'quantidade_maxima' => PedidoItemModel::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE,
                'previsao' => $previsoes,
            ];
        }

        return [
            'ultimo_frete_escolhido' => $ultimoFreteEscolhido,
            'frete_padrao' => $objetoFretePadrao,
            'frete_expresso' => $objetoFreteExpresso,
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
}

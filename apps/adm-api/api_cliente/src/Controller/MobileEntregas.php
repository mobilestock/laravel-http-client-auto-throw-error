<?php

namespace api_cliente\Controller;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\Municipio;
use MobileStock\model\Pedido\PedidoItem as PedidoItemModel;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\IBGEService;
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

        $idTipoFrete = TransportadoresRaio::buscaEntregadorDoSantosExpressQueAtendeColaborador(
            $endereco->id_cidade,
            $endereco->latitude,
            $endereco->longitude
        );

        $atendeFreteExpresso = Municipio::verificaSeCidadeAtendeFreteExpresso(
            $endereco->id_cidade,
            $idTipoFrete['id_colaborador']
        );

        $atendeFretePadrao = !empty($idTipoFrete);

        return [
            'eh_endereco_padrao' => $endereco->eh_endereco_padrao,
            'pode_ser_atendido_frete_padrao' => $atendeFretePadrao,
            'pode_ser_atendido_frete_expresso' => $atendeFreteExpresso,
        ];
    }
    public function buscaDetalhesPraCompra(PrevisaoService $previsao)
    {
        $nomeTamanho = 'Unico';
        $objetoFreteExpresso = null;
        $objetoFretePadrao = null;
        $produtoFrete = ProdutoService::buscaPrecoEResponsavelProduto(ProdutoModel::ID_PRODUTO_FRETE, $nomeTamanho);
        $produtoFreteExpresso = ProdutoService::buscaPrecoEResponsavelProduto(
            ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
            $nomeTamanho
        );

        $endereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        $dadosTipoFrete = TransportadoresRaio::buscaEntregadorDoSantosExpressQueAtendeColaborador(
            $endereco->id_cidade,
            $endereco->latitude,
            $endereco->longitude
        );

        // Setando coisas necessarias para o frete padrão
        $atendeFretePadrao = !empty($dadosTipoFrete['id_tipo_frete']);

        // Setando coisas necessarias para o frete expresso
        $atendeFreteExpresso = Municipio::verificaSeCidadeAtendeFreteExpresso(
            $endereco->id_cidade,
            $dadosTipoFrete['id_colaborador']
        );

        // Ambos
        $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
        $agenda->id_colaborador = $dadosTipoFrete['id_colaborador_ponto_coleta'];
        $prazosPontoColeta = $agenda->buscaPrazosPorPontoColeta();
        $destinatario = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        $dadosTipoFrete['horarios'] = $prazosPontoColeta['agenda'];

        /**
         * dias média entrega do fornecedor
         * agenda -> calculaProximoDiaEnviarPontoColeta()
         * dias pra chegar no destino (ponto coleta)
         * dias pra cidade
         * ['dias_chegar_destino', 'dias_cidade', 'margem' = 0]
         */

        /**
         * TODO: criar lógica de previsão para transportadora somando a data da agenda do entregador mais o tempo da cidade
         * Detalhe: https://github.com/mobilestock/backend/pull/244/files#diff-204a494c85514fe465b3fbd7e818a692452519102f859068874f8a7ecf88887e:~:text=%24agenda%20%3D,%7D
         */

        // if ($atendeFreteExpresso) {

        $previsoes = null;
        if (!empty($prazosPontoColeta['agenda'])) {
            $diasProcessoEntrega = Arr::only($dadosTipoFrete, [
                'dias_entregar_cliente',
                'dias_pedido_chegar',
                'dias_margem_erro',
            ]);

            $mediasEnvio = $previsao->calculoDiasSeparacaoProduto(
                ProdutoModel::ID_PRODUTO_FRETE,
                $nomeTamanho,
                $produtoFrete['id_responsavel']
            );
            $proximoEnvio = $previsao->calculaProximoDiaEnviarPontoColeta($prazosPontoColeta['agenda']);
            $previsoes = $previsao->calculaPorMediasEDias(
                $mediasEnvio,
                $diasProcessoEntrega,
                $prazosPontoColeta['agenda']
            );
            $previsoes = current(
                array_filter($previsoes, fn(array $item): bool => $item['responsavel'] === 'FULFILLMENT')
            );
            $dataEnvio = $proximoEnvio['data_envio']->format('d/m/Y');
            $horarioEnvio = current($proximoEnvio['horarios_disponiveis'])['horario'];
            $previsoes['data_limite'] = "$dataEnvio às $horarioEnvio";
        }

        if ($atendeFretePadrao) {
            $objetoFretePadrao = [
                'id_tipo_frete' => $dadosTipoFrete['id_tipo_frete'],
                'preco_produto_frete' => $produtoFrete['preco'],
                'preco_entregador' => $dadosTipoFrete['valor'],
            ];
        }

        if (1 === 1) {
            $transportadora = IBGEService::buscaIDTipoFretePadraoTransportadoraMeulook();
            $objetoFreteExpresso = [
                'id_tipo_frete' => TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA,
                'preco_produto_frete' => $produtoFreteExpresso['preco'],
                'valor' => $transportadora['valor_frete'],
                'valor_adicional' => $transportadora['valor_adicional'],
                'quantidade_expresso' => PedidoItemModel::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE,
            ];
        }

        $diasParaCalculoFreteExpresso['dias_chegar_destino'] = $prazosPontoColeta['dias_pedido_chegar'];
        $diasParaCalculoFreteExpresso['dias_cidade'] = Municipio::buscaCidade($endereco->id_cidade)->dias_entrega;
        $diasParaCalculoFreteExpresso['dias_margem_erro'] = 0;

        $previsoesExpresso = $previsao->calculaPorMediasEDias(
            $mediasEnvio,
            $diasParaCalculoFreteExpresso,
            $prazosPontoColeta['agenda']
        );
        $objetoFretePadrao['previsao'] = $previsoes;
        $objetoFreteExpresso['previsao'] = $previsoesExpresso;

        return [
            'destinatario' => $destinatario,
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

<?php

namespace api_cliente\Controller;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\Municipio;
use MobileStock\model\Pedido\PedidoItem as PedidoItemModel;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\IBGEService;
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

        $atendeFreteExpresso = Municipio::verificaSeCidadeAtendeFreteExpresso($endereco->id_cidade);
        $idTipoFrete = TransportadoresRaio::buscaEntregadorDoSantosExpressQueAtendeColaborador();

        $podeAtenderDestino = !empty($idTipoFrete);

        if (!$podeAtenderDestino && !$atendeFreteExpresso) {
            return false;
        }

        return [
            'eh_endereco_padrao' => $endereco->eh_endereco_padrao,
            'pode_ser_atendido_frete_padrao' => $podeAtenderDestino,
            'pode_ser_atendido_frete_expresso' => $atendeFreteExpresso,
        ];
    }
    public function buscaDetalhesPraCompra(PrevisaoService $previsao)
    {
        $nomeTamanho = 'Unico';
        $produtoFrete = ProdutoService::buscaPrecoEResponsavelProduto(ProdutoModel::ID_PRODUTO_FRETE, $nomeTamanho);
        $produtoFreteExpresso = ProdutoService::buscaPrecoEResponsavelProduto(
            ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
            $nomeTamanho
        );

        $endereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        $atendeFreteExpresso = Municipio::verificaSeCidadeAtendeFreteExpresso($endereco->id_cidade);

        $idTipoFrete = TransportadoresRaio::buscaEntregadorDoSantosExpressQueAtendeColaborador();

        /**
         * TODO: Verificar se a cidade é atendida pelo Frete Expresso
         * $objetoTransportadora = null;
         * $objetoTransportadora = [
         *     etc...
         * ];
         * TODO: Verificar se o santos express atende frete padrão
         * $objetoFretePadrao = null;
         * $objetoFretePadrao = [
         *     etc...
         * ];
         */
        $transportadora = IBGEService::buscaIDTipoFretePadraoTransportadoraMeulook();
        $destinatario = ColaboradorEndereco::buscaEnderecoPadraoColaborador();
        $tipoFrete = $previsao->buscaTransportadorPadrao();
        if (empty($tipoFrete)) {
            throw new NotFoundHttpException('Verifique se o colaborador possui um transportador padrão.');
        } elseif ($tipoFrete['id_colaborador_ponto_coleta'] !== TipoFrete::ID_COLABORADOR_SANTOS_EXPRESS) {
            throw new InvalidArgumentException('Entregador padrão não é o correto.');
        }

        /**
         * TODO: criar lógica de previsão para transportadora somando a data da agenda do entregador mais o tempo da cidade
         * Detalhe: https://github.com/mobilestock/backend/pull/244/files#diff-204a494c85514fe465b3fbd7e818a692452519102f859068874f8a7ecf88887e:~:text=%24agenda%20%3D,%7D
         */

        $previsoes = null;
        $dataLimite = null;
        if (!empty($tipoFrete['horarios'])) {
            $diasProcessoEntrega = Arr::only($tipoFrete, [
                'dias_entregar_cliente',
                'dias_pedido_chegar',
                'dias_margem_erro',
            ]);

            $mediasEnvio = $previsao->calculoDiasSeparacaoProduto(
                ProdutoModel::ID_PRODUTO_FRETE,
                $nomeTamanho,
                $produtoFrete['id_responsavel']
            );
            $proximoEnvio = $previsao->calculaProximoDiaEnviarPontoColeta($tipoFrete['horarios']);
            $previsoes = $previsao->calculaPorMediasEDias($mediasEnvio, $diasProcessoEntrega, $tipoFrete['horarios']);
            $previsoes = current(
                array_filter($previsoes, fn(array $item): bool => $item['responsavel'] === 'FULFILLMENT')
            );
            $dataEnvio = $proximoEnvio['data_envio']->format('d/m/Y');
            $horarioEnvio = current($proximoEnvio['horarios_disponiveis'])['horario'];
            $dataLimite = "$dataEnvio às $horarioEnvio";
        }

        return [
            'previsao' => $previsoes,
            'destinatario' => $destinatario,
            'data_limite' => $dataLimite,
            'preco_produto_frete' => $produtoFrete['preco'],
            'preco_entregador' => $tipoFrete['valor'],
            'frete_expresso' => [
                'preco_produto_frete_expresso' => $produtoFreteExpresso['preco'],
                'valor' => $transportadora['valor_frete'],
                'valor_adicional' => $transportadora['valor_adicional'],
                'quantidade_expresso' => PedidoItemModel::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE,
                'id_tipo_frete' => TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA,
            ],
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

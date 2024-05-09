<?php

namespace api_cliente\Controller;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
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

        $idTipoFrete = TransportadoresRaio::buscaEntregadorDoSantosExpressQueAtendeColaborador();
        $ehEntregadorPadrao = false;
        $podeAtenderDestino = false;

        if (!empty($idTipoFrete)) {
            $podeAtenderDestino = true;
            $colaborador = ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador);
            $ehEntregadorPadrao = $colaborador->id_tipo_entrega_padrao === $idTipoFrete;
        }

        return [
            'eh_endereco_padrao' => $endereco->eh_endereco_padrao,
            'eh_entregador_padrao' => $ehEntregadorPadrao,
            'pode_ser_atendido' => $podeAtenderDestino,
            'id_tipo_frete' => $idTipoFrete,
        ];
    }
    public function buscaDetalhesPraCompra(PrevisaoService $previsao)
    {
        $nomeTamanho = 'Unico';
        $produtoFrete = ProdutoService::buscaPrecoEResponsavelProduto(ProdutoModel::ID_PRODUTO_FRETE, $nomeTamanho);

        $destinatario = ColaboradorEndereco::buscaEnderecoPadraoColaborador();
        $tipoFrete = $previsao->buscaTransportadorPadrao();
        if (empty($tipoFrete)) {
            throw new NotFoundHttpException('Verifique se o colaborador possui um transportador padrão.');
        } elseif ($tipoFrete['id_colaborador'] !== TipoFrete::ID_COLABORADOR_SANTOS_EXPRESS) {
            throw new InvalidArgumentException('Entregador padrão não é o correto.');
        }

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

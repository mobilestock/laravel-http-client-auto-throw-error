<?php

namespace MobileStock\jobs;

use DomainException;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Frete\FreteEstadoService;
use MobileStock\service\Frete\FreteService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\Pagamento\PagamentoCreditoInterno;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\PedidoItem\PedidoItem;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY, ReceiveFromQueue::class];

    public function run(array $dados): array
    {
        DB::beginTransaction();

        $transacao = new TransacaoFinanceiraService();
        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->buscaTransacaoCR(DB::getPdo());

        $dadosTipoFrete = TipoFreteService::dadosPontoPorIdColaborador($dados['id_colaborador_tipo_frete']);
        $valorFrete = 0;
        $comissaoPontoColeta = 0;
        $produtos = [];
        $ultimoItemNaoExpedido = false;
        if ($dadosTipoFrete['id'] === 2) {
            // É transportadora
            $valorAdicional = 0;

            $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();
            $qtdItensNaoExpedidos = count($itensNaoExpedidos);
            $produtos = TransacaoFinanceiraItemProdutoService::buscaDadosProdutosTransacao(
                DB::getPdo(),
                $transacao->id ?: 0,
                Auth::user()->id_colaborador
            );
            if ($qtdItensNaoExpedidos > 0) {
                $ultimoItemNaoExpedido = true;
                if ($qtdItensNaoExpedidos + count($produtos) >= PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE) {
                    $valorAdicional = FreteEstadoService::buscaValorAdicional();
                }
            } else {
                $valoresFrete = FreteEstadoService::buscaValorFrete(
                    Auth::user()->id_colaborador,
                    $qtdItensNaoExpedidos + count($produtos) > PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE
                );
                $valorFrete = $valoresFrete['valor_frete'];
                $valorAdicional = $valoresFrete['valor_adicional'];
            }

            $valorFrete = FreteService::calculaValorFrete(
                $qtdItensNaoExpedidos,
                count($produtos),
                $valorFrete,
                $valorAdicional
            );
        }

        $colaborador = ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador);
        if ($colaborador->id_tipo_entrega_padrao !== $dadosTipoFrete['id']) {
            $colaborador->id_tipo_entrega_padrao = $dadosTipoFrete['id'];
            $colaborador->update();
        }

        if (!in_array($dadosTipoFrete['id'], explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE))) {
            $comissaoPontoColeta = ConfiguracaoService::buscaComissaoPontoColeta();
            $produtos = TransacaoFinanceiraItemProdutoService::buscaDadosProdutosTransacao(
                DB::getPdo(),
                $transacao->id ?: 0,
                Auth::user()->id_colaborador
            );
            if (empty($produtos)) {
                throw new Exception('Não é possível fechar um pedido sem produtos');
            }
            $valorFrete = round($dadosTipoFrete['valor_frete'] * count($produtos), 2);

            foreach ($produtos as $produto) {
                $valorFrete += round(
                    ($produto['valor_custo_produto'] * ($comissaoPontoColeta + $dadosTipoFrete['valor_ponto_coleta'])) /
                        100,
                    2
                );
            }

            $valorFrete = round($valorFrete, 2);
        }

        if (!$transacao->id && !$ultimoItemNaoExpedido && $valorFrete > 0) {
            $transacao->pagador = Auth::user()->id_colaborador;
            $transacao->metodos_pagamentos_disponiveis = 'CA,CR,PX';
            $transacao->criaTransacao(DB::getPdo());
        }

        $idsTransacoesProdutosPagos = PedidoItem::buscaIdsTransacoesDireitoItemCliente();
        if ($transacao->id) {
            $idsTransacoesProdutosPagos[] = $transacao->id;
        } elseif (empty($idsTransacoesProdutosPagos)) {
            throw new DomainException('Para fechar pedido, você precisa de pelo menos 1 transação');
        }

        /**
         * @issue https://github.com/mobilestock/backend/issues/109
         */
        $colaboradorEndereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        $enderecoCliente = Arr::only($colaboradorEndereco->toArray(), [
            'numero',
            'bairro',
            'complemento',
            'cidade',
            'latitude',
            'longitude',
            'id_cidade',
            'uf',
            'ponto_de_referencia',
            'nome_destinatario',
            'telefone_destinatario',
        ]);
        $enderecoCliente['id_raio'] = null;
        $enderecoCliente['endereco'] = $colaboradorEndereco->logradouro;

        if ($dadosTipoFrete['tipo_ponto'] === 'PM') {
            $entregador = TransportadoresRaio::buscaEntregadorMaisProximoDaCoordenada(
                $enderecoCliente['id_cidade'],
                $enderecoCliente['latitude'],
                $enderecoCliente['longitude']
            );
            $enderecoCliente['id_raio'] = $entregador->id;
        }
        foreach ($idsTransacoesProdutosPagos as $idTransacao) {
            $metadadosService = new TransacaoFinanceirasMetadadosService();
            $metadadosService->id_transacao = $idTransacao;

            $metadadosService->chave = 'ID_COLABORADOR_TIPO_FRETE';
            $metadadosService->valor = $dados['id_colaborador_tipo_frete'];
            $metadadosService->salvar(DB::getPdo());

            $metadadosService = new TransacaoFinanceirasMetadadosService();
            $metadadosService->id_transacao = $idTransacao;
            $metadadosService->chave = 'VALOR_FRETE';
            $metadadosService->valor = $valorFrete;
            $metadadosService->salvar(DB::getPdo());

            $metadadosService = new TransacaoFinanceirasMetadadosService();
            $metadadosService->id_transacao = $idTransacao;
            $metadadosService->chave = 'ID_PEDIDO';
            $metadadosService->valor = hash('sha512', implode('', $idsTransacoesProdutosPagos));
            $metadadosService->salvar(DB::getPdo());

            $metadadosService = new TransacaoFinanceirasMetadadosService();
            $metadadosService->id_transacao = $idTransacao;
            $metadadosService->chave = 'ENDERECO_CLIENTE_JSON';
            $metadadosService->valor = $enderecoCliente;
            $metadadosService->salvar(DB::getPdo());
        }

        if ($transacao->id) {
            if (
                !$ultimoItemNaoExpedido &&
                $valorFrete > 0 &&
                in_array($dadosTipoFrete['id'], explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE))
            ) {
                $adicionaItem = new TransacaoFinanceiraItemProdutoService();
                $adicionaItem->id_transacao = $transacao->id;
                $adicionaItem->comissao_fornecedor = $valorFrete;
                $adicionaItem->preco = $valorFrete;
                $adicionaItem->id_fornecedor = $dados['id_colaborador_tipo_frete'];
                $adicionaItem->tipo_item = 'FR';
                $adicionaItem->criaTransacaoItemProduto(DB::getPdo());
            } elseif (!$ultimoItemNaoExpedido && $valorFrete > 0) {
                foreach ($produtos as $produto) {
                    if ($dadosTipoFrete['valor_frete'] > 0) {
                        $comissaoEntrega = new TransacaoFinanceiraItemProdutoService();
                        $comissaoEntrega->id_transacao = $transacao->id;
                        $comissaoEntrega->comissao_fornecedor = $dadosTipoFrete['valor_frete'];
                        $comissaoEntrega->preco = $dadosTipoFrete['valor_frete'];
                        $comissaoEntrega->id_fornecedor = $dados['id_colaborador_tipo_frete'];
                        $comissaoEntrega->tipo_item = $dadosTipoFrete['tipo_ponto'] === 'PM' ? 'CM_ENTREGA' : 'CE';
                        $comissaoEntrega->momento_pagamento = 'CARENCIA_ENTREGA';
                        $comissaoEntrega->uuid_produto = $produto['uuid_produto'];
                        $comissaoEntrega->criaTransacaoItemProduto(DB::getPdo());
                    }

                    $comissaoColeta = new TransacaoFinanceiraItemProdutoService();
                    $comissaoColeta->id_transacao = $transacao->id;
                    $comissaoColeta->comissao_fornecedor = round(
                        ($produto['valor_custo_produto'] * $comissaoPontoColeta) / 100,
                        2
                    );
                    $comissaoColeta->preco = round(
                        ($produto['valor_custo_produto'] *
                            ($comissaoPontoColeta + $dadosTipoFrete['valor_ponto_coleta'])) /
                            100,
                        2
                    );
                    $comissaoColeta->id_fornecedor = $dadosTipoFrete['id_colaborador_ponto_coleta'];
                    $comissaoColeta->tipo_item = 'CM_PONTO_COLETA';
                    $comissaoColeta->momento_pagamento = 'CARENCIA_ENTREGA';
                    $comissaoColeta->uuid_produto = $produto['uuid_produto'];
                    $comissaoColeta->criaTransacaoItemProduto(DB::getPdo());
                }
            }

            $metodosOriginais = $transacao->metodos_pagamentos_disponiveis;
            if (empty($metodosOriginais)) {
                throw new Exception('Impossível pagar uma transação sem métodos de pagamento disponíveis');
            }
            $transacao->metodos_pagamentos_disponiveis = 'CR';
            $transacao->atualizaTransacao(DB::getPdo());
            $transacao->metodo_pagamento = 'CR';
            $transacao->numero_parcelas = 1;
            $transacao->calcularTransacao(DB::getPdo(), 1);

            $transacao->retornaTransacao(DB::getPdo());

            if ($transacao->valor_liquido == 0) {
                $pagador = new ProcessadorPagamentos(DB::getPdo(), $transacao, [PagamentoCreditoInterno::class]);
                $pagador->executa();
                $logistica = true;
            } else {
                $transacao->metodos_pagamentos_disponiveis = $metodosOriginais;
                $transacao->atualizaTransacao(DB::getPdo());
                $transacao->metodo_pagamento = 'CA';
                $transacao->numero_parcelas = 1;
                $transacao->calcularTransacao(DB::getPdo(), 1);
            }
        } else {
            $logistica = false;
            $colaboradoresService = new ColaboradoresService();
            $colaboradoresService->id = Auth::user()->id_colaborador;
            $colaboradoresService->buscaSituacaoFraude(DB::getPdo(), ['CARTAO']);
            $ehFraude = in_array($colaboradoresService->situacao_fraude, ['PE', 'FR']);

            if (!$ehFraude) {
                $logisticaItem = new LogisticaItemModel();
                $logisticaItem->id_cliente = Auth::user()->id_colaborador;
                $logisticaItem->id_transacao = $idsTransacoesProdutosPagos[0];
                $logisticaItem->id_colaborador_tipo_frete = $dados['id_colaborador_tipo_frete'];
                $logisticaItem->liberarLogistica(Origem::MS);
                $logistica = true;
            } else {
                foreach ($idsTransacoesProdutosPagos as $idTransacao) {
                    $listaProdutos = TransacaoFinanceiraItemProdutoService::buscaProdutosTransacao($idTransacao, [
                        'PR',
                        'RF',
                    ]);
                    $pedidoItem = new PedidoItem();
                    $pedidoItem->id_transacao = $idTransacao;
                    $pedidoItem->situacao = in_array($colaboradoresService, ['LG', 'LT']) ? 'DI' : 'FR';
                    if ($listaProdutos > 0) {
                        $pedidoItem->atualizaIdTransacaoPI($listaProdutos);
                    }
                }
            }
        }
        DB::commit();

        return [
            'id_transacao' => $transacao->id,
            'pedido_pago' => isset($logistica),
        ];
    }
};

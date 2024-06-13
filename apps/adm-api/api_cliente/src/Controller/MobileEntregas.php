<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Retentador;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Municipio;
use MobileStock\model\Pedido\PedidoItem as PedidoItemModel;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\Frete\FreteService;
use MobileStock\service\PedidoItem\TransacaoPedidoItem;
use MobileStock\service\PrevisaoService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraLogCriacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use Throwable;

class MobileEntregas
{
    public function buscaDetalhesFreteDoEndereco(int $idEndereco)
    {
        $entregador = TransportadoresRaio::buscaEntregadoresMobileEntregas($idEndereco);

        $atendeFreteExpresso =
            $entregador['id_colaborador_ponto_coleta_frete_expresso'] !== TipoFrete::ID_COLABORADOR_CENTRAL;

        return [
            'eh_endereco_padrao' => $entregador['eh_endereco_padrao'],
            'valor_coleta' => $entregador['valor_coleta'],
            'pode_ser_atendido_frete_padrao' => !empty($entregador['id_tipo_frete']),
            'pode_ser_atendido_frete_expresso' => $atendeFreteExpresso,
        ];
    }

    public function buscaDetalhesPraCompra()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'id_endereco_coleta' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
        ]);

        $nomeTamanho = 'Unico';

        $montarPrevisao = function (array $produtos): array {
            $produto = current($produtos);

            $previsoes = current(
                array_filter($produto['previsoes'], fn(array $item): bool => $item['responsavel'] === 'FULFILLMENT')
            );

            return $previsoes;
        };

        $ultimoFreteEscolhido =
            ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador)->id_tipo_entrega_padrao ===
            TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA
                ? 'EXPRESSO'
                : 'PADRAO';

        $dadosTipoFrete = TransportadoresRaio::buscaEntregadoresMobileEntregas();

        $coletador = null;
        if (!empty($dados['id_endereco_coleta'])) {
            $coletador = TransportadoresRaio::buscaEntregadoresMobileEntregas($dados['id_endereco_coleta']);
            if (empty($coletador['id_tipo_frete'])) {
                $coletador = null;
            }
        }

        if (!empty($dadosTipoFrete['id_tipo_frete'])) {
            $produtoFrete = ProdutoService::buscaPrecoEResponsavelProduto(ProdutoModel::ID_PRODUTO_FRETE, $nomeTamanho);

            $previsao = app(PrevisaoService::class);
            $resultado = $previsao->processoCalcularPrevisao(
                $dadosTipoFrete['id_colaborador_ponto_coleta_frete_padrao'],
                [
                    'dias_entregar_cliente' => $dadosTipoFrete['dias_entregar_cliente_frete_padrao'],
                    'dias_coletar_produto' => $coletador['dias_entregar_cliente_frete_padrao'] ?? 0,
                    'dias_margem_erro' => $dadosTipoFrete['dias_margem_erro'] + ($coletador['dias_margem_erro'] ?? 0),
                ],
                [
                    [
                        'id' => ProdutoModel::ID_PRODUTO_FRETE,
                        'nome_tamanho' => $nomeTamanho,
                        'id_responsavel_estoque' => $produtoFrete['id_responsavel'],
                    ],
                ]
            );

            $previsoes = $montarPrevisao($resultado);

            $objetoFretePadrao = [
                'id_tipo_frete' => $dadosTipoFrete['id_tipo_frete'],
                'preco_produto_frete' => $produtoFrete['preco'],
                'preco_entregador' => $dadosTipoFrete['valor_entrega'],
                'previsao' => $previsoes,
            ];
        }

        if ($dadosTipoFrete['id_colaborador_ponto_coleta_frete_expresso'] !== TipoFrete::ID_COLABORADOR_CENTRAL) {
            $produtoFreteExpresso = ProdutoService::buscaPrecoEResponsavelProduto(
                ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                $nomeTamanho
            );

            $previsao = app(PrevisaoService::class);
            $resultado = $previsao->processoCalcularPrevisao(
                $dadosTipoFrete['id_colaborador_ponto_coleta_frete_expresso'],
                [
                    'dias_entregar_cliente' => $dadosTipoFrete['dias_entregar_cliente_frete_expresso'],
                    'dias_coletar_produto' => $coletador['dias_entregar_cliente_frete_padrao'] ?? 0,

                    'dias_margem_erro' => $coletador['dias_margem_erro'] ?? 0,
                ],
                [
                    [
                        'id' => ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                        'nome_tamanho' => $nomeTamanho,
                        'id_responsavel_estoque' => $produtoFreteExpresso['id_responsavel'],
                    ],
                ]
            );

            $previsoes = $montarPrevisao($resultado);

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
            'valor_coleta' => $coletador['valor_coleta'] ?? null,
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
            'valor_frete' => [Validador::NUMERO],
            'valor_adicional' => [Validador::NUMERO],
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

    public function criarTransacaoMobileEntregas()
    {
        $idTransacao = Retentador::retentar(5, function () {
            try {
                DB::beginTransaction();

                $dadosJson = Request::all();

                Validador::validar($dadosJson, [
                    'produtos' => [Validador::ARRAY, Validador::OBRIGATORIO],
                    'detalhes' => [Validador::ARRAY, Validador::OBRIGATORIO],
                    'id_colaborador_coleta' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
                ]);

                ColaboradoresService::verificaDadosClienteCriarTransacao();
                $usuario = Auth::user();

                PedidoItem::verificaProdutosEstaoCarrinho($dadosJson['produtos']);
                $estoquesDisponiveis = TransacaoPedidoItem::retornaEstoqueDisponivel($dadosJson['produtos']);

                TransacaoPedidoItem::reservaEAtualizaPrecosProdutosCarrinho($estoquesDisponiveis);

                $ehFraudatario = ColaboradoresService::colaboradorEhFraudatario();
                $transacaoFinanceiraService = new TransacaoFinanceiraService();
                $transacaoFinanceiraService->id_usuario = $usuario->id;
                $transacaoFinanceiraService->pagador = $usuario->id_colaborador;
                $transacaoFinanceiraService->origem_transacao = 'ML';
                $transacaoFinanceiraService->valor_itens = 0;
                $transacaoFinanceiraService->metodos_pagamentos_disponiveis = $ehFraudatario ? 'CR,PX' : 'CA,CR,PX';
                $transacaoFinanceiraService->removeTransacoesEmAberto(DB::getPdo());
                $transacaoFinanceiraService->criaTransacao(DB::getPdo());

                $freteColaborador = TransacaoPedidoItem::buscaInformacoesFreteColaborador();
                $produtosReservados = TransacaoPedidoItem::buscaProdutosReservadosMeuLook();

                $enderecoColeta = null;
                $coletador = null;
                if (!empty($dadosJson['id_colaborador_coleta'])) {
                    $enderecoColeta = ColaboradorEndereco::buscaEnderecoPadraoColaborador(
                        $dadosJson['id_colaborador_coleta']
                    );
                    $coletador = TransportadoresRaio::buscaEntregadoresMobileEntregas($enderecoColeta['id']);
                    $enderecoColeta['id_raio'] = $coletador['id_raio'];

                    $freteColaborador['valor_coleta'] = $coletador['valor_coleta'];
                    $freteColaborador['id_colaborador_coleta'] = $coletador['id_colaborador'];
                }

                $transacaoPedidoItem = new TransacaoPedidoItem();
                $transacaoPedidoItem->id_transacao = $transacaoFinanceiraService->id;

                $transacoesProdutosItem = $transacaoPedidoItem->calcularComissoes(
                    $freteColaborador,
                    $produtosReservados
                );
                TransacaoFinanceiraItemProdutoService::insereVarios(DB::getPdo(), $transacoesProdutosItem);

                $colaboradorEndereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();
                TransacaoFinanceiraLogCriacaoService::criarLogTransacao(
                    DB::getPdo(),
                    $transacaoFinanceiraService->id,
                    $usuario->id_colaborador,
                    $dadosJson['detalhes']['ip'],
                    $dadosJson['detalhes']['user_agent'],
                    $colaboradorEndereco->latitude,
                    $colaboradorEndereco->longitude
                );

                $transacaoFinanceiraService->metodo_pagamento = 'CA';
                $transacaoFinanceiraService->numero_parcelas = 1;
                $transacaoFinanceiraService->calcularTransacao(DB::getPdo(), 1);

                $enderecoCliente = $colaboradorEndereco->toArray();
                $enderecoCliente['id_raio'] = null;

                $dadosEntregador = TransacaoFinanceirasMetadadosService::buscaDadosEntregadorTransacao(
                    $transacaoFinanceiraService->id
                );
                $idColaboradorTipoFrete = $dadosEntregador['tipo_entrega_padrao']['id_colaborador'];
                if ($dadosEntregador['tipo_entrega_padrao']['tipo_ponto'] === 'PM') {
                    $entregador = TransportadoresRaio::buscaEntregadorMaisProximoDaCoordenada(
                        $enderecoCliente['id_cidade'],
                        $enderecoCliente['latitude'],
                        $enderecoCliente['longitude']
                    );

                    $enderecoCliente['id_raio'] = $entregador->id;
                }

                $produtos = TransacaoFinanceirasMetadadosService::buscaProdutosTransacao(
                    $transacaoFinanceiraService->id
                );
                $chavesMetadadosExistentes = TransacaoFinanceirasMetadadosService::buscaChavesTransacao(
                    $transacaoFinanceiraService->id
                );

                $metadados = new TransacaoFinanceirasMetadadosService();
                $metadados->id_transacao = $transacaoFinanceiraService->id;
                $metadados->chave = 'ID_COLABORADOR_TIPO_FRETE';
                $metadados->valor = $idColaboradorTipoFrete;
                $metadadoExistente = $chavesMetadadosExistentes['ID_COLABORADOR_TIPO_FRETE'] ?? false;
                if ($metadadoExistente) {
                    if ($metadadoExistente['valor'] !== $metadados->valor) {
                        $metadados->id = $metadadoExistente['id'];
                        $metadados->alterar(DB::getPdo());
                    }
                } else {
                    $metadados->salvar(DB::getPdo());
                }

                $metadados = new TransacaoFinanceirasMetadadosService();
                $metadados->id_transacao = $transacaoFinanceiraService->id;
                $metadados->chave = 'VALOR_FRETE';
                $metadados->valor = $dadosEntregador['comissao_fornecedor'];
                $metadadoExistente = $chavesMetadadosExistentes['VALOR_FRETE'] ?? false;
                if ($metadadoExistente) {
                    if ($metadadoExistente['valor'] !== $metadados->valor) {
                        $metadados->id = $metadadoExistente['id'];
                        $metadados->alterar(DB::getPdo());
                    }
                } else {
                    $metadados->salvar(DB::getPdo());
                }

                $metadados = new TransacaoFinanceirasMetadadosService();
                $metadados->id_transacao = $transacaoFinanceiraService->id;
                $metadados->chave = 'ENDERECO_CLIENTE_JSON';
                $metadados->valor = $enderecoCliente;
                $metadadoExistente = $chavesMetadadosExistentes['ENDERECO_CLIENTE_JSON'] ?? false;
                if ($metadadoExistente) {
                    if ($metadadoExistente['valor'] !== $metadados->valor) {
                        $metadados->id = $metadadoExistente['id'];
                        $metadados->alterar(DB::getPdo());
                    }
                } else {
                    $metadados->salvar(DB::getPdo());
                }

                if (!empty($dadosJson['id_colaborador_coleta'])) {
                    $metadados = new TransacaoFinanceirasMetadadosService();
                    $metadados->id_transacao = $transacaoFinanceiraService->id;
                    $metadados->chave = 'ENDERECO_COLETA_JSON';
                    $metadados->valor = $enderecoColeta;
                    $metadadoExistente = $chavesMetadadosExistentes['ENDERECO_COLETA_JSON'] ?? false;
                    if ($metadadoExistente) {
                        if ($metadadoExistente['valor'] !== $metadados->valor) {
                            $metadados->id = $metadadoExistente['id'];
                            $metadados->alterar(DB::getPdo());
                        }
                    } else {
                        $metadados->salvar(DB::getPdo());
                    }
                }

                $idColaboradorTipoFreteEntregaCliente = explode(
                    ',',
                    TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE
                );

                if ($idColaboradorTipoFrete === TipoFrete::ID_COLABORADOR_TRANSPORTADORA) {
                    $previsao = app(PrevisaoService::class);
                    $dadosFreteExpresso = Municipio::buscaCidade($colaboradorEndereco->id_cidade);

                    $produtos = $previsao->processoCalcularPrevisao(
                        $dadosFreteExpresso->id_colaborador_ponto_coleta,
                        [
                            'dias_entregar_cliente' => $dadosFreteExpresso->dias_entregar_cliente,
                            'dias_coletar_produto' => $coletador['dias_entregar_cliente_frete_padrao'] ?? 0,
                            'dias_margem_erro' => $coletador['dias_margem_erro'] ?? 0,
                        ],
                        $produtos
                    );
                } elseif (!in_array($idColaboradorTipoFrete, $idColaboradorTipoFreteEntregaCliente)) {
                    $previsao = app(PrevisaoService::class);
                    $transportador = $previsao->buscaTransportadorPadrao($usuario->id_colaborador);

                    $produtos = $previsao->processoCalcularPrevisao(
                        $transportador['id_colaborador_ponto_coleta'],
                        [
                            'dias_entregar_cliente' => $transportador['dias_entregar_cliente'],
                            'dias_coletar_produto' => $coletador['dias_entregar_cliente_frete_padrao'] ?? 0,
                            'dias_margem_erro' =>
                                $transportador['dias_margem_erro'] + ($coletador['dias_margem_erro'] ?? 0),
                        ],
                        $produtos
                    );
                }

                $metadados = new TransacaoFinanceirasMetadadosService();
                $metadados->id_transacao = $transacaoFinanceiraService->id;
                $metadados->chave = 'PRODUTOS_JSON';
                $metadados->valor = $produtos;
                $metadadoExistente = $chavesMetadadosExistentes['PRODUTOS_JSON'] ?? false;
                if ($metadadoExistente) {
                    if ($metadadoExistente['valor'] !== $metadados->valor) {
                        $metadados->id = $metadadoExistente['id'];
                        $metadados->alterar(DB::getPdo());
                    }
                } else {
                    $metadados->salvar(DB::getPdo());
                }

                DB::commit();

                return $transacaoFinanceiraService->id;
            } catch (Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });

        return $idTransacao;
    }

    public function buscaColaboradoresColetasAnteriores()
    {
        $colaboradores = TransacaoFinanceirasMetadadosService::buscaColaboradoresColetasAnteriores();
        return $colaboradores;
    }

    public function buscaRelatorioColetas()
    {
        $coletas = TransacaoFinanceirasMetadadosService::buscaRelatorioColetas();
        return $coletas;
    }
}

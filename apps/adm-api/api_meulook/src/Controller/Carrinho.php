<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Retentador;
use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\PedidoItem as PedidoItemModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\EntregaService\EntregasDevolucoesServices;
use MobileStock\service\IBGEService;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\PedidoItem\TransacaoPedidoItem;
use MobileStock\service\PrevisaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraLogCriacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use PDO;
use Throwable;

class Carrinho extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function adicionaProdutoCarrinho(
        PDO $conexao,
        Request $request,
        PedidoItemMeuLookService $carrinho,
        Authenticatable $usuario
    ) {
        try {
            $conexao->beginTransaction();
            $dadosJson = $request->all();

            Validador::validar($dadosJson, [
                'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
            ]);

            $carrinho->id_cliente = $usuario->id_colaborador;
            $carrinho->produtos = $dadosJson['produtos'];
            $carrinho->insereProdutos($conexao);

            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollback();
            throw $th;
        }
    }

    public function buscaProdutosCarrinho(TransacaoFinanceiraService $transacao)
    {
        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->removeTransacoesEmAberto(DB::getPdo());
        $produtos = PedidoItemMeuLookService::consultaProdutosCarrinho(true);

        return $produtos;
    }

    public function removeProdutoCarrinho(PDO $conexao, PedidoItemMeuLookService $carrinho, string $uuidProduto)
    {
        try {
            $conexao->beginTransaction();
            $carrinho->uuid = $uuidProduto;
            $itemNoCarrinho = $carrinho->itemExiste($conexao);
            if ($itemNoCarrinho) {
                $carrinho->removeProdutos($conexao);
            }

            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollback();
            throw $th;
        }
    }

    public function buscaEntregaDisponivel()
    {
        $idColaborador = Auth::user()->id_colaborador;

        $pontoPadrao = ColaboradoresRepository::buscaTipoFretePadrao();
        $entregador = PedidoItemMeuLookService::buscaTipoFreteMaisBaratoCarrinho('PM');
        $entregadorExistente = $entregador;
        if (!empty($entregador['id']) && $entregador['distancia'] > $entregador['raio']) {
            $entregador = null;
        }

        $colaborador = ColaboradoresService::buscaCadastroColaborador($idColaborador);
        $faltandoDadosEntregador = false;
        try {
            Validador::validar($colaborador, [
                'endereco' => [Validador::OBRIGATORIO],
                'numero' => [Validador::OBRIGATORIO],
            ]);
        } catch (ValidacaoException $ignorado) {
            $faltandoDadosEntregador = true;
        }

        $pontoRetirada = PedidoItemMeuLookService::buscaTipoFreteMaisBaratoCarrinho(
            'PP',
            $pontoPadrao['id_tipo_entrega_padrao'] ?? null
        );

        if ($entregador || $pontoRetirada) {
            $produtos = PedidoItemMeuLookService::consultaProdutosCarrinho(false);
            if (empty($produtos['carrinho'])) {
                return;
            }

            $valoresCarrinho = array_column($produtos['carrinho'], 'valor_custo_produto');
            if ($entregador) {
                $valoresProdutos = array_map(
                    fn(float $valor): float => round($valor * ($entregador['porcentagem_frete'] / 100), 2),
                    $valoresCarrinho
                );
                $valorEntrega = round($entregador['valor'] * count($produtos['carrinho']), 2);
                $valorEntrega += round(array_sum($valoresProdutos), 2);
                $valorEntrega = round($valorEntrega, 2);
            }
            if ($pontoRetirada) {
                $valoresProdutos = array_map(
                    fn(float $valor): float => round($valor * ($pontoRetirada['porcentagem_frete'] / 100), 2),
                    $valoresCarrinho
                );
                $valorBuscar = round($pontoRetirada['valor'] * count($produtos['carrinho']), 2);
                $valorBuscar += round(array_sum($valoresProdutos), 2);
                $valorBuscar = round($valorBuscar, 2);
            }
        }

        $transportadora = IBGEService::buscaIDTipoFretePadraoTransportadoraMeulook();
        $faltandoDadosTransportadora = $faltandoDadosEntregador;
        try {
            Validador::validar($colaborador, [
                'cep' => [Validador::OBRIGATORIO],
                'cpf' => [Validador::SE(empty($colaborador['cnpj']), [Validador::OBRIGATORIO, Validador::CPF])],
                'cnpj' => [Validador::SE(empty($colaborador['cpf']), [Validador::OBRIGATORIO, Validador::CNPJ])],
            ]);
        } catch (ValidacaoException $ignorado) {
            $faltandoDadosTransportadora = true;
        }

        $retorno = [
            'tipo_frete_padrao' => $pontoPadrao,
            'id_entregador' => $entregador['id'] ?? null,
            'id_transportadora' => $transportadora['id_tipo_frete_transportadora_meulook'] ?? null,
            'entregador_local' => $entregadorExistente,
            'faltando_dados_entregador' => $faltandoDadosEntregador,
            'faltando_dados_transportadora' => $faltandoDadosTransportadora,
            'colaborador_endereco' => $colaborador['endereco'],
            'colaborador_numero' => $colaborador['numero'],
            'colaborador_complemento' => $colaborador['complemento'],
            'colaborador_ponto_de_referencia' => $colaborador['ponto_de_referencia'],
            'colaborador_cep' => $colaborador['cep'],
            'colaborador_bairro' => $colaborador['bairro'],
            'colaborador_cidade' => $colaborador['cidade'],
            'colaborador_uf' => $colaborador['uf'],
        ];

        return $retorno;
    }

    /**
     * @issue: https://github.com/mobilestock/backend/issues/113
     */
    public function criarTransacao()
    {
        $idTransacao = Retentador::retentar(5, function () {
            try {
                DB::beginTransaction();
                $dadosJson = \Illuminate\Support\Facades\Request::all();
                Validador::validar($dadosJson, [
                    'produtos' => [Validador::ARRAY, Validador::OBRIGATORIO],
                    'detalhes' => [Validador::ARRAY, Validador::OBRIGATORIO],
                ]);

                ColaboradoresService::verificaDadosClienteCriarTransacao();
                $usuario = Auth::user();

                PedidoItemModel::verificaProdutosEstaoCarrinho($dadosJson['produtos']);
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

                $transacaoPedidoItem = new TransacaoPedidoItem();
                $transacaoPedidoItem->id_transacao = $transacaoFinanceiraService->id;
                $transacoesProdutosItem = $transacaoPedidoItem->calculaComissoesMeuLook(
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
                $transacaoFinanceiraService->numero_parcelas = 5;
                $transacaoFinanceiraService->calcularTransacao(DB::getPdo(), 1);
                $transacaoFinanceiraService->retornaTransacao(DB::getPdo());

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

                $idColaboradorTipoFreteEntregaCliente = explode(
                    ',',
                    TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE
                );
                /**
                 * TODO
                 * IF
                 * Origem === MOBILE_ENTREGAS && $idColaboradorTipoFrete === TRANSPORTADORA
                 * ELSEIF
                 * !in_array($idColaboradorTipoFrete, $idColaboradorTipoFreteEntregaCliente)
                 */
                if (!in_array($idColaboradorTipoFrete, $idColaboradorTipoFreteEntregaCliente)) {
                    $previsao = app(PrevisaoService::class);
                    $transportador = $previsao->buscaTransportadorPadrao($usuario->id_colaborador);

                    if (!empty($transportador['horarios'])) {
                        $produtos = array_map(function (array $produto) use ($transportador, $previsao): array {
                            $diasProcessoEntrega = Arr::only($transportador, [
                                'dias_entregar_cliente',
                                'dias_pedido_chegar',
                                'dias_margem_erro',
                            ]);
                            $mediasEnvio = $previsao->calculoDiasSeparacaoProduto(
                                $produto['id'],
                                $produto['nome_tamanho'],
                                $produto['id_responsavel_estoque']
                            );
                            $previsoes = $previsao->calculaPorMediasEDias(
                                $mediasEnvio,
                                $diasProcessoEntrega,
                                $transportador['horarios']
                            );
                            if (!empty($previsoes)) {
                                $produto['previsao'] = reset($previsoes);
                            }

                            return $produto;
                        }, $produtos);
                    }
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

    public function comprarProntaEntrega()
    {
        $dadosJson = \Illuminate\Support\Facades\Request::all();

        Validador::validar($dadosJson, [
            'id_colaborador_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        foreach ($dadosJson['produtos'] as $produto) {
            Validador::validar($produto, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            EntregasDevolucoesServices::enviarMensagemInteresse(
                $dadosJson['id_colaborador_ponto'],
                $produto['id_produto'],
                $produto['nome_tamanho']
            );
        }
    }

    public function gerirProntaEntrega()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_colaborador_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'movimentacao' => [Validador::OBRIGATORIO, Validador::ENUM('VENDIDO', 'DEVOLVIDO')],
                'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            foreach ($dadosJson['produtos'] as $produto) {
                Validador::validar($produto, [
                    'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
                ]);

                $devolucaoService = new EntregasDevolucoesServices();
                $devolucaoService->gerenciarProntaEntrega(
                    $this->conexao,
                    $dadosJson['id_colaborador_ponto'],
                    $produto['id_produto'],
                    $produto['nome_tamanho'],
                    $dadosJson['movimentacao']
                );
            }
            $this->conexao->commit();
        } catch (Throwable $ex) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

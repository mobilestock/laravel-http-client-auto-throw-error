<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Retentador;
use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\TipoFrete;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\EntregaService\EntregasDevolucoesServices;
use MobileStock\service\IBGEService;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\PedidoItem\TransacaoPedidoItem;
use MobileStock\service\PrevisaoService;
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

    public function adicionaProdutoCarrinho(PedidoItemMeuLookService $carrinho)
    {
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'produtos' => [Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
        ]);

        $listaUuids = $carrinho->insereProdutos($dadosJson['produtos']);

        DB::commit();

        return $listaUuids;
    }

    public function buscaProdutosCarrinho()
    {
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
                $valorEntrega = round($entregador['preco_entrega'] * count($produtos['carrinho']), 2);
                $valorEntrega += round(array_sum($valoresProdutos), 2);
                $valorEntrega = round($valorEntrega, 2);
            }
            if ($pontoRetirada) {
                $valoresProdutos = array_map(
                    fn(float $valor): float => round($valor * ($pontoRetirada['porcentagem_frete'] / 100), 2),
                    $valoresCarrinho
                );
                $valorBuscar = round($pontoRetirada['preco_entrega'] * count($produtos['carrinho']), 2);
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
                $dadosJson = FacadesRequest::all();
                Validador::validar($dadosJson, [
                    'produtos' => [Validador::ARRAY, Validador::OBRIGATORIO],
                    'detalhes' => [Validador::ARRAY, Validador::OBRIGATORIO],
                    'id_tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]);

                // @issue https://github.com/mobilestock/backend/issues/373
                $colaborador = new ColaboradorModel();
                $colaborador->exists = true;
                $colaborador->id = Auth::user()->id_colaborador;
                $colaborador->id_tipo_entrega_padrao = $dadosJson['id_tipo_frete'];
                $colaborador->save();

                $freteColaborador = TransacaoPedidoItem::buscaInformacoesFreteColaborador();

                $dadosTransacao = TransacaoFinanceiraService::criarTransacaoOrigemML(
                    $dadosJson['produtos'],
                    $dadosJson['detalhes'],
                    $freteColaborador
                );

                $dadosEntregador = TransacaoFinanceirasMetadadosService::buscaDadosEntregadorTransacao(
                    $dadosTransacao['id_transacao']
                );

                $produtos = $dadosTransacao['produtos'];

                if (
                    !in_array(
                        $dadosEntregador['tipo_entrega_padrao']['id_colaborador'],
                        explode(',', TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE)
                    )
                ) {
                    $previsao = app(PrevisaoService::class);
                    $transportador = $previsao->buscaTransportadorPadrao();

                    $produtos = $previsao->processoCalcularPrevisaoResponsavelFiltrado(
                        $transportador['id_colaborador_ponto_coleta'],
                        Arr::only($transportador, ['dias_margem_erro', 'dias_entregar_cliente']),
                        $dadosTransacao['produtos']
                    );
                }

                $metadados = new TransacaoFinanceirasMetadadosService();
                $metadados->id_transacao = $dadosTransacao['id_transacao'];
                $metadados->chave = 'PRODUTOS_JSON';
                $metadados->valor = $produtos;
                $metadados->salvar(DB::getPdo());

                DB::commit();

                return $dadosTransacao['id_transacao'];
            } catch (Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        });

        return $idTransacao;
    }

    public function comprarProntaEntrega()
    {
        $dadosJson = FacadesRequest::all();

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

<?php

namespace api_pagamento\Controller;

use api_pagamento\Models\Request_m;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\Pagamento\PagamentoTransacaoNaoExisteException;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Fila\FilaService;
use MobileStock\service\PedidoItem\TransacaoPedidoItem;
use MobileStock\service\TaxasConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraLogCriacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;

class Pagamento extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function infoTransacao(int $idTransacao)
    {
        $consulta = new TransacaoFinanceiraService();
        $consulta->id = $idTransacao;

        $transacao = $consulta->retornaTransacao(DB::getPdo());

        return $transacao;
    }

    public function criaTransacaoProduto(Request $request, PDO $conexao)
    {
        try {
            $conexao->beginTransaction();

            $listaItens = $request->all();

            $transacoes = new TransacaoFinanceiraService();
            $transacoes->pagador = $this->idCliente;
            $transacoes->removeTransacoesEmAberto($conexao);

            $pedido = new TransacaoPedidoItem();

            $pedido->origem_transacao = 'MP';
            //$pedido->id_cliente = $listaItens['id_cliente'];

            $pedido->array_uuid = $listaItens;
            $pedido->situacao = 2;
            $transacoes->id = $pedido->criaTransacaoProduto($conexao, $this->idUsuario, $this->idCliente);

            $transacoes->metodo_pagamento = 'CA';
            $transacoes->numero_parcelas = 1;
            $transacoes->calcularTransacao($conexao, 1);
            TransacaoFinanceiraLogCriacaoService::criarLogTransacao(
                $conexao,
                $transacoes->id,
                $this->idCliente,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                null,
                null
            );
            $conexao->commit();

            return $transacoes->id;
        } catch (\Throwable $e) {
            $conexao->rollBack();
            throw $e;
        }
    }

    public function criaTransacaoCredito(Request $request, PDO $conexao)
    {
        try {
            $conexao->beginTransaction();

            $json = $request->all();

            Validador::validar($json, [
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $transacao = new TransacaoFinanceiraService();
            $transacao->origem_transacao = 'MC';
            $transacao->pagador = $json['id_cliente'];
            $transacao->removeTransacoesEmAberto($conexao);
            $transacao->id_usuario = $this->idUsuario;
            $transacao->valor_itens = $json['valor'];
            $transacao->metodos_pagamentos_disponiveis =
                ColaboradoresRepository::qtdPedidosEntregues($conexao, $this->idCliente) > 2 ? 'CA,PX' : 'PX';
            $transacao->criaTransacao($conexao);

            $adicionItem = new TransacaoFinanceiraItemProdutoService();
            $adicionItem->id_transacao = $transacao->id;
            $adicionItem->comissao_fornecedor = $transacao->valor_itens;
            $adicionItem->preco = $transacao->valor_itens;
            $adicionItem->id_fornecedor = $transacao->pagador;
            $adicionItem->tipo_item = 'AC';
            $adicionItem->criaTransacaoItemProduto($conexao);
            $transacao->metodo_pagamento = 'CA';
            $transacao->numero_parcelas = 5;
            $transacao->calcularTransacao($conexao, 0);

            $conexao->commit();
            $retornaTransacao = $transacao->retornaTransacao($conexao);

            return $retornaTransacao;
        } catch (\Throwable $e) {
            $conexao->rollBack();
            throw $e;
        }
    }

    public function criaTransacaoPagamentoSaldo(Request $request, FilaService $fila)
    {
        $dadosJson = $request->all();
        Validador::validar($dadosJson, [
            'grade' => [Validador::OBRIGATORIO, Validador::ARRAY],
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        Validador::validar($dadosJson['grade'][0], [
            'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            'qtd' => [Validador::NUMERO],
        ]);

        $dadosJson['id_cliente'] = $this->idCliente;
        $dadosJson['id_usuario'] = $this->idUsuario;

        $fila->conteudoArray = $dadosJson;
        $fila->url_fila = $_ENV['SQS_ENDPOINTS']['MS_PAGAMENTO_RAPIDO'];
        $fila->envia();

        return $fila->id;
    }

    public function criaTransacaoPagamentoPremio()
    {
        /* try {
            $this->conexao->beginTransaction();
            require_once __DIR__ . '/../../../classes/PontosDesempenho.php';


            Validador::validar(['json' => $this->json], [
                'json' => [Validador::JSON]
            ]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'grade' => [Validador::OBRIGATORIO],
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO]
            ]);

            Validador::validar($dadosJson['grade'][0], [
                'tamanho' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'qtd' => [Validador::NUMERO]
            ]);

            $id_produto = $dadosJson['id_produto'];
            $grade = $dadosJson['grade'];

            $transacao_retorno = new TransacaoFinanceiraService;
            $transacao_retorn->origem_transacao = 'MP';
            $transacao_retorno->id_usuario = $this->idUsuario;
            $transacao_retorno->pagador = $dadosJson['id_cliente'];
            $transacao_retorno->removeTransacoesEmAberto($this->conexao);
            $transacao_retorno->criaTransacao($this->conexao);
            $transacao_retorno->insereProdutoPagoPainel($this->conexao, $id_produto,$this->idUsuario, $grade);
            $valor = ProdutosRepository::retornValorProduto($id_produto, $transacao_retorno->pagador);
            /*\PontosDesempenho::descontaPontosPainel($grade, $id_produto, $transacao_retorno->pagador, $valor['premio_pontos'], $this->conexao);*/
        /*$this->conexao->commit();
            $this->retorno['data'] = $transacao_retorno->retornaTransacao($this->conexao);;
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno = ['status' => false,'message' => $e->getMessage(),'data' => []];
            $this->codigoRetorno = 400;
        }finally{
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
        }*/
        return false;
    }

    public function calculaTransacao(int $idTransacao)
    {
        // https://github.com/mobilestock/backend/issues/109
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'metodo_pagamento' => [Validador::OBRIGATORIO, Validador::ENUM('CA', 'PX', 'BL', 'DE')],
            'numero_parcelas' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'utiliza_credito' => [Validador::BOOLEANO],
        ]);

        $calculoPagamento = new TransacaoFinanceiraService();
        $calculoPagamento->id = $idTransacao;
        $calculoPagamento->metodo_pagamento = $dadosJson['metodo_pagamento'];
        $calculoPagamento->numero_parcelas = $dadosJson['numero_parcelas'];
        $utilizaCredito = (int) $dadosJson['utiliza_credito'];

        $calculoPagamento->calcularTransacao(DB::getPdo(), $utilizaCredito);
        $transacao = $calculoPagamento->retornaTransacao(DB::getPdo());
        DB::commit();

        return $transacao;
    }

    public function PagamentoTransacao(int $idTransacao, Request $request, PDO $conexao, FilaService $fila)
    {
        $dadosJson = $request->all();

        $pagamento = new TransacaoFinanceiraService();
        $pagamento->id = $idTransacao;
        $armazenarCartao = false;
        $consulta_transacao = $pagamento->retornaTransacao($conexao);

        if (!($consulta_transacao['id'] ?? 0)) {
            throw new PagamentoTransacaoNaoExisteException();
        }

        if ($consulta_transacao['metodo_pagamento'] === 'CA') {
            $armazenarCartao = (bool) ($dadosJson['salvar_cartao'] ?? false);
            $tokenCartao = $dadosJson['token_cartao'] ?? '';

            $pagamento->dados_cartao = [
                'holderName' => $dadosJson['holder_name'] ?? '',
                'cardNumber' => $dadosJson['card_number'] ?? '',
                'secureCode' => $dadosJson['secure_code'] ?? '',
                'expirationMonth' => $dadosJson['expiration_month'] ?? '',
                'expirationYear' => $dadosJson['expiration_year'] ?? '',
                'tokenCartao' => $tokenCartao,
            ];
        }
        $fila->conteudoArray = array_merge($pagamento->dados_cartao ?? [], [
            'id_transacao' => $idTransacao,
            'user' => [
                'id' => Auth::id(),
            ],
            'armazenar_cartao' => $armazenarCartao && !($tokenCartao ?? ''),
        ]);
        $fila->url_fila = $_ENV['SQS_ENDPOINTS']['GERAR_PAGAMENTO'];
        $fila->envia();

        return $fila->id;
    }

    public function deletaTransacoesEmAberto(PDO $conexao)
    {
        $transacoes = new TransacaoFinanceiraService();
        $transacoes->pagador = $this->idCliente;
        $transacoes->removeTransacoesEmAberto($conexao);
    }

    public function simulaCalculo()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'calculos' => [Validador::ARRAY],
        ]);

        Validador::validar($dadosJson['calculos'][0] ?? [], [
            'metodo_pagamento' => [Validador::OBRIGATORIO],
            'numero_parcelas' => [],
            'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $consultasTaxas = new TaxasConsultasService(DB::getPdo());

        $dadosPagamentoPadrao = ConfiguracaoService::consultaDadosPagamentoPadrao();
        $dadosJson['calculos'] = array_map(function (array $calculo) use ($dadosPagamentoPadrao, $consultasTaxas) {
            $numeroParcelas = $calculo['numero_parcelas'];
            $valor = $calculo['valor'];
            $metodoPagamento = $calculo['metodo_pagamento'];

            if ($numeroParcelas === 'padrao') {
                $calculo['numero_parcelas'] = $dadosPagamentoPadrao['parcelas'];
            }

            $calculador = new CalculadorTransacao($valor, $metodoPagamento, $calculo['numero_parcelas']);

            if ($metodoPagamento === 'BL') {
                $calculador->getTaxaBoleto = fn() => $consultasTaxas->consultaValorBoleto();
            }

            if ($metodoPagamento === 'CA') {
                $calculador->getValorParcela = fn(int $parcela) => $consultasTaxas->consultaValorTaxaParcela($parcela);

                $calculador->parcelas = [];
                for ($index = 1; $index <= 12; $index++) {
                    $calculadorAux = new CalculadorTransacao($valor, $metodoPagamento, $index);
                    $calculadorAux->getValorParcela = fn(int $parcela) => $consultasTaxas->consultaValorTaxaParcela(
                        $parcela
                    );
                    $calculadorAux->calcula();
                    $calculador->parcelas[] = $calculadorAux;
                }
            }

            $calculador->calcula();
            unset($calculador->valor_parcela);

            return $calculador;
        }, $dadosJson['calculos']);

        return $dadosJson['calculos'];
    }
}

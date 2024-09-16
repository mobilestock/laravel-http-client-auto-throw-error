<?php

namespace api_cliente\Controller;

use api_cliente\Models\Conect;
use api_cliente\Models\Painel as PainelModel;
use api_cliente\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\PedidoItem;
use MobileStock\model\Produto;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\Lancamento\LancamentoConsultas;
use MobileStock\service\Pedido;

class Painel extends Request_m
{
    public $conexao;

    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
        $this->conexao = Conect::conexao();
    }

    public function adicionaProdutoPainelStorage()
    {
        $dados = Request::all();
        Validador::validar($dados, ['produtos' => [Validador::OBRIGATORIO, Validador::ARRAY]]);

        DB::beginTransaction();

        foreach ($dados['produtos'] as $produto) {
            Validador::validar($produto, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'grade' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            foreach ($produto['grade'] as $grade) {
                Validador::validar($grade, [
                    'nome_tamanho' => [Validador::OBRIGATORIO],
                    'qtd' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'tipo_adicao' => [Validador::OBRIGATORIO, Validador::ENUM('PR', 'FL')],
                ]);

                for ($index = 1; $index <= $grade['qtd']; $index++) {
                    $produtoModel = Produto::buscarProdutoPorId($produto['id_produto']);
                    $pedidoItem = new PedidoItem();
                    $pedidoItem->id_cliente = Auth::user()->id_colaborador;
                    $pedidoItem->id_produto = $produto['id_produto'];
                    $pedidoItem->nome_tamanho = $grade['nome_tamanho'];
                    $pedidoItem->preco = $produtoModel->valor_venda_ms;
                    $pedidoItem->tipo_adicao = $grade['tipo_adicao'];
                    $pedidoItem->uuid = Auth::user()->id_colaborador . '_' . uniqid(rand(), true);
                    $pedidoItem->id_responsavel_estoque = 1;
                    $pedidoItem->observacao = $produto['observacao'] ?? '';
                    $pedidoItem->save();
                }
            }
        }

        DB::commit();
    }

    public function removeProdutoCarrinho($uuidProduto)
    {
        $produto = PedidoItem::consultaProdutoCarrinho($uuidProduto);
        if ($produto) {
            $produto->delete();
        }
    }

    public function limpaCarrinho()
    {
        PedidoItem::limpaProdutosCarrinho();
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/416
     */
    public function listaProdutosPedido()
    {
        Pedido::limparTransacaoEProdutosFreteDoCarrinhoSeNecessario();

        $produtos = PainelModel::consultaProdutosPedido(DB::getPdo(), Auth::user()->id_colaborador);
        $valorTaxaProduto = PainelModel::buscaValorTaxaProdutoPago();

        $produtos = PainelModel::analisaEstoquePedido(DB::getPdo(), $produtos);
        $pedido = $produtos['pedido'];
        $filaDeEspera = $produtos['reservados'];

        return [
            'pedido' => $pedido,
            'reservados' => $filaDeEspera,
            'valor_taxa_produto_pago' => $valorTaxaProduto,
        ];
    }

    public function saldoCliente()
    {
        try {
            $this->retorno['data'] = [
                'saldo' => LancamentoConsultas::consultaCreditoCliente($this->conexao, $this->idCliente),
            ];
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    // public function consultaValorFreteCliente()
    // {
    // 	try {
    // 		Validador::validar(['json' => $this->json], ['json' => [Validador::OBRIGATORIO, Validador::JSON]]);
    // 		$dadosFrete = json_decode($this->json, true);

    // 		$frete = new FreteService;

    // 		$this->retorno['data'] = $frete->calculaFrete($this->conexao, $dadosFrete, $this->uf, $this->idCliente);
    // 	} catch (\Throwable $e) {
    // 		$this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    // 		$this->codigoRetorno = 400;
    // 	} finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    // 		die;
    // 	}
    // }

    public function buscaFotoPerfil()
    {
        try {
            $this->retorno = ColaboradoresRepository::buscaFotoPerfil($this->idCliente);
        } catch (\Throwable $th) {
            $this->retorno = ['status' => false, 'message' => $th->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    // public function buscaUltimoFrete()
    // {
    // 	try {
    // 		$frete = ColaboradoresService::buscaUltimoFreteUsado(Conexao::criarConexao(), $this->idCliente);
    // 		$this->retorno['data'] = $frete;
    // 	} catch (\Throwable $th) {
    // 		$this->retorno = ['status' => false, 'message' => $th->getMessage(), 'data' => []];
    // 		$this->codigoRetorno = 400;
    // 	} finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    // 		die;
    // 	}
    // }

    // public function alteraClientePedidoItem()
    // {
    // 	try {
    // 		$this->conexao->beginTransaction();
    // 		Validador::validar(['json' => $this->json], [
    // 			'json' => [Validador::OBRIGATORIO, Validador::JSON]
    // 		]);
    // 		$dadosJson = json_decode($this->json, true);

    // 		Validador::validar($dadosJson, [
    // 			'uuid' => [Validador::OBRIGATORIO],
    // 			'cliente' => [Validador::OBRIGATORIO]
    // 		]);

    // 		$pedidoItemService = new PedidoItem();
    // 		$pedidoItemService->uuid = $dadosJson['uuid'];
    // 		$pedidoItemService->cliente = $dadosJson['cliente']['nome'];
    // 		$pedidoItemService->id_cliente_final = (string) $dadosJson['cliente']['id'] ?? 0;
    // 		$pedidoItemService->atualizaClientePedidoItem($this->conexao);

    // 		$this->retorno['message'] = 'Cliente atualizado com sucesso!';

    // 		$this->conexao->commit();
    // 	} catch (\Throwable $th) {
    // 		$this->conexao->rollBack();
    // 		$this->retorno = ['status' => false, 'message' => $th->getMessage(), 'data' => []];
    // 		$this->codigoRetorno = 400;
    // 	} finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    // 		die;
    // 	}
    // }
}

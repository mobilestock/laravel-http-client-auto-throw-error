<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\model\AvaliacaoProdutos;
use MobileStock\repository\FotosRepository;
use MobileStock\service\AcompanhamentoTempService;
use MobileStock\service\AvaliacaoProdutosService;
use MobileStock\service\CancelamentoProdutos;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Historico extends Request_m
{
    private $conexao;

    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function listaPedidos()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);
        $retorno = [
            'sem_entregas' => TransacaoConsultasService::buscaPedidosMobileStockSemEntrega(DB::getPdo(), Auth::user()->id_colaborador),
            'com_entregas' => TransacaoConsultasService::buscaPedidosComEntrega($dados['pagina']),
        ];

        return $retorno;
    }
    public function buscaProdutosPedidoSemEntrega(
        PDO $conexao,
        AcompanhamentoTempService $acompanhamentoService,
        Authenticatable $usuario
    ) {
        $historico = TransacaoConsultasService::buscaProdutosPedidoMobileStockSemEntrega(
            $conexao,
            $usuario->id_colaborador
        );

        foreach ($historico as &$item) {
            if (!empty($item['endereco_transacao']['id_cidade'])) {
                $acompanhamento = $acompanhamentoService->buscarAcompanhamentoDestino(
                    $usuario->id_colaborador,
                    $item['id_tipo_frete'],
                    $item['endereco_transacao']['id_cidade']
                );

                $item['acompanhamento'] = $acompanhamento;
            }
        }

        return $historico;
    }
    public function buscaProdutosPedidoComEntrega(int $idEntrega)
    {
        $produtos = TransacaoConsultasService::buscaProdutosPedidoMobileStockComEntrega($idEntrega);

        return $produtos;
    }

    public function insereAvaliacao()
    {
        try {
            $this->conexao->beginTransaction();
            $url = '';
            // $valor = 0;
            Validador::validar(['json' => $this->json], ['json' => [Validador::OBRIGATORIO, Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_faturamento' => [Validador::OBRIGATORIO],
                'id_produto' => [Validador::OBRIGATORIO],
            ]);
            $idFaturamento = $dadosJson['id_faturamento'];
            $idProduto = $dadosJson['id_produto'];
            $retorno = AvaliacaoProdutosService::existeAvaliacaoProdutoComFaturamento(
                $this->conexao,
                $idProduto,
                $idFaturamento
            );
            if ($retorno) {
                throw new Exception('Esse produto já foi avaliado e não pode ser avaliado novamente', 1);
            }

            if (isset($_FILES) && sizeof($_FILES) > 0) {
                extract($_FILES);
                $url = FotosRepository::salvarFotoAwsS3(
                    $foto,
                    $foto['name'] . '_avaliacao_' . $idFaturamento . '_' . $idProduto,
                    'PADRAO'
                );
            }

            $novaAvaliacao = new AvaliacaoProdutos();
            $novaAvaliacao->id_faturamento = $idFaturamento;
            $novaAvaliacao->id_cliente = $this->idCliente;
            $novaAvaliacao->id_produto = $idProduto;
            $novaAvaliacao->qualidade = $nota;
            $novaAvaliacao->comentario = $comentario;
            $novaAvaliacao->foto_upload = $url;
            AvaliacaoProdutosService::salva($this->conexao, $novaAvaliacao);

            // if ($resultado) {
            // $valor = $url != '' ? 0.1 : 0;
            // $valor += $comentario != '' ? 0.1 : 0;
            // $valor += $nota > 0 ? 0.1 : 0;

            // if ($valor > 0) {
            // 	$lancamento = new Lancamento('P', 1, 'CH', $this->idCliente, DATE('Y-m-d H:i:s'), $valor, $this->idUsuario, 1);
            // 	$lancamento->documento = 12;
            // 	$lancamento->documento_pagamento = 12;
            // 	$lancamento->insereObservacao("Crédito gerado a partir da Avaliação de produtos");
            // 	$conexao = Conexao::criarConexao();
            // 	LancamentoCrud::salva($conexao, $lancamento);
            // }
            // }
            $this->retorno['data'] = ['menssagem' => 'Avaliação inserida com sucesso'];
            $this->conexao->commit();
        } catch (Throwable $e) {
            $this->conexao->rollBack();
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

    public function exibeQrcodeEntregasProntas()
    {
        $resultado = EntregaServices::exibeQrcodeEntregasProntas();
        return $resultado;
    }
    public function pagamentosAbertos()
    {
        $retorno = TransacaoConsultasService::buscaPagamentosAbertos();

        return $retorno;
    }

    public function cancelamento(string $uuidProduto)
    {
        DB::getLock();
        DB::beginTransaction();
        $existeEmAlgumPedido = TransacaoFinanceiraItemProdutoService::produtoExisteEmAlgumPedido($uuidProduto);
        if (!$existeEmAlgumPedido) {
            throw new NotFoundHttpException('Esse produto já foi cancelado');
        }

        $cancelamentoProdutos = new CancelamentoProdutos([$uuidProduto]);
        $item = last(TransacaoFinanceiraItemProdutoService::buscaInfoProdutoCancelamento([$uuidProduto]));
        if ($item['sou_cliente']) {
            $cancelamentoProdutos->direitosItem();
        } elseif ($item['sou_responsavel_estoque']) {
            $cancelamentoProdutos->liberadosLogistica();
        } else {
            throw new NotFoundHttpException('Esse produto não pertence a você');
        }

        DB::commit();
    }
}

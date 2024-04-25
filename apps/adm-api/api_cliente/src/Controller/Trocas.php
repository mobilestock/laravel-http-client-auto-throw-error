<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\model\Origem;
use MobileStock\repository\FotosRepository;
use MobileStock\repository\TrocaPendenteRepository;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use MobileStock\service\Troca\TrocasService;
use MobileStock\service\TrocaFilaSolicitacoesService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class Trocas extends Request_m
{
    public \PDO $conexao;

    public function __construct()
    {
        parent::__construct();
        $this->conexao = app(\PDO::class);
    }

    public function listaPedidosTroca()
    {
        $pedidos = ProdutoService::buscaProdutosParaTroca();

        return $pedidos;
    }
    public function criaSolicitacaoDefeito(TrocaFilaSolicitacoesService $troca, Origem $origem)
    {
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();
        $fotos = array_slice($_FILES, 0, 3);
        foreach ($fotos as $index => $valor) {
            $dadosJson[$index] = $valor;
        }

        Validador::validar($dadosJson, [
            'foto_1' => [Validador::OBRIGATORIO],
            'uuid' => [Validador::OBRIGATORIO],
            'descricao_defeito' => [Validador::OBRIGATORIO],
        ]);

        $produtoFaturamentoItem = LogisticaItemService::consultaInfoProdutoTroca(
            $dadosJson['uuid'],
            Auth::user()->id_colaborador,
            !$origem->ehMl()
        );
        if (empty($produtoFaturamentoItem)) {
            throw new NotFoundHttpException('Informações do produto não encontradas!');
        }

        if ($origem->ehMl() && $produtoFaturamentoItem['situacao_entregas_faturamento_item'] !== 'EN') {
            EntregaServices::forcarEntregaDeProduto($dadosJson['uuid']);
        }

        $dadosProduto = PedidoItemMeuLookService::buscaDadosProdutoPorUuid(DB::getPdo(), $dadosJson['uuid'], $origem);
        if (!$dadosProduto['periodo_solicitar_troca_defeito_disponivel']) {
            throw new UnprocessableEntityHttpException('Prazo para solicitar troca expirado!');
        }

        $urlsFotos = [];
        $fotosRepository = new FotosRepository();
        foreach ($fotos as $index => $valor) {
            $horario = time();
            $url = $fotosRepository->salvarFotoAwsS3(
                $valor,
                "solicitacao_{$dadosJson['uuid']}_{$index}_{$horario}",
                'PADRAO'
            );
            $urlsFotos[] = $url;
        }

        $troca->id = $dadosProduto['id_solicitacao'];
        $troca->id_cliente = Auth::user()->id_colaborador;
        $troca->situacao = 'SOLICITACAO_PENDENTE';
        $troca->id_produto = $dadosProduto['id_produto'];
        $troca->nome_tamanho = $dadosProduto['nome_tamanho'];
        $troca->uuid_produto = $dadosJson['uuid'];
        $troca->descricao_defeito = $dadosJson['descricao_defeito'];
        foreach ($urlsFotos as $index => $valor) {
            $troca->{'foto' . ($index + 1)} = $valor;
        }
        if ($dadosProduto['solicitacao_existe']) {
            $troca->atualizar(DB::getPdo());
        } else {
            $troca->salva(DB::getPdo());
        }

        DB::getPdo()->commit();
        TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp(DB::getPdo(), $troca->id, $origem);
    }

    public function desisteTroca(TrocasService $trocasService, Origem $origem)
    {
        DB::beginTransaction();
        $conexao = DB::getPdo();
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, ['uuid_produto' => [Validador::OBRIGATORIO]]);
        $uuidProduto = $dadosJson['uuid_produto'];
        $valor = 0;
        if ($origem->ehMl()) {
            $valor = $trocasService->consultaValorTransacaoDesistirTroca(Auth::user()->id_colaborador, [$uuidProduto]);
        }
        if ($valor < 0) {
            return new Response($valor, Response::HTTP_PAYMENT_REQUIRED);
        } else {
            $dadosProduto = PedidoItemMeuLookService::buscaDadosProdutoPorUuid($conexao, $uuidProduto, $origem);

            if ($origem->ehMl()) {
                if ($dadosProduto['existe_agendamento']) {
                    TrocaPendenteRepository::removeTrocaAgendadadaNormalMeuLook($conexao, $uuidProduto);
                }
            }

            if ($dadosProduto['id_solicitacao']) {
                $model = new TrocaFilaSolicitacoesService();
                $model->id = $dadosProduto['id_solicitacao'];
                $model->situacao = 'CANCELADO_PELO_CLIENTE';
                $model->atualizar($conexao);
                TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp(
                    $conexao,
                    $dadosProduto['id_solicitacao'],
                    $origem
                );
            }
        }
        if ($conexao->inTransaction()) {
            DB::commit();
        }
    }

    public function geraPixTroca(
        TrocasService $trocasService,
        TransacaoFinanceiraItemProdutoService $transacaoFinanceiraItemProdutoService,
        TransacaoFinanceiraService $transacaoFinanceiraService,
        TransacaoFinanceirasMetadadosService $metadadosService
    ) {
        DB::beginTransaction();
        $dadosJson = FacadesRequest::all();
        $conexao = DB::getPdo();
        $uuidsProdutos = $dadosJson;
        $valor = $trocasService->consultaValorTransacaoDesistirTroca(Auth::user()->id_colaborador, $uuidsProdutos);
        if ($valor > 0) {
            throw new Exception('Não é possível gerar PIX para troca com valor positivo!');
        }
        $valor = abs($valor);
        $transacaoFinanceiraService->origem_transacao = 'ET';
        $transacaoFinanceiraService->pagador = Auth::user()->id_colaborador;
        $transacaoFinanceiraService->removeTransacoesEmAberto($conexao);

        $transacaoFinanceiraService->id_usuario = Auth::user()->id;
        $transacaoFinanceiraService->valor_itens = $valor;
        $transacaoFinanceiraService->metodos_pagamentos_disponiveis = 'PX';
        $transacaoFinanceiraService->criaTransacao($conexao);

        $transacaoFinanceiraItemProdutoService->id_transacao = $transacaoFinanceiraService->id;
        $transacaoFinanceiraItemProdutoService->comissao_fornecedor = $transacaoFinanceiraService->valor_itens;
        $transacaoFinanceiraItemProdutoService->preco = $transacaoFinanceiraService->valor_itens;
        $transacaoFinanceiraItemProdutoService->id_fornecedor = $transacaoFinanceiraService->pagador;
        $transacaoFinanceiraItemProdutoService->tipo_item = 'AC';
        $transacaoFinanceiraItemProdutoService->criaTransacaoItemProduto($conexao);

        $transacaoFinanceiraService->metodo_pagamento = 'PX';
        $transacaoFinanceiraService->numero_parcelas = 1;
        $transacaoFinanceiraService->calcularTransacao($conexao, 0);

        $transacaoFinanceiraService->retornaTransacao($conexao);

        $processadorPagamentos = ProcessadorPagamentos::criarPorInterfacesPadroes(
            $conexao,
            $transacaoFinanceiraService
        );
        $processadorPagamentos->executa();

        $metadadosService->id_transacao = $transacaoFinanceiraService->id;
        $metadadosService->chave = 'PRODUTOS_TROCA';
        $metadadosService->valor = $uuidsProdutos;
        $metadadosService->salvar($conexao);
    }

    public function abrirDisputaSolicitarTroca()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            $origem = $this->request->query->get('origem');

            Validador::validar(
                ['origem' => $origem],
                [
                    'origem' => [Validador::ENUM('ML', 'MS')],
                ]
            );

            Validador::validar($dadosJson, ['id_solicitacao' => [Validador::OBRIGATORIO]]);
            $idSolicitacao = $dadosJson['id_solicitacao'];
            $solicitacao = new TrocaFilaSolicitacoesService();
            $solicitacao->id = $idSolicitacao;
            $solicitacao->situacao = 'EM_DISPUTA';
            $solicitacao->atualizar($this->conexao);

            TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp($this->conexao, $idSolicitacao, $origem);
            $this->retorno['data'] = true;
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (Throwable $th) {
            $this->conexao->rollBack();
            $this->retorno['message'] = $th->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function reabrirTroca()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, ['uuid_produto' => [Validador::OBRIGATORIO]]);
            $uuidProduto = $dadosJson['uuid_produto'];
            $origem = $this->request->query->get('origem');

            Validador::validar(
                ['origem' => $origem],
                [
                    'origem' => [Validador::ENUM('ML', 'MS')],
                ]
            );

            $dadosProduto = PedidoItemMeuLookService::buscaDadosProdutoPorUuid($this->conexao, $uuidProduto, $origem);
            if (!$dadosProduto['periodo_solicitar_troca_defeito_disponivel']) {
                throw new Exception('Prazo para solicitar troca expirado!');
            }
            if ($dadosProduto['situacao'] !== 'CANCELADO_PELO_CLIENTE') {
                throw new Exception('Essa solicitação de troca não está cancelada!');
            }
            $idSolicitacao = $dadosProduto['id_solicitacao'];

            $solicitacao = new TrocaFilaSolicitacoesService();
            $solicitacao->id = $idSolicitacao;
            $solicitacao->situacao = 'SOLICITACAO_PENDENTE';
            $solicitacao->atualizar($this->conexao);
            $this->conexao->commit();
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (Throwable $th) {
            $this->conexao->rollBack();
            $this->retorno['message'] = $th->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function insereNovasFotosDefeito()
    {
        try {
            $this->conexao->beginTransaction();
            $request = $this->request->request->all();
            Validador::validar(
                ['request' => $request, 'files' => $_FILES],
                ['request' => [Validador::ARRAY], 'files' => [Validador::ARRAY]]
            );
            Validador::validar($request, ['uuid_produto' => [Validador::OBRIGATORIO]]);
            Validador::validar($_FILES, ['foto_1' => [Validador::OBRIGATORIO]]);
            $uuidProduto = $request['uuid_produto'];
            $origem = $this->request->query->get('origem');

            Validador::validar(
                ['origem' => $origem],
                [
                    'origem' => [Validador::ENUM('ML', 'MS')],
                ]
            );

            $fotosRepository = new FotosRepository();
            foreach ($_FILES as $index => $file) {
                if ($index >= 3) {
                    break;
                }
                $horario = time();
                $url = $fotosRepository->salvarFotoAwsS3(
                    $file,
                    "solicitacao_{$uuidProduto}_{$index}_{$horario}",
                    'PADRAO'
                );
                $urlFotos[] = $url;
            }

            $model = new TrocaFilaSolicitacoesService();
            $idSolicitacao = $model->buscaIdDaFilaDeTrocasPorUuid($this->conexao, $uuidProduto);
            $model->id = $idSolicitacao;
            $model->situacao = 'PENDENTE_FOTO';
            $model->uuid_produto = $uuidProduto;
            $model->foto4 = $urlFotos[0] ?? '';
            $model->foto5 = $urlFotos[1] ?? null;
            $model->foto6 = $urlFotos[2] ?? null;
            $model->atualizar($this->conexao);

            TrocaFilaSolicitacoesService::enviarNotificacaoWhatsapp($this->conexao, $idSolicitacao, $origem);
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (Throwable $th) {
            $this->conexao->rollBack();
            $this->retorno['message'] = $th->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

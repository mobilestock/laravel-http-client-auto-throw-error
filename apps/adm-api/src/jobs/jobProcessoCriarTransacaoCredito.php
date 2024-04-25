<?php

use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY, ReceiveFromQueue::class];

    public function run(array $dados, PDO $conexao): array
    {
        try {
            // Este job atualmente possui a regra da zagga, porem a ideia é que ele seja a central de criação de credito, contendo o domínio da regra de negocio da criação de transação de credito.
            if (TransacaoFinanceirasMetadadosService::existeMetadado($conexao, 'ID_UNICO', $dados['id_unico'])) {
                return ['message' => 'ID Único já utilizado'];
            }

            $conexao->beginTransaction();
            $transacao = new TransacaoFinanceiraService();
            $transacao->status = 'CR';
            $transacao->origem_transacao = 'ZA';
            $transacao->pagador = $dados['id_cliente'];
            $transacao->valor_itens = $dados['valor'];
            $transacao->metodos_pagamentos_disponiveis = 'PX';
            $transacao->criaTransacao($conexao);

            $metadado = new TransacaoFinanceirasMetadadosService();
            $metadado->id_transacao = $transacao->id;
            $metadado->chave = 'ID_UNICO'; // ID do pedido na zagga, responsável por identificar o pedido na zagga e de-duplicar as requisições.
            $metadado->valor = $dados['id_unico'];
            $metadado->salvar($conexao);

            $adicionaItem = new TransacaoFinanceiraItemProdutoService();
            $adicionaItem->id_transacao = $transacao->id;
            $adicionaItem->comissao_fornecedor = $transacao->valor_itens;
            $adicionaItem->preco = $transacao->valor_itens;
            $adicionaItem->id_fornecedor = $transacao->pagador;
            $adicionaItem->tipo_item = 'AC';
            $adicionaItem->criaTransacaoItemProduto($conexao);

            $transacao->metodo_pagamento = 'PX';
            $transacao->numero_parcelas = 1;
            $transacao->valor_liquido = $dados['valor'];
            $transacao->calcularTransacao($conexao, 0);

            $processadorPagamento = ProcessadorPagamentos::criarPorInterfacesPadroes($conexao, $transacao);
            $processadorPagamento->executa();

            $listaTransacoesApi = TransacaoFinanceiraService::listaTransacoesApi(
                $conexao,
                $dados['id_cliente'],
                $transacao->id,
                1
            );
            return $listaTransacoesApi;
        } catch (\Throwable $exception) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $exception;
        }
    }
};

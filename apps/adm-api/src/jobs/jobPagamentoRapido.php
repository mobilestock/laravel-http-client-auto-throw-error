<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ClienteException;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use MobileStock\model\PedidoItem;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\Pagamento\PagamentoCreditoInterno;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\PedidoItem\PedidoItem as PedidoItemService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY, ReceiveFromQueue::class];

    public function run(array $dados): array
    {
        try {
            DB::beginTransaction();
            $transacao = new TransacaoFinanceiraService();
            $transacao->pagador = $dados['id_cliente'];
            $transacao->id_usuario = $dados['id_usuario'];
            $transacao->removeTransacoesEmAberto(DB::getPdo());
            $transacao->criaTransacao(DB::getPdo());

            $direitoItem = new PedidoItemService();
            $direitoItem->id_produto = $dados['id_produto'];
            $direitoItem->id_cliente = $dados['id_cliente'];
            $direitoItem->id_transacao = $transacao->id;
            $direitoItem->situacao = '1';
            $direitoItem->grade = $dados['grade'];
            $direitoItem->adicionaPedidoItem(DB::getPdo());


            $infoProduto = ProdutosRepository::retornaValorProduto(DB::getPdo(), $dados['id_produto']);
            $transacaoItem = new TransacaoFinanceiraItemProdutoService();
            foreach ($direitoItem->grade as $item) {
                $transacaoItem->id_transacao = $transacao->id;
                $transacaoItem->id_produto = $dados['id_produto'];
                $transacaoItem->nome_tamanho = $item['nome_tamanho'];
                $transacaoItem->comissao_fornecedor = $infoProduto['valor_custo_produto'];
                $transacaoItem->preco = $infoProduto['valor'];
                $transacaoItem->id_fornecedor = $infoProduto['id_fornecedor'];
                $transacaoItem->uuid_produto = $item['uuid'];
                $transacaoItem->tipo_item = 'PR';
                $transacaoItem->id_responsavel_estoque = $infoProduto['id_responsavel_estoque'];
                $transacaoItem->criaTransacaoItemProduto(DB::getPdo());
            }

            $pedidoItem = new PedidoItem();
            $pedidoItem->situacao = '2';
            $pedidoItem->atualizaIdTransacaoPI(array_column($direitoItem->grade, 'uuid'));

            $transacao->metodo_pagamento = 'DE';
            $transacao->numero_parcelas = 1;
            $transacao->calcularTransacao(DB::getPdo(), 1);

            $transacao->retornaTransacao(DB::getPdo());
            $processadorPagamentos = new ProcessadorPagamentos(DB::getPdo(), $transacao, [
                PagamentoCreditoInterno::class,
            ]);
            $processadorPagamentos->executa();

            $transacao->retornaTransacao(DB::getPdo());
            if ($transacao->valor_liquido !== 0.0) {
                throw new ClienteException('Saldo nao foi suficiente para pagar os itens');
            }

            DB::commit();

            return ['metodo_pagamento' => 'DE'];
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
};

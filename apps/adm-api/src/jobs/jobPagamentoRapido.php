<?php

namespace MobileStock\jobs;

use MobileStock\helper\ClienteException;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use MobileStock\model\PedidoItem;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\Pagamento\PagamentoCreditoInterno;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\PedidoItem\PedidoItem;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY, ReceiveFromQueue::class];

    public function run(array $dados, PDO $conexao): array
    {
        try {
            $conexao->beginTransaction();
            $transacao = new TransacaoFinanceiraService();
            $transacao->pagador = $dados['id_cliente'];
            $transacao->id_usuario = $dados['id_usuario'];
            $transacao->removeTransacoesEmAberto($conexao);
            $transacao->criaTransacao($conexao);

            $direitoItem = new PedidoItem();
            $direitoItem->id_produto = $dados['id_produto'];
            $direitoItem->id_cliente = $dados['id_cliente'];
            $direitoItem->id_transacao = $transacao->id;
            $direitoItem->situacao = '1';
            $direitoItem->grade = $dados['grade'];
            $direitoItem->adicionaPedidoItem($conexao);

            $infoProduto = ProdutosRepository::retornaValorProduto($conexao, $dados['id_produto']);
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
                $transacaoItem->criaTransacaoItemProduto($conexao);
            }

            $direitoItem->situacao = '2';
            $direitoItem->atualizaIdTransacaoPI(array_column($direitoItem->grade, 'uuid'));
            $pedidoItem = new PedidoItem();
            $pedidoItem->situacao = '2';
            $pedidoItem->atualizaIdTransacaoPI(array_column($direitoItem->grade, 'uuid'));

            $transacao->metodo_pagamento = 'DE';
            $transacao->numero_parcelas = 1;
            $transacao->calcularTransacao($conexao, 1);

            $transacao->retornaTransacao($conexao);
            $processadorPagamentos = new ProcessadorPagamentos(
                $conexao,
                $transacao,
                [PagamentoCreditoInterno::class],
            );
            $processadorPagamentos->executa();

            $transacao->retornaTransacao($conexao);
            if ($transacao->valor_liquido !== 0.0) {
                throw new ClienteException('Saldo nao foi suficiente para pagar os itens');
            }

            $conexao->commit();

            return ['metodo_pagamento' => 'DE'];
        } catch (\Throwable $exception) {
            $conexao->rollBack();
            throw $exception;
        }
    }
};

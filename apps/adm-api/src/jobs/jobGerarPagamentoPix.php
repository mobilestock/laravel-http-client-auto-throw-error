<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ClienteException;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use MobileStock\service\Lancamento\LancamentoConsultas;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasProdutosTrocasService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY, ReceiveFromQueue::class];

    public function run(array $dados): array
    {
        DB::beginTransaction();
        $transacao = new TransacaoFinanceiraService();
        $transacao->origem_transacao = 'ET';

        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->removeTransacoesEmAberto(DB::getPdo());

        if ($transacao->existeTransacaoETPendente()) {
            throw new ClienteException('Não foi possível criar o pix pois o cliente ja possui um em aberto.');
        }

        if (LancamentoConsultas::temSaldo()) {
            throw new \InvalidArgumentException('Você não pode gerar pix se não estiver devendo.');
        }

        $transacao->id_usuario = Auth::id();
        $transacao->valor_itens = 0;
        $transacao->metodos_pagamentos_disponiveis = 'CA,BL,PX';
        $transacao->criaTransacao(DB::getPdo());

        $transacao->metodo_pagamento = 'PX';
        $transacao->numero_parcelas = 1;
        $transacao->calcularTransacao(DB::getPdo(), 0);

        $transacao->retornaTransacao(DB::getPdo());

        // Atualizar campo id_nova_transacao
        $trocas = new TransacaoFinanceirasProdutosTrocasService();
        $trocas->id_cliente = Auth::user()->id_colaborador;
        $trocas->id_nova_transacao = $transacao->id;
        $trocas->atualizaNovaTransacaoTroca(DB::getPdo());

        $processadorPagamentos = ProcessadorPagamentos::criarPorInterfacesPadroes(DB::getPdo(), $transacao);
        $processadorPagamentos->executa();

        return ['message' => 'ok'];
    }
};

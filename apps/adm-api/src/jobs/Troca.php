<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;

use Psr\Log\LogLevel;

class Troca implements ShouldQueue
{
    use Queueable;
    protected string $uuidProduto;

    public function __construct(string $uuidProduto)
    {
        $this->uuidProduto = $uuidProduto;

        $this->middleware[] = SetLogLevel::class . ':' . LogLevel::CRITICAL;
    }

    public function handle(TransacaoFinanceiraModel $transacao)
    {
        [$transacoes, $preco] = $transacao->buscaTransacoesPendentesTroca($this->uuidProduto);

        if (is_null($transacoes)) {
            return;
        }

        foreach ($transacoes as $transacao) {
            $transacao->valor_credito_bloqueado = max($transacao->valor_credito_bloqueado - $preco, 0);
            $preco -= $transacao->valor_credito_bloqueado;

            $transacao->save();

            if ($preco <= 0) {
                break;
            }
        }
    }
}

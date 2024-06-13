<?php

namespace MobileStock\jobs;

use Exception;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\NotificacaoService;
use PDO;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @issue: https://github.com/mobilestock/backend/issues/330
 */
return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL];

    public function run(PDO $conexao, NotificacaoService $notificacao)
    {
        $notificacao->notificacoesFalhas = [];
        $notificacao->verificaErroEstoque($conexao);
        $notificacao->verificaProdutosCorrigir();
        $notificacao->verificaTrocaLancamentoIncorreto($conexao);
        //        $notificacao->verificaProdutosValorZeradoTransacao($conexao);
        $notificacao->verificaTransacoesCRRemanentes($conexao);
        $notificacao->verificaComissoesErradasTransacao();
        $notificacao->verificaLancamentosDuplicadosTransacao($conexao);
        $notificacao->verificaProdutosBloqueados($conexao);
        $notificacao->verificaEstoqueForaDeLinha($conexao);
        $notificacao->verificaProdutosSemFotoPub($conexao);
        $notificacao->verificaTransacoesGarradasFraude($conexao);
        $notificacao->verificaPedidoItemPendenteTransacaoPaga($conexao);
        $notificacao->verificaEntregaDessincronizada($conexao);
        $notificacao->verificaSellerBloqueadoComEstoque($conexao);
        $notificacao->verificaSePontoResponsavelTrocaEstaDiferente($conexao);
        $notificacao->verificaValorEstornado($conexao);

        if (!empty($notificacao->notificacoesFalhas)) {
            $notificacaoErros = implode(',', $notificacao->notificacoesFalhas);

            throw new Exception($notificacaoErros);
        }
    }
};

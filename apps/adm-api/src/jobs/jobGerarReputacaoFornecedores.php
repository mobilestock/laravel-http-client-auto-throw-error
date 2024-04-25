<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ReputacaoFornecedoresService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL];

    public function run()
    {
        DB::beginTransaction();
        ReputacaoFornecedoresService::limparReputacoes();
        ReputacaoFornecedoresService::gerarValorEQuantidadeVendas();
        ReputacaoFornecedoresService::gerarVendasEntregues();
        ReputacaoFornecedoresService::gerarMediaEnvio();
        ReputacaoFornecedoresService::gerarCancelamentos();
        ReputacaoFornecedoresService::gerarReputacao();
        DB::commit();
    }
};

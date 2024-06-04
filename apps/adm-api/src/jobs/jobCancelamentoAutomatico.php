<?php

namespace MobileStock\jobs;

use DateTime;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\RetryMiddleware;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\LogisticaItemModel;
use MobileStock\service\CancelamentoProdutos;
use MobileStock\service\DiaUtilService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [RetryMiddleware::class];

    public function run()
    {
        if (!DiaUtilService::ehDiaUtil((new DateTime('yesterday'))->format('Y-m-d'))) {
            return;
        }

        DB::beginTransaction();

        $produtos = LogisticaItemModel::buscaProdutosCancelamento();
        if (empty($produtos)) {
            return;
        }

        (new CancelamentoProdutos($produtos))->liberadosLogistica();
        DB::commit();
    }
};

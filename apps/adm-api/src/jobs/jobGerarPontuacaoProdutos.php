<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\ProdutosPontosService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL];

    public function run()
    {
        DB::beginTransaction();
        $produtosPontosService = new ProdutosPontosService();
        $produtosPontosService->removeItensInvalidos();
        $produtosPontosService->geraNovosProdutos();
        DB::commit();

        $idsProdutosAtualizados = $produtosPontosService->atualizaDadosProdutos();
        if (!empty($idsProdutosAtualizados)) {
            $produtosPontosService->calcularTotalNormalizado();
            ProdutosRepository::atualizaDataQualquerAlteracao($idsProdutosAtualizados);
        }
    }
};

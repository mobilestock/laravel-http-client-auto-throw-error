<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\ProdutosPontuacoes;
use MobileStock\repository\ProdutosRepository;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL];

    public function run()
    {
        DB::beginTransaction();
        ProdutosPontuacoes::removeItensInvalidosSeNecessario();
        ProdutosPontuacoes::geraNovosProdutos();
        DB::commit();

        $idsProdutosAtualizados = ProdutosPontuacoes::atualizaDadosProdutos();
        if (!empty($idsProdutosAtualizados)) {
            ProdutosPontuacoes::calcularTotalNormalizado();
            ProdutosRepository::atualizaDataQualquerAlteracao($idsProdutosAtualizados);
        }
    }
};

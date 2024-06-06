<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ProdutosRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();
        ProdutosRepository::atualizarQuantidadeVendida();
        ProdutosRepository::atualizarQuantidadeCompradoresUnicos();
        DB::commit();
    }
};

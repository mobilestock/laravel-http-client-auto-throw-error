<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\CatalogoFixoService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();
        CatalogoFixoService::geraCatalogoModaComPorcentagem(CatalogoFixoService::TIPO_MODA_GERAL);

        for ($porcentagem = 20; $porcentagem <= 100; $porcentagem += 20) {
            $tag = 'MODA_' . $porcentagem;
            CatalogoFixoService::geraCatalogoModaComPorcentagem($tag, $porcentagem);
        }
        DB::commit();
    }
};

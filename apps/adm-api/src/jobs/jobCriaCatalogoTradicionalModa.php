<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\CatalogoFixoService;
use MobileStock\service\UsuarioService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();
        UsuarioService::calculaTendenciaCompra();
        CatalogoFixoService::geraCatalogoModaComPorcentagem(CatalogoFixoService::TIPO_MODA_GERAL);
        CatalogoFixoService::geraCatalogoModaPorcentagemFixa();
        DB::commit();
    }
};

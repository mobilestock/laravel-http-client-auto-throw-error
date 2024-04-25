<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ColaboradoresService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();

        ColaboradoresService::atualizarTelefoneNosEnderecos();

        DB::commit();
    }
};

<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\MensagensNovidadesService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    public function run(\PDO $conexao)
    {
            $service = new MensagensNovidadesService();
            $service->enviaNotificacao($conexao);
    }
};
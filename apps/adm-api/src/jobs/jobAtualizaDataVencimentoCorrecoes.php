<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\TrocaPendenteRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    public function run(\PDO $conexao)
    {
        TrocaPendenteRepository::atualizarDataVencimentoCorrecoes($conexao);
    }
};
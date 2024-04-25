<?php

use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ColaboradoresService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(PDO $conexao)
    {
        try {
            $conexao->beginTransaction();

            $colaboradores = ColaboradoresService::buscaColaboradoresParaBloquear($conexao);

            foreach ($colaboradores as $colaborador) {
                ColaboradoresService::bloquearReposicaoSeller($conexao, $colaborador, true);
            }

            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }
};

<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ProdutosRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    public function run(\PDO $conexao)
    {
      try {
        $conexao->beginTransaction();
        ProdutosRepository::atualizarQuantidadeVendida($conexao);
        $conexao->commit();
      } catch (\Throwable $exception) {
        $conexao->rollBack();
        throw $exception;
      }
    }
};
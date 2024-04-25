<?php

namespace MobileStock\jobs;

use Exception;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\CatalogoFixoService;
use MobileStock\repository\ProdutosRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    public function run(\PDO $conexao)
    {
        try {
            $conexao->beginTransaction();
            // CatalogoFixoService::geraMelhoresFabricantes($conexao);
            CatalogoFixoService::removeItensInvalidos($conexao);
            ProdutosRepository::limparUltimosAcessos($conexao);
            CatalogoFixoService::atualizaInformacoesProdutosCatalogoFixo($conexao);
            CatalogoFixoService::geraVendidosRecentemente();
            CatalogoFixoService::geraMelhoresProdutos($conexao);
            $conexao->commit();
        } catch (Exception $exception) {
            $conexao->rollBack();
            throw $exception;
        }
    }
};

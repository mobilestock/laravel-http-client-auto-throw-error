<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\CatalogoFixoService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(\PDO $conexao)
    {
        $conexao->beginTransaction();
        CatalogoFixoService::removeItensInvalidos();
        ProdutosRepository::limparUltimosAcessos();
        CatalogoFixoService::atualizaInformacoesProdutosCatalogoFixo();
        CatalogoFixoService::geraVendidosRecentemente();
        CatalogoFixoService::geraMelhoresProdutos();
        CatalogoFixoService::geraCatalogoModaComPorcentagem();
        $conexao->commit();
    }
};

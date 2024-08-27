<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\CatalogoFixoService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();
        CatalogoFixoService::removeItensInvalidos();
        ProdutosRepository::limparUltimosAcessos();
        CatalogoFixoService::atualizaInformacoesProdutosCatalogoFixo();
        CatalogoFixoService::geraVendidosRecentemente();
        CatalogoFixoService::geraMelhoresProdutos();
        CatalogoFixoService::geraCatalogoModaComPorcentagem();
        DB::commit();
    }
};

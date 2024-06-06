<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\CatalogoFixoService;
use MobileStock\repository\ProdutosRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(\PDO $conexao)
    {
        $conexao->beginTransaction();
        CatalogoFixoService::removeItensInvalidos($conexao);
        ProdutosRepository::limparUltimosAcessos($conexao);
        CatalogoFixoService::atualizaInformacoesProdutosCatalogoFixo($conexao);
        CatalogoFixoService::geraVendidosRecentemente();
        CatalogoFixoService::geraMelhoresProdutos($conexao);
        CatalogoFixoService::geraCatalogoModaComPorcentagem(CatalogoFixoService::TIPO_MODA_GERAL);

        for ($porcentagem = 20; $porcentagem <= 100; $porcentagem += 20) {
            $tag = 'MODA_' . $porcentagem;
            CatalogoFixoService::geraCatalogoModaComPorcentagem($tag, $porcentagem);
        }
        $conexao->commit();
    }
};

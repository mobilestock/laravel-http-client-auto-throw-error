<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\service\ProdutoService;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\Estoque\EstoqueService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $listaProdutosComLocalizacao = ProdutoService::buscaLocalizacaoComEstoqueLiberado();

        foreach ($listaProdutosComLocalizacao as $index => $produtoLocalizacao) {
            DB::beginTransaction();
            echo $index + 1 . PHP_EOL;
            EstoqueService::atualizaLocalizacaoProduto(
                DB::getPdo(),
                $produtoLocalizacao['id_produto'],
                $produtoLocalizacao['localizacao'],
                null,
                Auth::id(),
                0
            );
            if ($produtoLocalizacao['tem_aguardando_entrada']) {
                EstoqueService::limpaLocalizacaoProdutosAguardaEntrada($produtoLocalizacao['id_produto']);
            }
            DB::commit();
        }
    }
};

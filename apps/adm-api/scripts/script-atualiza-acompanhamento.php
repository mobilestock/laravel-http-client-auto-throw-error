<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\GerenciarAcompanhamento;

require_once __DIR__ . '/../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $produtos = DB::select(
            "SELECT
                acompanhamento_item_temp.uuid_produto,
                acompanhamento_temp.situacao
            FROM acompanhamento_item_temp
            INNER JOIN acompanhamento_temp ON acompanhamento_temp.id = acompanhamento_item_temp.id_acompanhamento
            GROUP BY acompanhamento_item_temp.uuid_produto"
        );

        $rowCount = DB::delete('DELETE FROM acompanhamento_temp;');

        if (!$rowCount) {
            throw new DomainException('Não foi possível limpar a tabela de acompanhamento_item_temp');
        }

        app()->call([
            new GerenciarAcompanhamento(
                array_column($produtos, 'uuid_produto'),
                GerenciarAcompanhamento::CRIAR_ACOMPANHAMENTO
            ),
            'handle',
        ]);

        $produtosPausados = array_filter($produtos, fn($produto) => $produto['situacao'] === 'PAUSADO');

        if (empty($produtosPausados)) {
            return;
        }

        app()->call([
            new GerenciarAcompanhamento(
                array_column($produtosPausados, 'uuid_produto'),
                GerenciarAcompanhamento::PAUSAR_ACOMPANHAMENTO
            ),
            'handle',
        ]);
    }
};

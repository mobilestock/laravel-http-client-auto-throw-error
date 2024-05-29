<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ConfiguracaoService;

require_once __DIR__ . '/../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();

        echo 'Movendo regras de pontuações' . PHP_EOL;
        $pontosMetadados = DB::select(
            "SELECT
                produtos_pontos_metadados.grupo,
                produtos_pontos_metadados.chave,
                produtos_pontos_metadados.valor
            FROM produtos_pontos_metadados;"
        );
        $produtosPontosMetadados = array_filter(
            $pontosMetadados,
            fn(array $metadado): bool => $metadado['grupo'] === 'PRODUTOS_PONTOS'
        );
        $produtosPontosMetadados = array_reduce(
            $produtosPontosMetadados,
            fn(array $inicial, array $atual): array => array_merge($inicial, [
                Str::lower($atual['chave']) => (float) $atual['valor'],
            ]),
            []
        );
        ksort($produtosPontosMetadados);
        $reputacaoPontosMetadados = array_filter(
            $pontosMetadados,
            fn(array $metadado): bool => $metadado['grupo'] === ConfiguracaoService::REPUTACAO_FORNECEDORES
        );
        $reputacaoPontosMetadados = array_reduce(
            $reputacaoPontosMetadados,
            fn(array $inicial, array $atual): array => array_merge($inicial, [
                Str::lower($atual['chave']) => (float) $atual['valor'],
            ]),
            []
        );
        ksort($reputacaoPontosMetadados);

        DB::update(
            "UPDATE configuracoes SET
                configuracoes.json_produto_pontuacoes = :json_produto_pontuacoes,
                configuracoes.json_reputacao_fornecedor_pontuacoes = :json_reputacao_fornecedor_pontuacoes;",
            [
                'json_produto_pontuacoes' => json_encode($produtosPontosMetadados),
                'json_reputacao_fornecedor_pontuacoes' => json_encode($reputacaoPontosMetadados),
            ]
        );

        echo 'Regras de pontuações movidas' . PHP_EOL;

        DB::commit();
    }
};

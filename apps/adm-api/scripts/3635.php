<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\ProdutoModel;
use MobileStock\service\ConfiguracaoService;

require_once __DIR__ . '/../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();

        $configuracoes = ConfiguracaoService::buscaFatoresEstoqueParado();
        $produtos = DB::select(
            "SELECT
                log_alteracao_produto.linha_tabela AS `int_id_produto`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'chave', log_alteracao_produto.nome_coluna,
                            'json_valor_anterior', log_alteracao_produto.valor_anterior,
                            'json_valor_novo', log_alteracao_produto.valor_novo
                        )
                        ORDER BY log_alteracao_produto.id DESC
                    ),
                    ']'
                ) AS `json_logs`,
                SUM(DISTINCT log_alteracao_produto.nome_coluna IN ('preco_promocao', 'promocao')) AS `tinha_promocao`
            FROM log_alteracao_produto
            WHERE log_alteracao_produto.data BETWEEN '2024-07-05 16:32:50' AND '2024-07-05 16:33:10'
                AND log_alteracao_produto.nome_coluna IN ('valor_custo_produto', 'preco_promocao', 'promocao')
            GROUP BY log_alteracao_produto.linha_tabela
            HAVING `tinha_promocao`;"
        );

        foreach ($produtos as $produto) {
            $ultimoValor = current(
                array_filter($produto['logs'], fn(array $log): bool => $log['chave'] === 'valor_custo_produto')
            );
            if ($ultimoValor['valor_anterior'] > $ultimoValor['valor_novo']) {
                continue;
            }

            $precoCorreto = max(
                round(($ultimoValor['valor_anterior'] * (100 - $configuracoes['percentual_desconto'])) / 100, 2),
                1
            );

            $produtoModel = new ProdutoModel();
            $produtoModel->exists = true;
            $produtoModel->id = $produto['id_produto'];
            $produtoModel->valor_custo_produto = $precoCorreto;
            $produtoModel->save();
        }

        DB::commit();
    }
};

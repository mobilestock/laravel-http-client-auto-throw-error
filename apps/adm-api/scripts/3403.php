<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;

require_once __DIR__ . '/../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        echo 'Atualizando entregas sem raio' . PHP_EOL;
        $entregas = DB::cursor(
            "SELECT
                entregas_faturamento_item.id_entrega,
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') AS `id_raio`
            FROM entregas
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
            WHERE entregas.id_cidade > 0
                AND entregas.id_raio IS NULL
                AND JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') IS NOT NULL
            GROUP BY entregas.id;"
        );

        foreach ($entregas as $entrega) {
            $linhasAlteradas = DB::update(
                "UPDATE entregas
                SET entregas.id_raio = :id_raio
                WHERE entregas.id = :id_entrega;",
                [
                    'id_raio' => $entrega['id_raio'],
                    'id_entrega' => $entrega['id_entrega'],
                ]
            );

            if ($linhasAlteradas !== 1) {
                throw new DomainException('Nada foi atualizado na entrega');
            }
        }

        echo 'Entregas atualizadas' . PHP_EOL;
    }
};

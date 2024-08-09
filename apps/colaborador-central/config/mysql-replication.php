<?php

$bancosMonitorados = [env('DB_DATABASE_ADM_API'), env('DB_DATABASE_LOOKPAY'), env('DB_DATABASE_MED')];
$tabelasMonitoradas = array_merge(
    ...array_map(
        fn(string $nomeBanco, array $tabelas) => array_map(fn(string $tabela) => "$nomeBanco.$tabela", $tabelas),
        $bancosMonitorados,
        [['colaboradores', 'usuarios'], ['establishments'], ['usuarios', 'lojas']]
    )
);

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'user' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'slaveId' => 1,
    'databasesOnly' => $bancosMonitorados,
    'tablesOnly' => $tabelasMonitoradas,
    'eventsOnly' => [],
];

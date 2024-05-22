<?php

namespace MobileStock\service;

use App;
use Illuminate\Support\Facades\DB;

class LogsService
{
    public static function consultar(string $select, string $from, string $where): array
    {
        $nomeConexao = App::isProduction() ? 'mysql_read_only' : 'mysql';
        $resultado = DB::connection($nomeConexao)->select(
            "SELECT id,
                data_criacao,
                $select AS dados_json
            FROM $from
            WHERE TRUE AND $where
            ORDER BY id DESC"
        );
        return $resultado;
    }
}

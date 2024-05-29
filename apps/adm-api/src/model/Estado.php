<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $codigouf
 * @property string $nome
 * @property string $uf
 * @property int $regiao
 */
class Estado extends Model
{
    protected $table = 'estados';
    protected $fillable = ['codigouf', 'nome', 'uf', 'regiao'];

    public static function buscaEstados(): array
    {
        $estados = DB::select(
            "SELECT
                estados.id,
                estados.nome,
                estados.uf
            FROM estados;"
        );

        return $estados;
    }
}

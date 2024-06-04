<?php

namespace MobileStock\model;

use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $nome
 * @property string $uf
 */
class Estado extends Model
{
    /**
     * @return Collection<self>
     */
    public static function buscaEstados()
    {
        $estados = self::fromQuery(
            "SELECT
                estados.id,
                estados.nome,
                estados.uf
            FROM estados;"
        );

        return $estados;
    }
}

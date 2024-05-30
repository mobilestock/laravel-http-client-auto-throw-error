<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property int $codigouf
 * @property string $nome
 * @property string $uf
 * @property int $regiao
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

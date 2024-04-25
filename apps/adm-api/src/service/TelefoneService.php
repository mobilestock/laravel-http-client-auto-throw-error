<?php
/*

namespace MobileStock\service;

abstract class TelefoneService 
{
    public static function identificaUfPeloTelefone(\PDO $conn, $telefone)
    {
        $consultaUfPorDDD = $conn->query('SELECT estados_ddd.uf FROM estados_ddd WHERE estados_ddd.ddd = '.substr($telefone,
                0, 2))->fetch(\PDO::FETCH_ASSOC);
        return isset($consultaUfPorDDD['uf']) ? $consultaUfPorDDD['uf'] : null;
    }
}
*/
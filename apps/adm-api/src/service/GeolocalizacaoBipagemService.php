<?php

namespace MobileStock\service;

use MobileStock\helper\GeradorSql;
use MobileStock\model\GeolocalizacaoBipagem;
use PDO;

class GeolocalizacaoBipagemService
{
    public static function salvaRegistro(PDO $conexao, GeolocalizacaoBipagem $dadosLocalizacao): void 
    {
        $geradorSql = new GeradorSql($dadosLocalizacao);
        $sql = $geradorSql->insert();
        $conexao->prepare($sql)->execute($geradorSql->bind);
    }
}
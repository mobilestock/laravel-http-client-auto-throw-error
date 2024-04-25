<?php

namespace MobileStock\service;

use MobileStock\database\Conexao;
use PDO;

class ZoopSellerService
{
    public static function buscarIdColaboradorComCodZoop(PDO $conexao = null, string $cod_zoop)
    {
        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();
        $query =
            "SELECT api_colaboradores.id_colaborador FROM api_colaboradores WHERE api_colaboradores.id_zoop = '" .
            $cod_zoop .
            "'
                  UNION ALL
                  SELECT api_colaboradores_inativos.id_colaborador FROM api_colaboradores_inativos WHERE api_colaboradores_inativos.id_zoop = '" .
            $cod_zoop .
            "'
                  LIMIT 1";
        $resultado = $conexao->query($query);
        $retorno = $resultado->fetch();
        return $retorno['id_colaborador'] ?? false;
    }
}

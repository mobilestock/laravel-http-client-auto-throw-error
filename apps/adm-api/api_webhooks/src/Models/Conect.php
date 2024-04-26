<?php

namespace api_webhooks\Models;

use PDO;

class Conect
{
    static function conexao()
    {   
        $instance = new PDO(
            DATA_LAYER_CONFIG["driver"] . ":host=" . DATA_LAYER_CONFIG["host"] . ";dbname=" . DATA_LAYER_CONFIG["dbname"] . ";port=" . DATA_LAYER_CONFIG["port"],
            DATA_LAYER_CONFIG["username"],
            DATA_LAYER_CONFIG["passwd"],
            DATA_LAYER_CONFIG["options"]
        );
        return $instance;
    }

   /* public function verifica_conteudo_qry($valor){
        if (is_string($valor)) {
            return str_replace(array("'",'"','drop ','insert ', 'create ', 'update ', 'call '), "", strtolower($valor));
        }else return $valor;
    }*/
}
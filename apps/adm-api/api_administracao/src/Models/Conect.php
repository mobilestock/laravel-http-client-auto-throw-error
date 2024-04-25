<?php

namespace api_administracao\Models;

use PDO;

/**
 * @deprecated
 * Utilizar conexão do Database.
 */
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

}
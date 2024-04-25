<?php

namespace api_webhooks\Controller;

use api_webhooks\Models\Request_m;

class Documentos extends Request_m{
   
    public function __construct($rota)
    {  
        parent::__construct();
        $this->rota = $rota;       
    }
   
    public function documentos(){        
        // $arquivo = fopen("documento_".date("YmdHis").".json", "a"); 
        // fwrite($arquivo, $this->json);
        // fclose($arquivo);   
        $this->resposta->send();        
    }
}
?>
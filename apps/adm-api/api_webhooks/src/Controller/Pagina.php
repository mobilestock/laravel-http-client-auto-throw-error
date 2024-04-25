<?php

namespace api_webhooks\Controller;

use api_webhooks\Models\Request_m;

class Pagina extends Request_m{
   
    public function __construct($rota)
    {  
        parent::__construct();
        $this->rota = $rota;       
    }
   
    public function pagina(){   
        $json_transacao  =  json_decode($this->json,false);
        // $arquivo = fopen($json_transacao->type."_".date("YmdHis").".json", "a"); 
        // fwrite($arquivo, $this->json);
        // fclose($arquivo);  
        $this->resposta->send();        
    }
}
?>
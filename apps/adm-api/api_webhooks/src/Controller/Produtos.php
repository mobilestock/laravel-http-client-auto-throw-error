<?php
namespace api_webhooks\Controller;

use api_webhooks\Models\Request_m;

class Produtos extends Request_m{
   
    public function __construct($rota)
    {  
        parent::__construct();
        $this->rota = $rota;       
    }
    
    public function produtos(){     
        
    }
}
?>
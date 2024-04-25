<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Symfony\Component\HttpFoundation\JsonResponse;
use MobileStock\repository\UsuariosRepository;
use Throwable;

class Autentica extends Request_m{   
    private $conexao;
    public function __construct($rota)
    {  
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->rota = $rota;   
        $this->respostaJson = new JsonResponse();
    }

    function validaUsuario(){
        $retorno = [];
        try{            
            $consultaUsuario = new UsuariosRepository();
            $retorno = $consultaUsuario->buscaUsuarioPorId($this->idUsuario); 

            
            $this->respostaJson->setData($retorno)->setStatusCode(200)->send();
        }catch (Throwable $e){
            $this->respostaJson
                ->setData([
                    'error'=>true,
                    "message"=>'Este token nao esta cadastrado em nosso sistema'
                ])
                ->setStatusCode(401)
                ->send();
            die;
        }
    }
}
?>
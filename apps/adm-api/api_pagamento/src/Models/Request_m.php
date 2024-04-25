<?php
namespace api_pagamento\Models;

use Exception;
use MobileStock\helper\RegrasAutenticacao;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated
 * https://github.com/mobilestock/web/issues/2665
 */
class Request_m
{
    protected $request;
    /*protected $resposta; */
    public $json;
    public $respostaJson;
    public $idUsuario;
    public $idCliente;
    public $regime;
    /*public $nome;*/
    /*protected $token;*/
    protected $nivelAcesso;
    /**
     * @deprecated
     * usar $this->resposta
     */
    public $retorno;
    public array $resposta;
    public $codigoRetorno;

    public function __construct()
    {   
       $this->request = Request::createFromGlobals();   
       $this->respostaJson = new JsonResponse();
       $this->json = $this->request->getContent();
       $this->retorno = ['status' => true,
                        'message' => 'sucesso!',
                        'data' => []
                        ];
       $this->codigoRetorno = 200;

       $this->validaNivelAcesso();
       
    } 

    private function validaNivelAcesso(){
        if(empty($this->nivelAcesso)) return;
        try{
            $dados = [];
            $autentica = new RegrasAutenticacao;
            $autentica->setToken(str_replace('"','',$this->request->headers->get('token'))); 
            $autentica->setAuthorization(str_replace('"','',$this->request->headers->get('Auth')));

            $temToken = $this->request->headers->has('token');
            $temAuth = $this->request->headers->has('auth');

            if($temToken) {
                $dados = $autentica->validaToken();
            } 
            if ($temAuth) {
                $dados = $autentica->validaAuthorization();
            }

            if ($dados) {
                $this->idUsuario = $dados['id_usuario'];
                $this->idCliente = $dados['id_colaborador'];
                $this->nivelAcesso = $dados['nivel_acesso'];
                $this->regime = $dados['regime'];
            }elseif($this->nivelAcesso !== "0") {
                throw new Exception("Não foi possivel validar o usuário", 1);                
            }
        }catch (\Throwable $e) {
            $this->retorno = ['status'=>false,'message'=>$e->getMessage(),'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
            die;
        }

    }
}
?>
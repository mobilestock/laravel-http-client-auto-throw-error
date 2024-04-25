<?php
namespace api_meulook\Models;


use Exception;
use MobileStock\database\Conexao;
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
    protected $json;
    protected $respostaJson;
    public $idUsuario;
    public $idCliente;
    public $regime;
    /*public $nome;*/
    /*protected $token;*/
    protected $nivelAcesso = 1;
    /**
     * @deprecated
     */
    public $retorno;
    public array $resposta;
    public $codigoRetorno;
    public $uf;
    public $nome;
    public \PDO $conexao;

    /**
     * @var int
     */
    private $idConsumidorFinal;
    /**
     * @var int
     */
    public $qtdComprasCliente;

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
       $this->qtdComprasCliente = 0;
       $this->validaNivelAcesso();
       $this->conexao = Conexao::criarConexao();
    }

    private function validaNivelAcesso(){
        try{
            $dados = [];
            $autentica = new RegrasAutenticacao;
            $autentica->setToken(str_replace('"','',$this->request->headers->get('token')));
            $autentica->setAuthorization(str_replace('"','',$this->request->headers->get('Auth')));
            $autentica->setMedAuthorization(str_replace('"','',$this->request->headers->get('X-med-auth')));

	        $temToken = $this->request->headers->has('token');
	        $temAuth = $this->request->headers->has('Auth');
	        $temAuthMed = $this->request->headers->has('X-med-auth');

            if($this->nivelAcesso == 0){
                if($temAuth)
                    $this->carregaDados($autentica->validaAuthorization()); 
                return true;
            }
            if($this->nivelAcesso == 1 && $temAuth){
                $this->carregaDados($autentica->validaAuthorization()); 
                return true;
            }
            if ($this->nivelAcesso == 2 && $temToken) {
                $this->carregaDados($autentica->validaToken());
                return true;
            }
            if($this->nivelAcesso == 3 && $temAuthMed){
                $this->carregaDados($autentica->validaMedAuthorization());              
                return true;
            }if($this->nivelAcesso == 4 && ($temToken || $temAuth)) {
                if($temAuth)
                    $this->carregaDados($autentica->validaAuthorization());
                else
                    $this->carregaDados($autentica->validaToken());
                return true;
            }
            if($this->nivelAcesso == 5 && ($temToken && $temAuth)) {
                $this->carregaDados($autentica->validaAuthorization()); 
                $autentica->validaToken();
            }
            throw new Exception("Não foi possivel validar o usuário", 1);
            /*** jose 05-07-2021
	        if ($this->nivelAcesso == 1 || $temAuth) {
              $dados = $autentica->validaAuthorization();
            
              if ($dados !== false && $dados['criado_em'] !== date('Y-m-d')) {

                  $dados['qtd_compras'] = $dados['nivel_acesso'] == 10 ? ColaboradoresRepository::qtdComprasCliente($dados['id_colaborador']) : 0;
                  $dados['criado_em'] = date('Y-m-d');
                  $this->respostaJson->headers->set('auth', RegrasAutenticacao::geraAuthorization($dados));
              }
            }elseif ($this->nivelAcesso == 2 || $temToken) {
                $dados = $autentica->validaToken();
            }

	        elseif ($this->nivelAcesso == 3 || $temAuth) {
                $dados = $autentica->validaMedAuthorization();
                $this->qtdComprasCliente = $dados['qtd_compras_cliente'];
                $this->idConsumidorFinal = $dados['id_consumidor_final'];
                $this->idCliente = $dados['id_cliente'];
            }

	        elseif($this->nivelAcesso == 5 || ($temToken && $temAuth)) {
              $autentica->validaAuthorization();
              $dados = $autentica->validaToken();
          }

            if ($dados) {
                $this->idUsuario = $dados['id_usuario'];
                $this->idCliente = $dados['id_colaborador'];
                $this->nivelAcesso = $dados['nivel_acesso'];
                $this->regime = $dados['regime'];
                $this->uf = $dados['uf'];
                $this->nome = $dados['nome'];

                $this->qtdComprasCliente = isset($dados['qtd_compras']) ? $dados['qtd_compras'] : 0;
            }elseif($this->nivelAcesso !== "0") {
                throw new Exception("Não foi possivel validar o usuário", 1);
            }*/
        }catch (\Throwable $e) {
            $this->retorno = ['status'=>false,'message'=>$e->getMessage(),'data' => []];
            $this->codigoRetorno = 401;
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
            die;
        }

    }
    private function carregaDados(array $dados):void{

     
        $this->idUsuario = $dados['id_usuario'];        
        $this->nivelAcesso = $dados['nivel_acesso'];        
        $this->idCliente = $dados['id_colaborador'];        
        $this->regime = $dados['regime'];        
        $this->nome = $dados['nome'];
        $this->uf = $dados['uf'];
        $this->qtdComprasCliente = isset($dados['qtd_compras']) ? $dados['qtd_compras'] : 0;
        $this->idConsumidorFinal = $dados['id_consumidor_final']??null;
    }

}
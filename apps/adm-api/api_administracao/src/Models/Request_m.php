<?php
namespace api_administracao\Models;

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
    public array $resposta;
    protected $json;
    protected $respostaJson;
    public $idUsuario;
    public $idCliente;
    public $nome;
    protected $token;
    protected $nivelAcesso;
    /**
     * @deprecated
     * usar $this->resposta
     */
    public $retorno;
    public $codigoRetorno;
    protected $conexao;

	const SEM_AUTENTICACAO = '0';
	const AUTENTICACAO = '2';
	const AUTENTICACAO_VALIDA = '6';

    public function __construct()
    {   
       $this->request = Request::createFromGlobals();   
       $this->respostaJson = new JsonResponse();
       $this->json = $this->request->getContent();
       $this->validaNivelAcesso();
       $this->retorno = ['status' => true,
                        'message' => 'sucesso!',
                        'data' => []
                        ];
       $this->codigoRetorno = 200;
       $this->nivelAcesso = $this->nivelAcesso ? $this->nivelAcesso : 2;
       $this->conexao = Conexao::criarConexao();
    }

    private function validaNivelAcesso()
    {
        try {
            $dados = [];
            $autentica = new RegrasAutenticacao;
            $autentica->setToken(str_replace('"', '', $this->request->headers->get('token')));
            $autentica->setAuthorization(str_replace('"', '', $this->request->headers->get('Auth')));
            switch ($this->nivelAcesso) {
                case 1:
                    $dados = $autentica->validaAuthorization();
                    break;
                case 2:
                    $dados = $autentica->validaToken();
                    break;
                case 5:
                    $autentica->validaAuthorization();
                    $dados = $autentica->validaToken();
                    break;
                case 6:
                    $dados = $autentica->validaToken();

                    if (in_array($dados["id_usuario"], [3266, 356, 526, 7224, 18, 19])) {
                        break;
                    } else {
                        throw new Exception("Não foi possivel validar o usuário");
                    }

                    break;
                default:
                    $dados = $autentica->validaToken();
                    break;
            }
            if ($dados) {
                $this->idUsuario = $dados['id_usuario'];
                $this->idCliente = $dados['id_colaborador'];
                $this->nivelAcesso = $dados['nivel_acesso'];
                $this->regime = $dados['regime'];
                $this->uf = $dados['uf'];
                $this->nome = $dados["nome"];
            } elseif ($this->nivelAcesso !== "0") {
                throw new \Exception("Não foi possivel validar o usuário", 1);
            }
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            if ($this->nivelAcesso == 6) {
                $this->codigoRetorno = 400;
            } else {
                $this->codigoRetorno = 401;
            }
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
            die;
        }
    }
}

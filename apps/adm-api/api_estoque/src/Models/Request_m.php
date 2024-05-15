<?php
namespace api_estoque\Models;

use Exception;
use MobileStock\database\Conexao;
use MobileStock\repository\UsuariosRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated
 */
class Request_m
{
    protected $request;
    public array $resposta;
    /**
     * @deprecated
     * usar $this->resposta
     */
    protected array $retorno;
    protected $json;
    protected $respostaJson;
    public $idUsuario;
    public $nivelAcesso;
    public $idColaborador;
    public $categoriaDoUsuario;
    public $dadosDoUsuario;
    protected $token;
    public $codigoRetorno;

    const AUTENTICACAO_TOKEN = '1';
    const AUTENTICACAO_COMPLETA = '2';
    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        $this->respostaJson = new JsonResponse();
        $this->json = $this->request->getContent();
        $this->retorno = [
            'message' => 'sucesso!',
            'data' => [],
        ];
        $this->codigoRetorno = 200;
        $this->validaAutorizacao();
    }
    protected function verificaToken()
    {
        $token = str_replace('"', '', $this->request->headers->get('token'));

        if (!mb_strlen($token)) {
            throw new Exception('Token inválido', 401);
        }

        $consultaUsuario = new UsuariosRepository();
        $this->idUsuario = $consultaUsuario->existeTokenMaquina($token);

        if (!$this->idUsuario) {
            throw new Exception('Este token não esta cadastrado para um usuario', 401);
        }
    }

    protected function validaAutorizacao()
    {
        try {
            $this->categoriaDoUsuario = 'INDEFINIDO';
            switch ($this->nivelAcesso) {
                case '1': // AUTENTICACAO_TOKEN
                    $this->verificaToken();
                    break;
                case '2': // AUTENTICACAO_COMPLETA
                    $this->verificaToken();
                    break;
            }

            if (isset($this->idUsuario)) {
                $conexao = Conexao::criarConexao();
                [
                    'dados_usuario' => $this->dadosDoUsuario,
                    'cateoria_usuario' => $this->categoriaDoUsuario,
                    'id_colaborador' => $this->idColaborador,
                ] = UsuariosRepository::buscaCategoriaUsuario($conexao, $this->idUsuario);
            }
        } catch (\Throwable $e) {
            $this->codigoRetorno = 401;
            $this->retorno = [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
}
?>

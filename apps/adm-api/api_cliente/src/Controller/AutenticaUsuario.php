<?php

namespace api_cliente\Controller;

use api_cliente\Models\Conect;
use api_cliente\Models\Request_m;
use Error;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use MobileStock\helper\Globals;
use MobileStock\helper\RegrasAutenticacao;
use MobileStock\helper\Validador;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\Email;
use MobileStock\service\MessageService;
use MobileStock\service\UsuarioService;

class AutenticaUsuario extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
        $this->conexao = Conect::conexao();
    }

    // public function validaUsuario(){

    //     try{
    //         $this->conexao->beginTransaction();
    //         Validador::validar(['json' => $this->json], [
    //             'json' => [Validador::JSON]
    //         ]);
    //         $dadosJson = json_decode($this->json, true);
    //         Validador::validar($dadosJson, [
    //             'username' => [Validador::OBRIGATORIO],
    //             'password'=> [Validador::OBRIGATORIO],
    //         ]);

    //         $usuario = UsuarioService::logaUsuario($this->conexao,$dadosJson['username'],$dadosJson['password']);
    //         if(!$usuario){
    //             throw new Error("Usuário ou senha incorreta.", 401);
    //         }
    //         $usuario['token'] = RegrasAutenticacao::geraTokenPadrao($this->conexao,$usuario['id']);

    //         $this->retorno['data']['token'] = $usuario['token'];
    //         $this->retorno['data']['Authorization'] = RegrasAutenticacao::geraAuthorization(
    //             $usuario['id'],
    //             $usuario['id_colaborador'],
    //             $usuario['nivel_acesso'],
    //             $usuario['permissao'],
    //             $usuario['nome'],
    //             $usuario['uf'],
    //             $usuario['regime'],
    //         );
    //         $this->retorno['data']['refID'] = Globals::createRefID($usuario['id_colaborador']);
    //         $this->conexao->commit();
    //     } catch (\Throwable $e) {
    //         $this->conexao->rollBack();
    //         $this->retorno = [
    //             'status'=> false,
    //             'message'=> $e->getMessage(),
    //             'data' => []
    //         ];
    //         $this->codigoRetorno = $e->getCode() ? $e->getCode() : 400;
    //     }finally{
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //         die;
    //     }
    // }

    public function filtraUsuarioLogin()
    {
        try {
            $dadosQuery = $this->request->query->all();
            Validador::validar($dadosQuery, [
                'telefone' => [Validador::OBRIGATORIO, Validador::TELEFONE],
                'origem' => [Validador::OBRIGATORIO, Validador::ENUM('MS', 'ML', 'LP', 'APP_ENTREGA', 'APP_INTERNO')],
            ]);
            $telefone = preg_replace('/[^0-9]/i', '', $dadosQuery['telefone']);
            $usuarios = ColaboradoresService::consultaUsuarioLogin($this->conexao, $telefone, $dadosQuery['origem']);
            $this->resposta = $usuarios;
        } catch (\Throwable $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = $e->getCode() > 0 ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function validaAutenticacaoUsuario()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_colaborador' => [Validador::OBRIGATORIO],
                'senha' => [],
                'origem' => [Validador::ENUM('ADM', 'MS', 'ML', 'LP', 'APP_ENTREGA', 'APP_INTERNO')],
            ]);

            $usuario = UsuarioService::validaAutenticacaoUsuariosColaborador(
                $this->conexao,
                $dadosJson['origem'],
                $dadosJson['id_colaborador'],
                !empty($dadosJson['senha']) ? $dadosJson['senha'] : null
            );

            if (empty($usuario)) {
                throw new \InvalidArgumentException('Credenciais inválidas');
            }

            $usuario['token'] = RegrasAutenticacao::geraTokenPadrao($this->conexao, $usuario['id']);

            $this->resposta['token'] = $usuario['token'];
            $this->resposta['Authorization'] = RegrasAutenticacao::geraAuthorization(
                $usuario['id'],
                $usuario['id_colaborador'],
                $usuario['nivel_acesso'],
                $usuario['permissao'],
                $usuario['nome'],
                $usuario['uf'],
                $usuario['regime']
            );
            $this->resposta['refID'] = Globals::createRefID($usuario['id_colaborador']);
            $this->resposta['tipoAutenticacao'] = $usuario['tipo_autenticacao'];
        } catch (\Throwable $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function validaUsuarioPorTokenTemporario()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'token' => [Validador::OBRIGATORIO],
                'origem' => [Validador::ENUM('MS', 'ML', 'LP', 'APP_ENTREGA', 'APP_INTERNO')],
            ]);

            $usuario = UsuarioService::buscaDadosUsuarioParaAutenticacao(
                $this->conexao,
                $dadosJson['origem'],
                'TOKEN_TEMPORARIO',
                $dadosJson['token']
            );
            if (!$usuario) {
                throw new Error('Usuário ou senha incorreta.', 401);
            }
            $this->resposta['token'] = RegrasAutenticacao::geraTokenPadrao($this->conexao, $usuario['id']);

            $this->resposta['Authorization'] = RegrasAutenticacao::geraAuthorization(
                $usuario['id'],
                $usuario['id_colaborador'],
                $usuario['nivel_acesso'],
                $usuario['permissao'],
                $usuario['nome'],
                $usuario['uf'],
                $usuario['regime']
            );
            $this->resposta['refID'] = Globals::createRefID($usuario['id_colaborador']);
            $this->resposta['tipoAutenticacao'] = $usuario['tipo_autenticacao'];
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function autenticaMed()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_revendedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'app_auth_token' => [Validador::OBRIGATORIO],
        ]);
        if ($dadosJson['app_auth_token'] !== $_ENV['MED_AUTH_TOKEN']) {
            throw new InvalidArgumentException('Não autorizado');
        }

        $dadosColaborador = ColaboradoresService::buscaCadastroColaborador($dadosJson['id_revendedor']);
        $retorno['token'] = RegrasAutenticacao::geraTokenPadrao(DB::getPdo(), $dadosColaborador['id_usuario']);
        $retorno['auth'] = RegrasAutenticacao::geraAuthorization(
            $dadosColaborador['id_usuario'],
            $dadosColaborador['id_colaborador'],
            $dadosColaborador['nivel_acesso'],
            implode(',', $dadosColaborador['permissao']),
            $dadosColaborador['nome'],
            $dadosColaborador['uf'],
            $dadosColaborador['regime']
        );

        return $retorno;
    }

    public function enviarLinkRedefinicao()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'origem' => [Validador::OBRIGATORIO, Validador::ENUM('LP', 'MS', 'ML')],
            ]);
            $dadosColaborador = UsuarioService::buscaDadosUsuarioParaAutenticacao(
                $this->conexao,
                $dadosJson['origem'],
                'ID_COLABORADOR',
                $dadosJson['id_colaborador']
            );
            if (empty($dadosColaborador['telefone']) && empty($dadosColaborador['email'])) {
                throw new \InvalidArgumentException('Essa conta não possui telefone ou e-mail cadastrado');
            }
            $token = RegrasAutenticacao::geraTokenTemporario($this->conexao, $dadosColaborador['id']);

            $link = '';
            $plataforma = '';
            switch ($dadosJson['origem']) {
                case 'LP':
                    $link = $_ENV['URL_LOOKPAY'] . "?token_redefinicao_senha=$token";
                    $plataforma = 'Look Pay';
                    break;
                case 'MS':
                    $link = $_ENV['URL_AREA_CLIENTE'] . "redefinir_senha/$token";
                    $plataforma = 'Mobile Stock';
                    break;
                case 'ML':
                    $link = $_ENV['URL_MEULOOK'] . "redefine-senha/$token";
                    $plataforma = 'Meulook';
                    break;
            }

            $mensagemAdicional = '';
            if (!empty($dadosColaborador['telefone'])) {
                $mensagemAdicional .= ' para o seu WhatsApp';
                $servicoMensageria = new MessageService();
                $servicoMensageria->sendMessageWhatsApp(
                    $dadosColaborador['telefone'],
                    "*Uma alteração de senha foi solicitada para sua conta no $plataforma.*" .
                        PHP_EOL .
                        'Se foi você, use o seguinte link para redefinir sua senha: ' .
                        $link
                );
            }
            if (!empty($dadosColaborador['email'])) {
                if ($mensagemAdicional !== '') {
                    $mensagemAdicional .= ' e';
                }
                $mensagemAdicional .= ' para o seu e-mail';
                $corpo = 'Não responder a esse e-mail. O seu link para redefinir a senha é: ' . $link;
                $envioDeEmail = new Email("$plataforma | Redefinir sua senha");
                $envioDeEmail->enviar(
                    $dadosColaborador['email'],
                    $dadosColaborador['email'],
                    "Redefinir sua senha $plataforma",
                    $corpo,
                    $corpo
                );
            }
            $this->resposta['message'] = 'O link para redefinir sua senha foi enviado' . $mensagemAdicional;
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

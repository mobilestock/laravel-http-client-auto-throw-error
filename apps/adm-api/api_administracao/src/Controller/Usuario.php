<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\Cadastros\CadastrosService;
use MobileStock\service\UsuarioService;

class Usuario extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::SEM_AUTENTICACAO;
        parent::__construct();
    }

    public function buscarNome($param)
    {
        Validador::validar($param, [
            'id' => [Validador::OBRIGATORIO],
        ]);
        $data = CadastrosService::buscaNome($this->conexao, $param['id']);
        $this->respostaJson
            ->setData(['status' => true, 'message' => 'Usuarios adicionado com sucesso!', 'data' => $data])
            ->setStatusCode(201)
            ->send();
    }
    public function verify_account()
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
                'regime' => [Validador::OBRIGATORIO],
                'cpf' => [Validador::OBRIGATORIO],
                'email' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            if ($resposta = CadastrosService::existeApiColaborador($this->conexao, $cpf)) {
                $this->respostaJson
                    ->setData([
                        'status' => false,
                        'message' =>
                            'O usuario:' .
                            ($resposta['first_name'] ? $resposta['first_name'] : $resposta['id_colaborador']) .
                            ', está usando este CPF',
                        'data' => 'Verificação Falhou',
                    ])
                    ->setStatusCode(200)
                    ->send();
                die();
            }
            if (!CadastrosService::existeEmail($this->conexao, $email)) {
                $this->respostaJson
                    ->setData([
                        'status' => false,
                        'message' =>
                            'Este E-mail já existe em nosso sistema! Caso você tenha conta Mobile Stock basta logar com as mesmas credenciais',
                        'data' => 'Verificação Falhou',
                    ])
                    ->setStatusCode(200)
                    ->send();
                die();
            }
            if ($resposta = CadastrosService::existeUser($this->conexao, $cpf)) {
                $this->respostaJson
                    ->setData([
                        'status' => false,
                        'message' =>
                            'Este CPF já existe em nosso sistema com o Nome:' .
                            $resposta['nome'] .
                            '! Caso você tenha conta Mobile Stock basta logar com as mesmas credenciais',
                        'data' => 'Verificação Falhou',
                    ])
                    ->setStatusCode(200)
                    ->send();
                die();
            }
            if ($resposta = CadastrosService::existeColaborador($this->conexao, $cpf)) {
                $this->respostaJson
                    ->setData([
                        'status' => false,
                        'message' =>
                            'O usuario:' .
                            ($resposta['nome'] ? $resposta['nome'] : $resposta['razao_social']) .
                            ', está usando este CPF',
                        'data' => 'Verificação Falhou',
                    ])
                    ->setStatusCode(200)
                    ->send();
                die();
            }
            if ($regime == 1) {
                Validador::validar($dadosJson, [
                    'cnpj' => [Validador::OBRIGATORIO],
                ]);
                if ($resposta = CadastrosService::existeUser($this->conexao, $cnpj)) {
                    $this->respostaJson
                        ->setData([
                            'status' => false,
                            'message' =>
                                'Este CNPJ já existe em nosso sistema com o Nome:' .
                                $resposta['nome'] .
                                '! Caso você tenha conta Mobile Stock basta logar com as mesmas credenciais',
                            'data' => 'Verificação Falhou',
                        ])
                        ->setStatusCode(200)
                        ->send();
                    die();
                }
                if ($resposta = CadastrosService::existeApiColaborador($this->conexao, $cnpj)) {
                    $this->respostaJson
                        ->setData([
                            'status' => false,
                            'message' =>
                                'O usuario:' .
                                ($resposta['first_name'] ? $resposta['first_name'] : $resposta['id_colaborador']) .
                                ', está usando este CNPJ',
                            'data' => 'Verificação Falhou',
                        ])
                        ->setStatusCode(200)
                        ->send();
                    die();
                }
                if ($resposta = CadastrosService::existeColaborador($this->conexao, $cnpj)) {
                    $this->respostaJson
                        ->setData([
                            'status' => false,
                            'message' =>
                                'O usuario:' .
                                ($resposta['nome'] ? $resposta['nome'] : $resposta['razao_social']) .
                                ', está usando este CNPJ',
                            'data' => 'Verificação Falhou',
                        ])
                        ->setStatusCode(200)
                        ->send();
                    die();
                }
            }
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'OK', 'data' => 'OK'])
                ->setStatusCode(200)
                ->send();
        } catch (\Throwable $e) {
            $this->respostaJson
                ->setData(['status' => true, 'message' => $e->getMessage(), 'data' => 'ERRO!'])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function novaSenhaTemporaria()
    {
        try {
            $this->conexao->beginTransaction();
            $permissoesUsuario = ColaboradoresRepository::buscaPermissaoUsuario($this->conexao, $this->idCliente);
            if (!in_array('INTERNO', $permissoesUsuario)) {
                throw new Exception('Não possui permissão para realizar essa operação');
            }

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'senha' => [Validador::OBRIGATORIO, Validador::TAMANHO_MINIMO(10)],
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            CadastrosService::cadastraSenhaTemporaria(
                $this->conexao,
                $dadosJson['id_colaborador'],
                $dadosJson['senha']
            );
            $this->conexao->commit();
            $this->retorno['message'] = 'Senha temporária cadastrada com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['data'] = null;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaPermissoes(array $data)
    {
        try {
            Validador::validar($data, [
                'id_colaborador' => [Validador::NUMERO],
            ]);
            if ($data['id_colaborador']) {
                $this->retorno['data']['permissoes_usuario'] = UsuarioService::buscaPermissaoColaborador(
                    $this->conexao,
                    $data['id_colaborador']
                );
            }
            $this->retorno['data']['todas_permissoes'] = UsuarioService::buscaPermissoes($this->conexao);
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    // Nesta query, não deve ser feita nenhuma alteração que envolva o endereço do cliente
    // Pois o endereço do cliente é alterado em outro lugar. Os campos complemento e ponto_de_referencia
    // são alterados pois não mudam a geolocalização do cliente.
    public function editaUsuario()
    {
        DB::beginTransaction();

        $dadosJson = \Illuminate\Support\Facades\Request::all();
        $dadosJson['telefone'] = \Illuminate\Support\Facades\Request::telefone();

        Validador::validar($dadosJson, [
            'senha' => [],
            'cpf' => [Validador::SE(VALIDADOR::OBRIGATORIO, Validador::CPF)],
            'cnpj' => [Validador::SE(Validador::OBRIGATORIO, Validador::CNPJ)],
            'complemento' => [Validador::SE(Validador::OBRIGATORIO, Validador::SANIZAR)],
            'ponto_de_referencia' => [Validador::SE(Validador::OBRIGATORIO, Validador::SANIZAR)],
            'email' => [Validador::SE(Validador::OBRIGATORIO, Validador::EMAIL)],
            'colaborador' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            'regime' => [Validador::SE(Validador::OBRIGATORIO, Validador::ENUM(1, 2, 3))],
            'id_usuario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'senha_alterada' => [Validador::BOOLEANO],
        ]);

        UsuarioService::atualizaUsuario($dadosJson);
        DB::commit();
    }
}

?>

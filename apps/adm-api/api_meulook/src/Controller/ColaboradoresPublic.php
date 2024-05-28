<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Illuminate\Support\Arr;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\RegrasAutenticacao;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\repository\UsuariosRepository;
use MobileStock\service\AvaliacaoTipoFreteService;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\InfluencersOficiaisLinksService;
use MobileStock\service\MessageService;
use MobileStock\service\ReputacaoFornecedoresService;
use MobileStock\service\UsuarioService;
use PDO;

class ColaboradoresPublic extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }

    // public function cadastro()
    // {
    //     try {
    //         $this->conexao->beginTransaction();

    //         Validador::validar(['json' => $this->json], [
    //             'json' => [Validador::JSON]
    //         ]);

    //         $dadosJson = json_decode($this->json, true);

    //         Validador::validar($dadosJson, [
    //             'nome' => [Validador::OBRIGATORIO, Validador::SANIZAR],
    //             'telefone' => [Validador::OBRIGATORIO, Validador::TELEFONE],
    //             'cidade' => [Validador::OBRIGATORIO, Validador::NUMERO]
    //         ]);

    //         // ColaboradoresRepository::validaNomeUsuarioMeuLook($dadosJson['usuario']);
    //         // if (ColaboradoresRepository::existeUsuarioMeuLook($this->conexao, $dadosJson['usuario'])) {
    //         //     throw new Exception('Nome de usuário já está sendo utilizado!');
    //         // }

    //         // if (ColaboradoresRepository::verificaCadastroExistente($this->conexao, $dadosJson['email'])) {
    //         //     throw new Exception('E-mail já está sendo utilizado!');
    //         // }
    //         $dadosJson['telefone'] = preg_replace('/[^0-9]/', '', $dadosJson['telefone']);
    //         if (mb_strpos($dadosJson["telefone"], '0') === 0) {
    //             throw new \Exception('O número de telefone não pode começar com 0');
    //         }

    //         if(ColaboradoresRepository::existeTelefoneCadastrado($this->conexao, $dadosJson['telefone'])) {
    //             throw new \Exception('Telefone já está sendo utilizado');
    //         }

    //         // $dadosJson['id_ponto_retirada_padrao'] = $dadosJson['id_ponto_retirada_padrao'] ?? 0;

    //         $infoCidade = IBGEService::buscarInfoCidade($this->conexao, $dadosJson['cidade']);

    //         if (empty($infoCidade)) {
    //             throw new \InvalidArgumentException('Cidade inválida');
    //         }

    //         $dadosJson['estado'] = $infoCidade['uf'];
    //         $dadosJson['cidade'] = $infoCidade['nome'];
    //         $dadosJson['id_cidade'] = $infoCidade['id'];

    //         if (mb_strlen($dadosJson['latitude'] ?? '') > 1 || mb_strlen($dadosJson['longitude'] ?? '') > 1) {
    //             Validador::validar($dadosJson, [
    //                 'latitude' => [Validador::LATITUDE],
    //                 'longitude' => [Validador::LONGITUDE]
    //             ]);

    //             $dadosJson['latitude'] = $dadosJson['latitude'];
    //             $dadosJson['longitude'] = $dadosJson['longitude'];
    //         } else {
    //             $dadosJson['latitude'] = $infoCidade['latitude'];
    //             $dadosJson['longitude'] = $infoCidade['longitude'];
    //         }

    //         if (!$dadosJson['latitude'] && !$dadosJson['longitude']) {
    //             $infoCidadeGoogle = IBGEService::buscaLatitudeLongitudePorCidadeApiGoogle("{$infoCidade['nome']}_{$infoCidade['uf']}");
    //             $dadosJson['latitude'] = $infoCidadeGoogle['latitude'];
    //             $dadosJson['longitude'] = $infoCidadeGoogle['longitude'];
    //         }

    //         try {
    //             Validador::validar($dadosJson, [
    //                 'latitude' => [Validador::LATITUDE],
    //                 'longitude' => [Validador::LONGITUDE]
    //             ]);
    //         } catch (ValidacaoException $ex) {

    //             throw new \InvalidArgumentException('Essa cidade não está disponível para cadastro meu look');
    //         }

    //         // $dadosJson['id_ponto_retirada_padrao'] = IBGEService::buscaPontoRetiradaMaisPróximo();

    //         $colaborador = new ColaboradoresRepository();
    //         $colaborador->razao_social = $dadosJson['nome'];
    //         $colaborador->telefone = $dadosJson['telefone'];
    //         $colaborador->latitude = $dadosJson['latitude'];
    //         $colaborador->longitude = $dadosJson['longitude'];
    //         $colaborador->uf = $dadosJson['estado'];
    //         $colaborador->cidade = $dadosJson['cidade'];
    //         $colaborador->email = '';
    //         // $colaborador->id_ponto_retirada_padrao = $dadosJson['id_ponto_retirada_padrao'];
    //         $colaborador->id_cidade = $dadosJson['id_cidade'];
    //         $colaborador->regime = 3;
    //         $colaborador->salvaMeuLook($this->conexao);
    //         $colaborador->usuario_meulook = ColaboradoresRepository::buscaNomeUsuarioMeuLook($this->conexao, $colaborador->id)['usuario_meulook'];

    //         CadastrosService::criaUsuario($this->conexao, [
    //             'id' => $colaborador->id,
    //             'nome' => $colaborador->usuario_meulook,
    //             'senha' => null,
    //             'cpf' => '',
    //             'email' => $colaborador->email,
    //             'telefone' => $dadosJson['telefone'],
    //             'cnpj' => '',
    //             'permissao' => false
    //         ]);

    //         $usuario = UsuarioService::tokenLogin($this->conexao, $colaborador->id, null);
    //         if (!$usuario) {
    //             throw new InvalidArgumentException("Usuario ou senha estão incorretos.", 401);
    //         }
    //         $usuario['token'] = RegrasAutenticacao::geraTokenPadrao($this->conexao, $usuario['id']);
    //         // $testeCompras = ColaboradoresRepository::qtdComprasCliente($usuario['id_colaborador']);
    //         $dadosUsuario = [
    //             'id_usuario' => $usuario['id'],
    //             'nivel_acesso' => $usuario['nivel_acesso'],
    //             'permissao' => $usuario['permissao'],
    //             'id_colaborador' => $usuario['id_colaborador'],
    //             'regime' => $usuario['regime'],
    //             'uf' => $usuario['uf'],
    //             'nome' => $usuario['nome'],
    //             'foto_perfil' => $usuario['foto_perfil'],
    //             'criado_em' => date('Y-m-d')
    //         ];

    //         try {
    //             ColaboradoresService::enviaAtalhoLogin($dadosJson["telefone"], $dadosJson["nome"]);
    //         } catch (\Throwable $exception) {
    //             NotificacaoRepository::enviarSemValidacaoDeErro([
    //                 'colaboradores' => [ 1 ],
    //                 'mensagem' => "Erro ao enviar notificação de cadastro whatsapp ($this->idUsuario) {$dadosJson["telefone"]}: " . $exception->getMessage(),
    //                 'tipoMensagem' => 'Z',
    //                 'titulo' => 'Erro notificação cadastro',
    //                 'imagem' => ''
    //             ], $this->conexao);
    //         }

    //         $this->conexao->commit();
    //         $this->status = 200;
    //         $this->retorno['data']['Authorization'] = JWT::encode($dadosUsuario, Globals::JWT_KEY);
    //         $this->retorno['data']['tipoAutenticacao'] = 'nenhum';
    //         $this->retorno['data']['token'] = $usuario['token'];
    //     } catch (\PDOException $pdoException) {
    //         $this->conexao->rollBack();
    //         $this->status = 500;
    //         $this->retorno['status'] = false;

    //         $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //     } catch (\Throwable $ex) {
    //         $this->conexao->rollback();
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    //         $this->status = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    //     public function recuperaSenha()
    //     {
    //         try {
    //             $this->conexao->beginTransaction();

    //             Validador::validar(['json' => $this->json], [
    //                 'json' => [Validador::JSON]
    //             ]);

    //             $dadosJson = json_decode($this->json, true);
    //             Validador::validar(["id_colaborador" => $dadosJson["id_colaborador"]], [
    //                 "id_colaborador" => [Validador::OBRIGATORIO, Validador::NUMERO]
    //             ]);

    //             if ($dadosJson['email'] ?? '') {
    //                 $email = $dadosJson['email'];
    //                 $user = UsuarioService::buscaUsuario($this->conexao, '', '', $dadosJson['id_colaborador']);
    //                 $usuario['token'] = RegrasAutenticacao::geraTokenTemporario($this->conexao, $user['id']);
    //                 $telefone = UsuarioService::buscaTelefoneUsuario($this->conexao, $user['id']);
    //                 $link = $_ENV['URL_MEULOOK'] . 'redefine-senha/' . $usuario['token'];
    //             } else if ($dadosJson['telefone'] ?? '') {
    //                 $dadosJson['telefone'] = preg_replace('/[^0-9]/', '', $dadosJson['telefone']);
    //                 $usuario = UsuarioService::buscaUsuario($this->conexao, '', '', $dadosJson['id_colaborador']);

    //                 if ($usuario['tipo_autenticacao'] == 'nenhuma') {
    //                     $this->retorno['data'] = RegrasAutenticacao::geraTokenTemporario($this->conexao, $usuario['id']);
    //                     $this->retorno['message'] = 'Conta sem senha, efetuando login automático';
    //                     $this->status = 200;
    //                     $this->conexao->commit();
    //                     return;
    //                 }

    //                 $email = $usuario['email'];
    //                 $usuario['token'] = RegrasAutenticacao::geraTokenTemporario($this->conexao, $usuario['id']);
    //                 $telefone = UsuarioService::buscaTelefoneUsuario($this->conexao, $usuario['id']);
    //                 $link = $_ENV['URL_MEULOOK'] . 'redefine-senha/' . $usuario['token'];
    //             } else {
    //                 throw new \InvalidArgumentException('Não foi possivel achar seus dados para a recuperação de senha');
    //             }

    //             $corpo = 'Nao responder a esse e-mail. O seu link para redefinir a senha é:' . $link;

    //             $envioDeEmail = new Email("Meulook | Redefinir sua senha");
    //             $envioDeEmail->enviar(
    //                 $email,
    //                 $email,
    //                 'Redefinir sua senha Meulook.',
    //                 $corpo,
    //                 $corpo
    //               );

    //             $emailArray = explode('@', $email);
    //             $metadeInicialEmail = substr($emailArray[0], 0, floor(strlen($emailArray[0]) / 2));
    //             $metadeFinalEmail = str_repeat("*", strlen(substr($emailArray[0], floor(strlen($emailArray[0]) / 2))));
    //             $email = $metadeInicialEmail . $metadeFinalEmail . '@'. $emailArray[1];

    //             $servicoMensageria = new MessageService();
    //             $servicoMensageria->sendMessageWhatsApp(
    //                 $telefone,
    // '*Uma alteração de senha foi solicitada para sua conta no meulook.*

    // Se foi você, use o seguinte link para redefinir sua senha: ' .
    // $link
    //             );

    //             $this->conexao->commit();
    //             $this->status = 200;
    //             $this->retorno['message'] = 'Foi enviado um link para a recuperação de senha para seu WhatsApp e também para seu e-mail: ' . $email;
    //         } catch (\PDOException $pdoException) {
    //             $this->conexao->rollBack();
    //             $this->status = 500;
    //             $this->retorno['status'] = false;

    //             $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //         } catch (\Throwable $ex) {
    //             $this->conexao->rollback();
    //             $this->retorno['status'] = false;
    //             $this->retorno['message'] = $ex->getMessage();
    //             $this->status = 400;
    //         } finally {
    //             $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //             exit;
    //         }
    //     }

    public function recuperarLogin($dados)
    {
        try {
            $idSolicitado = $dados['id'];
            if (!isset($idSolicitado)) {
                throw new \InvalidArgumentException('ID não informado.');
            }
            $this->conexao->beginTransaction();
            $dadosUsuario = $this->conexao
                ->query(
                    "SELECT colaboradores.telefone, usuarios.id FROM colaboradores JOIN usuarios ON usuarios.id_colaborador = colaboradores.id WHERE colaboradores.id = $idSolicitado"
                )
                ->fetch();
            $token = RegrasAutenticacao::geraTokenTemporario($this->conexao, $dadosUsuario['id']);
            $link = $_ENV['URL_MEULOOK'] . 'entrar/' . $token;
            $msgService = new MessageService();
            $msgService->sendMessageWhatsApp(
                $dadosUsuario['telefone'],
                'Olá! O ponto *' .
                    trim($this->nome) .
                    '* solicitou um link de recuperação de conta MeuLook pra você. Este é o seu link de acesso: ' .
                    $link
            );
            $this->conexao->commit();
            $this->status = 200;
            $this->retorno['message'] = 'Mensagem enviada!';
        } catch (\PDOException $pdoException) {
            $this->conexao->rollBack();
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->conexao->rollback();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    public function buscaPerfilMeuLook(string $usuarioMeuLook)
    {
        $informacoesPerfil = ColaboradoresService::buscaPerfilMeuLook($usuarioMeuLook);

        return $informacoesPerfil;
    }

    public function buscaUsuarioPorID(int $idColaborador, PDO $conexao)
    {
        $usuario = ColaboradoresService::buscaNomeUsuarioMeulookPorId($conexao, $idColaborador);

        return $usuario;
    }

    public function buscaAutocompleteFiltro()
    {
        try {
            $dadosGet = $this->request->query->all();
            Validador::validar($dadosGet, [
                'pesquisa' => [Validador::NAO_NULO],
            ]);

            $this->retorno['data']['colaboradores'] = ColaboradoresRepository::consultaColaboradoresAutocomplete(
                $this->conexao,
                $dadosGet['pesquisa']
            );
            $this->retorno['message'] = 'Colaboradores buscados com sucesso!';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();

            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    // public function buscaListaSeguidores(array $dados)
    // {
    //     try {
    //         $colaboradoresRepository = new ColaboradoresRepository();
    //         $this->retorno['data'] = $colaboradoresRepository->consultaSeguidores($this->conexao, $this->idCliente, $dados['usuarioMeuLook']);
    //         $this->retorno['message'] = 'Seguidores buscados com sucesso!';
    //         $this->status = 200;
    //     } catch (\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //     } catch (\Throwable $ex) {
    //         $this->status = 400;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    // public function buscaListaSeguindo(array $dados)
    // {
    //     try {
    //         $colaboradoresRepository = new ColaboradoresRepository();
    //         $this->retorno['data'] = $colaboradoresRepository->consultaSeguindo($this->conexao, $this->idCliente, $dados['usuarioMeuLook']);
    //         $this->retorno['message'] = 'Seguidores buscados com sucesso!';
    //         $this->status = 200;
    //     } catch (\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //     } catch (\Throwable $ex) {
    //         $this->status = 400;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function buscaDadosReputacao(int $idColaborador)
    {
        $dados = ColaboradoresService::buscaReputacao($idColaborador);
        return $dados;
    }
    //    /**
    //     * @deprecated
    //     *
    //     * Função não será mais utilizada.
    //     */
    //    public function notificaColeta() {
    //        try {
    //            $auth = json_decode($this->json, true);
    //            if ($auth != "JpJNXeZQX7eewJCEDtvj8z4s") Throw new \Exception("Autenticação inválida.");
    //            $this->retorno['data'] = ColaboradoresService::notificaPontosColetaTransportadora($this->conexao);
    //            $this->retorno['message'] = 'As notificações de coleta foram enviadas com sucesso para os pontos.';
    //            $this->status = 200;
    //        } catch (\Throwable $e) {
    //            $this->retorno['status'] = false;
    //            $this->retorno['message'] = $e->getMessage();
    //            $this->status = 400;
    //        } finally {
    //            $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //            exit;
    //        }
    //    }

    public function avaliacoesPonto(array $dados)
    {
        try {
            $this->retorno['data']['avaliacoes'] = AvaliacaoTipoFreteService::buscaAvaliacoesPonto(
                $this->conexao,
                $dados['id']
            );
            $this->retorno['message'] = 'Avaliação adiada com sucesso!';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function buscaUsuarioPorHash()
    {
        try {
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, ['hash' => [Validador::OBRIGATORIO]]);

            $this->retorno['data']['influencer'] = InfluencersOficiaisLinksService::buscaDadosInfluencerOficialPorHash(
                $this->conexao,
                $dadosJson['hash']
            );
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Dados bucados com sucesso!';
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['status_code'] = 400;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['data'] = [];
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function completarCadastroInfluencerOficial(array $dados)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, ['email' => [VALIDADOR::NAO_NULO], 'pwd' => [VALIDADOR::NAO_NULO]]);

            $idUsuario = $dados['id_usuario'];
            if ($dadosJson['email'] || $dadosJson['pwd']) {
                UsuariosRepository::atualizaAutenticacaoUsuario(
                    $this->conexao,
                    $idUsuario,
                    $dadosJson['pwd'],
                    $dadosJson['email']
                );
            }

            ColaboradoresRepository::adicionaPermissaoUsuario($this->conexao, $idUsuario, [12]);

            $this->retorno['data'] = true;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Cadastro concluído com sucesso!';
            $this->conexao->commit();
        } catch (\Throwable $th) {
            $this->conexao->rollback();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $th->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function requisitosMelhoresFabricantes()
    {
        $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores();
        $fatores = Arr::only($fatores, [
            'valor_vendido_melhor_fabricante',
            'media_dias_envio_melhor_fabricante',
            'taxa_cancelamento_melhor_fabricante',
        ]);

        return $fatores;
    }

    public function buscaUltimaMovimentacaoColaborador(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ColaboradoresService::buscaUltimaMovimentacaoColaborador(
                $this->conexao,
                $dados['id_colaborador']
            );
            $this->retorno['message'] = 'Movimentação buscada com sucesso!';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function filtraUsuariosRedefinicaoSenha()
    {
        try {
            $email = (string) $this->request->query->get('email');
            $telefone = (string) $this->request->query->get('telefone');
            $cpf = (string) $this->request->query->get('cpf');
            if (!empty($telefone)) {
                Validador::validar(
                    ['telefone' => $telefone],
                    [
                        'telefone' => [Validador::OBRIGATORIO],
                    ]
                );
                $telefone = preg_replace('/[^0-9]/is', '', $telefone);
            } elseif (!empty($email)) {
                Validador::validar(
                    ['email' => $email],
                    [
                        'email' => [Validador::OBRIGATORIO, Validador::EMAIL],
                    ]
                );
            } elseif (!empty($cpf)) {
                Validador::validar(
                    ['cpf' => $cpf],
                    [
                        'cpf' => [Validador::OBRIGATORIO, Validador::CPF],
                    ]
                );
            } else {
                throw new \InvalidArgumentException('Informe o telefone ou o email');
            }
            $this->retorno['data'] = UsuarioService::filtraUsuariosRedefinicaoSenha(
                $this->conexao,
                $email,
                $telefone,
                $cpf
            );
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaFornecedores()
    {
        try {
            $this->retorno['data'] = ReputacaoFornecedoresService::buscaFornecedoresFiltro($this->conexao);
            $this->retorno['message'] = 'Fornecedores buscados com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

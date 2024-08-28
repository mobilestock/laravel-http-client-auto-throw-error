<?php

namespace api_administracao\Controller;

use api_administracao\Models\Cadastro as CadastroModel;
use api_administracao\Models\Request_m;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use MobileStock\model\UsuarioModel;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\repository\UsuariosRepository;
use MobileStock\repository\ZoopSellerRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\CreditosDebitosService;
use MobileStock\service\IuguService\IuguServiceConta;
use MobileStock\service\UsuarioService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Cadastro extends Request_m
{
    // private $conexao;

    public function __construct()
    {
        // $this->nivelAcesso = 'Geral';
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function buscaCadastros()
    {
        try {
            if ($retorno = CadastroModel::buscaCadastros($this->conexao)) {
                $message = 'Cadastros encontrados com sucesso!';
            } else {
                throw new Exception('Cadastros não encontrado!', 1);
            }
            $this->respostaJson
                ->setData(['status' => true, 'message' => $message, 'data' => $retorno])
                ->setStatusCode(201)
                ->send();
        } catch (\Throwable $exception) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $exception->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }
    public function buscaCadastroColaborador(?int $idColaborador = null)
    {
        $idColaborador ??= Auth::user()->id_colaborador;
        $colaborador = ColaboradoresService::buscaCadastroColaborador($idColaborador);

        return $colaborador;
    }

    public function buscaColaboradoresProcessoSellerExterno()
    {
        try {
            $dadosJson['pesquisa'] = Request::telefone('pesquisa');
        } catch (ValidacaoException $ignorado) {
            $dadosJson = Request::all();
            Validador::validar($dadosJson, [
                'pesquisa' => [Validador::OBRIGATORIO],
            ]);
        }

        $colaboradores = ColaboradoresService::filtraColaboradoresProcessoSellerExterno($dadosJson['pesquisa']);

        return $colaboradores;
    }

    public function adicionaPermissao()
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_usuario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nova_permissao' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);
        $idColaborador = UsuarioModel::buscaIdColaboradorPorIdUsuario($dadosJson['id_usuario']);
        $dadosColaborador = ColaboradoresService::buscaCadastroColaborador($idColaborador);
        if (in_array($dadosJson['nova_permissao'], $dadosColaborador['permissao'])) {
            throw new ConflictHttpException('Permissão já existe para este usuário!');
        }

        ColaboradoresRepository::adicionaPermissaoUsuario(DB::getPdo(), $dadosJson['id_usuario'], [
            $dadosJson['nova_permissao'],
        ]);

        DB::commit();
    }

    public function deletaPermissao()
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
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'acesso' => [Validador::OBRIGATORIO],
            ]);
            $idUsuario = $dadosJson['id'];
            $acesso = (string) $dadosJson['acesso'];
            $retornoNivel = CadastroModel::buscaPermissaoAcesso($this->conexao, $idUsuario);
            $lista_acesso = explode(',', (string) $retornoNivel['permissao']);
            $key = array_search($acesso, $lista_acesso);
            if ($key !== false) {
                unset($lista_acesso[$key]);
                $lista_acesso = (string) implode(',', $lista_acesso);
            } else {
                throw new Exception('Usuário já não possui esta permissão', 1);
            }
            if (CadastroModel::alteraPermissaoAcesso($this->conexao, $idUsuario, $lista_acesso)) {
                $retornoNivel = CadastroModel::buscaPermissaoAcesso($this->conexao, $idUsuario);
            } else {
                throw new Exception('Alteração de nivel de acesso não completada', 1);
            }
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Nível alterado com sucesso!', 'data' => $retornoNivel])
                ->setStatusCode(201)
                ->send();
        } catch (\Throwable $exception) {
            $this->respostaJson
                ->setData(['status' => false, 'message' => $exception->getMessage(), 'data' => ''])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function buscaAcessoDisponivel()
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
                'acessos' => [Validador::OBRIGATORIO],
            ]);
            $acessos = '(' . $dadosJson['acessos'] . ')';
            if ($lista_acessos = CadastroModel::buscaAcessos($this->conexao, $acessos)) {
                $message = 'Acessos disponíveis!';
            } else {
                throw new Exception('Nenhum acesso disponível!', 1);
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $lista_acessos])->send();
        } catch (\Throwable $th) {
            $this->respostaJson
                ->setData(['status' => false, 'message' => $th->getMessage(), 'data' => []])
                ->setStatusCode(400)
                ->send();
        }
    }
    public function editaAcessoPrincipal()
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_usuario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'acesso' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);
        if (!Gate::allows('ADMIN') && Auth::user()->id !== $dadosJson['id_usuario']) {
            throw new UnauthorizedHttpException('Você não tem permissão para alterar este usuário!');
        }

        $idColaborador = UsuarioModel::buscaIdColaboradorPorIdUsuario($dadosJson['id_usuario']);
        $dadosColaborador = ColaboradoresService::buscaCadastroColaborador($idColaborador);
        Validador::validar($dadosJson, [
            'acesso' => [Validador::ENUM(...$dadosColaborador['permissao'])],
        ]);

        UsuarioService::editaAcessoPrincipal($dadosJson['acesso'], $dadosJson['id_usuario']);

        DB::commit();
    }

    public function cadastraIgugu($data)
    {
        $this->conexao->beginTransaction();
        try {
            $resposta_api = [];
            if ($idColaborador = isset($data['idColaborador']) ? (int) $data['idColaborador'] : false) {
                $cadastroSellerIugu = new IuguServiceConta();
                $cadastroSellerIugu->idSellerConta = $idColaborador;
                $cadastroSellerIugu->dadosColaboradores($this->conexao);
                $resposta = $cadastroSellerIugu->CriaContaIugo();
                if (ZoopSellerRepository::atualizaSellerDadosIugo($idColaborador, $resposta, $this->conexao) == 0) {
                    throw new Exception('Error para atualizar cadigo da iugu no banco de dados', 1);
                }
                $resposta_api['sucess'] = 'Sicronização com a Iugo Realizada';
            } else {
                throw new Exception('Error, não foi identificado colaborador', 1);
            }

            $this->conexao->commit();
            $this->retorno['data'] = $resposta_api;
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno = [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => ['id_iugu' => $resposta->account_id],
            ];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    public function editTipoAcessoPrincipal()
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
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'tipo' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            if (CadastroModel::editaTipoAcessoPrincipal($this->conexao, $id, $tipo)) {
                $message = 'Alterado com sucesso!';
            } else {
                throw new Exception('Erro! Você não possui esta permissão em sua lista!', 1);
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => []])->send();
        } catch (\Throwable $th) {
            $this->respostaJson
                ->setData(['status' => false, 'message' => $th->getMessage(), 'data' => []])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function hasPermissao()
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
                'idUsuario' => [Validador::OBRIGATORIO],
                'nivel' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            $retorno = CadastroModel::hasPermissaoAcesso($this->conexao, $idUsuario, $nivel);
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Cadastrado com sucesso', 'data' => $retorno])
                ->send();
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function atualizaDataEntredaProdutosSeller()
    {
        $this->conexao->beginTransaction();
        try {
            $consulta = UsuariosRepository::buscaDiasFaltaParaDesbloquearBotaoAtualizadaDataEntradaProdutos(
                $this->conexao,
                $this->idCliente
            );
            if ($consulta === false) {
                throw new Exception('Fornecedor não encontrado!');
            }
            $dias = (int) $consulta['dias'];
            if ($dias > 0) {
                throw new Exception('Função ainda não está liberada para esse fornecedor');
            }

            UsuariosRepository::atualizadaDataEntradaProdutosTodos($this->conexao, $this->idCliente);

            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->conexao->rollBack();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
    public function buscaNovosFornecedores()
    {
        try {
            $dadosJson = [
                'pagina' => $this->request->get('pagina', 1),
                'area' => $this->request->get('area', 'ESTOQUE'),
                'visualizados' => $this->request->get('visualizados', ''),
            ];

            Validador::validar($dadosJson, [
                'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'area' => [Validador::ENUM('ESTOQUE', 'VENDA')],
            ]);

            $dadosJson['visualizados'] = !!$dadosJson['visualizados'] ? explode(',', $dadosJson['visualizados']) : [];
            $this->retorno['data'] = ColaboradoresService::buscaNovosSeller(
                $this->conexao,
                $dadosJson['pagina'],
                $dadosJson['area'],
                $dadosJson['visualizados']
            );
            $this->retorno['message'] = 'Novos fornecedores encontrados com sucesso';
        } catch (\Throwable $th) {
            $this->retorno = [
                'status' => false,
                'message' => $th->getMessage(),
                'data' => [],
            ];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function alternaBloquearContaBancaria()
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
                'id_conta' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'acao' => [Validador::BOOLEANO],
            ]);

            CreditosDebitosService::alternaBloquearContaBancaria(
                $this->conexao,
                $dadosJson['acao'],
                $dadosJson['id_conta']
            );
            $this->resposta = [];
            $this->conexao->commit();
        } catch (\Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 400;
            $this->resposta['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function limpaItokenCliente(array $data)
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar($data, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            UsuarioService::limpaItokenComBaseNoIdColaborador($this->conexao, $data['id_colaborador']);
            $this->retorno = [
                'status' => true,
                'message' => 'O Itoken do cliente foi limpo.',
                'data' => [],
            ];
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (\Throwable $th) {
            $this->conexao->rollBack();
            $this->retorno = [
                'status' => false,
                'message' => $th->getMessage(),
                'data' => [],
            ];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function bloqueiaAdiantamento(array $data)
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar($data, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            ColaboradoresService::alternaPermissaoClienteDeFazerAdiantamento($this->conexao, $data['id_colaborador']);
            $adiantamentoBloqueado = ColaboradoresService::adiantamentoEstaBloqueado(
                $this->conexao,
                $data['id_colaborador']
            );
            $this->retorno = [
                'status' => true,
                'message' => $adiantamentoBloqueado
                    ? 'Adiantamento do cliente bloqueado.'
                    : 'Adiantamento do cliente liberado.',
                'data' => [],
            ];
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (\Throwable $th) {
            $this->conexao->rollBack();
            $this->retorno = [
                'status' => false,
                'message' => $th->getMessage(),
                'data' => [],
            ];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

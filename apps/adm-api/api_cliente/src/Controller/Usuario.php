<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\UsuarioModel;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\Cadastros\CadastrosService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Usuario extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '4';
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function adicionarPermissaoFornecedor()
    {
        DB::beginTransaction();
        $usuario = Auth::user();
        if (!ColaboradorEndereco::possuiEnderecoPadrao($usuario->id_colaborador)) {
            throw new BadRequestHttpException('Para se tornar fornecedor complete o seu cadastro');
        }
        if (!str_contains($usuario->permissao, '30')) {
            UsuarioModel::adicionarPermissao($usuario->id, 30);
        }

        $colaborador = ColaboradorModel::buscaInformacoesColaborador($usuario->id_colaborador);
        $colaborador->bloqueado_repor_estoque = 'F';
        $colaborador->update();
        $colaborador->buscaOuGeraUsuarioMeulook($usuario->id_colaborador);

        $usuarioModel = UsuarioModel::buscaInformacoesUsuario($usuario->id);
        $usuarioModel->tipos = 'F';
        if ($usuarioModel->nivel_acesso !== 30) {
            $usuarioModel->nivel_acesso = 30;
        }
        $usuarioModel->update();

        DB::commit();
    }

    public function verificarDadosFaltantes()
    {
        try {
            $dados = CadastrosService::verificarDadosFaltantes($this->conexao, $this->idCliente);
            $this->resposta = $dados;
        } catch (\Throwable $th) {
            $this->resposta['message'] = $th->getMessage();
            $this->codigoRetorno = Response::HTTP_BAD_REQUEST;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function completarDadosFaltantes()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);

            if (sizeof($dadosJson) === 0) {
                throw new \InvalidArgumentException('Nenhum dado enviado');
            }
            $dadosFaltantes = CadastrosService::verificarDadosFaltantes($this->conexao, $this->idCliente);

            if (!in_array(true, $dadosFaltantes)) {
                throw new \InvalidArgumentException('Nenhum dado para alterar!');
            }

            $faltaDocumento = $dadosFaltantes['falta_cpf'] || $dadosFaltantes['falta_cnpj'];
            Validador::validar($dadosJson, [
                'email' => [
                    Validador::SE(
                        Validador::OBRIGATORIO,
                        Validador::SE($dadosFaltantes['falta_email'], Validador::EMAIL)
                    ),
                ],
                'senha' => [
                    Validador::SE(
                        Validador::OBRIGATORIO,
                        Validador::SE($dadosFaltantes['falta_senha'], Validador::TAMANHO_MINIMO(6))
                    ),
                ],
                'regime' => [
                    Validador::SE(
                        Validador::OBRIGATORIO,
                        Validador::SE($faltaDocumento, Validador::ENUM('FISICO', 'JURIDICO'))
                    ),
                ],
                'documento' => [
                    Validador::SE(
                        Validador::OBRIGATORIO,
                        Validador::SE(
                            $faltaDocumento && !empty($dadosJson['regime']) && $dadosJson['regime'] === 'FISICO',
                            Validador::CPF,
                            Validador::CNPJ
                        )
                    ),
                ],
            ]);

            $camposAtualizados = [];
            $colaborador = ColaboradoresRepository::busca(['id' => $this->idCliente]);

            if ($dadosFaltantes['falta_email'] && !empty($dadosJson['email'])) {
                if (!CadastrosService::existeEmail($this->conexao, $dadosJson['email'])) {
                    throw new \InvalidArgumentException('Email já cadastrado!');
                }
                $colaborador->setEmail($dadosJson['email']);
                $camposAtualizados[] = 'Email';
            }

            if ($faltaDocumento && !empty($dadosJson['regime']) && !empty($dadosJson['documento'])) {
                if ($dadosJson['regime'] === 'JURIDICO') {
                    $colaborador->setRegime(1);
                    $colaborador->setCnpj($dadosJson['documento']);
                } else {
                    $colaborador->setRegime(2);
                    $colaborador->setCpf($dadosJson['documento']);
                }
                $camposAtualizados[] = 'Documento';
            }

            if (sizeof($camposAtualizados) > 0) {
                $colaborador->setId($this->idCliente);
                ColaboradoresRepository::atualiza($colaborador, [], $this->conexao);
            }

            if ($dadosFaltantes['falta_senha'] && !empty($dadosJson['senha'])) {
                CadastrosService::editPassword($this->conexao, $dadosJson['senha'], $this->idUsuario);
                $camposAtualizados[] = 'Senha';
            }

            $this->resposta['message'] =
                implode(', ', $camposAtualizados) .
                ' atualizado' .
                (sizeof($camposAtualizados) > 1 ? 's' : '') .
                ' com sucesso!';
            $this->conexao->commit();
        } catch (\Throwable $th) {
            $this->conexao->rollback();
            $this->resposta['message'] = $th->getMessage();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

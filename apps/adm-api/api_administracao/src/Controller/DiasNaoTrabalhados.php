<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\service\DiasNaoTrabalhadosService;
use PDO;

class DiasNaoTrabalhados extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function salvaDiaNaoTrabalhado()
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
                'data' => [Validador::OBRIGATORIO, Validador::DATA],
            ]);
            if (!in_array($this->idUsuario, [356, 526])) {
                throw new Exception('Você não tem permissão para cadastrar dias não trabalhados');
            }

            $diasNaoTrabalhados = new DiasNaoTrabalhadosService();
            $diasNaoTrabalhados->data = $dadosJson['data'];
            $diasNaoTrabalhados->id_usuario = $this->idUsuario;

            $diasNaoTrabalhados->salva($this->conexao);
            $this->conexao->commit();

            $this->retorno['message'] = 'Salvo com sucesso!';
            $this->codigoRetorno = 201;
            $this->retorno['data'] = $diasNaoTrabalhados;
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function removeDiaNaoTrabalhado(array $data)
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar($data, [
                'id_dia_nao_trabalhado' => [Validador::OBRIGATORIO],
            ]);
            if (!in_array($this->idUsuario, [356, 526])) {
                throw new Exception('Você não tem permissão para remover dias não trabalhados');
            }

            $diasNaoTrabalhados = new DiasNaoTrabalhadosService();
            $diasNaoTrabalhados->id = $data['id_dia_nao_trabalhado'];

            $diasNaoTrabalhados->remove($this->conexao);
            $this->conexao->commit();

            $this->retorno['message'] = 'Dia nao trabalhado removido com sucesso!';
            $this->retorno['data'] = null;
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function listaDiaNaoTrabalhado(PDO $conexao)
    {
        $diasNaoTrabalhados = DiasNaoTrabalhadosService::lista($conexao);

        return $diasNaoTrabalhados;
    }
}

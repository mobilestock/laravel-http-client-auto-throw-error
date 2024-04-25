<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Contracts\Auth\Authenticatable;
use MobileStock\database\Conexao;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\IBGEService;
use MobileStock\service\PontosColetaService;
use PDO;

class Entregadores extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function buscaValorAdicionalCidade()
    {
        try {
            $this->retorno['data'] = IBGEService::buscaCidadesComBonus($this->conexao);
            $this->retorno['status'] = true;
            $this->message = 'Buscado com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($e->getMessage());
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function alteraPrecoAdicionalCidade()
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
                'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'preco' => [Validador::NUMERO],
            ]);

            IBGEService::alteraValorCidade($this->conexao, $dadosJson['id_cidade'], $dadosJson['preco']);

            $this->conexao->commit();
            $this->retorno['status'] = true;
            $this->message = 'Buscado com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\PDOException $pdoException) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function listaEntregadores()
    {
        $retorno = PontosColetaService::listaEntregadoresEPontoDeColeta();
        return $retorno;
    }
    public function verificaColaboradorPontoDeColeta(PDO $conexao, Authenticatable $usuario)
    {
        $retorno = PontosColetaService::pontoColetaExiste($conexao, $usuario->id_colaborador);
        return $retorno;
    }
    public function listaCidadesAtendidasPeloEntregador(int $idColaborador)
    {
        $retorno = TransportadoresRaio::buscaCidadesAtendidasPeloEntregadorOuPontoDeColeta($idColaborador);
        return $retorno;
    }
    public function listaAreaEntregaEntregador(int $idColaborador, ?int $idCidade = null)
    {
        $retorno['cidades'] = TransportadoresRaio::buscaCidadesAtendidasPeloEntregadorOuPontoDeColeta(
            $idColaborador,
            $idCidade
        );

        $retorno['destinos'] = EntregasFaturamentoItemService::buscaCoberturaEntregador($idColaborador, $idCidade);
        return $retorno;
    }
}

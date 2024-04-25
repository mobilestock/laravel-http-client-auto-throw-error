<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\IBGEService;

class CidadesPublic extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }

    public function lista()
    {
        try {
            Validador::validar($this->request->query->all(), [
                'pesquisa' => [Validador::SANIZAR],
            ]);

            $this->retorno['data'] = IBGEService::buscarCidadesFiltro(
                $this->conexao,
                $this->request->query->get('pesquisa', '')
            );
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            exit();
        }
    }

    public function listaMeuLookPontos()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'pesquisa' => [Validador::SANIZAR, Validador::OBRIGATORIO],
        ]);
        $cidades = IBGEService::buscarCidadesMeuLookPontos($dadosJson['pesquisa'] ?? '');

        return $cidades;
    }

    public function listaCobertura(int $idCidade, int $idColaborador)
    {
        $retorno = TransportadoresRaio::buscaCoberturaDetalhadaDaCidade($idCidade, $idColaborador);

        return $retorno;
    }
}

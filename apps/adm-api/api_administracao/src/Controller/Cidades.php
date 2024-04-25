<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\service\IBGEService;

class Cidades extends Request_m
{
    public function listaMeuLook()
    {
        try {
            Validador::validar($this->request->query->all(), [
                'pesquisa' => [Validador::SANIZAR],
            ]);

            $this->retorno['data'] = IBGEService::buscarCidadesMeuLook(
                $this->conexao,
                $this->request->query->get('pesquisa', '')
            );
            $this->status = 200;
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

    public function cadastroMeuLook()
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
                'latitude' => [Validador::OBRIGATORIO, Validador::LATITUDE],
                'longitude' => [Validador::OBRIGATORIO, Validador::LONGITUDE],
            ]);

            $cidade = new IBGEService();
            $cidade->longitude = $dadosJson['longitude'];
            $cidade->latitude = $dadosJson['latitude'];
            $cidade->id = $dadosJson['id'];
            $cidade->salvarCidade($this->conexao);
            $this->retorno['message'] = 'Cidade salva com sucesso!';

            $this->status = 200;
        } catch (\PDOException $pdoEx) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao atualizar cidade: ' . $pdoEx->getMessage();
            $this->status = 400;
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
}

<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\service\RodonavesService;
use MobileStock\helper\ConversorStrings;
use MobileStock\service\TransporteService;

class Transporte extends Request_m
{
    public function insereDadosRastreioRodonaves()
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
                'numero_nota_fiscal' => [Validador::OBRIGATORIO],
                'cpf_cnpj' => [Validador::OBRIGATORIO],
                'id_entrega' => [Validador::OBRIGATORIO],
                'id_transportadora' => [Validador::OBRIGATORIO],
            ]);

            RodonavesService::insereNotaFiscal(
                $this->conexao,
                $dadosJson['numero_nota_fiscal'],
                $dadosJson['cpf_cnpj'],
                $dadosJson['id_entrega'],
                $dadosJson['id_transportadora']
            );
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($e->getMessage());
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode(200)
                ->send();
            die();
        }
    }
    public function alteraDadosRastreioRodonaves()
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
                'numero_nota_fiscal' => [Validador::OBRIGATORIO],
                'cpf_cnpj' => [Validador::OBRIGATORIO],
                'id_entrega' => [Validador::OBRIGATORIO],
                'id_transportadora' => [Validador::OBRIGATORIO],
            ]);

            RodonavesService::atualizaDadosRastreio(
                $this->conexao,
                $dadosJson['numero_nota_fiscal'],
                $dadosJson['cpf_cnpj'],
                $dadosJson['id_entrega'],
                $dadosJson['id_transportadora']
            );
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($e->getMessage());
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode(200)
                ->send();
            die();
        }
    }
    public function buscaEntregasPendentes()
    {
        $entregas = TransporteService::buscaEntregasPendentesDeDadosDeRastreio();

        return $entregas;
    }
    public function buscaEntregasRastreaveis()
    {
        try {
            $this->retorno['data'] = TransporteService::buscaRastreaveis($this->conexao);
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->status = 500;
            $this->retorno['data'] = false;
            $this->retorno['messae'] = ConversorStrings::trataRetornoBanco($e->getMessage());
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function buscaTransportadoras()
    {
        try {
            $this->retorno['data'] = TransporteService::buscaTransportadoras($this->conexao);
        } catch (\Throwable $e) {
            $this->status = 500;
            $this->retorno['data'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($e->getMessage());
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode(200)
                ->send();
        }
    }
}

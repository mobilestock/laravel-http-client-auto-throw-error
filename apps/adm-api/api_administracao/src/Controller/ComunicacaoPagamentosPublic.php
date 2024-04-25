<?php

namespace api_administracao\Controller;

use api_administracao\Models\MobilePay;
use api_administracao\Models\Request_m;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\service\IuguService\IuguServiceExtrato;
use MobileStock\service\Recebiveis\RecebiveisConsultas;

class ComunicacaoPagamentosPublic extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::SEM_AUTENTICACAO;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function buscaWithdraw()
    {
        try {
            $parametros = $this->request->query->all();
            Validador::validar($parametros, [
                'idS' => [Validador::OBRIGATORIO],
                'idC' => [Validador::OBRIGATORIO],
            ]);
            extract($parametros);
            $saque = base64_decode($idS);
            $idCliente = base64_decode($idC);
            $dados = MobilePay::buscarInformaçõesSaque($this->conexao, $saque);
            $iugu = new IuguServiceExtrato();
            $iugu->idPagador = $idCliente;
            $iugu->apiToken = $dados['iugu_token_live'];
            $to = $dados['datas'] . 'T14:30:00-03:00';
            $from = $dados['data_criacao'] . 'T14:30:00-03:00';
            $result = $iugu->sincronizaSaque($this->conexao, $from, $to);
            $lista = $result->withdraw_requests;
            $i = json_decode(json_encode($lista), true);

            foreach ($i as $key => $info) {
                $subs = explode('-', $dados['data_criacao']);
                $data = $subs[2] . '/' . $subs[1] . '/' . $subs[0];
                $i[$key]['created_at'] = $data;
                $url_parts = explode('T', $info['updated_at']);
                $part = $url_parts[0];
                $subs = explode('-', $part);
                $data = $subs[2] . '/' . $subs[1] . '/' . $subs[0];
                $i[$key]['updated_at'] = $data . ' ' . $url_parts[1];
                unset($i[$key]['account_id'], $i[$key]['id']);
            }
            $this->retorno['data'] = $i;
        } catch (\Throwable $e) {
            $this->retorno = [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaRecebiveisPendentes()
    {
        try {
            $this->retorno['data'] = RecebiveisConsultas::buscaRecebiveisPendentes($this->conexao);
            $this->retorno['message'] = 'Recebíveis buscados com sucesso!';
            $this->retorno['status'] = true;
        } catch (\Throwable $e) {
            $this->retorno['data'] = [];
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

?>

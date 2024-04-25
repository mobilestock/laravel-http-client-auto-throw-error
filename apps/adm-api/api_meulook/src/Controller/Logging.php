<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\service\LoggerService;

class Logging extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }
    // public function logLink()
    // {
    //     try {
    //         Validador::validar(['json' => $this->json], [
    //             'json' => [Validador::JSON]
    //         ]);
    //         $dadosJson = json_decode($this->json, true);

    //         $idCliente = $this->idCliente;
    //         $idLink = $dadosJson['link'];
    //         if (!isset($idLink)) {
    //             $idLink = 0;
    //         } else {
    //             $idLink = Globals::parseRefID($idLink);
    //         }
    //         $mensagem = "[Anonimo] - {$dadosJson['log']}";
    //         if (!empty($idCliente)) {
    //             $mensagem = "[$this->nome] - {$dadosJson['log']}";
    //         } else {
    //             $idCliente = 0; // anon
    //         }
    //         LoggerService::criarLogLink($this->conexao, $dadosJson['ip'], $dadosJson['ua'], $mensagem, $idCliente, $idLink);
    //         $this->retorno['status'] = true;
    //         $this->retorno['message'] = 'OK.';
    //         $this->status = 200;
    // 	} catch(\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();
    //     } catch (\Throwable $ex) {
    // 		$this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    // 		$this->status = 400;
    // 	} finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    // 	}
    // }
    public function buscaLogsDePesquisas()
    {
        try {
            $this->retorno['data'] = LoggerService::buscaLogPesquisa($this->conexao);
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'OK.';
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
}

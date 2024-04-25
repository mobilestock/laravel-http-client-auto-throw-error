<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\service\LogisticaItemService;

class FaturamentoPonto extends Request_m
{
    public function __construct($rota)
    {
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    function listaParesCorrigidos()
    {
        try {
            if ($this->nivelAcesso < 50 && $this->nivelAcesso > 59) {
                throw new Exception('Voce não tem permissão para acessar esta lista');
            }

            $lista = LogisticaItemService::listaParesCorrigidos($this->conexao);

            $this->retorno['data'] = [
                'produtos' => $lista,
            ];
            $this->codigoRetorno = 200;
            $this->retorno['message'] = 'Os produtos foram encontrados';
            $this->retorno['status'] = true;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}

?>

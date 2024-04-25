<?php
/*
namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use api_administracao\Models\Conect;
use Exception;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;

class MeuLook extends Request_m
{
    public function __construct($rota)
	{
		parent::__construct();
		$this->rota = $rota;
		$this->conexao = Conect::conexao();
	}

    public function buscaLogLinkMeuLook()
    {
        try {
            $this->retorno['data'] = PedidoItemMeuLookService::buscaLogLinksMeulook($this->conexao);
            $this->retorno['status'] = true;
			$this->retorno['message'] = "Operação realizada com sucesso!";
			$this->status = 200;
        } catch (Exception $exception) {
            $this->retorno['status'] = false;
			$this->retorno['message'] = $exception->getMessage();
			$this->status = 400;
        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
        }
    }
}
*/
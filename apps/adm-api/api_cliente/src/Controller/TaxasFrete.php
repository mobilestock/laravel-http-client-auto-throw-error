<?php

namespace api_cliente\Controller;

use MobileStock\database\Conexao;
use api_cliente\Models\Request_m;
use MobileStock\service\Frete\FreteEstadoService;

class TaxasFrete extends Request_m
{
    
    private $conexao;
    
    public function __construct() 
    {
        $this->nivelAcesso = 0;
        $this->conexao = Conexao::criarConexao();
        parent::__construct();
    }
    
    public function buscaFretesPorEstado()
    {
        try {
            $this->retorno['data'] = FreteEstadoService::buscaFretes($this->conexao, $this->json);
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao consultar fretes por estado: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
        }
    }
}
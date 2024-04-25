<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use DateTime;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;

class TransacaoConsultas extends Request_m{  
    private $conexao;
    public function __construct()
    {  
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();   
        $this->conexao = Conexao::criarConexao();
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    // function listaPedidosPagamentosPublico($data){
    //     $retorno = [];
    //     $filtros = [];
    //     try{ 
    //         if($filtros['dataIni'] = $this->request->get('dataini')??false){
    //             if(!$this->validateDate($filtros['dataIni'])){
    //                 throw new Exception("DataIni inválida", 1);
    //             }                
    //         }

    //         if($filtros['dataFim'] = $this->request->get('datafim')??false){
    //             if(!$this->validateDate($filtros['dataFim'])){
    //                 throw new Exception("DataFim inválida", 1);
    //             } 
    //         }

    //         if($filtros['situacao'] = $this->request->get('situacao')??'Pendente'){
    //            switch (strtolower ($filtros['situacao'])) {
    //                case 'pago':$filtros['situacao'] =  'PA';break;
    //                case 'pendente':$filtros['situacao'] =  'PE';break;
    //                case 'cancelado':$filtros['situacao'] =  'CA';break;                 
    //                default:throw new Exception("Situação da transação incorreta", 1); break;              
    //             }
    //         }

    //         if($filtros['metodo'] = $this->request->get('metodoPagamento')??'ambos'){
    //             switch (strtolower ($filtros['metodo'])) {
    //                 case 'boleto':$filtros['metodo'] =  "'BL'";break;
    //                 case 'pix':$filtros['metodo'] =  "'PX'";break;
    //                 case 'ambos':$filtros['metodo'] =  "'BL','PX'";break;                 
    //                 default:throw new Exception("Metodo de pagamento incorreto", 1); break; 
    //             }
    //         }
            
    //         $retorno  = TransacaoConsultasService::buscaTransacaoAbertas($this->conexao, $filtros);
    //         $this->respostaJson->setData($retorno)->setStatusCode(200)->send();
    //     }catch (\Throwable $e){         
    //         $this->respostaJson
    //             ->setData([
    //                 'error'=>true, 
    //                 'message=>'=>$e->getMessage()
    //                 ])
    //             ->setStatusCode(400)
    //             ->send();
    //     die;          
    //    }
    // }
}
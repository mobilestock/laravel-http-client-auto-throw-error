<?php /*
ini_set('default_charset', 'UTF-8');
require_once '../../../vendor/autoload.php';


use api_estoque\Cript\Cript; 
use MobileStock\database\Conexao;
use MobileStock\service\Faturamento\FaturamentoConsultasService;
use MobileStock\service\TransacaoFinanceira\SellersService;

function safe_json_encode($value, $options = 0, $depth = 512)
{
    $encoded = json_encode($value, $options, $depth);
    if ($encoded === false && $value && json_last_error() == JSON_ERROR_UTF8) {
        $encoded = json_encode(utf8ize($value), $options, $depth);
    }
    return $encoded;
}

function utf8ize($mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}

function arrayToUTF8($data)
{
    array_walk(
        $data,
        function (&$entry) {
            if (is_array($entry)) {
                $entry = arrayToUTF8($entry);
            } else {

                if (mb_detect_encoding($entry) == "UTF-8") {
                    if (preg_match('/([^ A-Za-z0-9.#$-:aàáâãäåcçćĉċčeèéêëiìíîïnñoòóôõöuùúûüyýÿ])/i', $entry)) {
                        //echo $entry . " == " .utf8_decode($entry)." == ".utf8_encode($entry)."\n";
                        $entry = utf8_decode($entry);
                    }
                    
                } else {
                    $entry = utf8_encode($entry);
                }
            }
        }
    );
    return $data;
}


date_default_timezone_set('America/Sao_Paulo');
if($_GET){
    extract($_GET);
}else{
    extract($_POST);
}

$servico = new SellersService;
$conexao = Conexao::criarConexao();

switch($action){

    case 'buscaTransacoes':
        $resultado['transacao'] = SellersService::buscaSellers($conexao,[]);
        echo safe_json_encode($resultado);
        break;

    case 'buscaTransacoesFiltros':
        // $filtros =  json_decode($filtros);
        $resultado['transacao'] = SellersService::buscaSellers($conexao, ['data_de'=> $data_de, 'data_ate' => $data_ate, 'ordenar' =>$ordenar, 'situacao' => $situacao]);
        echo safe_json_encode($resultado);
        break;
    
    case 'buscaTransacoesCliente': 
        $resultado['transacao'] = SellersService::buscaClienteHistorico($conexao,[]);
        echo safe_json_encode($resultado);
        break;
        
    case 'buscaFiltrosClientes':
        // $filtros =  json_decode($filtros);
        $resultado['transacao'] = SellersService::buscaClienteHistorico($conexao, ['data_de'=> $data_de, 'data_ate' => $data_ate, 'ordenar' =>$ordenar]);
        echo safe_json_encode($resultado);
        break;
    case 'buscaFaturamentoFiltros':
        // $filtros =  json_decode($filtros);
        $resultado['transacao'] = SellersService::buscaFaturamento($conexao, ['data_de'=> $data_de, 'data_ate' => $data_ate]);
        echo safe_json_encode($resultado);
        break; 
        
    case 'buscaValorQuantidadeFiltros':
        // $filtros =  json_decode($filtros);
        $resultado['transacao'] = SellersService::buscaValorQuantidade($conexao, ['data_de'=> $data_de, 'data_ate' => $data_ate, 'ordenar' =>$ordenar, 'situacao' => $situacao]);
        echo safe_json_encode($resultado);
        break;   

    case 'buscaVendaEstadoFiltros':
        // $filtros =  json_decode($filtros);
        if(isset($uf)){
            $pesquisa = ['uf'=>$uf];
        }else if(isset($cidade)){
            $pesquisa = ['cidade'=>$cidade];
        }else{
            $pesquisa = [];
        }
        $resultado['transacao'] = SellersService::buscaVendaEstado($conexao, $pesquisa);
        echo safe_json_encode($resultado);
        break;    
    
    case 'buscaFreteFiltros':
        // $filtros =  json_decode($filtros);
        $resultado['transacao'] = SellersService::buscaValorFrete($conexao, ['data_de'=> $data_de, 'data_ate' => $data_ate]);
        echo safe_json_encode($resultado);
    
}

?>*/
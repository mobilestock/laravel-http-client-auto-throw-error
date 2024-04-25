<?php
/*
ini_set('default_charset', 'UTF-8');
require_once '../../../vendor/autoload.php';

use api_estoque\Cript\Cript;
use FontLib\Table\Type\post;
use MobileStock\database\Conexao;
use MobileStock\service\Lancamento\LancamentoCrud;
// use MobileStock\service\Faturamento\LancamentoConsultasService;
use MobileStock\service\TransacaoFinanceira\LancamentoConsultasService;

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
$servico = new LancamentoConsultasService;
$conexao = Conexao::criarConexao();
switch($action){
    case 'buscaTransacoes':
        //     $resultado['transacao'] =  LancamentoConsultasService::buscaTransacao($conexao);
        //     echo safe_json_encode($resultado);
        // break;
   
    
    case 'buscaTransacaoDetalhe':
        
        $resultado['transacao'] = LancamentoConsultasService::buscaLancamentoID($conexao, $idFaturamento);
        $resultado['lancamentos'] = LancamentoConsultasService::buscaLancamentoTransacao($conexao, $idFaturamento);
        $resultado['recebiveis'] = LancamentoConsultasService::buscaRecebiveisTransacao($conexao, $idFaturamento);
        $resultado['transacao']['codCliente'] = Cript::criptInt($resultado['transacao']['id']);
        echo safe_json_encode($resultado);
        break;
        
    case 'atualizaObservacao':

        $lancDB = LancamentoCrud::busca(['id'=> $idLancamento]);
        $lancDB = $lancDB[0];
        $lancDB->observacao .=' / '.$textoObservacao;
        LancamentoCrud::atualiza($lancDB);
        
        
        
    

       
        break;

        

        
        
}

?>
*/
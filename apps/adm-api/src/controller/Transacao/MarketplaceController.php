<?php /*
ini_set('default_charset', 'UTF-8');
require_once '../../../vendor/autoload.php';
use MobileStock\database\Conexao;
use MobileStock\service\Faturamento\FaturamentoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

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
extract($_GET);
$servico = new TransacaoConsultasService;
$conexao = Conexao::criarConexao();
switch($action){
    case 'buscaMarketplace':
	    $resultado['faturamento'] = [];
	    $resultado['transacao'] = [];
	    $resultado['lancamento'] = [];
	    $resultado['pagos'] = [];
	    $resultado['creditos'] = [];
	    $resultado['produtos'] = [];
        if($faturamento = FaturamentoConsultasService::buscaFaturamento ($conexao, $idFaturamento)){
            $resultado['message'] = "Faturamento encontrado";
            $resultado['faturamento'] = $faturamento; 
        }
        if($lancamento = TransacaoConsultasService::buscaLancamentosFaturamento($conexao,$idFaturamento)){
            $resultado['message'] = "Transações encontradas";
            $resultado['lancamento'] = $lancamento; 
        }
        if ($pagos = TransacaoConsultasService::buscaSellersPago($conexao, $idFaturamento)) {
            $resultado['message'] = "Transações encontradas";
            $resultado['pagos'] = $pagos;
        }
        if ($creditos = TransacaoConsultasService::buscaCredito($conexao, $idFaturamento)) {
            $resultado['message'] = "Transações encontradas";
            $resultado['creditos'] = $creditos;
        }
	    if($transacao = TransacaoConsultasService::buscaTransacoes($conexao,$idFaturamento)){
		    $resultado['message'] = "Transações encontradas";
		    $resultado['transacao'] = $transacao;
	    }
        if ($produtos = TransacaoConsultasService::buscaProdutosFaturamento($conexao, $idFaturamento)) {
            $resultado['message'] = "Produtos encontradas";
            $resultado['produtos'] = $produtos;
        }
        //Faturamento
        echo safe_json_encode($resultado);
        break;
//    case 'buscaLancamentoProdutos':
//        if($produtos = TransacaoConsultasService::buscaProdutosTransacao($conexao,$idTransacao)){
//            $resultado['message'] = "Produtos encontradas";
//            $resultado['produtos'] = $produtos;
//        }else{
//            $resultado['message'] = "Produtos não encontradas";
//            $resultado['produtos'] = [];
//        }
//        if($idTransacao){
//            if($seller = TransacaoConsultasService::buscaLancamentoTransacaoOrigem($conexao, $idTransacao, 'PF')){
//                $resultado['sellers'] = $seller;
//            }
//            if($pagamento = TransacaoConsultasService::buscaLancamentoTransacaoOrigem($conexao, $idTransacao, 'FA')){
//                $resultado['pagamento'] = $pagamento;
//            }
//            if($credito = TransacaoConsultasService::buscaLancamentoTransacaoOrigem($conexao, $idTransacao, 'SC')){
//                $resultado['creditos'] = $credito;
//            }
//            if($pendente = TransacaoConsultasService::buscaLancamentoPendenteTransacao($conexao, $idTransacao)){
//                $resultado['pendente']= $pendente;
//            }
//
//        }
//        echo safe_json_encode($resultado);
//    break;
	case 'pagamentoDinheiroManual':

		try {

			$pagamento = new TransacaoFinanceiraService;
			$dados = TransacaoConsultasService::buscaInfoTransacaoParaPagamento($id_transacao);
			$pagamento->id = $dados['id'];
			$pagamento->valor_liquido = $dados['valor_liquido'];
			$pagamento->status = 'PA';
			$pagamento->atualizaSituacaoTransacao(Conexao::criarConexao());

			$resultado = [
				'status' => true,
				'message' => 'Pagamento realizado com sucesso!',
				'data' => []
			];
		} catch (Throwable $ex) {
			$resultado = [
				'status' => false,
				'message' => 'Erro ao realizar pagamento',
				'data' => []
			];
		}
		echo safe_json_encode($resultado);
	break;
}

?>*/
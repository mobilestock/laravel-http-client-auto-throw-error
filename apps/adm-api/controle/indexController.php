<?php

ini_set('default_charset', 'UTF-8');

//global $_POST;
require_once '../vendor/autoload.php';

use MobileStock\model\Taxas;

require_once '../classes/faturamento.php';
require_once '../classes/colaboradores.php';
require_once '../classes/separacao.php';
require_once '../classes/produtos.php';
require_once '../classes/painel.php';
require_once '../classes/defeitos.php';
require_once '../src/model/Taxas.php';
require_once '../regras/alertas.php';

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
        return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
    }
    return $mixed;
}

function arrayToUTF8($data)
{
    array_walk($data, function (&$entry) {
        if (is_array($entry)) {
            $entry = arrayToUTF8($entry);
        } else {
            if (mb_detect_encoding($entry) == 'UTF-8') {
                if (preg_match('/([^ A-Za-z0-9.#$-:aàáâãäåcçćĉċčeèéêëiìíîïnñoòóôõöuùúûüyýÿ])/i', $entry)) {
                    //echo $entry . " == " .utf8_decode($entry)." == ".utf8_encode($entry)."\n";
                    $entry = utf8_decode($entry);
                }
            } else {
                $entry = utf8_encode($entry);
            }
        }
    });
    return $data;
}

date_default_timezone_set('America/Sao_Paulo');

$act = $_REQUEST['action'];

switch ($act) {
    default:
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Dados insuficientes.';
        $retorno['post'] = $_REQUEST;
        echo json_encode(arrayToUTF8($retorno));
        break;

    // case 'buscaFaturamento':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Faturamento não encontrado';
    //     if ($faturamento = buscaFaturamentoEntrega($_POST['idFaturamento'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Faturamento encontrado com sucesso';
    //         $retorno['faturamento'] = $faturamento;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    case 'atualizaPedidosEntregues':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Faturamento não encontrado';

        foreach ($_POST['listaEntregas'] as $key => $idFaturamento) {
            //if ($faturamento = atualizaPedidosEntregues($idFaturamento, $_POST['idEntregador'])) {
            //    $retorno['status'] = 'ok';
            //    $retorno['mensagem'] = 'Faturamento encontrado com sucesso';

            //    $retorno['idsUpdated'][] = $faturamento;
            //}
        }

        echo safe_json_encode($retorno);
        break;

    // case 'buscaQtdPedidosPorCliente':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Faturamento não encontrado';

    //     if ($pedidos = buscaQtdPedidosPorCliente()) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Faturamento encontrado com sucesso';

    //         $retorno['pedidos'][] = $pedidos;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'entradaCompras':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi possível cadastrar as compras!';
    //     $listaEntradas = [];
    //     foreach ($_POST['listaCodigoBarras'] as $key => $codigo) {
    //         if ($dadosCompra = buscaDadosCodBarras($codigo)) {
    //             $listaEntradas[$codigo] = $dadosCompra;
    //         }
    //     }

    //     foreach ($listaEntradas as $key => $entrada) {
    //         $fornecedor = $listaEntradas[$key][0]['fornecedor'];
    //         $tamanho = isset($_POST['tamanhoParaFoto']) && (!isset($_POST['caminho']) || $_POST['caminho'] == '') ? $_POST['tamanhoParaFoto'] : $listaEntradas[$key][0]['tamanho'];
    //         if ($compra = entrada_compra($entrada, $_POST['idUsuarioLogado'], $tamanho)) {
    //             $retorno['status'] = 'ok';
    //             $retorno['mensagem'] = 'Compras cadastradas com sucesso';
    //             salva_historico_entrada($key, $fornecedor, $sequencia, 1);
    //             $retorno['comprasUpdated'][] = $compra;
    //         } else {
    //             salva_historico_entrada($key, $fornecedor, $sequencia, 0);
    //         }
    //     }
    //     echo safe_json_encode($retorno);
    //     break;
    // case 'entradaComprasMaisNotasFornecedor':

    //     $retorno['status'] = 'false';
    //     $listaEntradas = [];
    //     $retorno['listaEntradasErro'] = [];
    //     foreach ($_POST['listaCodigoBarras'] as $key => $codigo) {
    //         if ($dadosCompra = buscaDadosCodBarras($codigo)) {
    //             $listaEntradas[$codigo] = $dadosCompra;
    //         }
    //     }
    //     $sequencia = get_sequencia_historico_entrada();
    //     foreach ($listaEntradas as $key => $entrada) {
    //         $fornecedor = $listaEntradas[$key][0]['fornecedor'];
    //         $tamanho = !isset($_POST['caminho']) || $_POST['caminho'] == '' ? $_POST['tamanhoParaFoto'] : $listaEntradas[$key][0]['tamanho'];
    //         $result = entrada_compra($entrada, $_POST['idUsuarioLogado'], $tamanho);
    //         if ($result['status'] == 'ok') {
    //             $retorno['status'] = $result['status'];
    //             $retorno['listaEntradas'][] = $entrada;
    //             salva_historico_entrada($key, $fornecedor, $sequencia, 1);
    //         } else {
    //             $retorno['listaEntradasErro'][] = $entrada;
    //             salva_historico_entrada($key, $fornecedor, $sequencia, 0);
    //         }
    //         $retorno['mensagem'][] = $result['mensagem'];
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaDadosCodBarras':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Código inválido';

    //     if ($dadosCompra = buscaDadosCodBarras($_POST['codigoBarrasLeitor'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Dados encontrado com sucesso';

    //         $retorno['dadosCompra'] = $dadosCompra;
    //     } else {
    //         $retorno['mensagem'] = 'Código já possui baixa!';
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaHistoricoDadosCodBarras':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Código inválido';
    //     foreach ($_POST['codigoBarrasLeitor'] as $key => $codigoBarras) {
    //         if ($dadosCompra = buscaHistoricoDadosCodBarras($codigoBarras)) {
    //             $retorno['status'] = 'ok';
    //             $retorno['mensagem'] = 'Dados encontrado com sucesso';

    //             $retorno['dadosCompra'][] = $dadosCompra;
    //         } else {
    //             $retorno['mensagem'] = 'Código já possui baixa!';
    //         }
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaHistoricoEntradaCompras':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhum registro encontrado!';

    //     if ($listaHistorico = get_lista_historico_entrada()) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Dados encontrados com sucesso';

    //         $retorno['listaHistorico'] = $listaHistorico;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    //    case 'buscaNotificacoesUsuario':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Nenhuma nova mensagem';
    //        $user = buscaClienteVinculadoUsuario($_POST['idUser']);
    //
    //        $_POST['nivelAcesso'] == 2 && $user = buscaSellerComNivelDeAcesso2($_POST['idUser']);
    //
    //
    //        if ($_POST['nivelAcesso'] === '1' && $faturados = buscaStatusPedidosFaturados($user['id_cliente'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //
    //            $retorno['faturados'] = $faturados;
    //        }
    //
    //        if ($_POST['nivelAcesso'] === '1' && $reservados = buscaStatusPedidosAberto($user['id_cliente'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //
    //
    //            $retorno['pedidos'] = $reservados;
    //        }
    //
    //        if ($notificacoesMenuLateral = buscaNotificacoesMenuLateral($user['id_cliente'], $_POST['nivelAcesso'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //            $retorno['notificacoesMenuLateral'] = $notificacoesMenuLateral;
    //        }
    //
    //        if ($_POST['nivelAcesso'] === '1' && $itemsCorrigidos = buscaItemsCorrigidos($user['id_cliente'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //            $retorno['itemsCorrigidos'] = $itemsCorrigidos;
    //        }
    //        // if ($pedidos = buscaQtdPedidosPorCliente()) {
    //        //     $retorno['status'] = 'ok';
    //        //     $retorno['mensagem'] = 'Faturamento encontrado com sucesso';
    //
    //        //     $retorno['qtdPares'][] = $pedidos;
    //        // }
    //
    //        if ($zoopNotifica = notificacaoZoop($user['id_cliente'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //            $retorno['mensagensZoop'] = $zoopNotifica;
    //        }
    //
    //
    //        echo safe_json_encode($retorno);
    //        break;

    //    case 'buscaNotificacoesAdm':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Nenhuma nova mensagem';
    //
    //        if ($notificacoesMenuLateral = buscaNotificacoesMenuLateralAdm()) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //            $retorno['notificacoesMenuLateral'] = $notificacoesMenuLateral;
    //        }
    //        if ($zoopNotifica = notificacaoZoopADM($_SESSION['id_cliente'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Dados encontrado com sucesso';
    //            $retorno['mensagensZoop'] = $zoopNotifica;
    //        }
    //
    //        echo safe_json_encode($retorno);
    //        break;

    //    case 'limpaNotificacoes':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Nenhuma nova mensagem';
    //        $user = buscaClienteVinculadoUsuario($_POST['idUser']); //Futuramente se acaso ADMs tbm forem usar notificações , sera necessario substituir essa função
    //        if ($notificacoesMenuLateral = limpaNotificacoes($_POST['idUser'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Notificações excluídas';
    //        }
    //        if ($user) {
    //
    //            if ($notificacoesMenuLateral = limpaNotificacoes($_POST['idUser'])) {
    //                $retorno['status'] = 'ok';
    //                $retorno['mensagem'] = 'Notificações excluídas';
    //            }
    //        }
    //
    //        echo safe_json_encode($retorno);
    //        break;
    // case 'limpaNotificao':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhuma nova mensagem';
    //     $user = buscaClienteVinculadoUsuario($_POST['idUser']);
    //     if ($notificacoesMenuLateral = limpaNotificao(intval($_POST['idUser']), intval($_POST['idNotificacao']))) {
    //         $retorno = $notificacoesMenuLateral;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    //    case 'listarFaturamentoEmAberto':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Nenhuma faturamento localizado';
    //
    //        $faturamentos = listarFaturamentoEmAberto('where 1=1'); //Futuramente se acaso ADMs tbm forem usar notificações , sera necessario substituir essa função
    //
    //        if ($faturamentos) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Notificações excluídas';
    //            $retorno['faturamentos'] = $faturamentos;
    //        }
    //
    //        echo safe_json_encode($retorno);
    //        break;

    //    case 'listarFaturamentosFaturados':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Nenhuma faturamento localizado';
    //
    //        $faturamentos = listarFaturamentosFaturados($_POST['filtros']); //Futuramente se acaso ADMs tbm forem usar notificações , sera necessario substituir essa função
    //
    //        if ($faturamentos) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Notificações excluídas';
    //            $retorno['faturamentos'] = $faturamentos;
    //        }
    //
    //        echo safe_json_encode($retorno);
    //        break;

    case 'buscaListaDocumentos':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Nenhum documento localizado';

        if ($documentos = buscaListaDocumentos()) {
            $retorno['status'] = 'ok';
            $retorno['mensagem'] = 'Documentos localizados';
            $retorno['documentos'] = $documentos;
        }

        echo safe_json_encode($retorno);
        break;

    case 'buscaProdutoPelaDescricao':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Nenhuma faturamento localizado';
        if ($produtos = buscaProdutoPelaDescricao($_POST['descricao'])) {
            $retorno['status'] = 'ok';
            $retorno['mensagem'] = 'produtos encontrados';
            $retorno['produtos'] = $produtos;
        }

        echo safe_json_encode($retorno);
        break;

    // case 'buscaProdutosComCadastroIncompleto':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Erro';
    //     $produto = buscaProdutosComCadastroIncompleto($_POST['nome']);
    //     if ($produto) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Produtos encontrados';
    //         $retorno['produtos'] = $produto;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    //    case 'buscaHistoricoPedidos':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Não foi possível excluir o pedido';
    //        if ($logsmovimentacao = buscaHistoricoPedidos($_POST['filtros'])) {
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Logs do sistema';
    //            $retorno['logsmovimentacao'] = $logsmovimentacao;
    //        }
    //
    //        echo safe_json_encode($retorno);
    //        break;

    // case 'buscaLogsMovimentacao':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Erro na consulta de logs';
    //     if ($log = buscaLogsMovimentacao($_POST['filtros'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Consulta realizada com sucesso';
    //         $retorno['logs'] = $log;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'desbloquearSeparacao':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi possível desbloquear o pedido';
    //     $conexao = new Conexao;
    //     // alterei este bloco pois esta função pedia a conexao como parametro
    //     if ($faturamentos = liberarSeparacao($conexao->criarConexao(), $_POST['idFaturamento'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Pedido desbloqueado com sucesso!';
    //         $retorno['faturamentos'] = $faturamentos;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'getAllPedidosParaSeparar':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhuma pedido encontrado';
    //     if ($listaPedidos = getAllPedidosParaSeparar($_POST['filtros'])) {
    //         $quantidade = getQuantidadePedidosParaSeparar();
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Pedido encontrado com sucesso.';
    //         $retorno['pedidos'] = $listaPedidos;
    //         $retorno['quantidade'] = $quantidade[0]['total'];
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaOnePedido':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhuma pedido encontrado';
    //     if ($listaPedidos = buscaOnePedido($_POST['filtros'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Pedido encontrado com sucesso.';
    //         $retorno['pedidos'] = $listaPedidos;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'getOnePedidosParaSeparar':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhum produto encontrado';
    //     if ($listaProdutos = getOnePedido($_POST['idFaturamento'], $_POST['pagina'])) { //busca os produtos
    //         // if (verificaSeparando($_POST['idFaturamento'], $_POST['idSeparador'])) {
    //         setPedidoSeparando($_POST['idFaturamento'], $_POST['idSeparador']); // muda o status do faturamento pra separando e o id do separador nos item
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Produto encontrado com sucesso';
    //         $retorno['produtos'] = $listaProdutos;
    //         // }
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'cancelarSeparacaoPedido':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi possível cancelar o pedido.';
    //     if (limparSeparacao($_POST['idFaturamento'])) { //busca os produtos
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Pedido cancelado com sucesso.';
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'toggleFaturamentoItemSeparado':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi possível concluir a separação';
    //     if (toggleFaturamentoItemSeparado($_POST['idFaturamento'], $_POST['uuid'], $_POST['sequencia'], $_POST['idSeparador'])) { //busca os produtos
    //         $quantidade = getQuantidadePedidosSeparados($_POST['idSeparador']);
    //         $retorno['quantidade'] = $quantidade[0]['quantidade'];
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Separação concluída com sucesso';
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'togglePrioridadeSeparacao':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi possível atualizar o pedido';
    //     if (togglePrioridadeSeparacao($_POST['item'])) { //busca os produtos
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Pedido atualizado com sucesso';
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'getQuantidadePedidosSeparados':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi possível concluir a separação';
    //     if ($quantidade = getQuantidadePedidosSeparados($_POST['idSeparador'])) { //busca os produtos
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Separação concluída com sucesso';
    //         $retorno['quantidade'] = $quantidade[0]['quantidade'];
    //         $retorno['separador'] = $quantidade[0]['nome'];
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'getResultadoAnalise':

    //     $retorno['status'] = 'ok';
    //     $retorno['mensagem'] = 'Separação concluída com sucesso';
    //     $retorno['analise'] = buscaResultadoAnaliseEstoque($_POST['id_usuario']);
    //     $retorno['linhas'] = buscaResultadoAnaliseEstoqueItens($_POST['id_usuario']);
    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaCodigoDeBarraProduto':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhum código de barra localizado';

    //     if ($codigoBarras = buscaCodigoBarraProdutoPadrao($_POST['idProduto'], $_POST['tamanho'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Separação concluída com sucesso';
    //         $retorno['codigo_barras'] = $codigoBarras;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaCodigoDeBarraMultiplosProdutos':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhum código de barra localizado';

    //     if (is_array($_POST['produtos'])) {
    //         $json = [];
    //         foreach ($_POST['produtos'] as $key => $produto) {
    //             if ($cod_barras = buscaCodigoBarraProdutoPadrao($produto['tamanho'], $produto['tamanho'])) {
    //                 $json[] = array(
    //                     "referencia" => $produto['referencia'],
    //                     "tamanho" => $produto['tamanho'],
    //                     "cod_barras" => $cod_barras,
    //                     "consumidor" => $produto['consumidor'],
    //                     "localizacao" => $produto['localizacao']
    //                 );
    //             }
    //         }
    //         if (sizeof($retorno) > 0) {
    //             $retorno['status'] = 'ok';
    //             $retorno['mensagem'] = 'Separação concluída com sucesso';
    //             $retorno['produtos'] = json_encode($json);
    //         }
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaListaConferencia':

    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Não foi encontrado nenhum pedido';

    //     if ($listaConferencia = buscaListaConferencia($_POST['filtros'])) {
    //         $retorno['listaConferencia'] = $listaConferencia;
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Separação concluída com sucesso';
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    //    case 'buscaTransportadoras':
    //
    //        $retorno['status'] = 'false';
    //        $retorno['mensagem'] = 'Não foi encontrado nenhum pedido';
    //
    //        if ($listaTransportadoras = buscaTransportadoras()) {
    //            $retorno['listaTransportadoras'] = $listaTransportadoras;
    //            $retorno['status'] = 'ok';
    //            $retorno['mensagem'] = 'Separação concluída com sucesso';
    //        }
    //        echo safe_json_encode($retorno);
    //        break;

    // case 'listaHistoricoPagamentos':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhum pagamento localizado';
    //     $log = new LogPagamento();
    //     if ($listaPagamentos = $log->listaHistoricoPagamentos($_POST['filtros'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Separação concluída com sucesso';
    //         $retorno['listaPagamentos'] = $listaPagamentos;
    //     }

    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaProdutosMaisClicados':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Lista de produtos mais clicados não encontrada';
    //     $mes = $_POST['mes'] ? $_POST['mes'] : 'MONTH(CURDATE())';
    //     $ano = $_POST['ano'] ? $_POST['ano'] : 'YEAR(CURDATE())';
    //     $filtro = " AND EXISTS(SELECT 1 FROM estoque_grade WHERE estoque_grade.id_produto = p.id AND estoque_grade.id_responsavel = 1) AND p.id_fornecedor = {$_POST['idFornecedor']} AND pa.mes =  $mes AND pa.ano = $ano";
    //     if ($listaProdutosMaisAcessados = buscaProdutosMaisClicados($filtro)) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Lista de produtos mais clicados localizada com sucesso';
    //         $retorno['acessados'] = $listaProdutosMaisAcessados;
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaProdutosMaisAdicionados':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Lista de produtos mais adicionados não encontrada';
    //     $mes = $_POST['mes'] ? $_POST['mes'] : 'MONTH(CURDATE())';
    //     $ano = $_POST['ano'] ? $_POST['ano'] : 'YEAR(CURDATE())';
    //     $filtro = " AND EXISTS(SELECT 1 FROM estoque_grade WHERE estoque_grade.id_produto = p.id AND estoque_grade.id_responsavel = 1) AND p.id_fornecedor = {$_POST['idFornecedor']} AND pa.mes =  $mes AND pa.ano = $ano";
    //     if ($listaProdutosMaisAdicionados = buscaProdutosMaisAdicionados($filtro)) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Lista de produtos mais adicionados localizada com sucesso';
    //         $retorno['adicionados'] = $listaProdutosMaisAdicionados;
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaTotalVendidosDashboard':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Lista de produtos mais vendidos não encontrada';
    //     if ($listaProdutosMaisVendidos = buscaTotalVendidosDashboard($_POST['idFornecedor'], $_POST['intervalo'])) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Lista de produtos mais vendidos localizada com sucesso';
    //         $retorno['vendidos'] = $listaProdutosMaisVendidos;
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    // case 'buscaDemandaProdutosFornecedor':
    //     $retorno['status'] = 'false';
    //     $retorno['mensagem'] = 'Nenhum produto localizado';
    //     if ($listaDemandaProdutos = buscaDemandaProdutosFornecedor($_POST['idFornecedor'], $_SESSION['nivel_acesso'], 1, 0)) {
    //         $retorno['status'] = 'ok';
    //         $retorno['mensagem'] = 'Lista de demanda de produtos locazalida com sucesso';
    //         $retorno['listaDemandaProdutos'] = $listaDemandaProdutos;
    //     }
    //     echo safe_json_encode($retorno);
    //     break;

    case 'excluiProdutoPromocaoTemp':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Não foi encontrado nenhum pedido';

        //if ($listaTransportadoras = excluiProdutoPromocaoTemp($_POST['idProduto'])) {
        //    $retorno['listaTransportadoras'] = $listaTransportadoras;
        //    $retorno['status'] = 'ok';
        //    $retorno['mensagem'] = 'Separação concluída com sucesso';
        //}
        echo safe_json_encode($retorno);
        break;

    case 'excluiTodosItensEmPromocaoPedido':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Não foi possível excluir os itens';

        //if (excluiTodosItensEmPromocaoPedido($_POST['id_cliente'])) {
        //    $retorno['status'] = 'ok';
        //    $retorno['mensagem'] = 'Itens excluidos com sucesso';
        //}

        echo safe_json_encode($retorno);
        break;
    case 'buscaListaJuros':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Nennhuma configuração de taxas de juros encontrada.';
        if ($listaJuros = Taxas::buscaListaJuros()) {
            $retorno['status'] = 'ok';
            $retorno['mensagem'] = 'Configurações de taxas localizadas.';
            $retorno['listaJuros'] = $listaJuros;
        }

        echo safe_json_encode($retorno);
        break;

    case 'salvarConfigTaxas':
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'Nennhuma configuração de taxas de juros encontrada.';
        $taxas = new Taxas();
        if ($listaJuros = $taxas->adicionaListaDeTaxas($_POST['listaTaxas'])) {
            $retorno['status'] = 'ok';
            $retorno['mensagem'] = 'Configurações de taxas localizadas.';
            $retorno['listaJuros'] = $listaJuros;
        }

        echo safe_json_encode($retorno);
        break;
}
die();

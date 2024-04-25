<?php
ini_set('default_charset', 'UTF-8');

use MobileStock\repository\MovimentacoesManuaisCaixaRepository;

require '../../vendor/autoload.php';
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
$movimentacoes = new MovimentacoesManuaisCaixaRepository;

extract($_POST);

switch ($acao) {
    case 'buscarTodos':
        if ($lista = $movimentacoes->busca((isset($_POST['data_inicio']) ? $_POST['data_inicio'] : ''), (isset($_POST['data_final']) ? $_POST['data_final'] : ''))) {
            $retorno = $lista;
        } else {
            $retorno = false;
        }
        // echo  safe_json_encode($retorno);
        echo safe_json_encode($retorno);
        break;
    // case 'buscaDadosMovimentacaoCaixa':
    //     $retorno = [];
    //     $filtroLimpo = explode('-', $filtro);

    //     if (COUNT($filtroLimpo) > 1 && ($filtroLimpo[1] == "Tipo" || $filtroLimpo[1] == "Valor" || $filtroLimpo[1] == "Motivo" || $filtroLimpo[1] == "Responsavel")) {

    //         $pesquisa = [
    //             'where' => [
    //                 strtolower($filtroLimpo[1]) => [
    //                     'like', $busca
    //                 ]
    //             ],
    //             'limit' => 25,
    //             'pagina' => $pagina
    //         ];
    //     } else if ($filtro == "undefined" && strlen($busca) > 0) {
    //         $pesquisa = [
    //             'where' => [
    //                 'motivo' => [
    //                     'like', $busca
    //                 ]
    //             ],
    //             'limit' => 25,
    //             'pagina' => $pagina
    //         ];
    //     } else {
    //         $pesquisa = ['limit' => 25, 'pagina' => $pagina];
    //     }

    //     $dadosMobimentacoes = $movimentacoes->operacao_listar(new MovimentacoesManuaisCaixa, $pesquisa);

    //     foreach ($dadosMobimentacoes as $dado) :

    //         $buscaUsuario = UsuariosRepository::operacao_listar(new Usuario(), [
    //             'where' => [
    //                 'id' => $dado->getResponsavel()
    //             ],
    //             'limit' => 1
    //         ]);

    //         if (COUNT($buscaUsuario) > 0) {
    //             $nomeResponsavel =  $buscaUsuario[0]->getNome();
    //         } else {
    //             $nomeResponsavel = '';
    //         }

    //         $buscaUsuario = UsuariosRepository::operacao_listar(new Usuario(), [
    //             'where' => [
    //                 'id' => $dado->getConferidoPor()
    //             ],
    //             'limit' => 1
    //         ]);

    //         if (COUNT($buscaUsuario) > 0) {
    //             $nomeConferidor =  $buscaUsuario[0]->getNome();
    //         } else {
    //             $nomeConferidor = '';
    //         }

    //         array_push($retorno, [
    //             'id' => $dado->getId(),
    //             'tipo' => $dado->getTipo(),
    //             'valor' => $dado->getValor(),
    //             'motivo' => $dado->getMotivo(),
    //             'conferido_por' => $nomeConferidor,
    //             'responsavel' => $nomeResponsavel,
    //             'criado_em' => date('d/m/Y H:i:s', strtotime($dado->getCriadoEm())),
    //             'conferido_em' => $dado->getConferidoEm() ? date('d/m/Y H:i:s', strtotime($dado->getConferidoEm())) : ""
    //         ]);
    //     endforeach;

    //     echo json_encode([
    //         'status' => true,
    //         'message' => 'Dados encontrados',
    //         'body' => $retorno
    //     ], true);
    //     break;

    case 'criaMovimentacaoManual':
        try {
            $movimentacoes->criarMovimentacaoManual([
                'tipo' => $tipo,
                'valor' => $valor,
                'motivo' => $motivo,
                'responsavel' => intVal($responsavel)
            ]);
        } catch (\Throwable $th) {

            echo json_encode([
                'status' => false,
                'message' => $th->getMessage(),
                'body' => NULL
            ], true);
            exit();
        }
        echo json_encode([
            'status' => true,
            'message' => 'Movimentação salva com sucesso',
            'body' => NULL
        ], true);

        break;

    case 'atualizaMovimentacaoManual':


        try {
            $movimentacoes->atualizaMovimentacaoManual(['id' => $id, 'idColaborador' => $idColaborador]);
        } catch (\Throwable $th) {

            echo json_encode([
                'status' => false,
                'message' => $th->getMessage(),
                'body' => NULL
            ], true);
            exit();
        }
        echo json_encode([
            'status' => true,
            'message' => 'Movimentação salva com sucesso',
            'body' => NULL
        ], true);

        break;

    case 'totalCaixa':
        if ($saldo = $movimentacoes->saldoTotal((isset($_POST['data_inicio']) ? $_POST['data_inicio'] : ''), (isset($_POST['data_final']) ? $_POST['data_final'] : ''))) {
            echo safe_json_encode([
                'body' => $saldo
            ]);
        }
        break;
}

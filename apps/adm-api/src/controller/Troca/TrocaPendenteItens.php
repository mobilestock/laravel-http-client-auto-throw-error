<?php

use MobileStock\repository\TrocaPendenteRepository;

require_once '../../../vendor/autoload.php';
require_once __DIR__ . '/../../../regras/alertas.php';

extract($_REQUEST);

switch ($action) {
    // case 'buscaItensCompradosParametro':

    //     $retorno['status'] = false;
    //     $retorno['mensagem'] = 'Não foi possivel realizar a busca, verifique se o prazo da compra do produto está dentro de 1 ano e se o cliente realmente comprou esse produto';
    //     $retorno['data'] = false;
    //     $controle = new TrocaPendenteRepository();
    //     if ($data = $controle->buscaItensCompradosParametro($id_cliente, json_decode($parametros, true), $pagina)) {
    //         $retorno['status'] = true;
    //         $retorno['mensagem'] = 'Itens buscados com sucesso';
    //         $retorno['data'] = $data;
    //     }
    //     $js = new JsonResponse($retorno);
    //     $js->send();
    //     break;

    case 'buscaFornecedores':
        $retorno['status'] = false;
        $retorno['mensagem'] = 'não foi possivel realizar a busca';
        $retorno['data'] = [];
        $controleTrocaPendente = new TrocaPendenteRepository();

        if ($data = $controleTrocaPendente->buscaFornecedores()) {
            $retorno['status'] = true;
            $retorno['mensagem'] = 'Fornecedores buscados com sucesso';
            $retorno['data'] = $data;
        }
        echo json_encode($retorno);
        break;
    case 'buscaCategorias':
        $retorno['status'] = false;
        $retorno['mensagem'] = 'não foi possivel realizar a busca';
        $retorno['data'] = [];
        $controle = new TrocaPendenteRepository();
        if ($data = $controle->buscaCategorias()) {
            $retorno['status'] = true;
            $retorno['mensagem'] = 'categorias carregadas com sucesso';
            $retorno['data'] = $data;
        }
        echo json_encode($retorno);

        break;
    case 'buscaLinhas':
        $retorno['status'] = false;
        $retorno['mensagem'] = 'Não foi possivel buscar as linhas';

        $controle = new TrocaPendenteRepository();
        if ($data = $controle->buscaLinhas()) {
            $retorno['status'] = true;
            $retorno['mensagem'] = 'Linhas buscadas com sucesso!';
            $retorno['data'] = $data;
        }
        echo json_encode($retorno);
        break;
    default:
        $retorno['status'] = 'false';
        $retorno['mensagem'] = 'dados insuficientes';
        $retorno['data'] = [];
        echo json_encode($retorno);
        break;
}

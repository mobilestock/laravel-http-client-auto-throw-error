<?php

use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\Lancamento\LancamentoService;

require_once '../../regras/alertas.php';
require_once '../../vendor/autoload.php';
require_once '../../classes/conexao.php';
$usuario = idUsuarioLogado();
$tipo = 0;
$lancamento = new LancamentoService();
$lista_colaboradores = [];
foreach ($_POST['dados']['items'] as $key => $d) {

    $lancamento = LancamentoCrud::busca([
        'id' => intVal($d)
    ])[0];

    if ($tipo != 1 && $lancamento->tipo == 'P' && $lancamento->situacao == '1') {

        if (!array_key_exists($lancamento->id_colaborador, $lista_colaboradores)) {
            $lancamento->id_usuario_pag = $usuario;
            $lancamento->tipo = 'R';
            $lancamento->documento_pagamento = $_POST['dados']['documento'];
            $lista_colaboradores[$lancamento->id_colaborador] = $lancamento;
            $tipo = 2;
        } else {
            $lancamento_antigo = $lista_colaboradores[$lancamento->id_colaborador];
            $lancamento_antigo->valor += $lancamento->valor;
            $lancamento_antigo->valor_total += $lancamento->valor;
            $lancamentos[$lancamento->id_colaborador] = $lancamento_antigo;
        }
    } else if ($tipo != 2 && $lancamento->tipo == 'R' && $lancamento->situacao == '1') {
        if (!array_key_exists($lancamento->id_colaborador, $lista_colaboradores)) {
            $lancamento->id_usuario_pag = $usuario;
            $lancamento->tipo = 'P';
            $lancamento->documento_pagamento = $_POST['dados']['documento'];
            $lista_colaboradores[$lancamento->id_colaborador] = $lancamento;
            $tipo = 1;
        } else {
            $lancamento_antigo = $lista_colaboradores[$lancamento->id_colaborador];
            $lancamento_antigo->valor += $lancamento->valor;
            $lancamento_antigo->valor_total += $lancamento->valor;
            $lancamentos[$lancamento->id_colaborador] = $lancamento_antigo;
        }
    } else {
        $tipo = 0;
        break;
    }
}
if ($tipo != 0) {
    foreach ($lista_colaboradores as $l) {
        // $l->valor_pago = $l->valor_total;
        $l->situacao = 1;
        LancamentoService::criaPagamentoLancamento(Conexao::criarConexao(), $l);
    }
} else {
    $_SESSION['danger'] = "Lançamentos pagos parcialmente. Motivo: Lançamento já pago ou de tipos diferentes!";
}
$_SESSION['success'] = "Lançamento pagador gerado com sucesso!";
// header('Location: ');

<?php
require_once './vendor/autoload.php';
require_once __DIR__ . '/cabecalho.php';
// require_once 'controle/busca-colaboradores.php';
acessoUsuarioFinanceiro();
// $colaboradores = buscaFornecedor(['tipo' => "'C'"]);
// $seller = buscaFornecedor(['tipo' => "'F'"]);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

<div class="content-fluid">
    <div class="card bg-light">
        <div class="card-header" style="font-size:30px;background-color:#90caf9">Transações</div><br>
        <div class="card-body">
            <div class="row m-1">
                <div class="col-2">
                    <label>Transação(ID):</label>
                    <input class="form-control" id="id" placeholder="ID Transação">
                </div>
                <div class="col-3">
                    <label>Transação(Código):</label>
                    <input class="form-control" id="cod_transacao" placeholder="Código Transação">
                </div>
                <div class="col-3">
                    <label for="exampleFormControlInput1">Pagador:</label>
                    <!-- <select name="pagador" id="pagador" class="selectpicker form-control" data-live-search="true" multiple data-max-options="1">
                        <?php /* foreach ($colaboradores as $c) : ?>
                            <option value="<?= $c->getId() ?>"><?= $c->getRazao_social() ?></option>
                        <?php endforeach; */?>
                    </select> -->
                    <input class="form-control" id="pagador" placeholder="Nome Usuário">
                </div>
                <!-- <div class="col-3">
                    <label for="exampleFormControlInput1">Responsável:</label>
                    <select name="responsavel" id="responsavel" class="selectpicker form-control" data-live-search="true" multiple data-max-options="1">
                        <?php /* foreach ($seller as $c) : ?>
                            <option value="<?= $c->getId() ?>"><?= $c->getRazao_social() ?></option>
                        <?php endforeach; */ ?>
                    </select>
                </div> -->
                <div class="col-1">
                    <label>Entrega:</label>
                    <input class="form-control" id="id_entrega" placeholder="ID Entrega">
                </div>
            </div><br>
            <div class="row m-1">
                <div class="col-2">
                    <label>Pagamento:</label>
                    <select name="meio_pagamento" id="meio_pagamento" class="selectpicker form-control" data-live-search="true" multiple data-max-options="1">
                        <option value="BL">Boleto</option>
                        <option value="CA">Cartão</option>
                    </select>
                </div>
                <div class="col-2">
                    <label>Situação:</label>
                    <select name="status" id="status" class="selectpicker form-control" data-live-search="true" multiple data-max-options="1">
                        <option value="CR">Criado</option>
                        <option value="PE">Pendente</option>
                        <option value="PA">Pago</option>
                        <option value="CA">Cancelado</option>
                        <option value="FL">Falha</option>
                        <option value="RV">Reembolso</option>
                        <option value="DS">Disputa</option>
                    </select>
                </div>
                <div class="col-2">
                    <label for="exampleFormControlInput1">Data De:</label>
                    <input type="date" class="form-control" id="data_de">
                </div>
                <div class="col-2">
                    <label for="exampleFormControlInput1">Data Até:</label>
                    <input type="date" class="form-control" id="data_ate">
                </div>
                <div class="form-check justify-content-inline">
                    <br><br>
                    <input class="form-check-input" type="checkbox" value="1" id="credito">
                    <label class="form-check-label" for="credito">
                        <b>Transação Crédito</b>
                    </label>
                </div>
                <div class="col-1 p-1 m-1">
                    <br>
                    <button id="pesquisar" class="btn btn-dark btn-block" onClick="buscaTransacoesFiltro()"><b>Pesquisar</b></button>
                </div>
                <div class="col-1 p-1 m-1">
                    <br>
                    <button onClick="limpar()" class="btn btn-light btn-block"><b>Limpar</b></button>
                </div>
                <div class="col-1 p-1 m-1">
                    <br>
                    <a href="marketplace.php" class="btn btn-danger btn-block"><b>Voltar</b></a>
                </div>
            </div>
        </div>
        <br>
    </div>
    <div class="bg-white card-body" id="lista-transacoes"></div>
</div>
</div>
<script src="js/MobileStockApi.js"></script>
<script src="js/transacao.js"></script>
<script>
    $('.selectpicker').selectpicker();
    $('.selectpicker').selectpicker('deselectAll');
</script>
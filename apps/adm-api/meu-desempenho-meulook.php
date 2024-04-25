<?php
require_once 'cabecalho.php';
acessoUsuarioFornecedor();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
.row {
    padding: 0 0.25rem;
}
.col-6 {
    padding: 0 0.25rem;
}
.card {
    margin: 0.25rem 0;
}
</style>

<v-app id="meudesempenhomeulook">
    <div class="container">
        <h4 class="text-center">Meu Desempenho Seller</h4>
        <br />
        <div class="row">
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Vendas Totais</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.vendas_totais }}</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Vendas Entregues</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.vendas_entregues }}</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Vendas Canceladas</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.vendas_canceladas_totais }}</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Taxa Cancelamento</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.taxa_cancelamento }}%</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Média Envio</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.media_envio }}</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Vendas Canceladas (7 Dias)</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.vendas_canceladas_recentes }}</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Valor Vendido</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.valor_vendido | formatarDinheiro }}</b>
                    </span>
                </div>
            </div>
            <div class="col-6 m-0">
                <div class="card p-3">
                    <small>Reputação</small>
                    <br />
                    <span class="text-right">
                        <b>{{ dados.reputacao }}</b>
                    </span>
                </div>
            </div>
        </div>
    </div>
</v-app>

<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/meu-desempenho-meulook.js"></script>

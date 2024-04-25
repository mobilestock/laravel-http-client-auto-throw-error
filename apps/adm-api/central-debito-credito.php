<?php /*



require_once 'cabecalho.php';
require_once 'classes/colaboradores.php';
require_once 'controle/busca-colaboradores.php';
acessoUsuarioGerente();
$colaboradores = buscaFornecedor(['tipo' => "'F'"]);
if ($_GET['pagina']) {
    $pagina = $_GET['pagina'];
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
<style>
       The switch - the box around the slider
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

       Hide default HTML checkbox
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

       The slider
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .2s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

       Rounded sliders
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;

        
    }
 

    INPUT[type=checkbox]
    {
        background-color: #DDD;
        border-radius: 10px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 17px;
        height: 17px;
        cursor: pointer;
        position: relative;
        top: 3px;
    }

    INPUT[id=credito]:checked
    {
        background-color: #009933;
        background: #009933 url("data:image/gif;base64,R0lGODlhCwAKAIABAP////3cnSH5BAEKAAEALAAAAAALAAoAAAIUjH+AC73WHIsw0UCjglraO20PNhYAOw==") 3px 3px no-repeat;
    }

    INPUT[id=debito]:checked
    {
        background-color: #ff0000;
        background: #ff0000 url("data:image/gif;base64,R0lGODlhCwAKAIABAP////3cnSH5BAEKAAEALAAAAAALAAoAAAIUjH+AC73WHIsw0UCjglraO20PNhYAOw==") 3px 3px no-repeat;
    }

    .complete-auto
    {
        position: fixed;
        width: 46rem;
        height: 30rem;
        overflow: auto;
    }

    .complete-auto table:hover 
    {
        background-color: #C0C0C0;
    }
    
</style>
<!-- Latest compiled and minified JavaScript -->
<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<div class="container-fluid body-novo">
    <div class="card container-fluid bg-light">
        <br>
        <h3>Central Débito & Crédito</h3>
        <br>
        <div class="pesquisa text-dark bg-light rounded">
            <div class="d-flex align-items-center justify-content-center row">
                <input type="hidden" id="pagina" value="<?= $pagina ?>">
                <!-- 
                    <div class="col-2">
                        <label>Pedido</label>
                        <input type="text" class="form-control">
                    </div> -->
                        <div class="form-group col-4">
                            <label for="auto-complete">Usuário ou Telefone</label>
                            <input type="text" class="form-control" id="auto-complete" placeholder="Usuário ou Telefone">
                             <div id="complete" class="complete-auto"></div>
                        </div>
                <div class="col-4">
                    <br>
                    <!-- <button class="btn text-white" id="pesquisar" name="pesquisar" type="button" style="background-color:#af4448">Pesquisar</button> -->
                    <button type="button" class="btn btn-dark text-white" data-toggle="modal" data-target="#myModal" id="modal_saldo">Saldos</button>
                    <button class="btn btn-dark"><i class='fas fa-user-plus'></i><a href="novos-clientes.php" class="text-white" style="text-decoration: none;"> Novos Usuários</a></button>
                    <!-- <a class="btn btn-danger" href="extrato-mobile.php"><b><i class="fas fa-box-open"></i> EXTRATO MOBILE STOCK</b></a> -->
                </div>
                <div class="col-3">
                    <br>

                    <label><b>Acesso Cliente</b></label> <label class="switch"> <input type="checkbox" id="nivel" checked><span class="slider round bg-dark"></span></label> <label><b>Acesso Avançado</b></label>

                </div>
            </div>
        </div>
        <br>
    </div>
    <div class="container">
        <div class="modal fade" id="myModal">
            <div class="modal-dialog modal-xl">
            <div class="modal-content">
            
                
                <div class="modal-header">
                <h4 class="modal-title">Saldos</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div><div class="modal-body">
                    <div class="col-12 row justify-content-center"> 
                        <div class="col-3 card bg-light btn" onClick="listaTodo('C')">
                            <div class="card-header text-center bg-dark"><b>
                                    <h4>Clientes - Saldo</h4>
                                </b></div>
                            <div class="card-body">
                                <h3 id="cliente" class="text-center"></h3>
                                <!-- <p class="card-text"></p> -->
                            </div>
                        </div>
                        <div class="m-2">
                            <br>
                            <br>
                            <h1>+</h1>
                        </div>

                        <div class="col-3 btn card bg-light" onClick="listaTodo('F')">
                            <div class="card-header text-center bg-dark"><b>
                                    <h4>Sellers - Saldo</h4>
                                </b></div>
                            <div class="card-body">
                                <h3 id="fornecedor" class="text-center"></h3>
                                <!-- <p class="card-text"></p> -->
                            </div>
                        </div>
                        <div class="m-2">
                            <br>
                            <br>
                            <h1>+</h1>
                        </div>

                        <div class="col-3 btn card text-center bg-light" onClick="direciona()">
                            <div class="card-header text-center bg-dark"><b>
                                <h4>Mobile Stock - Saldo</h4>
                                </b></div>
                            <div class="card-body">
                                <h3 id="mobile" class="text-center"></h3>
                                <!-- <p class="card-text"></p>  -->
                            </div>
                        </div>
                            <div class="m-2">
                                <br>
                                <br>
                                <h1>=</h1>
                            </div>
                        <div class="col-2 card bg-light">
                            <div class="card-header text-center text-white" style="background-color:#af4448"><b>
                                <h4>Total</h4>
                                </b></div>
                            <div class="card-body">
                            <h3 id="resultado" class="text-center"></h3>
                                <p class="card-text"></p> 
                            </div>
                        </div>
                    </div> 
                </div>
                
                    
                
                            <div class="modal-footer">
                                <button type="button" class="btn text-white" data-dismiss="modal" style="background-color:#af4448">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
    
    </div>
</div>
    <div class="card-body div-principal">
        <div id="busca-colaboradores"></div>


        <div class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="titulo"></h5>
                        <h5 class="modal-title" id="subtitulo"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group d-flex align-items row">
                            <div class="col-3"><label>Emissao - De:</label>
                                <input class="form-control" type="date" id="data" name="data" class="form-control" value="<?= date('Y-m-d', strtotime('-30 days', strtotime(date('Y-m-d')))); ?>">
                            </div>
                            <div class="col-3"><label>Emissao - Até:</label>
                                <input class="form-control" type="date" id="data_ate" name="data_ate" class="form-control" value="<?= date('Y-m-d'); ?>">
                            </div>
                            <div class="col-3 btn-group-sm m-1">
                                <br>
                                <button id="pesquisar_modal" type="button" class="btn text-white btn-sm" style="background-color:#af4448"><b>PESQUISAR</b></button>
                                <button type="button" class="btn btn-light btn-sm" id="limpar"><b>LIMPAR</b></button>
                            </div>
                            <div class="col-3 dropdown btn-group-sm">
                                <br>
                                <button class="btn btn-dark dropdown-toggle text-white btn-sm" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Ordenar
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <button class="dropdown-item" value="2">Maior Crédito</button>
                                    <button class="dropdown-item" value="1">Menor Crédito</button>
                                    <!-- <a class="dropdown-item" href="#">Something else here</a> -->
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="card bg-light col-4" style="max-width: 30rem;">
                                <div class="card-header text-white bg-dark">Saldo Anterior</div>
                                <div class="card-body">
                                    <h3 class="text-center" id="saldo_anterior"></h3>

                                </div>
                            </div>
                            <!-- <div class="card bg-light col-3" style="max-width: 30rem;">
                                <div class="card-header text-white bg-dark">Saldo Periodo</div>
                                <div class="card-body">
                                    <h3 class="text-center" id="saldo_periodo"></h3>

                                </div>
                            </div> -->

                            <div class="card  col-4 bg-light" style="max-width: 30rem;">
                                <div class="card-header text-white" style="background-color:#af4448">Total Saldo Atual</div>
                                <div class="card-body">
                                    <h3 class="text-center" id="total"></h3>

                                </div>
                            </div>
                            <div class="card bg-light col-4" style="max-width: 30rem;">
                                <div class="card-header text-white  bg-dark">Ação</div>
                                <div class="d-flex justify-content-center">
                                <div class="d-flex card-body align-items-center justify-content-center d-inline">
                                    <h4>R$&nbsp;</h4>
                                    <input class="col-3 form-control form-control-sm" id="valor" type="number"></input>
                                    <div class="col-1" s></div>
                                    <div class="align-self-center">
                                        <input class="checkgroup" type="checkbox" value="1" id="credito">
                        
                                    <label class="form-check-label" for="credito"style="color:#006600">
                                    Crédito
                                    </label>
                                    <input class="checkgroup" type="checkbox" value="2" id="debito">
                                    <label class="form-check-label" for="debito" style="color:#ff0000">
                                    Débito
                                    </label>
                                    </div>
                                    <button class="col-4 btn btn-dark btn-sm" onClick="pagar()"><i class="fas fa-dollar-sign"></i> GERAR </button>
                                    <section class="border py-3">
                                </div>
                                </div>

                            </div>
                        </div>

                        <div id="busca-colaboradores-detalhes"></div>

                        <div class="modal-footer">
                            <button type="button" class="text-white btn" data-dismiss="modal" style="background-color:#af4448">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="ordenar" value="0">
    </div>
    <?php require_once 'rodape.php' ?> <script>
        $('.selectpicker').selectpicker();
        $('.selectpicker').selectpicker('deselectAll');
        $('#limpar').click(function() {
            $('#data').val('');
            $('#data_ate').val('');
            buscaDetalhes(colaborador);

        })
        $('#pesquisar_modal').on('click', function() {
            soma = 0;
            saldo = 0;
            buscaDetalhes(colaborador);
        })
        $('.dropdown-item').click(function() {
            $('#ordenar').val($(this).val());
            soma = 0;
            buscaDetalhes(colaborador);
        })

        $('#nivel').on('change', function() {
            if (!$('#nivel').is(':checked')) {
                window.location = 'central-debito-credito-cliente.php?nivel=10';
            }
        })
    </script>

    <script src="js/MobileStockApi.js"></script>
    <script src='js/central-debito-credito.js<?= $versao; ?>'></script>

    <script>
        $(function(){
           $('input.checkgroup').click(function(){
              if($(this).is(":checked")){
                 $('input.checkgroup').attr('disabled',true);
                 $(this).removeAttr('disabled');
              }else{
                 $('input.checkgroup').removeAttr('disabled');
              }
           })
        })

        </script>

        <style>*/
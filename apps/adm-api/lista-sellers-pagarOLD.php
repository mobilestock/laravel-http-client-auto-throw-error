<?php

/*use MobileStock\repository\ContaBancariaRepository;

require_once 'regras/alertas.php';
require_once 'cabecalho.php';
require_once 'classes/colaboradores.php';
// require_once 'controle/busca-colaboradores.php';
acessoUsuarioFinanceiro();
$colaboradores = ContaBancariaRepository::busca([]);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<style>
/*.overlay {
        position: fixed; 
        display: flex; 
        width: 100%; 
        height: 100%; 
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.5); 
        z-index: 3; 
        cursor: pointer; 
        align-items: center;
        justify-content: center;

}
</style>*/
/*
<div class="container-fluid">
  <div class="card">
    <div class="card-header bg-dark" style=" font-family: Gotham,sans-serif">
            <br>
            <h5>
                LISTA PAGAMENTOS DE SELLERS
            </h5>
            <br>
            <div class="row m-1">
                <div class="col-md-4 p-1 text-white">
                    <label id="recebiveis_pendentes">Recebíveis Pendentes: ...</label>
                    <input type="date" class="form-control" name="dataRecebivel"  value="<?= /* date("Y-m-d")?>" id="dataRecebivel">
                </div>
                <div class="col-md-4">
                  <br>
                  <button class="ml-2 mt-3 btn btn-sm btn-success" id='atualiza_recebivel'>Atualiza Recebível</buton>              
                </div>
           </div>
            <div class="row m-1">
                <div class="col-md-4 p-1 text-white">
                  <label>Colaboradores</label>
                    <select name="id_colaborador" id="id_colaborador" class="selectpicker form-control" data-live-search="true" multiple data-max-options="1">
                        <?php foreach ($colaboradores as $c) : ?>
                            <option value="<?= $c->getId() ?>"><?= $c->getHolderName() ?></option>
                        <?php endforeach; ?>
                    </select>
                                                             
                </div>
                <div class="col-md-4 ">
                  <br>
                  <button class="ml-2 mt-3 btn btn-sm btn-info" id='buscar'>Pesquisar</buton>    
                </div>
            </div>
           
            <div class="row m-1">
                <div class="col-md-4 p-1 text-white">
                  <br>
                  <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#cadastra-conta">Cadastrar Conta Bancária</button>                                           
                  <a class="btn btn-sm btn-warning" href="monitora-pagamento-auto.php">Monitora Pagamento Auto</a>
                </div>
               
            </div>
            <br>
        </div>
    </div>
</div>
<div class="m-3 tabela">
<table id="tabelaSellers" class="table table-striped table-bordered dt-responsive" style="width:100%">
        
        <thead>
            <tr class="font-table">
                <th>Posição</th>
                <th>Id transferencia</th>
                <th>Data do Saque</th>
                <th>Titular</th>                
                <th class="fundo-vermelho">Valor Saque</th>
                <th class="fundo_azul_claro text-dark">Valor Faltante <i class="fas fa-arrow-right"></i></th>
                <th>Valor Processando <i class="fas fa-arrow-right"></i></th>
                <th>Valor Pago <i class="fas fa-arrow-right"></i></th>
                <th>Valor Efetuado(FIM)</th>
                <th>Saldo Mobile</th>
                <th>Previsão Prox. Pagamento</th>
                <th>Situação</th>
                <th>Bloqueado</th>
                <th>Pagamento Prioritário</th>
            </tr>
        </thead>

        <tbody class="colaboradores">

        </tbody>

    </table>
</div>
<div class="modal fade bd-example-modal-lg" id="detalhe" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Extrato Detalhado</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body recebe">
      <table id="tabelaRecebivel" class="table table-striped table-bordered dt-responsive" style="width:100%">
        
        <thead>
            <tr class="font-table">
                <th>id</th>
                <th>Data</th>
                <th>Situacao</th>                
                <th>Valor</th>
                <th>Valor Pago</th>
                <th>ID Transacao</th>
            </tr>
        </thead>

        <tbody class="recebiveis">

        </tbody>

    </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>




<div class="modal fade bd-example-modal-lg" id="cadastra-conta" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Cadastra Conta Bancária</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="overlay d-none" id="loading">
          <div class="spinner-border spinner-border-lg text-white" role="status">
              <span class="sr-only">Loading...</span>
          </div>
        </div>
        <div class="row col-md-3">
          <label for="id_conta">ID Conta:</label>
          <input id="id_conta" class="form-control input-sm" disabled></input>
        </div>
          <div class="form-row my-3 p-2">
                <div class="col-sm-3 mb-1">
                    <label for="nome-portador">Nome do Titular:</label>
                    <input id="nome-portador" class="form-control input-sm" placeholder="Nome do Titular"></input>
                </div>
                <div class="col-sm-3 mb-1">
                    <label for="telefone">Telefone do Titular:</label>
                    <input id="telefone" name="telefone" class="form-control input-sm" placeholder="Telefone do Titular" required></input>
                </div>
                <div class="col-sm-3 mb-1">
                    <label for="regime">Tipo da Conta:</label>
                    <select id="regime" name="regime" class="form-select form-control" required>
                        <option value="0" selected>--Selecione</option>
                        <option value="1">Jurídico</option>
                        <option value="2">Físico</option>
                    </select>
                </div>
                <div class="col-sm-3 mb-1" id="fisico">
                    <label for="cpf">CPF do Titular:</label>
                    <input id="cpf" name="cpf" class="form-control input-sm" placeholder="CPF do Titular" required></input>
                </div>
        </div>
        <div class="form-row my-3 p-2">
            <div id="juridico" class="col-sm-3 mb-1">
                <label for="cnpj">CNPJ do Titular:</label>
                <input id="cnpj" name="cnpj" class="form-control input-sm" placeholder="CNPJ do Titular" required></input>
            </div>

            <div class="col-sm-3 mb-1">
                <label for="banco-select">Banco:</label>
                <select type="text" class="form-control selectpicker input-novo" name="banco-iugu" id="banco-iugu" placeholder="Número Conta Bancária" required>
                    <option value="">--Selecione</option>
                    <option numbers="341" value="Itaú"> Itaú </option>
                    <option numbers="237" value="Bradesco"> Bradesco </option>
                    <option numbers="104" value="Caixa Econômica"> Caixa Econômica </option>
                    <option numbers="001" value="Banco do Brasil"> Banco do Brasil </option>
                    <option numbers="033" value="Santander"> Santander </option>
                    <option numbers="041" value="Banrisul"> Banrisul </option>
                    <option numbers="748" value="Sicredi"> Sicredi </option>
                    <option numbers="756" value="Sicoob"> Sicoob </option>
                    <option numbers="077" value="Inter"> Inter </option>
                    <option numbers="070" value="BRB"> BRB </option>
                    <option numbers="085" value="Via Credi"> Via Credi </option>
                    <option numbers="655" value="Neon"> Neon </option>
                    <option numbers="655" value="Votorantim"> Votorantim </option>
                    <option numbers="260" value="Nubank"> Nubank </option>
                    <option numbers="290" value="Pagseguro"> Pagseguro </option>
                    <option numbers="212" value="Banco Original"> Banco Original </option>
                    <option numbers="422" value="Safra"> Safra </option>
                    <option numbers="746" value="Modal"> Modal </option>
                    <option numbers="021" value="Banestes"> Banestes </option>
                    <option numbers="136" value="Unicred"> Unicred </option>
                    <option numbers="274" value="Money Plus"> Money Plus </option>
                    <option numbers="389" value="Mercantil do Brasil"> Mercantil do Brasil </option>
                    <option numbers="376" value="JP Morgan"> JP Morgan </option>
                    <option numbers="364" value="Gerencianet Pagamentos do Brasil"> Gerencianet Pagamentos do Brasil </option>
                    <option numbers="336" value="Banco C6"> Banco C6 </option>
                    <option numbers="218" value="BS2"> BS2 </option>
                    <option numbers="082" value="Banco Topazio"> Banco Topazio </option>
                    <option numbers="099" value="Uniprime"> Uniprime </option>
                    <option numbers="197" value="Stone"> Stone </option>
                    <option numbers="707" value="Banco Daycoval"> Banco Daycoval </option>
                    <option numbers="633" value="Rendimento"> Rendimento </option>
                    <option numbers="004" value="Banco do Nordeste"> Banco do Nordeste </option>
                    <option numbers="745" value="Citibank"> Citibank </option>
                    <option numbers="301" value="PJBank"> PJBank </option>
                    <option numbers="97" value="Cooperativa Central de Credito Noroeste Brasileiro"> Cooperativa Central de Credito Noroeste Brasileiro </option>
                    <option numbers="084" value="Uniprime Norte do Paraná"> Uniprime Norte do Paraná </option>
                    <option numbers="384" value="Global SCM"> Global SCM </option>
                    <option numbers="237" value="Next"> Next </option>
                    <option numbers="403" value="Cora"> Cora </option>
                    <option numbers="323" value="Mercado Pago"> Mercado Pago </option>
                    <option numbers="003" value="Banco da Amazonia"> Banco da Amazonia </option>
                    <option numbers="752" value="BNP Paribas Brasil"> BNP Paribas Brasil </option>
                    <option numbers="383" value="Juno"> Juno </option>
                    <option numbers="133" value="Cresol"> Cresol </option>
                    <option numbers="173" value="BRL Trust DTVM"> BRL Trust DTVM </option>
                    <option numbers="047" value="Banco Banese"> Banco Banese </option>
                </select>
            </div>
            <div class="col-sm-3 mb-1">
                <label for="agencia">Agência:</label>
                <input id="agencia" class="form-control input-sm" placeholder="Agência"></input>
            </div>
            <div class="col-sm-3 mb-1">
                <label for="numero-conta-bancaria">Conta:</label>
                <input id="numero-conta-bancaria" class="form-control input-sm" placeholder="Conta"></input>
            </div>
        </div>
            <div class="form-row my-3 p-2">

                <div class="col-sm-3 mb-1">
                    <label for="tipo-conta-portador">Tipo da Conta</label>
                    <select type="text" name="tipo-conta-portador" class="form-control input-novo" id="tipo-conta-portador">
                        <option value="checking">Corrente</option>
                        <option value="savings">Poupanca</option>
                    </select>
                </div>
                <div class="d-flex justify-content-center col-md-3 mt-1">
                    <br>
                    <button class="btn btn-primary btn-sm" id="btn-conta-iugu-bank">Cadastrar Conta Bancária</button>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script> 
  var url = "<?php echo /* getenv('URL_MOBILE'); ?>" ;
</script>
<script src="js/bloqueia-pagamento.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>
                        -->
<?php require_once 'cabecalho.php' ;
require_once 'classes/historico.php';

acessoUsuarioVendedor();

?>
<h2><b>Histórico de usuários</b></h2>
<div class="row cabecalho">
  <div class="col-sm-3">
    Data
  </div>
  <div class="col-sm-3">
    Usuário
  </div>
  <div class="col-sm-3">
    Tipo
  </div>
  <div class="col-sm-3">
    Tela
  </div>
</div>
<?php $historicoUsuarios = buscaHistoricoUsuarios();
foreach ($historicoUsuarios as $key => $historico):
        if($key%2==0){$estilo="fundo-branco";}else{$estilo="fundo-cinza";}
  ?>
  <div class="row corpo <?=$estilo;?>">
    <div class="col-sm-3">
      <?php $data = date_create($historico['data']); echo date_format($data,'d/m/Y H:i:s');?>
    </div>
    <div class="col-sm-3">
      <?=$historico['nome_usuario'];?>
    </div>
    <div class="col-sm-3">
      <?php if($historico['nivel_acesso']>=57 && $historico['nivel_acesso'] <= 57){echo 'Admin';}
      else if($historico['nivel_acesso']>=56 && $historico['nivel_acesso'] <= 56){echo 'Financeiro';}
      else if($historico['nivel_acesso']>=55 && $historico['nivel_acesso'] <= 55){echo 'Gerente';}
      else if($historico['nivel_acesso']>=54 && $historico['nivel_acesso'] <= 54){echo 'Conferente';}
      else if($historico['nivel_acesso']>=53 && $historico['nivel_acesso'] <= 53){echo 'Estoquista';}
      else if($historico['nivel_acesso']>=52 && $historico['nivel_acesso'] <= 52){echo 'Separador';}
      else if($historico['nivel_acesso']>=51 && $historico['nivel_acesso'] <= 51){echo 'Vendedor';}
      else if($historico['nivel_acesso']>=50 && $historico['nivel_acesso'] <= 50){echo 'Transportador';}
      else if($historico['nivel_acesso']>=30 && $historico['nivel_acesso'] <= 39 ){echo 'Fornecedor';}
      else if($historico['nivel_acesso']>=10 && $historico['nivel_acesso'] <= 19){echo 'Cliente';} ?>
    </div>
    <div class="col-sm-3">
      <?=$historico['tela'];?>
    </div>
  </div>
  <?php
endforeach;?><br/>
<div class="row">
  <div class="col-sm-2">
    <a href="configuracoes-sistema.php" class="btn btn-block btn-danger"><b>VOLTAR</b></a>
  </div>
</div>
<?php require_once 'rodape.php'; ?>

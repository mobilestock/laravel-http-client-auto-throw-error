<?php

use MobileStock\service\Compras\MovimentacoesService;

require_once 'cabecalho.php';
require_once 'classes/movimentacao.php';

acessoUsuarioVendedor();

$idMov = $_GET['id'];
$conexao = Conexao::criarConexao();
$movimentacao = buscaMovimentacao($idMov);
?>
<div class="pesquisa">
  <h2><b>Movimentação nº <?=$idMov;?></b></h2><br/>
  <div>
    Data movimentação: <b><?=date('d/m/Y H:i:s',strtotime($movimentacao['data']));?></b>
  </div>
  <div>
    Usuário: <b><?=$movimentacao['nome_usuario'];?></b>
  </div><br/>
  <div class="row cabecalho">
    <div class="col-sm-2">
      Referência
    </div>
    <div class="col-sm-1">
      Compra
    </div>
    <div class="col-sm-1">
      Seq
    </div>
    <div class="col-sm-2">
      Volume
    </div>
    <div class="col-sm-5">
      Grade
    </div>
    <div class="col-sm-1">
      Pares
    </div>
  </div>
  <?php if($movimentacaoItens = buscaItensMovimentacao($idMov)){
    foreach ($movimentacaoItens as $key => $item):
      if($key%2==0){$estilo="fundo-branco";}else{$estilo="fundo-cinza";}
      ?>
      <div class="row corpo <?=$estilo;?>">
        <div class="col-sm-2">
          <?=$item['referencia'];?>
        </div>
        <div class="col-sm-1">
          <?=$item['compra'];?>
        </div>
        <div class="col-sm-1">
          <?=$item['sequencia_compra'];?>
        </div>
        <div class="col-sm-2">
        <?=$item['volume'];?>
    </div>
        <div class="col-sm-5">
          <table class="table-condensed">
            <?php $grade = MovimentacoesService::buscaGradeMovimentacao($conexao, $idMov, $item['sequencia'], $item['volume'], $item['compra'], $item['sequencia_compra']);
          foreach ($grade as $key => $par):
            ?>
            <td>
                <div class="form-group">
                <div><kbd><label>
                <?=$par['nome_tamanho'];?></label>
                </kbd><br>
                <b><label for="inputCity"><?=$par['quantidade'];?></label></b>
                </div>
                </div>
            </td>
            <?php
          endforeach;
           ?>
         </table>
        </div>
        <div class="col-sm-1">
          <?=$item['pares'];?>
        </div>
      </div>
      <?php
    endforeach;
    } ?>
</div><br/>
<div class="row">
  <div class="col-sm-2">
    <a href="javascript:history.back()" class="btn btn-block btn-danger"><b>VOLTAR</b></a>
  </div>
</div>
<?php require_once 'rodape.php'; ?>

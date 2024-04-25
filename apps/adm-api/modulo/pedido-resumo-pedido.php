<div class="form-group" style="font-family:Verdana;border-radius:10px;background-color:#F2F2F2;padding:20px;padding-top:15px;padding-bottom:1px;">
<div><h4>Resumo do pedido</h4></div>
<div class="row" style="font-size:20px;">
  <div class="col-sm-2">
    <div class="form-group">Pares: <b><?=$pedido['pares'];?></b></div>
  </div>
  <div class="col-sm-4">
    <div class="form-group">Total: <b>R$ <?=str_replace('.',',',$pedido['valor']);?></b></div>
  </div>
</div>
</div>

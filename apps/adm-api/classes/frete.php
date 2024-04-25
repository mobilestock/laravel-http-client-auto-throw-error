<?php
require_once 'conexao.php';

function atualizaFretePedido($idCliente,$tipoFrete,$frete){
  $query = "UPDATE pedido SET tipo_frete = {$tipoFrete}, frete={$frete}
  WHERE id_cliente = {$idCliente};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

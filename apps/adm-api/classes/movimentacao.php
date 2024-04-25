<?php
require_once 'conexao.php';

function buscaUltimaMovimentacao(){
    $query = "SELECT MAX(id)id FROM movimentacao_estoque;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $id = $resultado->fetch();
    return $id['id'];
}

function inserirHistoricoMovimentacao($id,$usuario,$origem,$tipo){
  date_default_timezone_set('America/Sao_Paulo');
  $data = DATE('Y-m-d H:i:s');
  $query = "INSERT INTO movimentacao_estoque (id,usuario,tipo,data,origem) VALUES ({$id},{$usuario},'{$tipo}','{$data}','{$origem}');";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function inserirHistoricoMovimentacaoItem($id,$id_produto,$tamanho,$sequencia,$sequenciaCompra,$compra,$volume,$preco){
  $query = "INSERT INTO movimentacao_estoque_item (id_mov,id_produto,tamanho,sequencia,quantidade,compra,sequencia_compra,volume,preco_unit) 
  VALUES ({$id},{$id_produto},{$tamanho},{$sequencia},1,{$compra},{$sequenciaCompra},{$volume},{$preco});";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function atualizaHistoricoMovimentacaoItem($id,$id_produto,$tamanho,$sequencia,$volume){
  $query = "UPDATE movimentacao_estoque_item set quantidade=quantidade+1 
  WHERE id_mov={$id} AND id_produto={$id_produto} AND tamanho={$tamanho} AND sequencia={$sequencia} AND volume={$volume};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function buscaMovimentacao($idMov){
  $query = "SELECT me.*, u.nome nome_usuario FROM movimentacao_estoque me
  INNER JOIN usuarios u ON (u.id=me.usuario) WHERE me.id={$idMov};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetch();
  return $lista;
}

function buscaItensMovimentacao($idMov){
    $query = "SELECT sum(mei.quantidade)pares, mei.sequencia, p.descricao referencia, 
    mei.compra, mei.sequencia_compra, mei.preco_unit, mei.volume 
    FROM movimentacao_estoque_item mei 
    INNER JOIN produtos p ON (p.id=mei.id_produto)
    LEFT OUTER JOIN compras_itens ci ON (ci.id_compra=mei.compra AND ci.sequencia=mei.sequencia)
    WHERE mei.id_mov={$idMov} 
    GROUP BY mei.compra, mei.id_produto, mei.volume, mei.sequencia,mei.sequencia_compra 
    ORDER BY mei.compra ASC, mei.sequencia_compra ASC, mei.volume ASC;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaGradeMovimentacao($idMov,$sequencia,$volume,$compra,$sequencia_compra){
//   $query = "SELECT mei.* from movimentacao_estoque_item mei WHERE mei.id_mov={$idMov}
//   AND mei.sequencia={$sequencia} AND mei.volume='{$volume}' AND mei.compra='{$compra}' AND mei.sequencia_compra={$sequencia_compra}
//   GROUP BY mei.sequencia, mei.nome_tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }
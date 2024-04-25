<?php
require_once 'conexao.php';

function listaCheques($filtro){
  $query = "SELECT ch.*, s.nome situacao,c.razao_social recebido FROM cheques ch
  INNER JOIN colaboradores c ON(c.id=ch.recebido_de)
  INNER JOIN situacao_cheque s ON(s.id=ch.situacao)
  {$filtro} ORDER BY ch.id DESC;";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  return $stmt->fetchAll();
}

//ok
function listaChequesPagina($filtro,$pagina,$itens){
  $query = "SELECT ch.*, s.nome situacao, c.razao_social recebido FROM cheques ch
  INNER JOIN colaboradores c ON(c.id=ch.recebido_de)
  INNER JOIN situacao_cheque s ON(s.id=ch.situacao)
  {$filtro} ORDER BY ch.id DESC LIMIT {$pagina},{$itens};";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  return $stmt->fetchAll();
}

//ok
// function listaChequesEmCarteiraPagamento(){
//   $query = "SELECT ch.*, c.razao_social recebido FROM cheques ch
//   INNER JOIN colaboradores c ON(c.id=ch.recebido_de)
//   WHERE ch.situacao=1 and ch.acerto_pagar=0 ORDER BY ch.data_vencimento";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

//ok
function listarChequesEmAbertoEAhGuardar($filtro){
  $query = "SELECT ch.*, c.razao_social recebido FROM cheques ch
  INNER JOIN colaboradores c ON(c.id=ch.recebido_de)
  WHERE (ch.situacao=1 {$filtro}) OR ch.guardou=0;";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  return $stmt->fetchAll();
}

//ok
function listarTotalChequesEmAbertoEAhGuardar($filtro){
  $query = "SELECT SUM(ch.valor)total FROM cheques ch
  WHERE ch.situacao=1 {$filtro};";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  $linha = $stmt->fetch();
  return $linha['total'];
}

//ok
function guardaCheque($cheque,$usuario){
  date_default_timezone_set('America/Sao_Paulo');
  $data = DATE('Y-m-d H:i:s');
  $query = "UPDATE cheques SET guardou = {$usuario}, data_guardou='{$data}' WHERE id={$cheque};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

//ok
function buscaUltimoCheque(){
  $query = "SELECT COALESCE(MAX(id),0) id FROM cheques";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  $linha = $stmt->fetch();
  return $linha['id'];
}

//ok
// function insereCheque($id,$valor,$data_emissao,$data_vencimento,$nome,$recebido,$situacao,$passado_para,$usuario,$passadoParaManual){
//   date_default_timezone_set('America/Sao_Paulo');
//   $data_situacao = date('Y-m-d H:i:s');
//   $valor_cheque = str_replace('.','',$valor);
//   $valor_cheque = str_replace(',','.',$valor);
//   $data_venc = DateTime::createFromFormat('Y-m-d',$data_vencimento)->format('Y-m-d H:i:s');
//   $query = "INSERT INTO cheques (id,valor,data_emissao,data_vencimento,data_situacao,nome,recebido_de,situacao,passado_para,usuario,passado_para_manual)
//   VALUES ({$id},{$valor_cheque},'{$data_emissao}','{$data_venc}',
//   '{$data_situacao}','{$nome}',{$recebido},{$situacao},{$passado_para},{$usuario},'{$passadoParaManual}');";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function insereChequePeloAcerto($num_cheque,$valor,$data,$vencimento,$situacao,$titular,$recebido,$usuario,$num_acerto)
// {
//   $query = "INSERT INTO cheques (id,valor,data_emissao,
//   data_vencimento,data_situacao,nome,recebido_de,situacao,usuario,acerto_receber)
//   VALUES ({$num_cheque},{$valor},'{$data}','{$vencimento}',
//   '{$data}','{$titular}',{$recebido},{$situacao},{$usuario},{$num_acerto});";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

function buscaCheque($id){
  $query = "SELECT * FROM cheques WHERE id={$id}";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  return $stmt->fetch();
}

function atualizaChequeSql($sql){
  $conexao = Conexao::criarConexao();
  return $conexao->exec($sql);
}

function buscaSituacaoCheque($id){
  $query = "SELECT situacao FROM cheques WHERE id={$id}";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  $stmt->execute();
  $linha = $stmt->fetch();
  return $linha['situacao'];
}

function removeCheque($id){
  $query = "DELETE FROM cheques WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function existeChequesFaturados($id){
  $query = "SELECT * from cheques
  WHERE acerto_receber={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

// function buscaChequesPorAcerto($id_acerto){
//   $query = "SELECT * from cheques
//   WHERE acerto_pagar={$id_acerto};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaTotalChequesAcerto($id_acerto){
//   $query = "SELECT SUM(valor)valor from cheques
//   WHERE acerto_pagar={$id_acerto};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

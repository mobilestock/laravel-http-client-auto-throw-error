<?php
require_once 'conexao.php';

//function listaHistorico(){
//  $query = "SELECT * FROM historico ORDER BY historico.data DESC LIMIT 100";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}

//function insereHistoricoParExpirado($id_cliente,$id_produto,$tamanho,$sequencia,$origem){
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = date('Y-m-d H:i:s');
//  $query = "INSERT INTO pares_expirados (id_cliente,id_produto,tamanho,data_expirado,sequencia,origem) VALUES
//  ({$id_cliente},{$id_produto},{$tamanho},'{$data}',{$sequencia},'{$origem}');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

// function buscaUltimaSequenciaHistoricoExpirados(){
//   $query = "SELECT COALESCE(MAX(sequencia),0)sequencia FROM pares_expirados;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['sequencia'];
// }

//function atualizarDataExpirar(){
//  $data = DATE('Y-m-d');
//  $query = "UPDATE configuracoes SET verificacao_expirar_pares='{$data}';";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

function apagaHistoricoUsuarioAntigo(){
  date_default_timezone_set('America/Sao_Paulo');
  $dataAtual = DATE('Y-m-d H:i:s');
  $dataLimpar = DATE('Y-m-d',strtotime("-20 days",strtotime($dataAtual)));
  $query = "DELETE FROM historico_usuario WHERE data < '{$dataLimpar}'";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function buscaHistoricoUsuarios(){
  $query = "SELECT h.*,u.nome nome_usuario, u.nivel_acesso FROM historico_usuario h INNER JOIN usuarios u ON (u.id=h.usuario) ORDER BY data DESC LIMIT 100";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

function buscaUltimoHistoricoLancamento($id){
  $query = "SELECT COALESCE(MAX(sequencia),0)sequencia FROM lancamento_financeiro_historico WHERE id_lancamento={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['sequencia'];
}

function insereHistoricoLancamento($id,$seq,$usuario,$acao,$data_atual){
  $query = "INSERT INTO lancamento_financeiro_historico (id_lancamento,sequencia,acao,data_registro,id_usuario) VALUES ({$id},{$seq},'{$acao}','{$data_atual}',{$usuario});";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

//function buscaHistoricoLancamento($id_lancamento){
//  $query = "SELECT lfh.*,u.nome usuario FROM lancamento_financeiro_historico lfh
//  INNER JOIN usuarios u ON (u.id=lfh.id_usuario)
//  WHERE lfh.id_lancamento = {$id_lancamento} ORDER BY lfh.sequencia";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}

//function insereHistoricoPedidos($id_cliente, $id_faturamento, $usuario){
//    date_default_timezone_set('America/Sao_Paulo');
//    $data_historico = date('Y-m-d H:i:s');
//    $query ="INSERT INTO historico_pedido (id_cliente,faturamento,descricao,usuario,data_hora) VALUES ({$id_cliente},{$id_faturamento},'Conferiu pedido.',{$usuario},'{$data_historico}'); ";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
<?php
require_once 'conexao.php';

// function buscaProdutosCorrecao($filtro){
//   $query="SELECT pic.data_hora,pic.tamanho,pic.id_produto,pic.id_cliente,pic.sequencia, p.descricao produto, u.nome usuario,
//   c.razao_social cliente FROM pedido_item_corrigir pic
//   INNER JOIN produtos p ON (p.id = pic.id_produto)
//   INNER JOIN usuarios u ON (u.id = pic.id_separador)
//   INNER JOIN colaboradores c ON (c.id = pic.id_cliente)
//   {$filtro} ORDER BY pic.data_hora;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// function inserePedidoItemEncontradoPar($cliente,$id_produto,$tamanho,$tipo_cobranca,$id_tabela,$preco,$sequencia,$vendedor,$data,$cod_barras,$situacao){
//   $uuid=uniqid(rand(), true);
//   date_default_timezone_set('America/Sao_Paulo');
//   $data_vencimento = date('Y-m-d',strtotime("+60 days",strtotime($data)));
//   $query = " (id_cliente,id_produto,sequencia,tamanho,
//   id_vendedor,preco,situacao,data_hora,data_vencimento,cod_barras,tipo_cobranca,id_tabela,uuid,separado)
//   VALUES ({$cliente},{$id_produto},{$sequencia},{$tamanho},
//   {$vendedor},{$preco},{$situacao},'{$data}','{$data_vencimento}','{$cod_barras}',
//   {$tipo_cobranca},{$id_tabela},'{$uuid}',1);";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function removerParCorrigido($sequencia){
//   $query = "DELETE FROM pedido_item_corrigir WHERE sequencia={$sequencia}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// function removeParesExclusaoAntigos(){
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = DATE('Y-m-d');
//   $data_expira = date('Y-m-d',strtotime("-5 days",strtotime($data)));
//   $query = "DELETE FROM pedido_item_corrigir WHERE DATE(data_hora)<=DATE('{$data_expira}')";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

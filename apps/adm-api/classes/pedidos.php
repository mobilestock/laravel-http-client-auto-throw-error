<?php
require_once 'conexao.php';
require_once 'data_calculo.php';

//Métodos de inserção no pedido
// --Commented out by Inspection START (12/08/2022 14:47):
//function inserePedido($id_cliente){
//    $query = "INSERT INTO pedido (id_cliente) VALUES ({$id_cliente});";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


function buscaObservacaoPedido($id_cliente){
  $query = "SELECT observacao FROM pedido WHERE id_cliente={$id_cliente};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['observacao'];
}

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaUltimaSeqParcial($id){
//  $query = "SELECT COALESCE(MAX(seq_parcial),0) seq FROM pedido_item WHERE id_cliente={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['seq'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaProdutoPedidoNaoConfirmado($id_cliente){
//  $query = "SELECT * from pedido_item
//  WHERE id_cliente={$id_cliente} AND situacao = 6
//  AND confirmado=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaSaldoExpirado($data){
//    $query = "SELECT * from saldo_troca
//    WHERE DATE(data_vencimento)<='{$data}'";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $lista = $resultado->fetchAll();
//    return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


function insereObservacaoPedido($id_cliente,$string){
  $query = "UPDATE pedido SET observacao = '{$string}' WHERE id_cliente= {$id_cliente};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function insereObservacaoConferenciaPedido($id_cliente,$string){
  $query = "UPDATE pedido SET observacao2 = '{$string}' WHERE id_cliente= {$id_cliente};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

// --Commented out by Inspection START (12/08/2022 14:47):
//function atualizaDataPedidoConfirmado($id_cliente){
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = DATE('Y-m-d H:i:s');
//  $query = "UPDATE pedido SET data_confirmar = '{$data}' WHERE id_cliente= {$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function limpaObservacaoFretePedido($id_cliente){
//  $query = "UPDATE pedido SET observacao = '', tipo_frete = null, frete = 0 WHERE id_cliente= {$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function inserePedidoItem($cliente,$produto,$tipo_cobranca,$id_tabela,$preco,$sequencia,$vendedor,$data,$cod_barras,$situacao){
//  $uuid=uniqid(rand(), true);
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_vencimento = buscaDataVencimentoCliente($cliente,$data);
//  $query = "INSERT INTO pedido_item (id_cliente,id_produto,sequencia,tamanho,
//  id_vendedor,preco,situacao,data_hora,data_vencimento,cod_barras,tipo_cobranca,id_tabela,uuid,cliente)
//  VALUES ({$cliente},{$produto['id_produto']},{$sequencia},{$produto['tamanho']},
//  {$vendedor},{$preco},{$situacao},'{$data}','{$data_vencimento}','{$cod_barras}',
//  {$tipo_cobranca},{$id_tabela},'{$uuid}','');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function inserePedidoItemPorCodigo($cliente,$produto,$tipo_cobranca,$id_tabela,$preco,$sequencia,$vendedor,$data,$cod_barras,$separador){
//  $uuid=uniqid(rand(), true);
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = Date('Y-m-d H:i:s');
//  $data_vencimento = buscaDataVencimentoCliente($cliente,$data);
//  $query = "INSERT INTO pedido_item (id_cliente,id_produto,sequencia,tamanho,
//  id_vendedor,preco,situacao,data_hora,data_vencimento,data_separacao,id_separador,cod_barras,tipo_cobranca,id_tabela,uuid,separado,cliente,venda_balcao)
//  VALUES ({$cliente},{$produto['id']},{$sequencia},{$produto['tamanho']},
//  {$vendedor},{$preco},6,'{$data}','{$data_vencimento}','{$data}',{$separador},'{$cod_barras}',
//  {$tipo_cobranca},{$id_tabela},'{$uuid}',1,'',1);";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function retornarPedidoItem(
//    $cliente,
//    $id_produto,
//    $tamanho,
//    $separado,
//    $tipo_cobranca,
//    $id_tabela,
//    $preco,
//    $sequencia,
//    $vendedor,
//    $separador,
//    $data_separacao,
//    $data,
//    $cod_barras,
//    $situacao,
//    $consumidor,
//    $data_garantido,
//    $id_garantido,
//    $garantido_pago){
//  $uuid=uniqid(rand(), true);
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_vencimento = buscaDataVencimentoCliente($cliente,$data);
//  $query = "INSERT INTO pedido_item (
//    id_cliente,
//    id_produto,
//    sequencia,
//    tamanho,
//    separado,
//    id_vendedor,
//    id_separador,
//    data_separacao,
//    preco,
//    situacao,
//    data_hora,
//    data_vencimento,
//    cod_barras,
//    tipo_cobranca,
//    id_tabela,
//    uuid,
//    cliente,
//    data_garantido,
//    id_garantido,
//    garantido_pago)
//    VALUES ({$cliente},{$id_produto},{$sequencia},{$tamanho},{$separado},
//    {$vendedor},{$separador},'{$data_separacao}',{$preco},{$situacao},'{$data}','{$data_vencimento}','{$cod_barras}',
//    {$tipo_cobranca},{$id_tabela},'{$uuid}','{$consumidor}','{$data_garantido}',{$id_garantido},{$garantido_pago});";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function inserePedidoItemReserva($cliente,$produto,$tipo_cobranca,$id_tabela,$preco,$sequencia,$vendedor,$data,$cod_barras,$situacao){
//  $uuid=uniqid(rand(), true);
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_vencimento = date('Y-m-d',strtotime("+45 days",strtotime($data)));
//  $query = "INSERT INTO pedido_item (id_cliente,id_produto,sequencia,tamanho,
//  id_vendedor,preco,situacao,data_hora,data_vencimento,cod_barras,tipo_cobranca,id_tabela,uuid,cliente)
//  VALUES ({$cliente},{$produto['id_produto']},{$sequencia},{$produto['tamanho']},
//  {$vendedor},{$preco},{$situacao},'{$data}','{$data_vencimento}','{$cod_barras}',
//  {$tipo_cobranca},{$id_tabela},'{$uuid}','');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function inserePedidoParcialItemCodigo($cliente,$produto,$tipo_cobranca,$id_tabela,
//$preco,$sequencia,$vendedor,$data,$cod_barras,$seqParcial,$separador){
//  $uuid=uniqid(rand(), true);
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_vencimento = buscaDataVencimentoCliente($cliente,$data);
//  $data_separacao = date('Y-m-d H:i:s');
//  $query = "INSERT INTO pedido_item (id_cliente,id_produto,sequencia,tamanho,confirmado,
//  id_vendedor,preco,situacao,data_hora,data_vencimento,cod_barras,tipo_cobranca,id_tabela,uuid,seq_parcial,data_separacao,id_separador,separado,cliente)
//  VALUES ({$cliente},{$produto['id']},{$sequencia},{$produto['tamanho']},1,{$vendedor},
//  {$preco},6,'{$data}','{$data_vencimento}','{$cod_barras}',{$tipo_cobranca},{$id_tabela},'{$uuid}',{$seqParcial},'{$data_separacao}',{$separador},1,'');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function inserePedidoProdutoDevolucao($cliente,$produto,$tipo_cobranca,
//$id_tabela,$preco,$sequencia,$vendedor,$data,$cod_barras){
//  $uuid=uniqid(rand(), true);
//  $query = "INSERT INTO pedido_item (id_cliente,id_produto,sequencia,tamanho,
//  id_vendedor,preco,situacao,data_hora,cod_barras,tipo_cobranca,id_tabela,uuid,confirmado,cliente)
//  VALUES ({$cliente},{$produto['id']},{$sequencia},{$produto['tamanho']},
//  {$vendedor},{$preco},8,'{$data}','{$cod_barras}',{$tipo_cobranca},{$id_tabela},'{$uuid}',1,'');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function removePedidoProdutoUnidade($id_cliente,$sequencia,$situacao){
//   $query = "DELETE FROM pedido_item WHERE id_cliente={$id_cliente}
//   AND sequencia={$sequencia} AND situacao={$situacao};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function removePedidoProdutoUnidadeUuid($uuid){
//  $query = "DELETE FROM pedido_item WHERE uuid='{$uuid}';";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function removePedidoItemDevolucao($id_cliente){
//  $query = "DELETE FROM pedido_item WHERE id_cliente={$id_cliente} AND (situacao = 8 OR situacao = 12);";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function excluirProdutoPedidoTotal($id_cliente,$id_produto,$data,$separado,$situacao){
//    $query = "DELETE FROM pedido_item WHERE id_cliente={$id_cliente}
//    AND DATE(data_hora)='{$data}' AND id_produto={$id_produto} AND situacao={$situacao}
//    AND separado = {$separado};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function removerPedidoUnidade($id_cliente,$sequencia){
//    $query = "DELETE FROM pedido_item WHERE id_cliente={$id_cliente}
//    AND sequencia={$sequencia} AND situacao=6";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function removerPedidoNaoConfirmados($cliente){
//  $query = "DELETE FROM pedido_item WHERE id_cliente={$cliente} AND situacao=6 AND confirmado=0";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


function finalizarPedidoMaisTarde($cliente){
  $query = "UPDATE pedido set finalizar = 1;";
  $conexao = Conexao::criarConexao();
  $conexao->exec($query);
}

// --Commented out by Inspection START (12/08/2022 14:47):
//function finalizarPedidoMaisTardeNao($cliente){
//  $query = "UPDATE pedido set finalizar = 0, data_confirmar=NULL WHERE id_cliente={$cliente};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function atualizaTabelaPrecoPedidoCliente($id_cliente,$tipo_cobranca){
//  $query = "UPDATE pedido set tabela_preco = $tipo_cobranca WHERE id_cliente={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function atualizaTabelaFaturamentoCliente($id_faturamento,$tipo_cobranca){
//  $query = "UPDATE faturamento set tabela_preco = $tipo_cobranca WHERE id={$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function verificaSePedidoEstaFinalizado(int $id_cliente){
//  $query = "SELECT finalizar FROM pedido WHERE id_cliente={$id_cliente}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  if($lista['finalizar']==1){
//    return true;
//  }else{
//    return false;
//  }
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function existePedido($cliente){
//  $query = "SELECT * from pedido WHERE id_cliente ={$cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaUltimaSequenciaProdutoPedido($cliente){
//   $query = "SELECT MAX(sequencia) seq FROM pedido_item WHERE id_cliente={$cliente};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['seq'];
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaProdutoPedidoSeq($id_cliente,$sequencia){
//  $query = "SELECT id_produto, tamanho, uuid, separado FROM pedido_item WHERE id_cliente={$id_cliente} AND sequencia={$sequencia};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedido($id_cliente){
//  $query = "SELECT p.ordem_separacao_situacao, COALESCE(SUM(pi.id_produto),0)valor,
//  COALESCE(COUNT(pi.id_produto),0)pares, p.id_cliente, c.razao_social,
//  pi.separado, p.usuario_contato, p.data_contato,
//  p.tabela_preco, p.observacao, p.observacao2 from pedido p
//  INNER JOIN colaboradores c ON (c.id = p.id_cliente)
//  LEFT OUTER JOIN pedido_item pi ON (p.id_cliente = pi.id_cliente)
//  WHERE p.id_cliente = {$id_cliente} GROUP BY pi.garantido_pago";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


function buscaPedidoCliente($id_cliente){
  $query = "SELECT * from pedido WHERE id_cliente={$id_cliente};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

// function buscaTabelaPedido($id_cliente){
//   $query = "SELECT p.tabela_preco, tt.nome tabela from pedido p
//   INNER JOIN tipo_tabela tt ON (tt.id = p.tabela_preco)
//   WHERE p.id_cliente={$id_cliente};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

//usado no novo pedido
// function buscaPedidoItem($id_cliente){
//   $query = "SELECT pedido_item.data_hora, 
//   DATE(pedido_item.data_vencimento) data_vencimento, 
//   pedido_item.id_produto,
//   produtos.localizacao,
//   produtos.descricao produto, 
//   pedido_item.preco,   
//   if(pedido_item.situacao = 1,'No painel','Em pagmento ou reservado') situacao,
//   pedido_item.tamanho
// FROM pedido_item
//   INNER JOIN produtos ON (produtos.id = pedido_item.id_produto) INNER JOIN situacao ON (situacao.id = pedido_item.situacao)
// WHERE pedido_item.situacao IN (1,2,6)
//   AND pedido_item.id_cliente={$id_cliente}

// UNION ALL

//  SELECT transacao_financeiras_produtos_itens.data_criacao,
//    '',
//    produtos.id,
//    produtos.localizacao,
//   produtos.descricao,
//   transacao_financeiras_produtos_itens.preco,
//   IF(transacao_financeiras.status = 'PE','Confirmação de pagamento','Pago ou cancelado'),
//   transacao_financeiras_produtos_itens.tamanho
// FROM transacao_financeiras_produtos_itens
//   INNER JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
//   INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
// WHERE transacao_financeiras_produtos_itens.situacao <> 'CR'  
//   AND transacao_financeiras.pagador = {$id_cliente}


// ORDER BY situacao";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemTotalVendidos($id_cliente,$situacao){
//  $query = "SELECT COUNT(pedido_item.id_produto) pares from pedido_item
//  WHERE id_cliente={$id_cliente} AND situacao={$situacao} AND separado=0 AND confirmado=1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)



//usado no novo pedido
// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemConfirmar($id_cliente,$situacao){
//  $query = "SELECT * from pedido_item
//  INNER JOIN produtos ON (produtos.id = pedido_item.id_produto) INNER JOIN situacao ON (situacao.id = pedido_item.situacao)
//  WHERE pedido_item.id_cliente={$id_cliente} AND {$situacao}
//  ORDER BY pedido_item.sequencia DESC,produtos.descricao;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


//usado no novo pedido
// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemRelatorio($id_cliente,$situacao){
//  $query = "SELECT pedido_item.data_hora data, DATE(pedido_item.data_vencimento) data_vencimento, pedido_item.id_produto,
//  produtos.descricao produto, pedido_item.preco, SUM(pedido_item.preco) valor, pedido_item.cliente,
//  COUNT(pedido_item.id_produto) quantidade, pedido_item.situacao, situacao.nome nome_situacao from pedido_item
//  INNER JOIN produtos ON (produtos.id = pedido_item.id_produto) INNER JOIN situacao ON (situacao.id = pedido_item.situacao)
//  WHERE pedido_item.id_cliente={$id_cliente} AND {$situacao}
//  GROUP BY produtos.descricao, pedido_item.situacao, pedido_item.cliente
//  ORDER BY pedido_item.sequencia DESC,produtos.descricao;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaTotalPedidoItem($id_cliente,$situacao){
//  $query = "SELECT COUNT(sequencia) pares from pedido_item WHERE id_cliente={$id_cliente} AND {$situacao};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaTotalPedidoItemResumo($id_cliente,$situacao){
//  $query = "SELECT COUNT(sequencia) pares, SUM(preco)valor_total from pedido_item WHERE id_cliente={$id_cliente} AND {$situacao};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaTotalPedidoItemDev($id_cliente,$situacao){
//   $query = "SELECT COUNT(pitt.sequencia) pares from pedido_item_troca_temp pitt WHERE pitt.id_cliente={$id_cliente} AND {$situacao};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['pares'];
// }

//usado no novo pedido
// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemSeparado($id_cliente,$situacao){
//  $query = "SELECT * from pedido_item WHERE id_cliente={$id_cliente} AND situacao={$situacao} AND confirmado = 0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemSeparadoParcial($id_cliente){
//  $query = "SELECT * from pedido_item WHERE id_cliente={$id_cliente} AND situacao=6 AND confirmado = 1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemVendidoParcial($id_cliente){
//  $query = "SELECT uuid from pedido_item WHERE id_cliente={$id_cliente} AND situacao=6 AND separado=1 AND confirmado = 0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoProdutoSeparadoData($id_cliente,$id_produto,$data,$separado,$situacao){
//  $query = "SELECT * from pedido_item WHERE id_cliente={$id_cliente}
//  AND DATE(data_hora)='{$data}' AND id_produto={$id_produto} AND situacao={$situacao} AND separado = {$separado};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoProdutoSeparadoGrade($id_cliente,$id_produto,$dataExclusao){
//  $query = "SELECT id_produto,tamanho,COUNT(id_produto)quantidade from pedido_item WHERE id_cliente={$id_cliente}
//  AND DATE(data_hora)='{$dataExclusao}' AND id_produto={$id_produto} AND (situacao = 6 OR situacao=15) AND separado=1 GROUP BY tamanho;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


//usado no novo pedido
// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemConfirmado($id_cliente,$situacao){
//  $query = "SELECT pedido_item.data_hora data, pedido_item.preco,pedido_item.id_produto, produtos.descricao produto, SUM(pedido_item.preco) valor,
//  COUNT(pedido_item.id_produto) quantidade, pedido_item.situacao, situacao.nome nome_situacao, pedido_item.separado from pedido_item
//  INNER JOIN produtos ON (produtos.id = pedido_item.id_produto) INNER JOIN situacao ON (situacao.id = pedido_item.situacao)
//  WHERE pedido_item.id_cliente={$id_cliente} AND {$situacao} and pedido_item.confirmado=1 GROUP BY date(pedido_item.data_hora), produtos.descricao, pedido_item.situacao
//  ORDER BY pedido_item.data_hora DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaPedidoItemGrade($cliente,$produto,$data,$situacao,$separado){
//   $query = "SELECT tamanho, count(tamanho) quantidade FROM pedido_item
//   WHERE id_cliente={$cliente} AND id_produto={$produto} AND DATE(data_hora)=Date('{$data}') AND $situacao AND separado={$separado}
//   GROUP BY DATE(data_hora), tamanho ORDER BY tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoItemGradeConfirmado($cliente,$produto,$data,$situacao){
//   $query = "SELECT tamanho, count(tamanho) quantidade FROM pedido_item
//   WHERE id_cliente={$cliente} AND id_produto={$produto}
//   AND DATE(data_hora)=DATE('{$data}') AND $situacao AND confirmado=1
//   GROUP BY DATE(data_hora), tamanho ORDER BY tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoItemGradeRelatorio($id_cliente,$produto,$situacao,$cliente){
//   if($cliente==null){
//     $nome_cliente = " (cliente is null || cliente='') ";
//   }else{
//     $nome_cliente = "cliente = '{$cliente}'";
//   }
//   $query = "SELECT tamanho, count(tamanho) quantidade FROM pedido_item
//   WHERE id_cliente={$id_cliente} AND id_produto={$produto}
//   AND situacao = $situacao AND confirmado = 1 AND {$nome_cliente}
//   GROUP BY tamanho, situacao ORDER BY tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemGradeSeparadoRelatorio($id_cliente,$produto,$situacao,$cliente){
//  if($cliente==null){
//    $nome_cliente = " (cliente is null || cliente='') ";
//  }else{
//    $nome_cliente = "cliente = '{$cliente}'";
//  }
//  $query = "SELECT tamanho, count(tamanho) quantidade FROM pedido_item
//  WHERE id_cliente={$id_cliente} AND id_produto={$produto} AND separado=1 AND {$nome_cliente}
//  AND situacao = {$situacao} GROUP BY tamanho, situacao ORDER BY tamanho";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemGradeAhSepararRelatorio($id_cliente,$produto,$situacao,$cliente){
//  if($cliente==null){
//    $nome_cliente = "(cliente is null OR cliente='')";
//  }else{
//    $nome_cliente = "cliente = '{$cliente}'";
//  }
//  $query = "SELECT tamanho, count(tamanho) quantidade FROM pedido_item
//  WHERE id_cliente={$id_cliente} AND id_produto={$produto} AND separado=0 AND {$nome_cliente}
//  AND situacao = {$situacao} GROUP BY tamanho, situacao ORDER BY tamanho";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaPedidoItemDetalhes($id_cliente,$id_produto,$data,$situacao,$separado){
//   $query = "SELECT pi.id_cliente, pi.id_produto, pi.tamanho, pi.sequencia, pi.situacao, pi.data_hora, pi.data_vencimento, pi.cliente,
//   pi.preco, pi.id_garantido, pi.garantido_pago, pi.uuid, pi.premio, pi.separado, p.descricao produto, s.nome nome_situacao FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id = pi.id_produto) INNER JOIN situacao s ON (s.id = pi.situacao)
//   WHERE pi.id_cliente={$id_cliente} AND pi.id_produto={$id_produto} AND pi.separado = {$separado}
//   AND DATE(pi.data_hora)=DATE('{$data}') AND {$situacao} GROUP BY pi.sequencia ORDER BY  pi.tamanho, pi.sequencia;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoGerenciar($cliente,$situacao){
//   $query = "SELECT pi.*, p.descricao, s.nome nome_situacao FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//   WHERE pi.id_cliente={$cliente} AND pi.situacao = {$situacao} ORDER BY pi.data_hora, pi.tamanho, pi.sequencia;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoProdutosConcluidos($cliente){
//   $query = "SELECT pi.*, p.descricao, s.nome nome_situacao FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//   WHERE pi.id_cliente={$cliente} AND pi.confirmado=1 AND pi.situacao=6 ORDER BY pi.preco, pi.tamanho, pi.sequencia;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoProdutosPraTroca($cliente){
//   $query = "SELECT pi.*, p.descricao, s.nome nome_situacao FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//   WHERE pi.id_cliente={$cliente} AND (pi.situacao = 8) ORDER BY pi.preco, pi.tamanho, pi.sequencia;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoProdutoParcial($cliente){
//  $query = "SELECT pi.*, p.descricao, s.nome nome_situacao FROM pedido_item pi
//  INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//  WHERE pi.id_cliente={$cliente} AND pi.confirmado=0 AND pi.situacao=6
//  ORDER BY pi.data_hora, pi.sequencia;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaPedidoFaturamento($cliente){
//   $query = "SELECT COUNT(pi.id_produto) quantidade, SUM(pi.preco) valor
//   FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto)
//   WHERE pi.id_cliente={$cliente} AND (pi.situacao = 6 OR pi.situacao = 9
//   OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao=16) AND pi.confirmado=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// function buscaPedidoFaturamentoTroca($cliente){
//   $query = "SELECT COUNT(pi.id_produto) quantidade, SUM(pi.preco) valor FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto) WHERE pi.id_cliente={$cliente}
//   AND (pi.situacao = 12) AND confirmado=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetch();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoFaturamentoDevolucoes($cliente){
//  $query = "SELECT COUNT(pi.id_produto) quantidade, SUM(pi.preco) valor FROM pedido_item pi
//  INNER JOIN produtos p ON (p.id=pi.id_produto) WHERE pi.id_cliente={$cliente} AND pi.situacao =12 AND confirmado=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoProdutoConfirmado($cliente){
//  $query = "SELECT pi.*, p.descricao, s.nome nome_situacao FROM pedido_item pi
//  INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//  WHERE pi.id_cliente={$cliente} AND pi.confirmado = 1 AND pi.situacao=6 ORDER BY pi.seq_parcial DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaPedidoFaturamentoRelatorio($cliente){
//   $query = "SELECT pi.id_produto, pi.data_hora, pi.preco,pi.situacao, p.descricao, s.nome nome_situacao,
//   COUNT(pi.id_produto) quantidade, SUM(pi.preco) valor, pi.cliente FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//   WHERE pi.id_cliente={$cliente} AND pi.confirmado = 1 AND (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao = 16)
//   GROUP BY pi.id_produto, pi.situacao, pi.preco, pi.cliente ORDER BY s.nome";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoFaturamentoResumoRelatorio($cliente){
//   $query = "SELECT pi.id_produto, pi.data_hora, pi.situacao, p.descricao produto, s.nome nome_situacao,
//   COUNT(pi.id_produto) quantidade, pi.preco FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//   WHERE pi.id_cliente={$cliente} AND pi.confirmado = 1 AND (pi.situacao = 6 OR pi.situacao = 9
//   OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao=16) GROUP BY pi.preco ORDER BY pi.preco;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoFaturamentoDevolucaoResumoRelatorio($cliente){
//   $query = "SELECT pi.id_produto, pi.data_hora, pi.situacao, p.descricao produto, s.nome nome_situacao,
//   COUNT(pi.id_produto) quantidade, pi.preco FROM pedido_item pi INNER JOIN produtos p ON (p.id=pi.id_produto)
//   INNER JOIN situacao s ON (pi.situacao=s.id) WHERE pi.id_cliente={$cliente} AND (pi.situacao = 12) AND pi.confirmado = 1
//   GROUP BY pi.preco ORDER BY pi.preco;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPedidoDevolucoesRelatorio($cliente){
//   $query = "SELECT pi.*, p.descricao, s.nome nome_situacao, COUNT(pi.id_produto) quantidade, SUM(pi.preco) valor, pi.cliente
//   FROM pedido_item pi INNER JOIN produtos p ON (p.id=pi.id_produto) INNER JOIN situacao s ON (pi.situacao=s.id)
//   WHERE pi.id_cliente={$cliente} AND pi.situacao = 12 AND pi.confirmado = 1
//   GROUP BY pi.situacao, pi.id_produto, pi.preco ORDER BY pi.data_hora, pi.sequencia;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function atualizaConfirmadoPedidoItem($id_cliente,$item,$confirmado){
//   $query = "UPDATE pedido_item SET confirmado = {$confirmado} WHERE
//   id_cliente={$id_cliente} and id_produto={$item['id_produto']} AND sequencia={$item['sequencia']};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function atualizaConfirmadoPedidoUnidade($id_cliente,$sequencia,$seqParcial){
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = DATE('Y-m-d H:i:s');
//   $query = "UPDATE pedido_item SET confirmado = 1,seq_parcial={$seqParcial},data_separacao='{$data}'
//   WHERE id_cliente={$id_cliente} and sequencia={$sequencia}";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function prorrogaPrazoProduto($cliente,$seq){
//   $query = "UPDATE pedido_item SET data_vencimento = ADDDATE( data_vencimento, INTERVAL 7 DAY)
//   WHERE id_cliente = {$cliente} AND sequencia = {$seq};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function buscaProdutosPedido($cliente){
//   $query = "SELECT pi.id_produto, pi.tamanho, pi.sequencia, pi.uuid, pi.id_vendedor, pi.preco, pi.separado, pi.id_separador, pi.data_separacao,
//   pi.tipo_cobranca, pi.situacao, pi.data_hora, pi.id_cliente, pi.cliente, pi.id_garantido, pi.data_garantido, pi.garantido_pago, pi.pedido_cliente
//   FROM pedido_item pi WHERE pi.id_cliente={$cliente} AND pi.situacao=6 AND (pi.id_garantido=0 OR (pi.id_garantido>0 AND pi.garantido_pago=1));";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function atualizaDefeitoPedidoItem($uuid,$defeito){
//     $query = "UPDATE pedido_item set defeito = {$defeito}
//     WHERE uuid='{$uuid}'";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function atualizaAutorizadoPedidoItem($uuid,$autorizado){
//     $query = "UPDATE pedido_item set autorizado = {$autorizado}
//     WHERE uuid='{$uuid}'";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function buscaPedidoItemVendidos($id,$tamanho){
//   $query = "SELECT COUNT(sequencia)pares, pi.data_hora, c.razao_social cliente,pi.id_cliente FROM pedido_item pi
//   INNER JOIN colaboradores c ON (c.id=pi.id_cliente) WHERE pi.id_produto={$id} AND pi.tamanho={$tamanho} AND situacao = 6
//   GROUP BY pi.id_cliente,DATE(pi.data_hora);";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function listaPedidoProdutos($cliente){
//  $query = "SELECT pi.* FROM pedido_item pi WHERE pi.id_cliente={$cliente} AND (situacao<>8 OR situacao<>12)";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidoItemConfirmadoDetalhe($id_cliente){
//    $query = "SELECT * FROM pedido_item pi WHERE (pi.situacao=6 OR pi.situacao=9 OR pi.situacao=10
//    OR pi.situacao=11 OR pi.situacao=16) AND pi.confirmado=1 AND pi.id_cliente = {$id_cliente}";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $lista = $resultado->fetchAll();
//    return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaPedidosConfirmadosExpirados(){
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = DATE('Y-m-d H:i:s');
//  $dataExpira = date('Y-m-d',strtotime("-4 days",strtotime($data)));
//  $query = "SELECT id_cliente FROM pedido WHERE DATE(data_confirmar)<DATE('{$dataExpira}');";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaProdutosExpirados(string $situacao){
//  date_default_timezone_set('America/Sao_Paulo');
//  $dataExpira = DATE('Y-m-d');
//  $query = "SELECT id_cliente, id_produto, tamanho, sequencia, cliente, uuid, separado
//  FROM pedido_item WHERE DATE(data_vencimento) < '{$dataExpira}' AND {$situacao} AND id_garantido=0 AND garantido_pago=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaProdutosExpiradosGarantidos(){
//  $data = DATE('Y-m-d H:i:s');
//  $dataExpira = date('Y-m-d',strtotime("-5 days",strtotime($data)));
//  $query = "SELECT uuid
//  FROM pedido_item WHERE DATE(data_vencimento)<'{$dataExpira}'
//  AND id_garantido > 0 AND garantido_pago = 0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


function buscaParesAVencer1Dia($id){
  date_default_timezone_set('America/Sao_Paulo');
  $data = DATE('Y-m-d H:i:s');
  $dataExpira = date('Y-m-d',strtotime("+1 days",strtotime($data)));
  $query = "SELECT pi.* FROM pedido_item pi
  WHERE pi.id_cliente={$id} AND DATE(pi.data_vencimento) < DATE('{$dataExpira}')
  AND pi.situacao=6 AND pi.separado=1;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

function buscaParesAVencer3Dia($id){
  date_default_timezone_set('America/Sao_Paulo');
  $data = DATE('Y-m-d H:i:s');
  $dataExpira = date('Y-m-d',strtotime("+3 days",strtotime($data)));
  $query = "SELECT pi.* FROM pedido_item pi
  WHERE pi.id_cliente={$id} AND DATE(pi.data_vencimento) < DATE('{$dataExpira}')
  AND pi.situacao=6 AND pi.separado=1;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

// function buscaParesExpirando3Dias($filtro){
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = DATE('Y-m-d H:i:s');
//   $dataExpira = date('Y-m-d',strtotime("+3 days",strtotime($data)));
//   $query = "SELECT COUNT(pi.id_produto), pi.id_cliente, c.razao_social cliente FROM pedido_item pi
//   INNER JOIN colaboradores c ON (c.id=pi.id_cliente)
//   INNER JOIN produtos p ON (p.id=pi.id_produto)
//   WHERE DATE(pi.data_vencimento) < DATE('{$dataExpira}') AND pi.situacao <> 15 {$filtro}
//   GROUP BY pi.id_cliente ORDER BY pi.data_vencimento ASC;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaParesExpirando3DiasDetalhes($id_cliente){
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = DATE('Y-m-d H:i:s');
//   $dataExpira = date('Y-m-d',strtotime("+3 days",strtotime($data)));
//   $query = "SELECT pi.tamanho, pi.data_vencimento, p.descricao referencia FROM pedido_item pi
//   INNER JOIN produtos p ON (p.id=pi.id_produto)
//   WHERE DATE(pi.data_vencimento) < DATE('{$dataExpira}') AND pi.id_cliente={$id_cliente} AND pi.situacao<>15
//   GROUP BY p.descricao ORDER BY pi.data_vencimento ASC;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }


// --Commented out by Inspection START (12/08/2022 16:03):
//function paresFaturadosNoMesVendedor($vendedor){
//  $data = DATE('Y-m-d');
//  $query = "SELECT fi.id_vendedor, COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//  INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//  WHERE fi.id_vendedor = {$vendedor} AND f.situacao>=2 AND MONTH(f.data_fechamento)=MONTH('{$data}');";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 16:03)


// --Commented out by Inspection START (12/08/2022 16:03):
//function paresFaturadosNoDiaVendedor($vendedor){
//  $data = DATE('Y-m-d');
//  $query = "SELECT fi.id_vendedor, COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//  INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//  WHERE fi.id_vendedor = {$vendedor} AND f.situacao>=2 AND DATE(f.data_fechamento)='{$data}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 16:03)


// --Commented out by Inspection START (12/08/2022 16:03):
//function posicaoRankingVendedor(){
//  $data = DATE('Y-m-d');
//  $query = "SELECT fi.id_vendedor, COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//  INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//  WHERE f.situacao>=2 AND MONTH(f.data_fechamento)=MONTH('{$data}') GROUP BY fi.id_vendedor ORDER BY pares DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 16:03)


// --Commented out by Inspection START (12/08/2022 16:03):
//function paresFaturadosNoMesSeparador($separador){
//  $data = DATE('Y-m-d');
//  $query = "SELECT fi.id_separador, COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//  INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//  WHERE fi.id_separador = {$separador} AND f.situacao>=2 AND MONTH(f.data_fechamento)=MONTH('{$data}');";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 16:03)


// --Commented out by Inspection START (12/08/2022 16:03):
//function paresFaturadosNoDiaSeparador($separador){
//  $data = DATE('Y-m-d');
//  $query = "SELECT fi.id_separador, COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//  INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//  WHERE fi.id_separador = {$separador} AND f.situacao>=2 AND DATE(f.data_fechamento)='{$data}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 16:03)


// --Commented out by Inspection START (12/08/2022 16:03):
//function posicaoRankingSeparador(){
//  $data = DATE('Y-m-d');
//  $query = "SELECT fi.id_separador, COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//  INNER JOIN faturamento f ON (f.id=fi.id_faturamento)
//  WHERE f.situacao>=2 AND MONTH(f.data_fechamento)=MONTH('{$data}') GROUP BY fi.id_separador ORDER BY pares DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 16:03)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaTotalParesConfirmadosSeparado($id_cliente){
//    $query = "SELECT COUNT(pi.id_produto)pares FROM pedido_item pi
//    WHERE pi.id_cliente={$id_cliente} AND pi.situacao=6 AND pi.separado = 1 AND pi.confirmado=1;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaTotalParesConfirmadosVendidos($id_cliente){
//  $query = "SELECT COUNT(pi.id_produto)pares FROM pedido_item pi
//  WHERE pi.id_cliente={$id_cliente} AND pi.situacao=6 AND pi.separado = 0 AND pi.confirmado=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function buscaProdutoSeparadoPedido($id_cliente){
//   $query = "SELECT pi.id_produto, pi.tamanho, COUNT(id_produto)quantidade FROM pedido_item pi WHERE pi.id_cliente={$id_cliente} AND pi.separado=1
//   GROUP BY pi.id_produto, pi.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaProdutoPedidoClienteUuid($uuid){
//  $query = "SELECT * FROM pedido_item WHERE uuid='{$uuid}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function atualizaContatoComCliente($id_cliente,$usuario,$data){
//  $query = "UPDATE pedido set usuario_contato = {$usuario}, data_contato='{$data}'
//  WHERE id_cliente={$id_cliente}";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// --Commented out by Inspection START (12/08/2022 14:47):
//function limpaContatoComCliente($id_cliente){
//  $query = "UPDATE pedido set usuario_contato = 0, data_contato=NULL
//  WHERE id_cliente={$id_cliente}";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function insereDescricaoDefeito(array $produtosDevolucao, array $post){
//   $query="";
  
//   foreach ($produtosDevolucao as $devolucao) {

//     $idProduto = $devolucao['id_produto'];
//     $tamanho = $devolucao['tamanho'];
//     $sequencia = $devolucao['sequencia'];
//     $descDefeito = '';
//     if(isset($post["cd-$idProduto-$tamanho-$sequencia"])&& $post["cd-$idProduto-$tamanho-$sequencia"]!=''){
//       $descDefeito = $post["cd-$idProduto-$tamanho-$sequencia"];
//     }
    
//     if($descDefeito!= ""){
//           $query .="UPDATE pedido_item SET descricao_defeito ='{$descDefeito}'
//           WHERE id_produto ={$idProduto} AND sequencia = {$sequencia};";
//     }
//   }

//   if($query!=""){
//     $conexao = Conexao::criarConexao();
//     $conexao->exec($query);
//   }
  
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function buscaFaturamentosClienteEmAberto(){
//    $query = "SELECT COUNT(id) quant FROM faturamento WHERE situacao=1;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function atualizaProdutosDoPedidoPelaTabela($produtosPedido,$devolucoes,$tipo_cobranca){

//     $query = "";

//     foreach ($produtosPedido as $produto):
//       $tabela = buscaTabelaProduto($produto['id_produto']);
//       $preco = buscaPrecoTabelaProduto($tabela,$tipo_cobranca);
//       $preco = floatval($preco);
//       if( $produto['garantido_pago']!=1 && $produto['premio']!=1 ){
//         $query .= atualizaPrecoPedidoProdutoUuid($preco,$tipo_cobranca,$produto['uuid']);
//       }
//     endforeach;

//     if(sizeof($devolucoes)>0)
//     {
//       foreach ($devolucoes as $d):
//         $tabela = buscaTabelaProduto($d['id_produto']);
//         $preco = buscaPrecoTabelaProduto($tabela,$tipo_cobranca);
//         $preco = floatval($preco);
//         $query .= atualizaPrecoDevolucaoProdutoUuid($preco,$tipo_cobranca,$d['uuid']);
//       endforeach;
//     }

//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function atualizaProdutosDoFaturamentoPelaTabela($id_faturamento,$tipo_cobranca){
//     $query = "SELECT fi.id_faturamento, fi.id_produto, fi.id_garantido, fi.premio, fi.desconto, ti.preco, fi.uuid 
//     FROM faturamento_item fi 
//     INNER JOIN produtos p ON (p.id = fi.id_produto) 
//     INNER JOIN tabela_item ti ON (ti.id_tipo = $tipo_cobranca AND ti.id_tabela=p.id_tabela) 
//     where fi.id_faturamento = {$id_faturamento};";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();
//     $valor_produtos = 0;

//     foreach ($linhas as $key => $produto) {
//         $tabela = buscaTabelaProduto($produto['id_produto']);
//         $preco = buscaPrecoTabelaProduto($tabela,$tipo_cobranca);
//         $preco = floatval($preco);
//         if($produto['id_garantido']==0&&$produto['premio']==0){
//             atualizaPrecoFaturamentoProdutoUuid($conexao,$preco,$tipo_cobranca,$produto['uuid']);
//             $valor_produtos += $preco;
//         }
//     }

//     $valor_devolucao = 0;
//     $query = "SELECT di.id_faturamento, di.id_produto, ti.preco, di.uuid, di.percentual FROM devolucao_item di INNER JOIN produtos p ON (p.id = di.id_produto) 
//     INNER JOIN tabela_item ti ON (ti.id_tipo = $tipo_cobranca AND ti.id_tabela=p.id_tabela) where di.id_faturamento = {$id_faturamento}";
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();

//     foreach ($linhas as $key => $produto) {
//         $tabela = buscaTabelaProduto($produto['id_produto']);
//         $preco = buscaPrecoTabelaProduto($tabela,$tipo_cobranca);
//         $preco = floatval($preco);
//         if($produto['percentual']>0){
//           $preco -= $preco*floatval($produto['percentual']/100);
//         }
//         atualizaPrecoFaturamentoDevolucaoProdutoUuid($conexao,$preco,$tipo_cobranca,$produto['uuid']);
//         $valor_devolucao += $preco;
//     }

//     $query = "UPDATE faturamento SET 
//     valor_produtos={$valor_produtos}, 
//     valor_total={$valor_produtos}-{$valor_devolucao}-desconto,
//     valor_liquido = {$valor_produtos} - desconto + valor_frete - valor_creditos
//     WHERE id={$id_faturamento};";
//     return $conexao->exec($query);

// }

// function atualizaPrecoFaturamentoDevolucaoProdutoUuid($conexao,$preco,$tipo_cobranca,$uuid){
//   $query = "UPDATE devolucao_item set preco = {$preco}, valor_total = {$preco} - desconto, tipo_cobranca = {$tipo_cobranca} WHERE uuid='{$uuid}';";
//   return $conexao->exec($query); 
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function atualizaPrecoFaturamentoProdutoUuid($conexao,$preco,$tipo_cobranca,$uuid){
//    $query = "UPDATE faturamento_item set preco = {$preco}, valor_total = {$preco} - desconto, tipo_cobranca = {$tipo_cobranca} WHERE uuid='{$uuid}';";
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function atualizaPrecoPedidoProdutoUuid($preco,$tipo_cobranca,$uuid){
//   return "UPDATE pedido_item set preco = {$preco}, tipo_cobranca = {$tipo_cobranca} WHERE uuid='{$uuid}';";
// }

// --Commented out by Inspection START (12/08/2022 14:47):
//function atualizaPrecoDevolucaoProdutoUuid($preco,$tipo_cobranca,$uuid){
//  return "UPDATE troca_pendente_item set preco = {$preco}, tipo_cobranca = {$tipo_cobranca} WHERE uuid='{$uuid}';";
//}
// --Commented out by Inspection STOP (12/08/2022 14:47)


// function atualizaPrecoPedidoProduto($cliente,$id_produto,$sequencia,$preco,$tipo_cobranca){
//     $query = "UPDATE pedido_item set preco = {$preco}, tipo_cobranca = {$tipo_cobranca}
//     WHERE id_cliente={$cliente} AND id_produto={$id_produto} AND sequencia = {$sequencia}";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function buscaUltimoIdParesGarantidos(){
//     $query = "SELECT COALESCE(MAX(id),0)id FROM garantir_pares";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linha = $resultado->fetch();
//     return $linha['id'];
// }

//function baixaPedidoAcompanhamento($pedido,$data_atual,$usuario){
//    $query = "UPDATE faturamento SET separado = 1, 
//    data_separacao = IF(data_separacao IS NULL,'{$data_atual}',data_separacao),
//    id_separador = IF(id_separador=0 OR id_separador IS NULL,{$usuario},id_separador),
//    conferido = 1,
//    data_conferencia = IF(data_conferencia IS NULL, '{$data_atual}',data_conferencia),
//    id_conferidor = IF(id_conferidor=0 OR id_conferidor IS NULL,{$usuario},id_conferidor),
//    expedido = 1,
//    data_expedicao = IF(data_expedicao IS NULL,'{$data_atual}',data_expedicao),
//    id_expedidor = IF(id_expedidor=0 OR id_expedidor IS NULL,{$usuario},id_expedidor),
//    entregue = 1,
//    data_entrega = IF(data_entrega IS NULL,'{$data_atual}',data_entrega),
//    id_entregador = IF(id_entregador=0 OR id_entregador IS NULL,{$usuario},id_entregador)
//    WHERE id={$pedido};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
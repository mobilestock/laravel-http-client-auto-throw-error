<?php /*
require_once 'conexao.php';
require_once 'troca-pendente.php';
require_once 'saldo.php';

//function insereFaturamentoCliente(
//  $id_cliente,
//  $data,
//  $id_usuario,
//  $observacao,
//  $observacao2,
//  $tipo_frete,
//  $valor_frete,
//  $frete_gratis,
//  $transportadora,
//  $data_envio,
//  $vendedor,
//  $listaEstantes,
//  $conferido,
//  $id_conferente,
//  $data_conferencia,
//  $expedido,
//  $id_expedidor,
//  $data_expedicao,
//  $entregue,
//  $id_entregador,
//  $data_entrega,
//  $tipo_pagamento_frete,
//  $acrescimo,
//  $separado
//) {
//  $query = "INSERT INTO faturamento
//  (id_cliente,
//  data_emissao,
//  id_usuario,
//  situacao,
//  observacao,
//  observacao2,
//  tipo_frete,
//  valor_frete,
//  frete_gratis,
//  transportadora,
//  data_envio,
//  vendedor,
//  lista_painel,
//  conferido,
//  id_conferidor,
//  data_conferencia,
//  expedido,
//  id_expedidor,
//  data_expedicao,
//  entregue,
//  id_entregador,
//  data_entrega,
//  tipo_pagamento_frete,
//  separado)
//  VALUES
//  ({$id_cliente},
//  '{$data}',
//  {$id_usuario},
//  1,
//  '{$observacao}',
//  '{$observacao2}',
//  {$tipo_frete},
//  {$valor_frete},
//  {$frete_gratis},
//  {$transportadora},
//  '{$data_envio}',
//  {$vendedor},
//  '{$listaEstantes}',
//  {$conferido},
//  {$id_conferente},";
//  if ($data_conferencia == NULL) {
//    $query .= "NULL,";
//  } else {
//    $query .= "'{$data_conferencia}',";
//  }
//  $query .= "{$expedido},
//  {$id_expedidor},";
//  if ($data_expedicao == NULL) {
//    $query .= "NULL,";
//  } else {
//    $query .= "'{$data_expedicao}',";
//  }
//  $query .= "{$entregue},
//  {$id_entregador},";
//  if ($data_entrega == NULL) {
//    $query .= "NULL,";
//  } else {
//    $query .= "'{$data_entrega}',";
//  }
//  $query .= "{$tipo_pagamento_frete},
//  {$separado});";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}


// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaFaturamentoItemValor($faturamento, $preco, $id_produto, $situacao)
//{
//  $query = "UPDATE faturamento_item SET preco = {$preco}, valor_total=({$preco}-desconto)
//  WHERE id_faturamento = {$faturamento} AND id_produto={$id_produto} AND situacao={$situacao};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function atualizaFaturamentoDevolucaoItemValor($faturamento, $preco, $id_produto, $situacao)
// {
//   $query = "UPDATE devolucao_item SET preco = {$preco}, valor_total=({$preco}-desconto)
//   WHERE id_faturamento = {$faturamento} AND id_produto={$id_produto} AND situacao={$situacao};";
//   $conexao = Conexao::criarConexao();

//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaFaturamentoItemDesconto($id, $desconto, $id_produto, $situacao)
//{
//  $query = "UPDATE faturamento_item SET
//  desconto = {$desconto},
//  valor_total=(preco-{$desconto})
//  WHERE id_faturamento = {$id}
//  AND id_produto={$id_produto}
//  AND situacao={$situacao};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaFaturamentoItemValorDesconto($faturamento, $unit_desconto, $id_produto)
//{
//  $query = "UPDATE faturamento_item SET desconto={$unit_desconto},valor_total=preco-{$unit_desconto}
//  WHERE id_faturamento = {$faturamento} AND id_produto={$id_produto};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function atualizaDevolucaoItemValorDesconto($faturamento, $unit_desconto, $id_produto)
// {
//   $query = "UPDATE devolucao_item SET desconto={$unit_desconto},valor_total=preco-{$unit_desconto}
//     WHERE id_faturamento = {$faturamento} AND id_produto={$id_produto};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function atualizaFaturamentoDoPedido($id, $valor_bruto, $valor_total, $desconto)
// {
//   $query = "UPDATE faturamento SET
//     valor_produtos = {$valor_bruto},
//     valor_total = {$valor_total},
//     valor_liquido = {$valor_total}+valor_frete-valor_creditos,
//     desconto = {$desconto}
//     WHERE id={$id};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaValorBrutoProdutosFaturados($id)
//{
//  $query = "SELECT SUM(valor_total)valor from faturamento_item WHERE id_faturamento={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function atualizaFaturamentoDoPedidoFrete($id, $frete)
// {
//   $query = "UPDATE faturamento SET
//   valor_frete={$frete},
//   valor_liquido = valor_produtos-valor_devolucao+{$frete}-desconto-valor_creditos
//   WHERE id={$id};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function atualizaFaturamentoDoPedidoComDesconto($id, $valor_bruto, $valor, $desconto, $valor_devolvido)
// {
//   $query = "UPDATE faturamento SET
//   desconto = {$desconto},
//   valor_produtos = {$valor_bruto},
//   valor_liquido = {$valor}+valor_frete-valor_creditos,
//   valor_total = {$valor},
//   valor_devolucao={$valor_devolvido}
//   WHERE id={$id};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizarObservacaoCliente($id_faturamento, $listaEstantes)
//{
//  $query = "UPDATE faturamento SET observacao = concat(observacao,'{$listaEstantes}')
//  WHERE id = {$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function insereObservacaoFaturamento($id_cliente, $string)
//{
//  $query = "UPDATE faturamento SET observacao = '{$string}'
//  WHERE id_cliente = {$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaDevolucoesFaturamento($id)
// {
//   $query = "SELECT di.*, SUM(di.preco) valor, di.preco, COUNT(di.uuid) quantidade,
//     di.situacao, p.descricao produto, di.id_produto, c.razao_social cliente,
//     di.data_hora, di.id_tabela, s.nome nome_situacao
//     FROM devolucao_item di
//     INNER JOIN colaboradores c ON (c.id=di.id_cliente)
//     INNER JOIN situacao s ON (s.id=di.situacao)
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.id_faturamento = {$id}
//     GROUP BY di.id_produto, di.situacao ORDER BY di.data_hora ASC";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaDevolucoesFaturamentoResumo($id)
// {
//   $query = "SELECT di.*, SUM(di.preco) valor, di.preco, COUNT(di.uuid) quantidade,
//     di.situacao, p.descricao produto, di.id_produto, c.razao_social cliente,
//     di.data_hora, di.id_tabela, s.nome nome_situacao
//     FROM devolucao_item di
//     INNER JOIN colaboradores c ON (c.id=di.id_cliente)
//     INNER JOIN situacao s ON (s.id=di.situacao)
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.id_faturamento = {$id}
//     GROUP BY di.preco ORDER BY quantidade";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaFaturamentoDevolucao($id)
// {
//   $query = "SELECT COALESCE(SUM(di.preco),0) valor, COUNT(di.uuid) quantidade
//   FROM devolucao_item di WHERE di.id_faturamento = {$id}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

//function insereFaturamentoClienteItem($id, $item, $conferido, $id_conferente, $data_conferencia)
//{
//  $query = "INSERT INTO faturamento_item (
//    id_faturamento,
//    id_cliente,
//    id_produto,sequencia,
//    tamanho, tipo_cobranca,
//    id_tabela, id_vendedor,
//    id_separador,
//    data_separacao,
//    preco, desconto,
//    valor_total,
//    situacao,
//    defeito,
//    data_hora,
//    cod_barras,
//    uuid,
//    troca_pendente,
//    conferido,
//    id_conferidor,
//    data_conferencia,
//    separado,
//    pedido_cliente,
//    cliente,
//    venda_balcao,
//    data_garantido,
//    garantido_pago)
//    VALUES (
//    {$id},
//    {$item['id_cliente']},
//    {$item['id_produto']},
//    {$item['sequencia']},
//    {$item['tamanho']},
//    {$item['tipo_cobranca']},
//    {$item['id_tabela']},
//    {$item['id_vendedor']},
//    {$item['id_separador']},
//    '{$item['data_separacao']}',
//    {$item['preco']},
//    0,
//    {$item['preco']},
//    {$item['situacao']},
//    {$item['defeito']},
//    '{$item['data_hora']}',
//    '{$item['cod_barras']}',
//    '{$item['uuid']}',
//    '{$item['troca_pendente']}',
//    {$conferido},
//    {$id_conferente},
//    '{$data_conferencia}',
//    {$item['separado']},
//    {$item['pedido_cliente']},
//    '{$item['cliente']}',
//    {$item['venda_balcao']},
//    '{$item['data_garantido']}',
//    {$item['garantido_pago']});";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

// --Commented out by Inspection START (12/08/2022 14:46):
//function verificaSeExisteFaturamentoAberto($id_cliente)
//{
//  $query = "SELECT * FROM faturamento
//  WHERE id_cliente = {$id_cliente} AND situacao=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function insereProdutoDevolucao($id, $item)
// {
//   $query = "";
//   $query .= "INSERT INTO devolucao_item (id_faturamento,id_cliente,id_produto,
//     sequencia, tamanho, tipo_cobranca, id_tabela, id_vendedor, id_separador, preco, desconto, valor_total, situacao,
//     data_hora, cod_barras, uuid, troca_pendente,defeito,descricao_defeito) VALUES ({$id},{$item['id_cliente']},
//     {$item['id_produto']},{$item['sequencia']},{$item['tamanho']},
//     {$item['tipo_cobranca']},{$item['id_tabela']},{$item['id_vendedor']},{$item['id_separador']},
//     {$item['preco']},0,{$item['preco']},{$item['situacao']},'{$item['data_hora']}',
//     '{$item['cod_barras']}','{$item['uuid']}','{$item['troca_pendente']}',{$item['defeito']},'{$item['descricao_defeito']}');";

//   if ($item['uuid_tbl_defeito'] != $item['uuid'] && $item['defeito'] != 0) {

//     $query .= "INSERT INTO defeitos (id_fornecedor,id_vendedor,id_cliente,id_produto,descricao,descricao_defeito,tamanho,preco,sequencia,data_hora,abater,uuid)
//           VALUES ({$item['p_id_fornecedor']},{$item['id_vendedor']},{$item['id_cliente']},{$item['id_produto']},'{$item['p_descricao']}',
//           '{$item['descricao_defeito']}',{$item['tamanho']},{$item['preco']}
//           ,{$item['sequencia']},'{$item['data_hora']}',0,'{$item['uuid']}');";
//   }
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function removeParDoPedido($item)
//{
//  $query = "DELETE FROM pedido_item WHERE uuid='{$item['uuid']}'";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function removeValorFaturamentoItem($id_faturamento, $sequencia)
//{
//  $query = "DELETE FROM faturamento_lancamentos WHERE id_faturamento={$id_faturamento}
//  AND sequencia={$sequencia}";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function excluirFaturamento($id_faturamento)
//{
//  $query = "DELETE FROM faturamento WHERE id = {$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function excluirFaturamentoItem($id_faturamento)
// {
//   $query = "DELETE FROM faturamento_item WHERE id_faturamento = {$id_faturamento};";
//   $conexao = Conexao::criarConexao();
//   $conexao->exec($query);
// }

// function excluirDevolucaoFaturamentoItem($id_faturamento)
// {
//   $query = "DELETE FROM devolucao_item WHERE id_faturamento = {$id_faturamento};";
//   $conexao = Conexao::criarConexao();
//   $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaDescontoFaturamentoItem($id_faturamento, $sequencia, $desconto)
//{
//  $query = "UPDATE faturamento_lancamentos SET desconto = {$desconto},
//  valor_liquido = valor-valor*({$desconto}/100)
//  WHERE id_faturamento={$id_faturamento} AND sequencia={$sequencia};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function mudaFaturamentoEmAberto($id)
//{
//  $query = "UPDATE faturamento SET situacao = 1,
//  data_fechamento = NULL , usuario_fechamento = NULL
//  WHERE id={$id};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaValorProdutosFaturados($id)
// {
//   $query = "SELECT COALESCE(SUM(fi.valor_total),0)valor FROM faturamento_item fi
//     WHERE fi.id_faturamento = {$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

// function buscaValorProdutosDevolvidos($id)
// {
//   $query = "SELECT COALESCE(SUM(di.valor_total),0)valor FROM devolucao_item di
//     WHERE di.id_faturamento = {$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaValorTotalDescontoProdutos($id)
//{
//  $query = "SELECT COALESCE(SUM(fi.desconto),0)desconto FROM faturamento_item fi
//  WHERE fi.id_faturamento = {$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['desconto'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosConfirmadosPedido($id_cliente)
//{
//  $query = "SELECT pi.* FROM pedido_item pi WHERE pi.id_cliente = {$id_cliente}
//  AND (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao=16) AND pi.confirmado=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosConfirmadosSemSeparacao($id_cliente)
//{
//  $query = "SELECT pi.* FROM pedido_item pi WHERE pi.id_cliente = {$id_cliente}
//  AND (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao=16) AND pi.confirmado=1 AND pi.separado=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosNaoConfirmadosPedido($id_cliente)
//{
//  $query = "SELECT pi.* FROM pedido_item pi WHERE pi.id_cliente = {$id_cliente}
//  AND (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao=16) AND pi.confirmado=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaProdutosNaoConfirmadosPedidoSeparados($id_cliente)
// {
//   $query = "SELECT pi.uuid, pi.id_cliente, pi.id_produto, pi.tamanho FROM pedido_item pi WHERE pi.id_cliente = {$id_cliente}
//   AND (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao = 10 OR pi.situacao = 11 OR pi.situacao=16) AND pi.separado=1 AND pi.confirmado=0;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosNaoConfirmadosPedidoVendidos($id_cliente)
//{
//  $query = "SELECT pi.* FROM pedido_item pi WHERE pi.id_cliente = {$id_cliente} AND pi.situacao = 6 AND pi.confirmado=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosDevolvidosPedido($id_cliente)
//{
//  $query = "SELECT pi.*,d.uuid uuid_tbl_defeito,p.id_fornecedor p_id_fornecedor,p.descricao p_descricao FROM pedido_item pi
//  INNER JOIN produtos p ON (pi.id_produto=p.id) LEFT OUTER JOIN defeitos d oN (d.uuid=pi.uuid)
//  WHERE pi.id_cliente = {$id_cliente} AND pi.situacao = 12 AND pi.confirmado = 1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 16:54):
//function buscaFaturamento($id_faturamento)
//{
//  $query = "SELECT f.*, c.razao_social cliente, s.nome nome_situacao,
//  SUM(fi.preco) valor, COUNT(fi.uuid) pares, u.nome usuario_pedido,
//  tf.nome nome_frete, uu.nome usuario_financeiro FROM faturamento f
//  INNER JOIN faturamento_item fi ON (fi.id_faturamento = f.id)
//  INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//  INNER JOIN situacao_pedido s ON (s.id=f.situacao)
//  LEFT OUTER JOIN usuarios u ON (u.id=f.id_usuario)
//  LEFT OUTER JOIN usuarios uu ON (uu.id=f.usuario_fechamento)
//  INNER JOIN tipo_frete tf ON (tf.id=f.tipo_frete)
//  WHERE f.id = {$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 16:54)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaFaturamentoFechamento($id_faturamento)
//{
//  $query = "SELECT
//  f.id,
//  f.id_cliente,
//  f.data_emissao,
//  f.valor_frete,
//  f.desconto,
//  f.valor_total,
//  f.valor_liquido,
//  c.razao_social cliente,
//  s.nome nome_situacao,
//  SUM(fi.valor_total) valor_produtos,
//  COUNT(uuid) pares,
//  u.nome usuario_pedido,
//  COALESCE(tf.nome,'Sem Frete') nome_frete,
//  uu.nome usuario_financeiro
//  FROM faturamento f
//  INNER JOIN faturamento_item fi ON (fi.id_faturamento = f.id)
//  INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//  INNER JOIN situacao s ON (s.id=f.situacao)
//  INNER JOIN usuarios u ON (u.id=f.id_usuario)
//  LEFT OUTER JOIN usuarios uu ON (uu.id=f.usuario_fechamento)
//  LEFT OUTER JOIN tipo_frete tf ON (tf.id=f.tipo_frete)
//  WHERE f.id = {$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosFaturamento(int $id_faturamento)
//{
//  $query = "SELECT * FROM faturamento_item
//  WHERE id_faturamento = {$id_faturamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosFaturamentoSemSeparacao(int $id_faturamento)
//{
//  $query = "SELECT * FROM faturamento_item
//  WHERE id_faturamento = {$id_faturamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaTabelaFaturamento(int $id_faturamento)
//{
//  $query = "SELECT tabela_preco FROM faturamento
//    WHERE separado=0 AND id = {$id_faturamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  if ($linha) {
//    return $linha['tabela_preco'];
//  } else {
//    return 0;
//  }
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosFaturamentoEntregue($id_faturamento)
//{
//  $query = "SELECT id_produto,tamanho FROM faturamento_item
//  WHERE id_faturamento = {$id_faturamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaProdutosDevolvidosFaturamento($id_faturamento)
//{
//  $query = "SELECT * FROM devolucao_item
//  WHERE id_faturamento = {$id_faturamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaFaturamentoItem($id_faturamento)
// {
//   $query = "SELECT SUM(fi.preco) valor, fi.preco, COUNT(fi.uuid) quantidade, fi.tipo_cobranca,
//   fi.situacao, fi.sequencia, p.descricao produto, fi.id_produto, c.razao_social cliente, fi.cliente nome_cliente,
//   fi.data_hora, fi.id_tabela, SUM(fi.desconto)desconto , SUM(fi.valor_total)valor_total, s.nome nome_situacao
//   FROM faturamento f
//   INNER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//   INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//   INNER JOIN situacao s ON (s.id=fi.situacao)
//   INNER JOIN produtos p ON (p.id=fi.id_produto)
//   WHERE f.id = {$id_faturamento}
//   GROUP BY f.id, fi.id_produto, fi.situacao, fi.preco, fi.cliente
//   ORDER BY f.data_emissao, fi.id_produto, fi.situacao, fi.preco ASC";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaFaturamentoItemResumo($id_faturamento)
//{
//  $query = "SELECT SUM(fi.preco) valor, fi.preco, COUNT(fi.uuid) quantidade, fi.tipo_cobranca,
//  fi.situacao, fi.sequencia, p.descricao produto, fi.id_produto, c.razao_social cliente,
//  fi.data_hora, fi.id_tabela, SUM(fi.desconto)desconto , SUM(fi.valor_total)valor_total, s.nome nome_situacao
//  FROM faturamento f
//  INNER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//  INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//  INNER JOIN situacao s ON (s.id=fi.situacao)
//  INNER JOIN produtos p ON (p.id=fi.id_produto)
//  WHERE f.id = {$id_faturamento}
//  GROUP BY fi.preco
//  ORDER BY fi.preco";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaFaturamentoItemDetalhe($id_faturamento)
// {
//   $query = "SELECT fi.tamanho, fi.preco, p.descricao produto, fi.id_produto,
//   uv.nome vendedor, us.nome separador
//   FROM faturamento_item fi
//   INNER JOIN produtos p ON (p.id=fi.id_produto)
//   LEFT OUTER JOIN usuarios uv ON (uv.id=fi.id_vendedor)
//   LEFT OUTER JOIN usuarios us ON (us.id=fi.id_separador)
//   WHERE fi.id_faturamento = {$id_faturamento}
//   ORDER BY fi.preco";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaFaturamentoItemDevolucaoDetalhe($id_faturamento)
// {
//   $query = "SELECT di.tamanho, di.preco, p.descricao produto, di.id_produto
//   FROM devolucao_item di
//   INNER JOIN produtos p ON (p.id=di.id_produto)
//   WHERE di.id_faturamento = {$id_faturamento}
//   ORDER BY di.preco";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaFaturamentoItemAtualiza($id_faturamento)
//{
//  $query = "SELECT * FROM faturamento_item
//  WHERE id_faturamento = {$id_faturamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaFaturamentoItemDevolucaoAtualiza($id_faturamento)
//{
//  $query = "SELECT * FROM devolucao_item
//  WHERE id_faturamento = {$id_faturamento} GROUP BY id_produto";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaFaturamentoItemGrade($id_faturamento, $id_produto, $situacao, $preco)
// {
//   $query = "SELECT fi.tamanho, count(fi.tamanho) quantidade FROM faturamento_item fi
//   WHERE fi.id_faturamento = {$id_faturamento} AND fi.id_produto = {$id_produto}
//   AND fi.situacao = {$situacao} AND fi.preco={$preco}
//   GROUP BY fi.situacao, fi.tamanho, fi.preco ORDER BY fi.tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaFaturamentoItemGradeRelatorio($id_faturamento, $id_produto, $situacao, $preco, $cliente)
// {
//   if ($cliente == null) {
//     $nome_cliente = "(cliente is null OR cliente='')";
//   } else {
//     $nome_cliente = "cliente = '{$cliente}'";
//   }
//   $query = "SELECT fi.tamanho, count(fi.tamanho) quantidade FROM faturamento_item fi
//   WHERE fi.id_faturamento = {$id_faturamento} AND fi.id_produto = {$id_produto}
//   AND fi.situacao = {$situacao} AND fi.preco={$preco} AND {$nome_cliente}
//   GROUP BY fi.situacao, fi.tamanho, fi.preco ORDER BY fi.tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaCondicaoPagamento($condicao_pagamento)
//{
//  $query = "SELECT c.* from condicao_pagamento c WHERE id={$condicao_pagamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaUltimaSequenciaFaturamentoLanc($id_faturamento)
//{
//  $query = "SELECT MAX(sequencia) sequencia FROM faturamento_lancamentos
//  WHERE id_faturamento = {$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['sequencia'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaFaturamentoLancamentos($id_faturamento)
//{
//  $query = "SELECT fl.*, d.nome documento, sl.nome nome_situacao,
//  fl.desconto desconto
//  FROM faturamento_lancamentos fl
//  INNER JOIN documentos d ON (fl.documento = d.id)
//  INNER JOIN situacao_lancamento sl ON (fl.situacao = sl.id)
//  WHERE fl.id_faturamento={$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaDescontoDocumentos($documento)
//{
//  $query = "SELECT desconto from documentos
//  WHERE id = {$documento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['desconto'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaFaturamento($id, $valor_produtos, $valor, $valor_devolucao, $observacao)
//{
//  $query = "UPDATE faturamento SET
//  valor_produtos = {$valor_produtos},
//  valor_total = {$valor_produtos},
//  valor_liquido= {$valor},
//  observacao='{$observacao}' WHERE id={$id};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function atualizaFaturamentoValorRestante($id_faturamento, $valor_informado)
// {
//   $query = "UPDATE faturamento SET valor_restante = valor_prazo-{$valor_informado}
//   WHERE id={$id_faturamento};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaValorFaturamentoDistribuido($id_faturamento)
//{
//  $query = "SELECT SUM(valor) valor FROM faturamento_lancamentos
//  WHERE id_faturamento = {$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 16:50):
//function listarFaturamento($filtro)
//{
//  $query = "SELECT f.id, f.data_fechamento, f.valor_liquido, f.data_emissao, f.valor_total,
//  c.razao_social, COUNT(fi.id_produto)pares, s.nome nome_situacao
//  FROM faturamento f
//  INNER JOIN colaboradores c ON(c.id = f.id_cliente)
//  INNER JOIN faturamento_item fi ON (fi.id_faturamento = f.id)
//  INNER JOIN situacao_pedido s ON(s.id=f.situacao)
//  LEFT OUTER JOIN acertos a ON (a.numero_documento=f.id)
//  LEFT OUTER JOIN acertos_documentos ad ON(ad.id_acerto=a.id)
//  {$filtro} AND f.situacao>=2 AND f.separado=1 GROUP BY f.id DESC LIMIT 25;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 16:50)


// --Commented out by Inspection START (12/08/2022 14:46):
//function listarFaturamentoPedidos($filtro)
//{
//  $query = "SELECT f.id, f.data_fechamento, f.valor_liquido, f.data_emissao, f.valor_total,
//  c.razao_social, COUNT(fi.id_produto)pares, s.nome nome_situacao
//  FROM faturamento f
//  INNER JOIN colaboradores c ON(c.id = f.id_cliente)
//  INNER JOIN faturamento_item fi ON (fi.id_faturamento = f.id)
//  INNER JOIN situacao_pedido s ON(s.id=f.situacao)
//  {$filtro} AND f.situacao>=2 GROUP BY f.id DESC LIMIT 25;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 16:54):
//function listarFaturamentoEmAberto($filtro)
//{
//  $data_vencimento = DATE('Y-m-d H:m:s');
//  $query = "SELECT f.id,f.prioridade, f.tabela_preco, f.data_emissao, c.razao_social, f.valor_liquido,  COALESCE(c.telefone,'') telefone,
//  f.tipo_frete, f.frete_gratis, f.status_separacao as status, c.id as id_colab, f.conta_deposito, f.separado,
//  (SELECT count(lf.id)lancamentos from lancamento_financeiro lf WHERE lf.situacao = 1
//  AND DATE(lf.data_vencimento) < DATE('{$data_vencimento}') and lf.id_colaborador=f.id_cliente) lancamentos_vencidos,
//  (SELECT COUNT(ch.id) from cheques ch WHERE ch.situacao=4 and ch.recebido_de=f.id_cliente) cheques_sem_fundo,
//  (SELECT SUM(fi.valor_total) FROM faturamento_item fi WHERE fi.id_faturamento = f.id) valor_produtos,
//  (SELECT COUNT(*) FROM faturamento WHERE faturamento.id_cliente = id_colab) as tCompras,
//  COUNT(fi.id_faturamento)pares, s.nome nome_situacao FROM faturamento f
//  LEFT OUTER JOIN colaboradores c ON(c.id = f.id_cliente)
//  LEFT OUTER JOIN faturamento_item fi ON (fi.id_faturamento = f.id)
//  LEFT OUTER JOIN situacao s ON(s.id=f.situacao)
//  {$filtro} AND f.situacao = 1 GROUP BY f.id ORDER BY tCompras ASC;"; //ORDER BY f.acrescimo_pares DESC, f.data_emissao ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll(PDO::FETCH_ASSOC);
//}
// --Commented out by Inspection STOP (12/08/2022 16:54)




// --Commented out by Inspection START (12/08/2022 16:54):
//function listarFaturamentosFaturados($filtros)
//{
//  $query = "SELECT f.id,d.nome as tipo_documento, f.data_fechamento, f.valor_liquido, f.data_emissao, f.valor_total,
//              c.razao_social, COUNT(fi.id_produto)pares, s.nome nome_situacao
//              FROM faturamento f
//              INNER JOIN colaboradores c ON(c.id = f.id_cliente)
//              INNER JOIN faturamento_item fi ON (fi.id_faturamento = f.id)
//              INNER JOIN situacao_pedido s ON(s.id=f.situacao)
//              INNER JOIN lancamento_financeiro lf ON lf.numero_documento = f.id
//              INNER JOIN documentos d ON d.id = lf.documento
//            where f.situacao>=2";
//
//  if (isset($filtros['faturamento']) && !empty($filtros['faturamento'])) {
//    $query .= " AND f.id = {$filtros['faturamento']}";
//  };
//  if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
//    $query .= " AND c.id = {$filtros['cliente']}";
//  };
//  if (isset($filtros['documento']) && !empty($filtros['documento'])) {
//    $query .= " AND d.id = {$filtros['documento']}";
//  };
//  if ($filtros['valor_de'] && $filtros['valor_ate'] && !empty($filtros['valor_de']) && !empty($filtros['valor_ate'])) {
//    $query .= " AND f.valor_liquido BETWEEN '{$filtros['valor_de']}' and '{$filtros['valor_ate']}'";
//  };
//  if ($filtros['data_inicial'] && $filtros['data_fim'] && !empty($filtros['data_inicial']) && !empty($filtros['data_fim'])) {
//    $query .= " AND f.data_fechamento BETWEEN '{$filtros['data_inicial']} 00:00:00' and '{$filtros['data_fim']} 23:59:00'";
//  };
//
//  $query .= ' GROUP BY f.id DESC LIMIT 100;';
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll(PDO::FETCH_ASSOC);
//}
// --Commented out by Inspection STOP (12/08/2022 16:54)


// --Commented out by Inspection START (12/08/2022 14:46):
//function listaFaturamentoProdutos($cliente)
//{
//  $query = "SELECT fi.* FROM faturamento_item fi
//  WHERE fi.id_cliente={$cliente} AND (fi.situacao<>8 OR fi.situacao<>12)";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaPrecoFaturamentoProduto($cliente, $id_produto, $sequencia, $preco, $tipo_cobranca)
//{
//  $query = "UPDATE faturamento_item set preco = {$preco}, valor_total = {$preco}-desconto, tipo_cobranca = {$tipo_cobranca}
//  WHERE id_cliente={$cliente} AND id_produto={$id_produto} AND sequencia = {$sequencia}";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaTabelaPrecoFaturamentoCliente($id_cliente, $tipo_cobranca)
//{
//  $query = "UPDATE faturamento set tabela_preco = $tipo_cobranca WHERE id_cliente={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function atualizaPrecoDevolucaoProduto($cliente, $id_produto, $sequencia, $preco, $tipo_cobranca)
// {
//   $query = "UPDATE devolucao_item set preco = {$preco},  valor_total = {$preco}-desconto, tipo_cobranca = {$tipo_cobranca}
//   WHERE id_cliente={$cliente} AND id_produto={$id_produto} AND sequencia = {$sequencia}";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function atualizaPrecoDevolucaoFaturamentoProduto($id, $id_produto, $preco, $tipo_cobranca)
// {
//   $query = "UPDATE devolucao_item set preco = {$preco},  valor_total = {$preco}-desconto, tipo_cobranca = {$tipo_cobranca}
//   WHERE id_faturamento={$id} AND id_produto={$id_produto};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 16:54):
//function buscaListaFaturamentoHistorico()
//{
//  $query = "SELECT f.*, c.razao_social cliente FROM faturamento f
//  INNER JOIN colaboradores c ON (c.id = f.id_cliente)
//  ORDER BY f.data_emissao DESC LIMIT 20;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 16:54)


// --Commented out by Inspection START (12/08/2022 16:54):
//function buscaFaturamentoHistorico($id_faturamento)
//{
//  $query = "SELECT f.id,
//  f.data_emissao,
//  f.valor_frete,
//  f.valor_total,
//  f.valor_frete,
//  f.desconto,
//  sp.nome situacao,
//  f.valor_liquido,
//  c.razao_social cliente,
//  uv.nome vendedor,
//  tf.nome tipo_frete
//  FROM faturamento f
//  INNER JOIN colaboradores c ON (c.id = f.id_cliente)
//  INNER JOIN usuarios uv ON (uv.id = f.vendedor)
//  INNER JOIN situacao_pedido sp ON (sp.id = f.situacao)
//  LEFT OUTER JOIN tipo_frete tf ON (tf.id = f.tipo_frete)
//  WHERE f.id={$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 16:54)




// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaTipoPagamentoFreteTransportadora($transportadora)
//{
//  $query = "SELECT tipo_pagamento_frete FROM colaboradores
//  WHERE id = {$transportadora};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['tipo_pagamento_frete'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


//
// --Commented out by Inspection START (12/08/2022 14:46):
//function listaFaturamentoAcompanhamento($filtro)
//{
//  $query = "SELECT DISTINCT fi.id_cliente, f.data_emissao, f.id,
//  f.situacao, f.separado, f.conferido,f.expedido,
//  f.entregue, f.data_fechamento,
//  f.data_conferencia, f.data_expedicao, f.data_separacao,
//  f.data_entrega, c.razao_social cliente,
//  uf.nome faturador, uc.nome conferente, us.nome separador,
//  uex.nome expedidor, uen.nome entregador FROM faturamento f
//  INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//  LEFT OUTER JOIN usuarios uf ON (uf.id=f.usuario_fechamento)
//  LEFT OUTER JOIN usuarios us ON (us.id=f.id_separador)
//  LEFT OUTER JOIN usuarios uc ON (uc.id=f.id_conferidor)
//  LEFT OUTER JOIN usuarios uex ON (uex.id=f.id_expedidor)
//  LEFT OUTER JOIN usuarios uen ON (uen.id=f.id_entregador)
//  LEFT OUTER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//  LEFT OUTER JOIN produtos p ON (p.id=fi.id_produto)
//  WHERE 1=1 {$filtro} ORDER BY f.conferido, f.expedido, f.entregue,
//  f.data_emissao DESC, f.data_fechamento DESC LIMIT 50;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaFaturamentoAcompanhamento($id)
// {
//   $query = "SELECT f.*, fr.nome nome_freteiro, c.razao_social cliente, uv.nome u_vendedor, c.id id_cliente,
//   sp.nome situacao_pedido, tf.nome nome_frete, ct.razao_social nome_transportadora,
//   uf.nome u_faturamento, uc.nome u_conferencia, uex.nome u_expedicao, uen.nome u_entrega
//   FROM faturamento f
//   INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//   LEFT OUTER JOIN usuarios uv ON (uv.id=f.vendedor)
//   INNER JOIN situacao_pedido sp ON (sp.id=f.situacao)
//   LEFT OUTER JOIN tipo_frete tf ON (tf.id=f.tipo_frete)
//   LEFT OUTER JOIN colaboradores ct ON (ct.id = f.transportadora)
//   LEFT OUTER JOIN usuarios uf ON (uf.id=f.usuario_fechamento)
//   LEFT OUTER JOIN usuarios uc ON (uc.id=f.id_conferidor)
//   LEFT OUTER JOIN usuarios uex ON (uex.id=f.id_expedidor)
//   LEFT OUTER JOIN usuarios uen ON (uen.id=f.id_entregador)
//   LEFT OUTER JOIN freteiro fr ON (fr.id=f.freteiro)
//   WHERE f.id={$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// function verificaSeEstaEmUsoFaturamento($id_faturamento)
// {
//   $query = "SELECT em_uso FROM faturamento WHERE id={$id_faturamento};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['em_uso'];
// }

// function atualizaUsoFaturamento($id_faturamento, $usuario)
// {
//   $query = "UPDATE faturamento set em_uso = {$usuario}
//   WHERE id={$id_faturamento};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function limpaUsuarioFaturamento($usuario)
// {
//   $query = "UPDATE faturamento set em_uso = 0
//   WHERE em_uso={$usuario};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function existeFaturamentoNaoEntregue($id_cliente)
//{
//  $query = "SELECT * FROM faturamento WHERE id_cliente={$id_cliente} AND entregue = 0";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaUltimaSequenciaDevolucaoManual()
//{
//  $query = "SELECT COALESCE(MAX(sequencia),0) sequencia FROM devolucao_item
//  WHERE id_faturamento = 1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['sequencia'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


//function buscaUltimoFaturamentoCliente($id_cliente)
//{
//  $query = "SELECT faturamento.id FROM faturamento WHERE faturamento.id_cliente={$id_cliente} GROUP BY faturamento.data_emissao DESC LIMIT 1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch(PDO::FETCH_ASSOC);
//  return $linha;
//}

// function buscaFaturamentoClienteAtendimento($id_cliente)
// {
//   //$query="SELECT faturamento.id, faturamento.data_emissao FROM faturamento WHERE faturamento.id_cliente={$id_cliente} AND faturamento.tipo_frete=2  order by data_emissao DESC LIMIT 1;";
//   $query = "SELECT faturamento.id, faturamento.data_emissao,
//           DATEDIFF(NOW(),faturamento.data_emissao) AS diferenca
//             FROM faturamento
//               WHERE faturamento.origem_faturamento = 'MS'
//                AND faturamento.id_cliente={$id_cliente}
//                 AND faturamento.situacao=2
//                   GROUP BY faturamento.id
//                   order by data_emissao DESC ;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetchAll();
//   return $linha;
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaUltimosTrêsFaturamentoCliente($id_cliente)
//{
//  //$query="SELECT faturamento.id, faturamento.data_emissao FROM faturamento WHERE faturamento.id_cliente={$id_cliente} AND faturamento.tipo_frete=2  order by data_emissao DESC LIMIT 3;";
//  $query = "SELECT faturamento.id, faturamento.data_emissao
//            FROM faturamento WHERE faturamento.id_cliente={$id_cliente}
//              order by data_emissao DESC LIMIT 3;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetchAll();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaProdutosFaturamentoSeparacao($id)
// {
//   $query = "SELECT fi.id_produto, fi.tamanho, fi.sequencia, fi.uuid FROM faturamento_item fi WHERE fi.id_faturamento={$id} AND fi.situacao=6;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:46):
//function qteFaturamentoAberto($cliente)
//{
//  $query = "SELECT COALESCE(COUNT(f.id_cliente),0)faturamento FROM faturamento f WHERE f.id_cliente={$cliente} AND f.situacao=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['faturamento'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaIdClienteNoFaturamento($id)
//{
//  $query = "SELECT id_cliente FROM faturamento WHERE id={$id}; ";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['id_cliente'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaHistoricoFaturamento($id, $id_cliente)
//{
//  $query = "SELECT hp.*, u.nome usuario FROM historico_pedido hp
//  INNER JOIN usuarios u ON (u.id=hp.usuario)
//  WHERE hp.faturamento={$id} AND hp.id_cliente={$id_cliente} ORDER BY hp.data_hora ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaOdemSeparacaoPorFaturamento($id_faturamento)
// {
//   $query = "SELECT osi.id_sep FROM ordem_separacao_item osi
//   WHERE osi.id_faturamento={$id_faturamento} GROUP BY osi.id_sep;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function atualizaValoresFaturamentoLista($id)
// {
//   $valor_produtos = buscaValorProdutosFaturados($id);
//   $valor_devolvido = buscaValorProdutosDevolvidos($id);

//   $valor = $valor_produtos - $valor_devolvido;

//   if ($valor < 0) {
//     $valor = $valor * -1;
//     $tipo = 'P';
//   } else if ($valor > 0) {
//     $tipo = 'R';
//   }

//   $query = "UPDATE faturamento SET valor_liquido = {$valor}+valor_frete, valor_total = {$valor}
//     WHERE id={$id};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function buscaCreditoFaturamento(int $id_faturamento)
// {
//   $query = "SELECT valor FROM credito WHERE id_pedido={$id_faturamento}; ";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

// function removeCreditoFaturamento($id_faturamento)
// {
//   $query = "DELETE FROM credito WHERE id_pedido={$id_faturamento}; ";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }
//
//function limparBaixaLancamentoFinanceiro($usuario, $id_faturamento)
//{
//  $query = "UPDATE lancamento_financeiro set
//    situacao=1,
//    id_usuario_edicao={$usuario},
//    data_pagamento=NULL,
//    pedido_destino=NULL WHERE
//    pedido_destino={$id_faturamento}; ";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
//Condicao de Reembolso Status Estorno Adicionada
// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaLancamentosDeCreditoEmAberto($id_cliente)
//{
//  $query = "SELECT
//              lancamento_financeiro.id,
//              lancamento_financeiro.tipo,
//              COALESCE(CASE lancamento_financeiro.origem
//                WHEN 'TR' THEN 'Troca'
//                WHEN 'PC' THEN 'Pagamento de Crédito'
//                WHEN 'CP' THEN 'Correção de Par'
//                WHEN 'AT' THEN 'Atendimento'
//                WHEN 'AU' THEN 'Automático'
//                WHEN 'CP' THEN 'Manual'
//                WHEN 'CM' THEN 'Credito Mobile'
//              END,'') origem,
//              lancamento_financeiro.valor,
//              lancamento_financeiro.data_emissao,
//              lancamento_financeiro.data_vencimento
//            FROM lancamento_financeiro
//            WHERE lancamento_financeiro.origem not in ('AU','FA')
//                AND lancamento_financeiro.situacao = 1
//                AND lancamento_financeiro.id_colaborador ={$id_cliente}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


/*function buscaLancamentoDeDebitoEmAberto($id_cliente) Jose 17/12/2020
{
  $query = "SELECT lf.id, lf.valor, lf.data_emissao, lf.data_vencimento
  FROM lancamento_financeiro lf WHERE lf.id_colaborador={$id_cliente} AND lf.tipo='R' AND lf.situacao=1 ORDER BY lf.data_vencimento;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}*/

// --Commented out by Inspection START (12/08/2022 14:46):
//function listaFaturamentosCliente()
//{
//  $query = "SELECT f.*, COUNT(fi.id_produto)pares, c.razao_social, c.tipo_tabela, c.telefone,
//  os.bloqueado, os.id id_separacao FROM faturamento f
//  INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//  LEFT OUTER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//  LEFT OUTER JOIN ordem_separacao os ON (os.id_faturamento=f.id)
//  WHERE f.situacao=1 GROUP BY f.id;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaUltimaTransportadoraCliente(int $id_cliente)
//{
//  $query = "SELECT c.razao_social FROM faturamento f
//  INNER JOIN colaboradores c ON(f.transportadora = c.id)
//  WHERE f.id_cliente={$id_cliente} AND f.transportadora>=1 order by f.data_emissao DESC limit 1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  if ($linha) {
//    return $linha;
//  } else {
//    return false;
//  }
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


//function rotinaExclusaoFaturamento($id_faturamento, $id_user)
//{
//  $conexao = Conexao::criarConexao();
//  $usuario = $id_user;
//  $faturamento = buscaFaturamento($id_faturamento);
//  $id_cliente = $faturamento['id_cliente'];
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = date('Y-m-d H:i:s');
//
//  $sql = "UPDATE faturamento set acrescimo_pares = 0 where id in (SELECT id FROM FATURAMENTO where acrescimo_pares = $id_faturamento);";
//  $conexao->query($sql);
//
//  $sql = "INSERT INTO historico_pedido(id_cliente,faturamento,descricao,usuario,data_hora)
//  VALUES ($id_cliente,$id_faturamento,'Excluiu faturamento.', $id_user, '$data')";
//  if (!$resultado = $conexao->query($sql)) {
//    return;
//  }
//
//  $sql = "INSERT INTO historico_pedido_item ( id_pedido, id_produto,tamanho, status)
//    SELECT  $id_faturamento, faturamento_item.id_produto,faturamento_item.tamanho, 'Excluido.'
//    FROM    faturamento_item
//    WHERE   faturamento_item.id_faturamento = $id_faturamento;";
//  if (!$resultado = $conexao->query($sql)) {
//    return;
//  }
//
//  //buscar produtos da devolucao
//  $produtosDevolvidos = buscaProdutosDevolvidosFaturamento($id_faturamento);
//
//  //voltar pares de devolucao com troca pendente
//  if ($produtosDevolvidos) {
//    retornaProdutoTrocaPendenteDoPedido($id_cliente, $produtosDevolvidos, $data);
//  }
//
//  removeSaldoClienteFaturado($id_cliente, $id_faturamento);
//  atualizaCreditoAbatido($id_cliente, $id_faturamento);
//
//  if ($lancamentos = buscaLancamentosFaturamentoExcluir($id_faturamento)) {
//    removeLancamentosFaturamento($id_faturamento);
//  }
//
//
//  if ($idOrdem = buscaOdemSeparacaoPorFaturamento($id_faturamento)) {
//    foreach ($idOrdem as $key => $id) {
//      $sql = "DELETE FROM ordem_separacao WHERE id={$id['id_sep']};";
//      $sql .= "DELETE FROM ordem_separacao_item WHERE id_sep = {$id['id_sep']};";
//    }
//    $conexao->query($sql);
//  }
//
//
//  excluirFaturamento($id_faturamento);
//  excluirFaturamentoItem($id_faturamento);
//  excluirDevolucaoFaturamentoItem($id_faturamento);
//
//  return true;
//}

// --Commented out by Inspection START (12/08/2022 16:50):
//function buscaHistoricoPedidos($filtros)
//{
//  $conexao = Conexao::criarConexao();
//  $faturamentos = [];
//  $sql = "SELECT f1.descricao as status,
//          f1.faturamento,
//          f1.data_hora as data,
//          fa.valor_total as valor,
//          IF(f1.descricao = 'Excluiu faturamento.',
//            (SELECT count(id)
//                FROM historico_pedido_item
//              WHERE id_pedido = f1.faturamento),
//            (SELECT count(id_produto)
//                FROM faturamento_item
//              WHERE id_faturamento = f1.faturamento)) as pares
//          FROM historico_pedido f1
//          LEFT JOIN faturamento fa ON f1.faturamento = fa.id
//          where f1.id in (SELECT max(id) from historico_pedido group by faturamento)
//          AND f1.id_cliente = {$filtros['cliente']}";
//
//  if ($filtros['valor_de'] && $filtros['valor_ate']) {
//    $sql .= " AND fa.valor_total BETWEEN '{$filtros['valor_de']}' and '{$filtros['valor_ate']}'";
//  };
//
//  if ($filtros['data_inicial'] && $filtros['data_fim']) {
//    $sql .= " AND f1.data_hora BETWEEN '{$filtros['data_inicial']}' and '{$filtros['data_fim']}'";
//  };
//
//  $sql .= " order by f1.faturamento DESC";
//  $resultado = $conexao->query($sql);
//  $faturamentos = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  return $faturamentos;
//}
// --Commented out by Inspection STOP (12/08/2022 16:50)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaUltimoFaturamento()
//{
//  $query = "SELECT MAX(id) id FROM faturamento;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['id'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaPrecoProdutoFaturamento(int $faturamento, float $preco, int $sequencia)
//{
//  $conexao = Conexao::criarConexao();
//  $query = "UPDATE faturamento_item SET preco = {$preco}, valor_total={$preco}-desconto WHERE id_faturamento={$faturamento} AND sequencia={$sequencia};";
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function atualizaValorTotalDoFaturamento(int $faturamento, float $desconto, float $frete)
// {
//   $conexao = Conexao::criarConexao();

//   $query = "SELECT SUM(preco)preco, SUM(valor_total)valor_total, SUM(desconto)desconto FROM faturamento_item WHERE id_faturamento={$faturamento};";
//   $resultado = $conexao->query($query);
//   $produto = $resultado->fetch();

//   $query = "SELECT SUM(preco)preco, SUM(valor_total)valor_total, SUM(desconto)desconto FROM devolucao_item WHERE id_faturamento={$faturamento};";
//   $resultado = $conexao->query($query);
//   $devolucao = $resultado->fetch();

//   $valor_total = $produto['valor_produto'] - $produto['desconto'];
//   $valor_liquido = $valor_total + $frete - $desconto;

//   $query = "UPDATE faturamento SET
//       valor_produtos = {$produto['valor_produto']},
//       valor_total = {$valor_total},
//       valor_frete = {$frete},
//       desconto = {$desconto},
//       valor_liquido = {$valor_liquido}-valor_creditos WHERE id_faturamento={$faturamento};";
//   return $conexao->exec($query);
// }

function buscaListaDocumentos()
{
  $query = "SELECT * FROM documentos ORDER BY nome;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll(PDO::FETCH_ASSOC);
}

// function atualizaTabelaPedido_item_corrigir(string $uuid)
// {
//   $conexao = Conexao::criarConexao();
//   $query = "INSERT INTO pedido_item_corrigir (uuid) VALUES ('{$uuid}');";
//   return $conexao->exec($query);
// }

// function verificaSeFaturamentoPossuiItems($idFaturamento, $idConferidor)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $conexao = Conexao::criarConexao();
//   $sql = "SELECT * FROM faturamento
//           WHERE id = {$idFaturamento}";

//   $resultado = $conexao->query($sql);
//   if ($faturamentos = $resultado->fetchAll(PDO::FETCH_ASSOC)) { //se todos os items do faturamento foram corrigidos/excluidos
//     foreach ($faturamentos as $key => $faturamento) {
//       $sql = "SELECT count(id_faturamento) qtd from faturamento_item where id_faturamento = {$faturamento['id']}";
//       $resultado = $conexao->query($sql);
//       $res = $resultado->fetchAll(PDO::FETCH_ASSOC);
//       if ($res[0]['qtd'] == 0) {
//         //buscar produtos da devolucao
//         $produtosDevolvidos = buscaProdutosDevolvidosFaturamento($faturamento['id']);

//         //voltar pares de devolucao com troca pendente
//         if ($produtosDevolvidos) {
//           retornaProdutoTrocaPendenteDoPedido($faturamento['id_cliente'], $produtosDevolvidos, $data);
//         }

//         $query = "DELETE from faturamento where id = '{$faturamento['id']}'";
//         return $conexao->exec($query);
//       }
//       $query = "UPDATE pares_corrigidos set conferido = 1, id_conferidor = {$idConferidor}, data_conferencia = '{$data}' where id_faturamento = '{$faturamento['id']}'";
//       $conexao->exec($query);
//     }
//   }
//   return;
// }

// function atualizaProdutoLocalizado($produto, $idConferidor)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $query = "UPDATE pares_corrigidos
//             SET localizado = 1, id_conferidor = {$idConferidor}, data_conferencia = '{$data}'
//             WHERE id_faturamento = {$produto['id_faturamento']} and tamanho = {$produto['tamanho']} and uuid = '{$produto['uuid']}';";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }
// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaPedidosConferidosNaoFinalizado($id_cliente)
//{
//  $query = "SELECT COUNT(*) as contador FROM `faturamento` WHERE faturamento.id_cliente = {$id_cliente} and faturamento.separado = 1  and faturamento.conferido = 0 and faturamento.tipo_frete<>0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)

// --Commented out by Inspection START (12/08/2022 14:46):
//function PedidoPaiJaConferido($id_cliente)
//{
//  $query = "SELECT COUNT(*) as contador FROM `faturamento` WHERE tipo_frete<>0 and faturamento.id_cliente = {$id_cliente} and faturamento.conferido = 1 and faturamento.entregue=0 ";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// function buscaLogsMovimentacao($filtros)
// {
//   $conexao = Conexao::criarConexao();
//   $faturamentos = [];
//   $sql = "SELECT DATE_FORMAT(log_estoque_movimentacao.data,'%d/%m/%Y %H:%i:%S') data,
//             log_estoque_movimentacao.id_produto,
//             log_estoque_movimentacao.tamanho,
//             log_estoque_movimentacao.oldEstoque,
//             log_estoque_movimentacao.newEstoque,
//             log_estoque_movimentacao.oldVendido,
//             log_estoque_movimentacao.newVendido,
//               CASE
//                 WHEN log_estoque_movimentacao.tipo_movimentacao = 'M' then '(M) Movimentação'
//                 WHEN log_estoque_movimentacao.tipo_movimentacao = 'E' then '(E) Entrada de Estoque'
//                 WHEN log_estoque_movimentacao.tipo_movimentacao = 'S' then '(S) Saida de Estoque'
//                 WHEN log_estoque_movimentacao.tipo_movimentacao = 'X' then '(X) Saida Manual'
//                 WHEN log_estoque_movimentacao.tipo_movimentacao = 'N' then '(N) Entrada como Vendido'
//                 WHEN log_estoque_movimentacao.tipo_movimentacao = 'C' then '(C) Correção manual'
//               END tipo_movimentacao,
//             log_estoque_movimentacao.descricao,
//             produtos.descricao produto
//           FROM log_estoque_movimentacao
//             INNER JOIN produtos on produtos.id = log_estoque_movimentacao.id_produto
//           WHERE 1=1 ";
//   if ($filtros['data_fim']) {
//     $sql .= " AND DATE(log_estoque_movimentacao.data) < '" . $filtros['data_fim'] . "'";
//   }
//   if ($filtros['data_inicial']) {
//     $sql .= " AND DATE(log_estoque_movimentacao.data) >= '" . $filtros['data_inicial'] . "'";
//   }
//   if ($filtros['descricao']) {
//     $sql .= " AND UPPER(log_estoque_movimentacao.descricao) LIKE UPPER('%" . $filtros['descricao'] . "%')";
//   }
//   if ($filtros['produto']) {
//     $sql .= " AND UPPER(produtos.descricao) LIKE UPPER('%" . $filtros['produto'] . "%')";
//   }
//   if ($filtros['tamanho']) {
//     $sql .= " AND log_estoque_movimentacao.tamanho = " . $filtros['tamanho'];
//   }
//   if ($filtros['negativo'] == "T") {
//     $sql .= " AND (log_estoque_movimentacao.NewEstoque < 0 OR log_estoque_movimentacao.NewVendido < 0)";
//   }

//   $sql .= " ORDER BY log_estoque_movimentacao.data DESC";
//   //echo $sql;
//   $resultado = $conexao->query($sql);
//   $faturamentos = $resultado->fetchAll(PDO::FETCH_ASSOC);
//   return $faturamentos;
// }

//function atualizaFaturamentoSituacao(int $id_faturamento, string $campo, int $situacao, int $usuario)
//{
  //date_default_timezone_set('America/Sao_Paulo');
  //$dataAtual = DATE('Y-m-d H:i:s');

  //$query = "UPDATE faturamento SET {$campo} = {$situacao}";
  //if ($campo == "expedido") {
  //  $query .= ", id_expedidor = {$usuario}, data_expedicao='{$dataAtual}'";
  //}

  //if ($campo == "entregue") {
  //  $query .= ", id_entregador = {$usuario}, data_entrega='{$dataAtual}'";
  //}

  //$query .= " WHERE id={$id_faturamento};";
  //$conexao = Conexao::criarConexao();
  //return $conexao->exec($query);
//}

//function atualizaFaturamentoSituacaoRetirada(int $id_faturamento, string $campo, int $situacao, int $usuario)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $dataAtual = DATE('Y-m-d H:i:s');

//  $query = "UPDATE faturamento SET {$campo} = {$situacao}";
//  if ($campo == "expedido") {
//    $query .= ", id_expedidor = {$usuario}, data_expedicao='{$dataAtual}', entregue = 1, data_entrega ='{$dataAtual}' ";
//  }

//  $query .= " WHERE id={$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

//function atulizaFaturamentoSituacaoPrevia(int $id_faturamento, string $campo, int $situacao, int $frete, int $usuario)
//{
  //if (in_array($frete, [1, 3, 4, 5, 6, 7, 8])) {
  //  atualizaFaturamentoSituacao($id_faturamento, $campo, $situacao, $usuario);
  //  atualizaFaturamentoItemSituacao($id_faturamento, $campo, 1);
  //}
//}

// --Commented out by Inspection START (12/08/2022 14:46):
//function atualizaFaturamentoItemSituacao(int $id_faturamento, string $campo, int $situacao)
//{
//  if(in_array($campo,['conferido', 'expedido','separado', 'entregue'])):
//    return false;
//  endif;
//  $query = "UPDATE faturamento_item SET {$campo} = {$situacao} WHERE id_faturamento={$id_faturamento};";
//  $conexao = Conexao::criarConexao();
//  if ($conexao->exec($query)) {
//    return true;
//  }
//  return false;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaUltimaCompraNãoPaga($idCliente)
//{
//  $query = "SELECT id FROM faturamento WHERE id_cliente={$idCliente} AND tabela_preco = 2 AND situacao = 1;";
//  $conexao = Conexao::criarConexao();
//  $query = $conexao->prepare($query);
//  if ($query->execute()) {
//    return $query->fetch(PDO::FETCH_ASSOC);
//  }
//  return false;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaEntregasCliente(int $idCliente ):array{
//  $curl = curl_init();
//  $tokenColaborador = $_SESSION['token'];
//
//  curl_setopt_array($curl, array(
//    CURLOPT_URL => "{$_ENV['URL_MOBILE']}api_estoque/expedicao/entregas_cliente/$idCliente",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_ENCODING => "gzip, deflate",
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 30,
//    CURLOPT_CUSTOMREQUEST => "GET",
//    CURLOPT_HTTPHEADER => array(
//      "token: $tokenColaborador",
//      "Content-Type: application/json"
//    )
//  ));
//
//  $response = curl_exec($curl);
//  $err = curl_error($curl);
//  $info = curl_getinfo($curl);
//
//  curl_close($curl);
//
//  $payload = [
//    "status"=>$info['http_code'],
//    "data"=> json_decode($response | $err,true)
//  ];
//
//  return $payload;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)

// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaEntregasFaturamento(int $idFaturamento ):array{
//  $curl = curl_init();
//  $tokenColaborador = $_SESSION['token'];
//
//  curl_setopt_array($curl, array(
//    CURLOPT_URL => "{$_ENV['URL_MOBILE']}api_estoque/expedicao/entregas_id_faturamento/$idFaturamento",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_ENCODING => "gzip, deflate",
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 30,
//    CURLOPT_CUSTOMREQUEST => "GET",
//    CURLOPT_HTTPHEADER => array(
//      "token: $tokenColaborador",
//      "Content-Type: application/json"
//    )
//  ));
//
//  $response = curl_exec($curl);
//  $err = curl_error($curl);
//  $info = curl_getinfo($curl);
//
//  curl_close($curl);
//
//  $payload = [
//    "status"=>$info['http_code'],
//    "data"=> json_decode($response | $err,true)
//  ];
//
//  return $payload;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function criaNovaEntregaCliente(int $idCliente,int $tipo_frete,int $transportadora, int $id_faturamento):array{
//
//  $curl = curl_init();
//  $tokenColaborador = $_SESSION['token'];
//  $payload = [
//    "id_cliente" => $idCliente,
//    "tipo_frete" => $tipo_frete,
//    "transporte" => $transportadora,
//    "faturamento" => [$id_faturamento]
//  ];
//  curl_setopt_array($curl, array(
//    CURLOPT_URL => "{$_ENV['URL_MOBILE']}api_estoque/expedicao/nova_entrega",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 0,
//    CURLOPT_FOLLOWLOCATION => true,
//    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//    CURLOPT_CUSTOMREQUEST => "POST",
//    CURLOPT_POSTFIELDS => json_encode($payload),
//    CURLOPT_HTTPHEADER => array(
//      "Content-Type: application/json",
//      "token: $tokenColaborador"
//    )
//  ));
//
//  $response = curl_exec($curl);
//  $err = curl_error($curl);
//  $info = curl_getinfo($curl);
//
//  curl_close($curl);
//
//  $payload = [
//    "status"=>$info['http_code'],
//    "data"=> json_decode($response | $err,true)
//  ];
//
//  return $payload;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function alteraSituacaoDaEntrega(string $uuid, string $situacao,$volumes = 1):array{
//  $curl = curl_init();
//  $tokenColaborador = $_SESSION['token'];
//  $payload = [
//    "uuid_entrega" => $uuid,
//    "situacao" => $situacao,
//    "volumes" => $volumes
//  ];
//  curl_setopt_array($curl, array(
//    CURLOPT_URL => "{$_ENV['URL_MOBILE']}api_estoque/expedicao/bip_entrega",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 0,
//    CURLOPT_FOLLOWLOCATION => true,
//    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//    CURLOPT_CUSTOMREQUEST => "PUT",
//    CURLOPT_POSTFIELDS => json_encode($payload),
//    CURLOPT_HTTPHEADER => array(
//      "Content-Type: application/json",
//      "token: $tokenColaborador"
//    )
//  ));
//
//  $response = curl_exec($curl);
//  $err = curl_error($curl);
//  $info = curl_getinfo($curl);
//
//  curl_close($curl);
//  $payload = [
//    "status"=>$info['http_code'],
//    "data"=> json_decode($response | $err,true)
//  ];
//
//  return $payload;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)


// --Commented out by Inspection START (12/08/2022 14:46):
//function buscaIdClientePorFaturamento(int $idFaturamento):array
//{
//  $curl = curl_init();
//  $tokenColaborador = $_SESSION['token'];
//  $authColaborador = $_COOKIE["auth"];
//
//  curl_setopt_array($curl, array(
//    CURLOPT_URL => "{$_ENV['URL_MOBILE']}api_administracao/produtos/busca_id_consumidor/$idFaturamento",
//    CURLOPT_RETURNTRANSFER => 1,
//    CURLOPT_ENCODING => "gzip, deflate",
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 30,
//    CURLOPT_CUSTOMREQUEST => "GET",
//    CURLOPT_HTTPHEADER => array(
//      "auth: $authColaborador",
//      "token: $tokenColaborador",
//      "Content-Type: application/json"
//    )
//  ));
//  $response = curl_exec($curl);
//  $err = curl_error($curl);
//  $info = curl_getinfo($curl);
//
//  curl_close($curl);
//
//  $payload = [
//    "status" => $info['http_code'],
//    "data" => json_decode($response | $err, true)
//  ];
//
//  return $payload;
//}
// --Commented out by Inspection STOP (12/08/2022 14:46)

//function confereEntregas(array $etiquetas):array{
//  $curl = curl_init();
//  $tokenColaborador = $_SESSION['token'];
//  $payload = [
//    "etiquetas" => $etiquetas
//  ];
//  curl_setopt_array($curl, array(
//    CURLOPT_URL => "{$_ENV['URL_MOBILE']}api_estoque/expedicao/confere_entregas",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 0,
//    CURLOPT_FOLLOWLOCATION => true,
//    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//    CURLOPT_CUSTOMREQUEST => "PUT",
//    CURLOPT_POSTFIELDS => json_encode($payload),
//    CURLOPT_HTTPHEADER => array(
//      "Content-Type: application/json",
//      "token: $tokenColaborador"
//    )
//  ));

//  $response = curl_exec($curl);
//  $err = curl_error($curl);
//  $info = curl_getinfo($curl);

//  curl_close($curl);
//  $payload = [
//    "status"=>$info['http_code'],
//    "data"=> json_decode($response | $err,true)
//  ];

//  return $payload;
//}*/

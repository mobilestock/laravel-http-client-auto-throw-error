<?php
/*
require_once 'conexao.php';
require_once 'data_calculo.php';

//function buscaUltimaSequenciaProdutoTrocaPendente($cliente)
//{
//  $query = "SELECT MAX(sequencia) seq FROM troca_pendente_item WHERE id_cliente={$cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['seq'];
//}

//function inserePedidoProdutoTrocaPendente($id_cliente, $produto, $sequencia, $vendedor, $data)
//{
//  $uuid = uniqid(rand(), true);
//  $query = "INSERT INTO troca_pendente_item (id_cliente,id_produto,sequencia,tamanho,
//  id_vendedor,preco,data_hora,tipo_cobranca,id_tabela,uuid,defeito,cod_barras,troca_pendente)
//  VALUES ({$id_cliente},{$produto['id_produto']},{$sequencia},{$produto['tamanho']},
//  {$vendedor},{$produto['preco']},'{$data}',{$produto['tipo_cobranca']},{$produto['id_tabela']},'{$uuid}',{$produto['defeito']},'{$produto['cod_barras']}',1);";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
//
//function buscaParesTrocaPendenteNormal($id_cliente, $limite)
//{
//  $query = "SELECT * FROM troca_pendente_item
//  WHERE id_cliente = {$id_cliente} AND defeito=0
//  ORDER BY sequencia LIMIT {$limite};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

// --Commented out by Inspection START (18/08/2022 13:29):
//function retornaProdutoTrocaPendenteDoPedido(int $cliente, array $devolucoes, string $data)
//{
//  $sequencia = buscaUltimaSequenciaProdutoTroca($cliente);
//  $sequencia++;
//  $query = "";
//  foreach ($devolucoes as $key => $d) {
//    $preco = buscaPrecoTabela($d['id_tabela'], $d['tipo_cobranca']);
//    $query .= "INSERT INTO troca_pendente_item (id_cliente,id_produto,sequencia,tamanho,
//        tipo_cobranca,id_tabela,id_vendedor,preco,data_hora,uuid,defeito,descricao_defeito)
//        VALUES ({$cliente},{$d['id_produto']},{$sequencia},{$d['tamanho']},
//        {$d['tipo_cobranca']},{$d['id_tabela']},{$d['id_vendedor']},{$preco},'{$d['data_hora']}','{$d['uuid']}',{$d['defeito']},'{$d['decricao_defeito']}');";
//  }
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)


//function buscaTotalTrocaPendenteSemDefeito($id_cliente)
//{
//  $query = "SELECT COUNT(sequencia)pares FROM troca_pendente_item WHERE id_cliente = {$id_cliente} AND defeito = 0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['pares'];
//}
//
////pedido-troca-devoluca-automatica.php
//function buscaPedidoItemConcluidosPreco($id_cliente)
//{
//  $query = "SELECT * from pedido_item pi  WHERE pi.id_cliente = {$id_cliente}
//  AND pi.situacao = 6 AND pi.separado=1 ORDER BY pi.id_produto,pi.sequencia;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
//
//function buscaPedidoTrocaPendente($id_cliente)
//{
//  $query = "SELECT tp.* from troca_pendente tp WHERE id_cliente = {$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
//function calculaDataCompraDataTroca($id_cliente)
//{
//  $query = "SELECT DATEDIFF(tpi.data_hora,(SELECT MAX(faturamento_item.data_hora )
//  FROM faturamento_item WHERE faturamento_item.id_cliente = $id_cliente
//  AND faturamento_item.id_produto = tpi.id_produto)) as diferenca
//  FROM troca_pendente_item tpi
//  INNER JOIN produtos ON (produtos.id = tpi.id_produto)
//  LEFT OUTER JOIN usuarios u ON (u.id = tpi.id_vendedor)
//  LEFT OUTER JOIN defeitos d ON (d.uuid = tpi.uuid)
//  WHERE tpi.id_cliente = $id_cliente";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
//
//function verificaDataCompraDataTroca($id_cliente, $id_produto)
//{
//  $query = "SELECT DATEDIFF(NOW(),MAX(faturamento_item.data_hora))as diferenca
//  FROM faturamento_item WHERE faturamento_item.id_cliente = $id_cliente
//  AND faturamento_item.id_produto = $id_produto";
//
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista;
//}
//
////Essa função retorna a diferença de dias entre compra e troca
//function verificaCompraProdutoparaTroca($id_cliente, $id_produto)
//{
//  $query = "SELECT count(faturamento_item.id_faturamento) as existe_compra, faturamento_item.premio FROM faturamento_item WHERE faturamento_item.id_cliente = $id_cliente
//  AND faturamento_item.id_produto = $id_produto;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista;
//}
//Essa função realiza um select verificando se existe ou não compra do produto selecionado para troca
// --Commented out by Inspection START (18/08/2022 13:29):
//function retornaDataTrocaPedidoRelatorio($id_cliente)
//{
//  $query = "SELECT tpi.data_hora,(SELECT MAX(faturamento_item.data_hora )
//  FROM faturamento_item WHERE faturamento_item.id_cliente = $id_cliente
//  AND faturamento_item.id_produto = tpi.id_produto order by MAX(faturamento_item.data_hora)) as data_compra FROM troca_pendente_item tpi
//  INNER JOIN produtos ON (produtos.id = tpi.id_produto)
//  LEFT OUTER JOIN usuarios u ON (u.id = tpi.id_vendedor)
//  LEFT OUTER JOIN defeitos d ON (d.uuid = tpi.uuid)
//  WHERE tpi.id_cliente = $id_cliente AND((tpi.defeito =0) OR (tpi.defeito = 1 AND d.abater=0)) GROUP BY tpi.uuid ORDER BY tpi.preco DESC LIMIT 1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)

//Essa função verifica a data do relatório e em relação as datas de pedidos de troca.

//function buscaPedidoDataRelatorio($id_cliente)
//{
//  $query = "SELECT tpi.*, (SELECT MAX(faturamento_item.data_hora )
//  FROM faturamento_item WHERE faturamento_item.id_cliente = $id_cliente
//  AND faturamento_item.id_produto = tpi.id_produto) as data_compra FROM troca_pendente_item tpi
//  INNER JOIN produtos ON (produtos.id = tpi.id_produto)
//  LEFT OUTER JOIN usuarios u ON (u.id = tpi.id_vendedor)
//  LEFT OUTER JOIN defeitos d ON (d.uuid = tpi.uuid)
//  WHERE tpi.id_cliente = {$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

// --Commented out by Inspection START (18/08/2022 13:29):
//function buscaPedidoTrocaPendenteItem($id_cliente)
//{
//  $query = "SELECT tpi.*, produtos.descricao produto, u.nome nome_usuario,d.abater,(SELECT MAX(faturamento_item.data_hora )
//  FROM faturamento_item WHERE faturamento_item.id_cliente = $id_cliente
//  AND faturamento_item.id_produto = tpi.id_produto) as data_compra FROM troca_pendente_item tpi
//  INNER JOIN produtos ON (produtos.id = tpi.id_produto)
//  LEFT OUTER JOIN usuarios u ON (u.id = tpi.id_vendedor)
//  LEFT OUTER JOIN defeitos d ON (d.uuid = tpi.uuid)
//  WHERE tpi.id_cliente = {$id_cliente} AND ((tpi.defeito =0) OR (tpi.defeito = 1 AND d.abater=0)) GROUP BY tpi.uuid ORDER BY tpi.preco DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)

// AND faturamento_item.tamanho = tpi.tamanho
// --Commented out by Inspection START (18/08/2022 13:29):
//function buscaProdutosTrocaPendente($id_cliente)
//{
//  $query = "SELECT * from troca_pendente_item WHERE id_cliente={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)

//
//function existeTrocaPendente($cliente)
//{
//  $query = "SELECT * from troca_pendente WHERE id_cliente={$cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
//
//function insereTrocaPendente($cliente)
//{
//  $query = "INSERT INTO troca_pendente SET id_cliente={$cliente}, tabela_preco = 1;";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

//function atualizaTabelaPrecoTrocaPendente($id_cliente, $tipo_cobranca)
//{
//  $query = "UPDATE troca_pendente SET tipo_tabela = {$tipo_cobranca} WHERE id_cliente={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

// --Commented out by Inspection START (18/08/2022 13:29):
//function atualizaPrecoTrocaPendente($cliente, $id_produto, $sequencia, $preco, $tipo_cobranca)
//{
//  $query = "UPDATE troca_pendente_item set preco = {$preco}, tipo_cobranca = {$tipo_cobranca}
//    WHERE id_cliente={$cliente} AND id_produto={$id_produto} AND sequencia = {$sequencia}";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)


function buscaProdutoTrocaPendente($cliente, $seq)
{
  $query = "SELECT * from troca_pendente_item WHERE id_cliente={$cliente} AND sequencia={$seq};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetch();
}

// function removeProdutoTrocaPendente($uuid)
// {
//   $query = "DELETE FROM troca_pendente_item WHERE uuid='{$uuid}'";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (18/08/2022 13:29):
//function buscaParesTrocaPendente($cliente)
//{
//  $query = "SELECT COUNT(tpi.sequencia)pares from troca_pendente_item tpi
//  WHERE tpi.id_cliente={$cliente}
//  ORDER BY tpi.preco DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)


//function atualizaDefeitoTrocaPendente($cliente, $sequencia, $defeito)
//{
//  $query = "UPDATE troca_pendente_item SET defeito = {$defeito}
//  WHERE id_cliente={$cliente} AND sequencia = {$sequencia};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

// --Commented out by Inspection START (18/08/2022 13:29):
//function buscaTrocaPendenteRelatorio($cliente)
//{
//  $query = "SELECT c.razao_social cliente, COUNT(tpi.sequencia) pares,
//  p.descricao referencia, SUM(tpi.preco) valor FROM colaboradores c
//  INNER JOIN troca_pendente_item tpi ON (tpi.id_cliente = c.id)
//  INNER JOIN produtos p ON (tpi.id_produto=p.id) WHERE c.id = {$cliente}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)


// function buscaTrocaPendenteGradeRelatorio($cliente, $produto)
// {
//   $query = "SELECT troca_pendente_item.nome_tamanho, count(troca_pendente_item.sequencia) pares FROM troca_pendente_item
//   WHERE troca_pendente_item.id_cliente = {$cliente} AND troca_pendente_item.id_produto = {$produto}
//   GROUP BY troca_pendente_item.nome_tamanho ORDER BY troca_pendente_item.nome_tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (18/08/2022 13:29):
//function buscaPedidoTrocaPendenteItemRelatorio($id_cliente)
//{
//  $query = "SELECT tpi.id_produto, COUNT(tpi.sequencia) pares, tpi.preco,
//  p.descricao referencia, SUM(tpi.preco) valor from troca_pendente_item tpi
//  INNER JOIN produtos p ON (p.id = tpi.id_produto)
//  WHERE tpi.id_cliente = {$id_cliente}
//  GROUP BY tpi.id_produto, tpi.preco ORDER BY tpi.sequencia;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (18/08/2022 13:29)


function inserePedidoItemDevolucaoDaTroca(
  $cliente,
  $id_produto,
  $data,
  $tamanho,
  $sequencia,
  $tipo_cobranca,
  $id_tabela,
  $preco,
  $vendedor,
  $cod_barras,
  $uuid,
  $defeito,
  $desc_defeito
) {
  date_default_timezone_set('America/Sao_Paulo');
  $data_vencimento = buscaDataVencimentoCliente($cliente,$data);
  $query = "INSERT INTO pedido_item (id_cliente,id_produto,data_hora,data_vencimento,tamanho,sequencia,
    tipo_cobranca,id_tabela,preco,id_vendedor,situacao,cod_barras,uuid,defeito,confirmado,troca_pendente,cliente,descricao_defeito)
    VALUES ({$cliente},{$id_produto},'{$data}','{$data_vencimento}',{$tamanho},{$sequencia},{$tipo_cobranca},
    {$id_tabela},{$preco},{$vendedor},8,'{$cod_barras}','{$uuid}',{$defeito},1,1,'','{$desc_defeito}');";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function removeParTrocaPendente($cliente, $sequencia)
{
  $query = "DELETE FROM troca_pendente_item WHERE id_cliente={$cliente}
  AND sequencia = {$sequencia};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

//function buscaPedidoProdutoTrocaPendente($cliente)
//{
//  $query = "SELECT * from pedido_item pi
//  WHERE pi.id_cliente = {$cliente} AND pi.troca_pendente = 1 AND pi.situacao=8;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

// function removeProdutoDefeito($uuid)
// {
//   $query = "DELETE FROM defeitos WHERE uuid='{$uuid}'";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

//function buscaPedidoProdutoDevolucao($cliente)
//{
//  $query = "SELECT * from pedido_item pi
//  WHERE pi.id_cliente = {$cliente} AND pi.troca_pendente = 0 AND pi.situacao=12 ;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

//function buscaPedidoProdutoDevolucaoConfirmado($cliente)
//{
//  $query = "SELECT * from pedido_item pi
//  WHERE pi.id_cliente = {$cliente} AND pi.troca_pendente = 1 AND pi.situacao=12;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

//function buscaPedidoProdutoDevolucaoSimulado($cliente)
//{
//  $query = "SELECT * from pedido_item_troca_temp pi
//  WHERE pi.id_cliente = {$cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

//function buscaPedidoProdutoTrocaPendenteItem($id, $seq)
//{
//  $query = "SELECT * from pedido_item pi
//  WHERE pi.id_cliente = {$id}  AND pi.sequencia = {$seq} AND pi.troca_pendente = 1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}

function buscaUltimaSequenciaProdutoTroca($cliente)
{
  $query = "SELECT COALESCE(MAX(sequencia),0) seq FROM troca_pendente_item WHERE id_cliente={$cliente};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['seq'];
}

//function removeProdutosTrocaPendentePedido($cliente)
//{
//  $query = "DELETE FROM pedido_item WHERE id_cliente={$cliente}
//  AND troca_pendente = 1 AND situacao = 8;";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

//function removeProdutosTrocaPendentePedidoItem($id_cliente, $seq)
//{
//  $query = "DELETE FROM pedido_item WHERE id_cliente={$id_cliente}
//  AND sequencia = {$seq} AND troca_pendente = 1;";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
//function buscaEtiquerasUnitariasTrocas($id, $tamanho)
//{
//  $query = "SELECT
//      produtos.descricao,
//      produtos_grade_cod_barras.cod_barras,
//      produtos_grade_cod_barras.tamanho,
//      produtos.localizacao
//    FROM produtos
//    inner join produtos_grade_cod_barras on(produtos.id = produtos_grade_cod_barras.id_produto)
//
//  WHERE produtos.id = '{$id}' and produtos_grade_cod_barras.tamanho = '{$tamanho}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
*/

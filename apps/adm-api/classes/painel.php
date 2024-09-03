<?php
require_once 'conexao.php';

// --Commented out by Inspection START (18/08/2022 13:28):
//function buscaPainel($id_cliente)
//{
//  $query = "SELECT saldo FROM saldo_troca
//  WHERE id_cliente = {$id_cliente}
//  ORDER BY sequencia DESC LIMIT 1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  if ($lista) {
//    return $lista['saldo'];
//  } else {
//    return 0;
//  }
//}
// --Commented out by Inspection STOP (18/08/2022 13:28)

// --Commented out by Inspection START (12/08/2022 15:56):
//function limparPainel($id)
//{
//  $query = "DELETE FROM pedido_estante WHERE estante={$id};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaUltimaLinhaSaldo($id_cliente)
//{
//  $query = "SELECT saldo FROM saldo_troca
//  WHERE id_cliente = {$id_cliente}
//  ORDER BY sequencia DESC LIMIT 1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaListaDePaineis($filtro)
//{
//  $query = "SELECT pe.*,c.razao_social cliente FROM pedido_estante pe
//  INNER JOIN colaboradores c ON (c.id=pe.id_cliente) {$filtro};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function atualizaUltimaLinhadeSaldo($cliente, $sequencia, $saldo)
//{
//  $query = "UPDATE saldo_troca set saldo=saldo-{$saldo}
//  WHERE id_cliente={$cliente} AND sequencia={$sequencia};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (18/08/2022 13:28):
//function buscaParesTrocados($id_cliente)
//{
//  $query = "SELECT SUM(troca) trocas FROM saldo_troca
//  WHERE id_cliente={$id_cliente} AND tipo='T';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['trocas'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:28)

// --Commented out by Inspection START (18/08/2022 13:28):
//function buscaParesComprados($cliente)
//{
//  $dataAtual = DATE('Y-m-d');
//  $query = "SELECT pares, saldo_compra, troca from saldo_troca
//  WHERE id_cliente = {$cliente} and tipo='E' AND data_vencimento>='{$dataAtual}'
//  ORDER BY sequencia DESC ;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:28)

// --Commented out by Inspection START (12/08/2022 15:56):
//function listaPainel($filtro)
//{
//  $query = "SELECT c.id, c.razao_social, u.nome usuario,pe.estante,(SELECT count(pi.sequencia)pares_separados FROM pedido_item pi
//  WHERE id_cliente = c.id AND (situacao = 6 OR situacao = 9 OR situacao=10 OR situacao=11 OR situacao=16) AND separado=1) pares_separados
//  FROM colaboradores c
//  LEFT OUTER JOIN pedido_item pi ON (pi.id_cliente = c.id)
//  LEFT OUTER JOIN produtos pr ON (pr.id = pi.id_produto)
//  LEFT OUTER JOIN pedido_estante pe ON (pe.id_cliente = pi.id_cliente AND pe.cheio=0)
//  LEFT OUTER JOIN usuarios u ON (c.em_uso = u.id) {$filtro} AND c.tipo='C'
//  GROUP BY c.id ORDER BY pares_separados DESC";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

function listaPainelPagina($filtro, $pagina, $itens)
{
    $query = "SELECT c.id, c.razao_social, u.nome usuario,
  (SELECT count(pi.sequencia)pares_separados FROM pedido_item pi
  WHERE id_cliente = c.id) pares_separar
  FROM colaboradores c
  LEFT OUTER JOIN pedido_item pi ON (pi.id_cliente = c.id)
  LEFT OUTER JOIN produtos pr ON (pr.id = pi.id_produto)
  LEFT OUTER JOIN usuarios u ON (c.em_uso = u.id)
  {$filtro}
  GROUP BY c.id ORDER BY pares_separar DESC LIMIT {$pagina},{$itens}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

function buscaTotalDePedidos($filtro)
{
    $query = "SELECT SUM(p.id_cliente)pedidos FROM pedido p
  LEFT OUTER JOIN colaboradores c ON (p.id_cliente = c.id)
  LEFT OUTER JOIN pedido_item pi ON (pi.id_cliente = c.id)
  LEFT OUTER JOIN produtos pr ON (pr.id = pi.id_produto)
  LEFT OUTER JOIN usuarios u ON (c.em_uso = u.id)
  LEFT OUTER JOIN usuarios u1 ON (p.usuario_contato = u1.id)
  {$filtro} AND c.tipo='C';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['pedidos'];
}

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTodosOsClientes()
//{
//  $query = "SELECT c.id, c.razao_social, pe.estante, (SELECT count(pi.sequencia)pares_separados FROM pedido_item pi
//  WHERE id_cliente = c.id AND separado=1 AND (situacao = 6 OR situacao = 9 OR situacao=10 OR situacao=11 OR situacao=16)) pares_separados
//  FROM colaboradores c
//  LEFT OUTER JOIN pedido_item pi ON (pi.id_cliente = c.id)
//  LEFT OUTER JOIN produtos pr ON (pr.id = pi.id_produto)
//  LEFT OUTER JOIN pedido_estante pe ON (pe.id_cliente = pi.id_cliente AND pe.cheio=0)
//  GROUP BY c.id ORDER BY pares_separados DESC";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaEstantesCliente($id_cliente)
//{
//  $query = "SELECT * FROM pedido_estante WHERE id_cliente={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesASeparar($id_cliente)
//{
//  $query = "SELECT count(pi.sequencia)pares_separar FROM pedido_item pi
//  WHERE id_cliente = {$id_cliente} AND (situacao = 6);";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares_separar'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTodosOsParesASeparar()
//{
//  $query = "SELECT count(pi.sequencia)pares_separar FROM pedido_item pi
//  WHERE separado=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares_separar'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesSeparados($id_cliente)
//{
//  $query = "SELECT count(pi.sequencia)pares_separados FROM pedido_item pi
//  WHERE id_cliente = {$id_cliente} AND separado=1 AND (situacao = 6 OR situacao = 9 OR situacao=10 OR situacao=11 OR situacao=16);";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares_separados'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesFaturados($id_cliente)
//{
//  $query = "SELECT * FROM faturamento
//  WHERE id_cliente = {$id_cliente} AND situacao=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// function buscaParesExclusao($id_cliente)
// {
//   $query = "SELECT * FROM pedido_item_exclusao
//   WHERE id_cliente = {$id_cliente};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchALL();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTotalParesSeparados()
//{
//  $query = "SELECT count(pi.sequencia)total_pares_separados FROM pedido_item pi
//  WHERE (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao=10 OR pi.situacao=11 OR pi.situacao=16) AND pi.separado=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total_pares_separados'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTotalParesSeparadosPedido()
//{
//  $query = "SELECT count(pi.sequencia)total_pares_separados FROM pedido_item pi
//  WHERE (pi.situacao = 6 OR pi.situacao = 9 OR pi.situacao=10 OR pi.situacao=11 OR pi.situacao=16) AND pi.separado=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total_pares_separados'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTotalParesSepararPedido()
//{
//  $query = "SELECT count(pi.sequencia)total_pares_separados FROM pedido_item pi
//  WHERE pi.situacao = 6;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total_pares_separados'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTotalParesSeparadosFaturamento()
//{
//  $query = "SELECT count(fi.sequencia)total_pares_separados FROM faturamento_item fi
//  WHERE (fi.situacao = 6 OR fi.situacao = 9 OR fi.situacao=10 OR fi.situacao=11 OR fi.situacao=16) AND fi.separado=1 AND fi.conferido=0 AND fi.entregue=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total_pares_separados'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTotalParesSepararFaturamento()
//{
//  $query = "SELECT count(fi.sequencia)total_pares_separados FROM faturamento_item fi
//  WHERE fi.situacao = 6 and fi.conferido=0 AND fi.entregue=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total_pares_separados'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaTotalParesASeparar()
//{
//  $query = "SELECT count(pi.sequencia)total_pares_separar FROM pedido_item pi WHERE (situacao = 6);";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total_pares_separar'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

function buscaDataUltimaCompra($id_cliente)
{
    $query = "SELECT DATE(MAX(data_emissao))data FROM saldo_troca pi WHERE id_cliente = {$id_cliente};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['data'] != null ? Date('d/m/Y', strtotime($linha['data'])) : '-';
    //return $linha['data'];
}

// function buscaPainelProdutoSeparadoPorCodigo($cliente, $cod_barras)
// {
//   $query = "SELECT * from pedido_item pi INNER JOIN produtos_grade_cod_barras pgcb ON
//     (pgcb.id_produto=pi.id_produto AND pgcb.tamanho=pi.tamanho)
//     where pgcb.cod_barras ='{$cod_barras}' AND pi.confirmado=0
//     AND pi.id_cliente={$cliente} AND (pi.situacao=6 OR pi.situacao=11);";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// function alteraConfirmacaoProdutoUnitarioPedidoCliente($cliente, $sequencia, $seqParcial, $vendedor)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $query = "UPDATE pedido_item SET confirmado=1, seq_parcial = {$seqParcial}, separado=1, id_vendedor={$vendedor}
//   WHERE id_cliente={$cliente} AND sequencia = {$sequencia} AND confirmado = 0;";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function alteraConfirmacaoProdutoUnitarioPedido($cliente, $sequencia, $seqParcial)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $query = "UPDATE pedido_item SET confirmado=1, seq_parcial = {$seqParcial}, separado=1
//   WHERE id_cliente={$cliente} AND sequencia = {$sequencia} AND confirmado = 0;";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function alteraSituacaoProdutoUnitarioPedido($cliente, $sequencia, $situacaoAntes, $situacaoDepois, $separador)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $query = "UPDATE pedido_item set situacao = {$situacaoDepois}, separado=1, data_hora= '{$data}', id_separador={$separador}, data_separacao='{$data}'
//   WHERE id_cliente={$cliente} AND situacao = {$situacaoAntes} AND sequencia = {$sequencia};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (18/08/2022 13:28):
//function buscaPares60Dias($id_cliente, $data60Dias)
//{
//  $query = "SELECT COUNT(*) pares FROM `faturamento_item`
//    WHERE id_cliente ={$id_cliente} and DATE(data_hora)>= DATE('{$data60Dias}') and separado = 1
//  ";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:28)

// --Commented out by Inspection START (18/08/2022 13:28):
//function buscaData60Dias($id_cliente, $data60Dias)
//{
//  $query = "SELECT MIN(data_vencimento) data FROM saldo_troca WHERE id_cliente={$id_cliente}
//  AND data_emissao >= DATE('{$data60Dias}');";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['data'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:28)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesAVencer($id_cliente, $dataVencimento)
//{
//  $query = "SELECT pares FROM saldo_troca WHERE id_cliente={$id_cliente}
//  AND data_vencimento <= '{$dataVencimento}' AND saldo>0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function removeSaldoPainel($cliente)
//{
//  $query = "UPDATE pedido set saldo_devolucao = saldo_devolucao-1 WHERE id_cliente={$cliente};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesASepararPainel($painel)
//{
//  $query = "SELECT pi.*,p.descricao referencia FROM pedido_item pi
//  INNER JOIN produtos p ON (p.id=pi.id_produto)
//  INNER JOIN pedido_estante pe ON(pe.id_cliente = pi.id_cliente)
//  WHERE pe.estante = {$painel} and pi.situacao=6;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchALL();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesSeparadosPainel($painel)
//{
//  $query = "SELECT pi.*,p.descricao referencia,u.nome separador FROM pedido_item pi
//  LEFT OUTER JOIN usuarios u ON (u.id=pi.id_separador)
//  INNER JOIN produtos p ON (p.id=pi.id_produto)
//  INNER JOIN pedido_estante pe ON(pe.id_cliente = pi.id_cliente)
//  WHERE pe.estante = {$painel} and pi.situacao=6 AND separado=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchALL();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesFaturadosPainel($painel)
//{
//  $query = "SELECT fi.*,p.descricao referencia,u.nome separador FROM faturamento_item fi
//  LEFT OUTER JOIN usuarios u ON (u.id=fi.id_separador)
//  INNER JOIN produtos p ON (p.id=fi.id_produto)
//  INNER JOIN pedido_estante pe ON(pe.id_cliente = fi.id_cliente)
//  WHERE pe.estante = {$painel};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchALL();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesPedidoCliente()
//{
//  $mes = DATE('m');
//  $ano = DATE('Y');
//  $query = "SELECT COUNT(pi.id_cliente)pares FROM pedido_item pi
//  WHERE pi.pedido_cliente=1 AND MONTH(pi.data_hora)='{$mes}' AND YEAR(pi.data_hora)='{$ano}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesFaturamentoCliente()
//{
//  $mes = DATE('m');
//  $ano = DATE('Y');
//  $query = "SELECT COUNT(fi.id_cliente)pares FROM faturamento_item fi
//  WHERE fi.pedido_cliente=1 AND MONTH(fi.data_hora)='{$mes}' AND YEAR(fi.data_hora)='{$ano}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesClientePedidoDetalhes()
//{
//  $mes = DATE('m');
//  $ano = DATE('Y');
//  $query = "SELECT c.razao_social nome, COUNT(pi.id_produto) pares FROM pedido_item pi
//  INNER JOIN colaboradores c ON (pi.id_cliente=c.id)
//  WHERE pi.pedido_cliente=1 AND MONTH(pi.data_hora)='{$mes}' AND YEAR(pi.data_hora)='{$ano}' GROUP BY pi.id_cliente ORDER BY pares DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linhas = $resultado->fetchAll();
//  return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function buscaParesClienteFaturamentoDetalhes()
//{
//  $mes = DATE('m');
//  $ano = DATE('Y');
//  $query = "SELECT c.razao_social nome, COUNT(fi.id_produto) pares FROM faturamento_item fi
//  INNER JOIN colaboradores c ON (fi.id_cliente=c.id)
//  WHERE fi.pedido_cliente=1 AND MONTH(fi.data_hora)='{$mes}' AND YEAR(fi.data_hora)='{$ano}' GROUP BY fi.id_cliente ORDER BY pares DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linhas = $resultado->fetchAll();
//  return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function limparSinalizacaoPedidos()
//{
//  $data = DATE('Y-m-d H:i:s');
//  $data_antes = date('Y-m-d H:i:s', strtotime("-5 days", strtotime($data)));
//  $query = "UPDATE pedido SET sinalizado=0, data_sinalizado=null WHERE data_sinalizado<'{$data_antes}';";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// function buscaRelatorioGerencialClientes($mes, $ano)
// {
//   $retorno = [];
//   $retorno['informacoes_primeira_compra'] = getQuantidadePrimeiraCompra($mes, $ano);
//   $retorno['informacoes_atividade_usuarios'] = getQuantidadeClientesAtivos();
//   $informacoes_regime_usuarios = getUsuariosPorTipoRegime($mes, $ano);
//   $retorno['informacoes_usuarios_por_regime'] = $informacoes_regime_usuarios['usuarios_por_regime'];
//   $retorno['informacoes_usuarios_por_regime']['quantidade_total'] = $informacoes_regime_usuarios['quantidade_total'];
//   $retorno['informacoes_vendas_por_regime'] = $informacoes_regime_usuarios['vendas_por_regime'];
//   $retorno['informacoes_vendas_por_regime']['valor_total'] = $informacoes_regime_usuarios['valor_total'];
//   $retorno['informacoes_tipo_venda'] = getQuantidadeVendidosFullfilment($mes, $ano);

//   return $retorno;
// }

// --Commented out by Inspection START (12/08/2022 15:56):
//function getusuariosReincidentes()
//{
//  // $sql = "WITH UL1 AS (
//  //           SELECT   count(f.id_cliente) quantidade_de_compras, f.id_cliente, cv.razao_social
//  //           FROM     clientes_view cv LEFT OUTER JOIN faturamento f ON f.id_cliente = cv.id
//  //           WHERE    PrimeiraCompra IS NOT NULL
//  //           GROUP BY f.id_cliente
//  //         )
//  //         SELECT sum(case when UL1.quantidade_de_compras = 1 then 0 else 1 end) as Reincidentes,
//  //               sum(case when UL1.quantidade_de_compras = 1 then 1 else 0 end) as Compra_Unica
//  //         from UL1;";
//
//  // $conexao = Conexao::criarConexao();
//  // $resultado = $conexao->query($sql);
//  // if ($result =  $resultado->fetchALL(PDO::FETCH_ASSOC)) {
//  //   $retorno = $result[0];
//  //   $Reincidentes = $retorno['Reincidentes'];
//  //   $Compra_Unica = $retorno['Compra_Unica'];
//  //   $retorno['Reincidentes'] = [];
//  //   $retorno['Compra_Unica'] = [];
//  //   $retorno['total'] = $Reincidentes + $Compra_Unica;
//  //   $retorno['Reincidentes']['quantidade'] = $Reincidentes;
//  //   $retorno['Reincidentes']['percentual'] = round(($Reincidentes / $retorno['total']) * 100, 2) . '%';
//  //   $retorno['Compra_Unica']['quantidade'] = $Compra_Unica;
//  //   $retorno['Compra_Unica']['percentual'] = round(($Compra_Unica / $retorno['total']) * 100, 2) . '%';
//  // }
//
//  return false;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// function getQuantidadeClientesAtivos()
// {
//   $sql = "SELECT sum(case when PrimeiraCompra is null then 0 else 1 end) as Ja_Compraram,
//             sum(case when PrimeiraCompra is null then 1 else 0 end) as Nunca_Compraram
//           from clientes_view;";

//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   $retorno = false;
//   if ($result =  $resultado->fetchALL(PDO::FETCH_ASSOC)) {
//     $retorno = $result[0];
//     $Ja_Compraram = $retorno['Ja_Compraram'];
//     $Nunca_Compraram = $retorno['Nunca_Compraram'];
//     $retorno['Ja_Compraram'] = [];
//     $retorno['Nunca_Compraram'] = [];
//     $retorno['total'] = $Ja_Compraram + $Nunca_Compraram;
//     $retorno['Ja_Compraram']['quantidade'] = $Ja_Compraram;
//     $retorno['Ja_Compraram']['percentual'] = round(($Ja_Compraram / $retorno['total']) * 100, 2) . '%';
//     $retorno['Nunca_Compraram']['quantidade'] = $Nunca_Compraram;
//     $retorno['Nunca_Compraram']['percentual'] = round(($Nunca_Compraram / $retorno['total']) * 100, 2) . '%';

//     $reincidentes = getusuariosReincidentes();
//     $retorno['Ja_Compraram']['children'] = $reincidentes;
//   }
//   return false;
// }

// function getQuantidadePrimeiraCompra($mes, $ano)
// {
//   $sql = "SELECT count(id) quantidade from clientes_view where MONTH(PrimeiraCompra) = $mes AND YEAR(PrimeiraCompra) = $ano";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   $retorno =  $resultado->fetchALL(PDO::FETCH_ASSOC);
//   return $retorno ? $retorno[0]['quantidade'] : $retorno;
// }

// function getUsuariosPorTipoRegime($mes, $ano)
// {
//   $conexao = Conexao::criarConexao();
//   $retorno = false;
//   $sql = "SELECT case when regime = 1 then 'Juridico' when regime = 2 then 'Fisico' else 'Indefinido' end regime, count(regime) quantidade, (select count(id)from colaboradores where MONTH(data_cadastro) = $mes and YEAR(data_cadastro) = $ano) total
//           from colaboradores
//           where MONTH(data_cadastro) = $mes and YEAR(data_cadastro) = $ano
//           group by regime;";
//   $resultado = $conexao->query($sql);
//   if ($results = $resultado->fetchALL(PDO::FETCH_ASSOC)) {
//     $quantidade = 0;
//     foreach ($results as $key => $value) {
//       $retorno['usuarios_por_regime'][$value['regime']]['quantidade'] = $value['quantidade'];
//       $retorno['usuarios_por_regime'][$value['regime']]['percentual'] = round(($value['quantidade'] / $value['total']) * 100, 2) . '%';
//       $quantidade += $value['quantidade'];
//     }
//   }
//   $sql = "SELECT  sum(fi.preco) valor, count(f.id) quantidade, ROUND((sum(fi.preco) / count(f.id)),2) ticket_medio,
//             CASE WHEN c.regime = 1 THEN 'Juridico' WHEN c.regime = 2 THEN 'Fisico' WHEN c.regime = 3 THEN 'Mobile' ELSE 'Indefinido' END regime
//           FROM     faturamento f
//           INNER JOIN faturamento_item fi on fi.id_faturamento = f.id
//           INNER JOIN colaboradores c ON c.id = f.id_cliente
//           WHERE    MONTH(f.data_fechamento) = $mes AND YEAR(f.data_fechamento) = $ano
//           GROUP BY c.regime;";
//   $resultado = $conexao->query($sql);
//   if ($results = $resultado->fetchALL(PDO::FETCH_ASSOC)) {
//     $total = 0;
//     foreach ($results as $key => $value) {
//       $total += $value['valor'];
//     }
//     foreach ($results as $key => $value) {
//       $retorno['vendas_por_regime'][$value['regime']]['valor'] = "R$ " . $value['valor'];
//       $retorno['vendas_por_regime'][$value['regime']]['ticket_medio'] = "R$ " . $value['ticket_medio'];
//       $retorno['vendas_por_regime'][$value['regime']]['percentual'] = round(($value['valor'] / $total) * 100, 2) . '%';
//     }

//     $retorno['valor_total'] = "R$ " . round($total, 2);
//     $retorno['quantidade_total'] = $quantidade;
//   }

//   return $retorno;
// }

// function getQuantidadeVendidosFullfilment($mes, $ano)
// {
//   $sql = "SELECT
//         SUM(CASE WHEN estoque_grade.id_responsavel = 1 THEN 1 ELSE 0 END) fullfilment,
//         SUM(CASE WHEN COALESCE(estoque_grade.id_responsavel, 1) <> 1  THEN 1 ELSE 0 END) aquarius,
//         SUM(CASE WHEN estoque_grade.id_responsavel = 1 THEN faturamento_item.preco ELSE 0 END) vendas_fullfilment,
//         SUM(CASE WHEN COALESCE(estoque_grade.id_responsavel, 1) <> 1  THEN faturamento_item.preco ELSE 0 END) vendas_aquarius
//       FROM faturamento
//       INNER JOIN faturamento_item ON faturamento_item.id_faturamento = faturamento.id
//       INNER JOIN estoque_grade ON estoque_grade.id_produto = faturamento_item.id_produto AND estoque_grade.tamanho = faturamento_item.tamanho
//       WHERE MONTH(faturamento.data_fechamento) = $mes AND YEAR(faturamento.data_fechamento) = $ano;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   $retorno = false;
//   if ($results =  $resultado->fetchALL(PDO::FETCH_ASSOC)) {
//     $retorno['fullfilment']['quantidade'] = $results[0]['fullfilment'];
//     $retorno['fullfilment']['valor'] = "R$ " . $results[0]['vendas_fullfilment'];
//     $retorno['aquarius']['quantidade'] = $results[0]['aquarius'];
//     $retorno['aquarius']['valor'] = "R$ " . $results[0]['vendas_aquarius'];
//     $retorno['quantidade_total'] = $results[0]['fullfilment'] + $results[0]['aquarius'];
//     $retorno['valor_total'] = "R$ " . ($results[0]['vendas_fullfilment'] + $results[0]['vendas_aquarius']);
//     $retorno['fullfilment']['percentual'] = round(($retorno['fullfilment']['quantidade'] / $retorno['quantidade_total']) * 100, 2) . '%';
//     $retorno['aquarius']['percentual'] = round(($retorno['aquarius']['quantidade'] / $retorno['quantidade_total']) * 100, 2) . '%';
//   }
//   return $retorno;
// }

function buscaTotalClientesComParesPainel()
{
    $query =
        'select count(p.id_cliente)clientes FROM pedido p inner join pedido_item pi ON (p.id_cliente=pi.id_cliente) group by p.id_cliente;';
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchALL();
    return $lista;
}

// --Commented out by Inspection START (12/08/2022 15:56):
//function MeuEstoqueDigital_buscaTotalParesSituacao()
//{
//  $query = "SELECT COUNT(DISTINCT pedido_item.uuid) pedidos, COUNT(DISTINCT faturamento_item.uuid) faturados
//    FROM med_venda_produtos_consumidor_final
//    INNER JOIN colaboradores ON colaboradores.id = med_venda_produtos_consumidor_final.id_cliente
//    LEFT JOIN pedido_item ON pedido_item.uuid = med_venda_produtos_consumidor_final.uuid_pedido_item
//    LEFT JOIN faturamento_item ON faturamento_item.uuid = med_venda_produtos_consumidor_final.uuid_pedido_item
//    WHERE DATE_FORMAT(med_venda_produtos_consumidor_final.data, '%m/%Y' ) = DATE_FORMAT(NOW(), '%m/%Y' )";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function MeuEstoqueDigital_buscaParesClientePainelDetalhes()
//{
//  $query = "SELECT colaboradores.razao_social nome, COUNT(*) pares
//    FROM med_venda_produtos_consumidor_final
//      INNER JOIN colaboradores ON colaboradores.id = med_venda_produtos_consumidor_final.id_cliente
//      INNER JOIN pedido_item ON pedido_item.uuid = med_venda_produtos_consumidor_final.uuid_pedido_item
//    GROUP BY colaboradores.razao_social
//    ORDER BY pares DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linhas = $resultado->fetchAll();
//  return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function MeuEstoqueDigital_buscaParesClienteFaturadoDetalhes()
//{
//  $query = "SELECT colaboradores.razao_social nome,
//              COUNT(faturamento_item.id_produto) pares
//            FROM faturamento_item
//              INNER JOIN colaboradores ON (faturamento_item.id_cliente = colaboradores.id)
//              INNER JOIN med_venda_produtos_consumidor_final ON med_venda_produtos_consumidor_final.uuid_pedido_item = faturamento_item.uuid
//              WHERE faturamento_item.pedido_cliente=1
//                  AND DATE_FORMAT(faturamento_item.data_hora, '%m/%Y' ) = DATE_FORMAT(NOW(), '%m/%Y' )
//            GROUP BY faturamento_item.id_cliente
//            ORDER BY pares DESC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linhas = $resultado->fetchAll();
//  return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function MeuEstoqueDigital_buscaParessituacaoClitente()
//{
//  $query = "SELECT colaboradores.razao_social nome,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'R' then med_venda_produtos_consumidor_final.qtd END) removido,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'A' then med_venda_produtos_consumidor_final.qtd END) aberto,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'C' then med_venda_produtos_consumidor_final.qtd END) confirmado,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'P' then med_venda_produtos_consumidor_final.qtd END) pago,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'E' then med_venda_produtos_consumidor_final.qtd END) entregue,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'N' then med_venda_produtos_consumidor_final.qtd END) arquivado,
//	  SUM(med_venda_produtos_consumidor_final.qtd) total
//  FROM med_venda_produtos_consumidor_final
//    INNER JOIN colaboradores ON colaboradores.id = med_venda_produtos_consumidor_final.id_cliente
//  WHERE DATE_FORMAT(med_venda_produtos_consumidor_final.data, '%m/%Y' ) = DATE_FORMAT(NOW(), '%m/%Y' )
//  GROUP BY colaboradores.razao_social
//
//  UNION ALL
//
//  SELECT '' razao_social,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'R' then med_venda_produtos_consumidor_final.qtd END) removido,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'A' then med_venda_produtos_consumidor_final.qtd END) aberto,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'C' then med_venda_produtos_consumidor_final.qtd END) confirmado,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'P' then med_venda_produtos_consumidor_final.qtd END) pago,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'E' then med_venda_produtos_consumidor_final.qtd END) entregue,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'N' then med_venda_produtos_consumidor_final.qtd END) arquivado,
//    SUM(med_venda_produtos_consumidor_final.qtd) total
//  FROM med_venda_produtos_consumidor_final
//    INNER JOIN colaboradores ON colaboradores.id = med_venda_produtos_consumidor_final.id_cliente
//  WHERE DATE_FORMAT(med_venda_produtos_consumidor_final.data, '%m/%Y' ) = DATE_FORMAT(NOW(), '%m/%Y')
//  ORDER BY nome";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linhas = $resultado->fetchAll();
//  return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

// --Commented out by Inspection START (12/08/2022 15:56):
//function MeuEstoqueDigital_buscaParessituacaoClitenteFinal()
//{
//  $query = "SELECT med_consumidor_final.nome,
//    med_consumidor_final.telefone,
//    colaboradores.razao_social nome_cliente_mobile,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'R' then med_venda_produtos_consumidor_final.qtd END) removido,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'A' then med_venda_produtos_consumidor_final.qtd END) aberto,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'C' then med_venda_produtos_consumidor_final.qtd END) confirmado,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'P' then med_venda_produtos_consumidor_final.qtd END) pago,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'E' then med_venda_produtos_consumidor_final.qtd END) entregue,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'N' then med_venda_produtos_consumidor_final.qtd END) arquivado,
//    SUM(med_venda_produtos_consumidor_final.qtd) total
//  FROM med_venda_produtos_consumidor_final
//    INNER JOIN med_consumidor_final ON med_consumidor_final.id = med_venda_produtos_consumidor_final.id_consumidor_final
//    INNER JOIN colaboradores ON colaboradores.id = med_consumidor_final .id_cliente
//  WHERE DATE_FORMAT(med_venda_produtos_consumidor_final.data, '%m/%Y') = DATE_FORMAT(NOW(), '%m/%Y')
//  GROUP BY med_consumidor_final.nome
//
//  UNION ALL
//
//  SELECT '' nome,
//    '' telefone,
//    '' nome_cliente_mobile,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'R' then med_venda_produtos_consumidor_final.qtd END) removido,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'A' then med_venda_produtos_consumidor_final.qtd END) aberto,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'C' then med_venda_produtos_consumidor_final.qtd END) confirmado,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'P' then med_venda_produtos_consumidor_final.qtd END) pago,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'E' then med_venda_produtos_consumidor_final.qtd END) entregue,
//    SUM(case when med_venda_produtos_consumidor_final.situacao = 'N' then med_venda_produtos_consumidor_final.qtd END) arquivado,
//    SUM(med_venda_produtos_consumidor_final.qtd) total
//  FROM med_venda_produtos_consumidor_final
//    INNER JOIN med_consumidor_final ON med_consumidor_final.id = med_venda_produtos_consumidor_final.id_consumidor_final
//    INNER JOIN colaboradores ON colaboradores.id = med_consumidor_final .id_cliente
//  WHERE DATE_FORMAT(med_venda_produtos_consumidor_final.data, '%m/%Y') = DATE_FORMAT(NOW(), '%m/%Y')
//  ORDER BY nome_cliente_mobile";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linhas = $resultado->fetchAll();
//  return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:56)

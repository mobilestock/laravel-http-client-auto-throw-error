<?php

require_once 'conexao.php';

// --Commented out by Inspection START (12/08/2022 14:48):
//function buscaFilaParesVendidos($filtro)
//{
//  $query = "SELECT COUNT(pi.sequencia)pares,pi.prioridade_separacao,pi.data_hora,pi.id_cliente,c.razao_social cliente,
//  u.nome separador FROM pedido_item pi INNER JOIN colaboradores c ON (c.id = pi.id_cliente)
//  INNER JOIN pedido p ON (p.id_cliente = pi.id_cliente)
//  INNER JOIN produtos pr ON (pr.id = pi.id_produto)
//  LEFT OUTER JOIN usuarios u ON (u.id=p.separador_temp)
//  WHERE pi.situacao = 6 {$filtro}
//  GROUP BY pi.id_cliente ORDER BY pi.prioridade_separacao DESC, MIN(pi.data_hora) ASC";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// --Commented out by Inspection START (12/08/2022 14:48):
//function buscaNomeSeparador($id_cliente)
//{
//  $query = "SELECT u.nome separador FROM pedido
//  LEFT OUTER JOIN usuarios u ON (u.id=pedido.separador_temp)
//  WHERE id_cliente={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['separador'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// function limpaSeparadorOrdem($id)
// {
//   $query = "UPDATE ordem_separacao set id_separador = 0 WHERE id={$id};";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// --Commented out by Inspection START (12/08/2022 14:48):
//function buscaFilaPrimeiroParesVendidos()
//{
//  $query = "SELECT COUNT(pi.sequencia)pares,pi.prioridade_separacao,pi.data_hora,
//  pi.id_cliente,c.razao_social cliente, p.separador_temp FROM pedido_item pi
//  INNER JOIN colaboradores c ON (c.id = pi.id_cliente)
//  INNER JOIN pedido p ON (p.id_cliente = pi.id_cliente)
//  WHERE pi.situacao = 6 and p.separador_temp>0
//  GROUP BY pi.id_cliente ORDER BY pi.prioridade_separacao DESC, MIN(pi.data_hora) ASC LIMIT 1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


function alteraPrioridadeProdutoSeparacao($id)
{
    $query = "UPDATE ordem_separacao set prioridade = 1
  WHERE id={$id};";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

function adicionaPrioridadeDeClienteAguardando($id)
{
    $query = "UPDATE ordem_separacao set cliente_aguardando = 1, prioridade = 1
  WHERE id={$id};";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

// function buscaFilaParesVendidosCliente($id_cliente)
// {
//   $query = "SELECT pi.data_hora, pi.sequencia, pi.tamanho, u.nome usuario, p.descricao produto
//   FROM pedido_item pi INNER JOIN produtos p ON (p.id = pi.id_produto)
//   INNER JOIN usuarios u ON(u.id=pi.id_vendedor)
//   WHERE pi.situacao = 6 and pi.id_cliente={$id_cliente}
//   ORDER BY pi.data_hora ASC";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaProdutoPedidoSeparado($id_cliente, $sequencia)
// {
//   $query = "SELECT * from produtos
//     INNER JOIN pedido_item pi ON (pi.id_produto = produtos.id)
//     WHERE pi.id_cliente = {$id_cliente} AND pi.sequencia={$sequencia};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch();
// }

// --Commented out by Inspection START (12/08/2022 14:48):
//function buscaUltimaOrdemSeparacao()
//{
//  $query = "SELECT MAX(id) id FROM ordem_separacao";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['id'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// --Commented out by Inspection START (12/08/2022 14:48):
//function emitirOrdemSeparacao($id, $id_cliente, $data, $prioridade, $presencial)
//{
//  $query = "INSERT INTO ordem_separacao (id,id_cliente,data_emissao,prioridade,presencial)
//    VALUES ({$id},{$id_cliente},'{$data}',{$prioridade},{$presencial});";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  return $stmt->execute();
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// --Commented out by Inspection START (12/08/2022 14:48):
//function buscaSeparacaoDoCliente($id_cliente)
//{
//  $query = "SELECT * from ordem_separacao
//    WHERE id_cliente = {$id_cliente} AND concluido=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetch();
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// --Commented out by Inspection START (12/08/2022 14:48):
//function removeSeparacaoCliente($id)
//{
//  $query = "DELETE FROM ordem_separacao WHERE id={$id};";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  return $stmt->execute();
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// function removeSeparacaoClienteItem($id_sep)
// {
//   $query = "DELETE FROM ordem_separacao_item WHERE id_sep={$id_sep};";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// function buscaOrdensSeparacao($filtro)
// {
//   $query = "SELECT os.*, c.razao_social cliente, COUNT(osi.id_produto)pares, u.nome separador
//   FROM ordem_separacao os
//   INNER JOIN colaboradores c ON (c.id=os.id_cliente)
//   INNER JOIN ordem_separacao_item osi ON (osi.id_sep=os.id)
//   INNER JOIN produtos pr ON (pr.id = osi.id_produto)
//   LEFT OUTER JOIN usuarios u ON (u.id=os.id_separador)
//   WHERE os.concluido=0 {$filtro} GROUP BY os.id
//   ORDER BY os.cliente_aguardando DESC, os.presencial DESC, os.prioridade DESC, os.data_emissao ASC;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 14:48):
//function buscaOrdensSeparacaoNovo()
//{
//  $query = "SELECT faturamento.id,
//              (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = faturamento.id_cliente) cliente,
//                COUNT(DISTINCT faturamento_item.sequencia) pares
//            FROM faturamento
//            INNER JOIN faturamento_item ON faturamento_item.id_faturamento = faturamento.id
//            WHERE faturamento.status_separacao = 3
//            GROUP BY faturamento.id
//            ";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// function buscaOrdensSeparacaoConcluidas($filtro)
// {
//   $query = "SELECT os.*, c.razao_social cliente, COUNT(osi.id_produto)pares, u.nome separador
//   FROM ordem_separacao os
//   INNER JOIN colaboradores c ON (c.id=os.id_cliente)
//   INNER JOIN ordem_separacao_item osi ON (osi.id_sep=os.id)
//   INNER JOIN produtos pr ON (pr.id = osi.id_produto)
//   LEFT OUTER JOIN usuarios u ON (u.id=os.id_separador)
//   WHERE os.concluido=1 {$filtro} GROUP BY os.id
//   ORDER BY prioridade DESC;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaOrdensSeparacaoItens($id)
// {
//   $query = "SELECT os.data_emissao, osi.tamanho, u.nome usuario, p.descricao produto, p.localizacao
//   FROM ordem_separacao_item osi
//   INNER JOIN produtos p ON (p.id = osi.id_produto)
//   INNER JOIN usuarios u ON(u.id=osi.id_vendedor)
//   INNER JOIN ordem_separacao os ON (osi.id_sep = os.id)
//   WHERE osi.id_sep={$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaFaturamento_itemSeparaccao($id)
// {
//   $query = "SELECT faturamento_item.tamanho,
//               produtos.descricao produto,
//               produtos.localizacao
//             FROM faturamento_item
//             INNER JOIN produtos ON produtos.id = faturamento_item.id_produto
//             WHERE faturamento_item.id_faturamento = {$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaOrdemSeparacao($id)
// {
//   $query = "SELECT os.*, c.razao_social cliente, COUNT(osi.id_produto)pares
//   FROM ordem_separacao os
//   INNER JOIN colaboradores c ON (c.id=os.id_cliente)
//   INNER JOIN ordem_separacao_item osi ON (osi.id_sep=os.id)
//   INNER JOIN produtos pr ON (pr.id = osi.id_produto)
//   WHERE os.id={$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

function desbloquearSeparacao(int $id_separacao)
{
    $conexao = Conexao::criarConexao();
    $query = "UPDATE ordem_separacao SET bloqueado=0 WHERE id = {$id_separacao} and bloqueado = 1";
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}


// Nova Tela de Separação
// function liberarSeparacao($idFaturamento, $status = 3)
// {
//   $conexao = Conexao::criarConexao();
//   $sql = "SELECT * from faturamento where acrescimo_pares = {$idFaturamento};";
//   $resultado = $conexao->query($sql);
//   if ($acrescimo = $resultado->fetchAll(PDO::FETCH_ASSOC)) { //verifica se o faturamento possui acrescimos
//     $status = 2; //aguardando acréscimo
//   }
//   $sql = "UPDATE faturamento SET status_separacao = $status, data_fechamento = now() WHERE id = {$idFaturamento}"; //status 3 = separar
//   $stmt = $conexao->prepare($sql);
//   //agora verifica se o faturamento era acrescimo de outro e se possui MAIS faturamentos que tambem sao acrescimos. Se todos estiverem desbloqueados, desbloqueia o pai
//   if ($results = $stmt->execute()) {
//     $sql = "  SELECT f2.id,
//                   f2.status_separacao AS status,
//                   f.id AS acrescimo,
//                   f.status_separacao AS status_acrescimo
//               FROM faturamento f LEFT JOIN faturamento f2 ON f2.id = f.acrescimo_pares
//               WHERE f.id IN (
//                 SELECT id
//                 FROM faturamento
//                 WHERE acrescimo_pares = (
//                   SELECT acrescimo_pares
//                   FROM faturamento
//                   WHERE id = {$idFaturamento}
//                 )
//               );
//             ";
//     $stmt = $conexao->prepare($sql);
//     $results = $conexao->query($sql);
//     if ($results = $results->fetchAll(PDO::FETCH_ASSOC)) {
//       foreach ($results as $key => $value) {
//         if ($results[$key]['status_acrescimo'] == 1) { //se possuir um filho bloqueado sai fora
//           return true;
//         }
//       }

//       // caso todos os filhos estejam liberados(pagos) , libera o pai pra separação (todos os itens do filho agora ficam dentro do pai na separação)
//       $sql = "UPDATE faturamento SET status_separacao = 3, data_fechamento = now() where id IN (SELECT id from faturamento where id = {$results[0]['id']} OR acrescimo_pares = {$results[0]['id']});";
//       $stmt = $conexao->prepare($sql);
//       $results = $stmt->execute();
//     }
//   }
//   return true;
// }

// function getAllPedidosParaSeparar($filtros)
// {

//   $offSet = $filtros['page'] ? $filtros['page'] * 5 - 5 : 0;
//   $sql = "SELECT   DISTINCT f.id, c.id as id_colab,
//             (SELECT COUNT(*) FROM faturamento WHERE faturamento.id_cliente = c.id) as tCompras,
//             COALESCE(f.data_fechamento, f.data_emissao) data_fechamento,
//             fi.id_separador,
//             u.nome AS separador,
//             c.razao_social AS nome,
//             s.status,
//             f.status_separacao,
//             f.prioridade,
//             DATEDIFF(f.data_emissao, NOW()) diferenca,
//             (count(fi.id_produto) + (SELECT count(id_produto)
//                                     FROM   faturamento_item
//                                     WHERE  id_faturamento = f.id)) quantidade
//           FROM     faturamento f
//           LEFT JOIN colaboradores c ON c.id = f.id_cliente
//           INNER JOIN status_separacao s ON s.id = f.status_separacao
//           INNER JOIN faturamento_item fi ON fi.id_faturamento = f.id
//           LEFT JOIN usuarios u ON u.id = fi.id_separador
//           WHERE    f.separado = 0 AND f.status_separacao NOT IN (1, 5, 6)
//           GROUP BY f.id
//           ORDER BY prioridade ASC, COALESCE(f.data_fechamento, f.data_emissao), f.status_separacao DESC limit 5 OFFSET $offSet;"; //status 1 e 6 = bloqueado, 5 = separado
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function getQuantidadePedidosParaSeparar()
// {
//   $sql = "SELECT count(id) as total from faturamento where separado = 0
//   AND situacao = 2  "; //status 1 = bloqueado, 5 = separado
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscaOnePedido($filtros)
// {
//   $sql = "SELECT   DISTINCT f.id, c.id as id_colab,
//   (SELECT COUNT(*) FROM faturamento WHERE faturamento.id_cliente = c.id) as tCompras,
//   COALESCE(f.data_fechamento, f.data_emissao) data_fechamento,
//   fi.id_separador,
//   u.nome AS separador,
//   c.razao_social AS nome,
//   s.status,
//   f.status_separacao,
//   f.prioridade,
//   DATEDIFF(f.data_emissao, NOW()) diferenca,
//   (count(fi.id_produto) + (SELECT count(id_produto)
//                           FROM   faturamento_item
//                           WHERE  id_faturamento = f.id))) quantidade
// FROM     faturamento f
// LEFT JOIN colaboradores c ON c.id = f.id_cliente
// INNER JOIN status_separacao s ON s.id = f.status_separacao
// INNER JOIN faturamento_item fi ON fi.id_faturamento = f.id
// LEFT JOIN usuarios u ON u.id = fi.id_separador
// WHERE  f.separado = 0 AND f.status_separacao NOT IN (1, 5, 6) "; //status 1 = bloqueado, 5 = separado


//   if ($filtros && !empty($filtros['cliente'])) {
//     $sql .= " AND f.id_cliente = {$filtros['cliente']}";
//   }
//   if ($filtros && !empty($filtros['faturamento'])) {
//     $sql .= " AND f.id = {$filtros['faturamento']}";
//   }
//   $sql .= " GROUP BY f.id
//             ORDER BY prioridade ASC, COALESCE(f.data_fechamento, f.data_emissao), f.status_separacao DESC limit 5";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function getOnePedido($idFaturamento, $pagina)
// {
//   $offSet = $pagina ? $pagina * 10 - 10 : 0;
//   $sql = "SELECT
//             fi.id_faturamento,
//             fi.sequencia,
//             fi.separado,
//             fi.id_cliente,
//             fi.id_vendedor,
//             fi.id_separador,
//             fi.id_produto,
//             p.descricao descricao,
//             fi.preco,
//             fi.uuid,
//             fi.tamanho,
//             (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.tamanho = fi.tamanho AND estoque_grade.id_produto = fi.id_produto) nome_tamanho,
//             COALESCE(
//               p.localizacao,
//               '')
//               localizacao,
//             p.mostruario
//           FROM
//             faturamento f
//             INNER JOIN faturamento_item fi ON fi.id_faturamento = f.id
//             LEFT JOIN produtos p ON p.id = fi.id_produto
//           WHERE
//             f.id = {$idFaturamento}
//             ORDER BY p.localizacao, p.id, fi.tamanho
//           LIMIT 10 OFFSET $offSet;";
//   $conexao = Conexao::criarConexao();
//   $produtos = $conexao->query($sql);
//   return $produtos->fetchAll(PDO::FETCH_ASSOC);
// }

// --Commented out by Inspection START (12/08/2022 14:48):
//function setPedidoSeparando($idFaturamento, $idSeparador)
//{
//  $sql = "UPDATE faturamento SET status_separacao = 4 WHERE id in (SELECT id from faturamento where id = {$idFaturamento} )";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($sql);
//  setSeparadorPedido($idFaturamento, $idSeparador);
//  return $stmt->execute();
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


// function setPedidoCorrigido($idFaturamento)
// {
//   $sql = "UPDATE faturamento SET status_separacao = 5 WHERE id in (SELECT id from faturamento where id = {$idFaturamento})";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($sql);
//   return $stmt->execute();
// }

// --Commented out by Inspection START (12/08/2022 14:48):
//function verificaSeparando($idFaturamento, $idSeparador = '')
//{
//  $conexao = Conexao::criarConexao();
//  $sql = "SELECT f.status_separacao, fi.id_separador, fi.separado from faturamento f
//          INNER JOIN faturamento_item fi ON fi.id_faturamento = f.id
//          WHERE f.id = $idFaturamento group by fi.id_faturamento;";
//  $resultado = $conexao->query($sql);
//  $statusPedido = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  if (
//    $statusPedido && $statusPedido[0]['status_separacao'] != 4 ||
//    $statusPedido && ($statusPedido[0]['id_separador'] == 0 || $statusPedido[0]['id_separador'] == $idSeparador)
//  ) {
//    return true;
//  }
//  return false;
//}
// --Commented out by Inspection STOP (12/08/2022 14:48)


//function setSeparadorPedido($idFaturamento, $idSeparador)
//{
//  $sql = "UPDATE faturamento_item SET id_separador = {$idSeparador} WHERE id_faturamento in (SELECT id from faturamento where id = {$idFaturamento})";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($sql);
//  return $stmt->execute();
//}

// function limparSeparacao($idFaturamento)
// {
//   $sql = "UPDATE faturamento SET status_separacao = 3 WHERE id in (SELECT id from faturamento where id = {$idFaturamento} )";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($sql);
//   $stmt->execute();

//   $sql = "UPDATE faturamento_item SET id_separador = 0, separado = 0 WHERE id_faturamento in (SELECT id from faturamento where id = {$idFaturamento} )";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($sql);
//   return $stmt->execute();
// }

// function toggleFaturamentoItemSeparado($idFaturamento, $uuid, $sequencia, $idSeparador)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $conexao = Conexao::criarConexao();
//   $sql = "UPDATE faturamento_item SET separado = separado ^ 1, id_separador = $idSeparador, data_separacao = '{$data}' WHERE id_faturamento in (SELECT id from faturamento where id = {$idFaturamento}) and uuid = '{$uuid}' and sequencia = $sequencia";
//   $stmt = $conexao->prepare($sql);
//   return $stmt->execute();
// }

// function togglePrioridadeSeparacao($item)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $conexao = Conexao::criarConexao();
//   $sql = "UPDATE faturamento SET prioridade = {$item['prioridade']} where id = {$item['id']}";
//   $stmt = $conexao->prepare($sql);
//   return $stmt->execute();
// }

// function getQuantidadePedidosSeparados($idSeparador)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d');

//   $sql = "SELECT count(fi.id_produto) as quantidade, u.nome
//           FROM faturamento_item fi
//           INNER JOIN usuarios u ON u.id = fi.id_separador
//           WHERE fi.id_separador = {$idSeparador} AND fi.data_separacao BETWEEN '{$data} 00:00:00' AND '{$data} 23:59:59' AND fi.separado = 1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function getPedidoCorrigido($id) //pega todos os produtos corrigidos de um pedido para corrigir
// {
//   $sql = "SELECT pc.*, p.descricao, p.localizacao, GROUP_CONCAT(DISTINCT pg.cod_barras) cod_barras
//           FROM   produtos p
//             INNER JOIN pares_corrigidos pc ON p.id = pc.id_produto
//             INNER JOIN produtos_grade_cod_barras pg ON pc.id_produto = pg.id_produto AND pc.tamanho = pg.tamanho
//           WHERE  pc.id_faturamento = {$id} GROUP BY p.id;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscaListaPedidosCorrigidos() //lista com todos os pedidos corrigidos com conferencia pendente
// {
//   $sql = "SELECT id_faturamento, id_separador, u.nome,count(id_produto) corrigidos
//           from pares_corrigidos pc
//           left join usuarios u on u.id = pc.id_separador
//           where pc.conferido = 0
//           group by id_faturamento;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

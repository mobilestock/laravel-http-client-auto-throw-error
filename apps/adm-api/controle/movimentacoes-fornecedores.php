<?php

// function buscaSaldoProdutosDefeituosos($filtros)
// {
//   $sql = "SELECT   d.id,
//             d.id_fornecedor,
//             d.id_cliente,
//             d.id_produto,
//             p.descricao referencia,
//             d.descricao_defeito,
//             d.data_hora,
//             d.tamanho,
//             d.sequencia,
//             d.abater,
//             d.uuid,
//             u.nome,
//             p.preco,
//             d.status,
//             (SELECT caminho FROM produtos_foto WHERE  id = p.id AND foto_calcada = 0 LIMIT  1) caminho
//           FROM defeitos d
//             INNER JOIN produtos p ON (p.id = d.id_produto)
//             INNER JOIN usuarios u ON (u.id = d.id_vendedor)
//           WHERE d.id_fornecedor = {$filtros['fornecedor']} AND abater = 0 AND status = 'A' GROUP BY d.uuid;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

function getAllLancamentosFornecedor($filtros)
{
  $offSet = $filtros['pagina'] ? $filtros['pagina'] * 5 - 5 : 0;
  $sql = "SELECT
  cf.*,
  (SELECT
    count(id_fornecedor)
  FROM
    controle_financeiro_fornecedores where id_fornecedor = cf.id_fornecedor)
    qtd_paginas,
  (SELECT sum(valor_lancamento) FROM controle_financeiro_fornecedores where id_fornecedor = cf.id_fornecedor) total
FROM
  controle_financeiro_fornecedores cf where id_fornecedor = {$filtros['fornecedor']} ORDER BY id DESC LIMIT 5 OFFSET {$offSet}";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($sql);
  return $resultado->fetchAll(PDO::FETCH_ASSOC);
}

function novoLancamentoFinanceiro($lancamento)
{
  $sql = "INSERT INTO controle_financeiro_fornecedores(
            id_fornecedor,
            data,
            valor_lancamento,
            valor_total_produtos,
            saldo,
            novo_saldo,
            saldo_defeitos
          )VALUES(
              {$lancamento['id_fornecedor']}, -- id_fornecedor - IN int(11)
              '{$lancamento['data']}', -- data - IN datetime
              {$lancamento['valor_lancamento']}, -- lancamento - IN double
              {$lancamento['valor_total_produtos']}, -- valor_total_produtos - IN double
              {$lancamento['saldo']}, -- saldo - IN double
              {$lancamento['novo_saldo']}, -- saldo - IN double
              {$lancamento['saldo_defeitos']} -- saldo - IN double
            );";

  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($sql);
  return $stmt->execute();
}

function editarLancamentoFinanceiro($lancamento)
{
  $sql = "UPDATE controle_financeiro_fornecedores
          SET
            id_fornecedor = {$lancamento['id_fornecedor']} 
            ,data = '{$lancamento['data']}'
            ,valor_lancamento = {$lancamento['valor_lancamento']}
            ,valor_total_produtos = {$lancamento['valor_total_produtos']}
            ,saldo = {$lancamento['saldo']}
            ,novo_saldo = {$lancamento['novo_saldo']}
            ,saldo_defeitos = {$lancamento['saldo_defeitos']}
          WHERE id = {$lancamento['id']};";

  $sql .= " UPDATE controle_financeiro_fornecedores set saldo = saldo + {$lancamento['valor_lancamento']}, novo_saldo = novo_saldo + {$lancamento['valor_lancamento']} where id_fornecedor = 3045 and id > {$lancamento['id']};";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($sql);
  return $stmt->execute();
}
// function buscaMovimentacaoFornecedor($filtros)
// {
//     if (!$filtros['fornecedor']) return false;
//     $conexao = \MobileStock\database\Conexao::criarConexao();
//     $retorno = [];
//     //busca estoque
//     $sql = "SELECT p.id, me.tipo,me.origem, me.data, sum(mi.quantidade) quantidade from movimentacao_estoque_item mi
//             inner join movimentacao_estoque me ON me.id = mi.id_mov
//             inner join produtos p on p.id = mi.id_produto
//             where p.id_fornecedor = {$filtros['fornecedor']} and me.origem is not null AND me.data > '2020-01-01'
//             group by p.id, me.origem, me.data
//             order by p.id , me.data desc";
//     $resultado = $conexao->query($sql);
//     $movimentacao = $resultado->fetchAll(PDO::FETCH_ASSOC);

//     //busca vendidos
//     $sql = "SELECT   p.id,
//             p.descricao,
//             p.valor_custo_produto preco,
//             (SELECT   count(id_produto)
//             FROM     faturamento_item fi
//             WHERE    fi.id_produto = p.id
//             GROUP BY id_produto)
//               vendidos,
//             (SELECT sum(estoque)
//             FROM   estoque_grade
//             WHERE  id_produto = p.id)
//               estoque,
//             (SELECT count(id_produto)
//             FROM   pedido_item
//             WHERE  id_produto = p.id and situacao = 6)
//               separados,
//             (SELECT   count(id_produto)
//             FROM     devolucao_item di
//             WHERE    di.id_produto = p.id AND di.defeito = 0
//             GROUP BY id_produto)
//             qtd_devolvidos,
//             (SELECT   count(id_produto)
//             FROM     troca_pendente_item ti
//             WHERE    ti.id_produto = p.id
//             GROUP BY id_produto)
//               qtd_trocados,
//             (SELECT caminho
//             FROM   produtos_foto
//             WHERE  id = p.id AND foto_calcada = 0
//             LIMIT  1)
//               caminho
//         FROM     PRODUTOS p
//         WHERE    p.id_fornecedor = {$filtros['fornecedor']} AND p.consignado = 1";

//     // if($filtros && !empty($filtros['data_inicial']) && !empty($filtros['data_fim']) ){
//     //   $sql .= " AND fi.data_hora BETWEEN '{$filtros['data_inicial']} 00:00:00' AND '{$filtros['data_fim']} 23:59:59'";
//     // }

//     if ($filtros && !empty($filtros['referencia'])) {
//         $referencia = utf8_encode($filtros['referencia']);
//         $sql .= " AND LOWER(p.descricao) like LOWER('%{$referencia}%')";
//     }
//     $sql .= " GROUP BY p.id";
//     $resultado = $conexao->query($sql);
//     $produtos = $resultado->fetchAll(PDO::FETCH_ASSOC);

//     if ($movimentacao && $produtos) {
//         foreach ($produtos as $prod) {
//             $prod['total_estoque'] = $prod['estoque'] + $prod['separados'];
//             $prod['qtd_vendidos'] = $prod['vendidos'] - ($prod['qtd_devolvidos'] + $prod['qtd_trocados']);
//             $prod['valor_a_pagar'] = floatval($prod['qtd_vendidos'] *  floatval($prod['preco']));
//             $prod['movimentacoes'] = [];
//             foreach ($movimentacao as $mov) {
//                 if ($prod['id'] == $mov['id']) {
//                     $mov['tipo_legivel'] = $mov['tipo'] == 'E' ? 'Entrada' : 'Saída';
//                     $prod['movimentacoes'][] = $mov;
//                 }
//             }
//             $retorno[$prod['id']] = $prod;
//         }
//     }

//     return array_values($retorno);
// }

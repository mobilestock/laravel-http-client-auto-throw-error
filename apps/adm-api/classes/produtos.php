<?php

use MobileStock\database\Conexao;

require_once 'conexao.php';
require_once 'gradesTamanhos.php';
require_once 'grades.php';

// function existeCodigoDeBarrasUnitario($cod_barras)
// {
//   $query = "SELECT p.descricao FROM produtos_grade_cod_barras pgcb
//   INNER JOIN produtos p ON (p.id = pgcb.id_produto)
//   where pgcb.cod_barras='{$cod_barras}';";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// function buscaProdutoGradeReservadoDetalhes($idProduto)
// {
//   $query = "SELECT pg.tamanho, COUNT(pi.tamanho)pares from pedido_item pi
//   INNER JOIN produtos_grade pg ON (pg.id=pi.id_produto AND pg.tamanho=pi.tamanho)
//   WHERE pi.id_produto = {$idProduto} AND pi.situacao = 15
//   GROUP BY pi.tamanho ORDER by pi.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function existeEstoqueProduto($id)
// {
//   $query = "SELECT COALESCE(SUM(estoque),0) estoque from estoque_grade WHERE id_produto={$id} AND estoque_grade.id_responsavel=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['estoque'];
// }

// function buscaProdutoGradePrevisaoDetalhes($idProduto)
// {
//   $query = "SELECT cig.tamanho, SUM(cig.quantidade_total)pares FROM compras_itens_grade cig
//   INNER JOIN compras_itens ci ON (ci.id_compra=cig.id_compra AND ci.sequencia=cig.id_sequencia)
//   WHERE cig.id_produto = {$idProduto} AND ci.id_situacao = 1
//   GROUP BY cig.id_produto, cig.tamanho ORDER by cig.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaProdutoGradeEstoqueDetalhes($idProduto)
// {
//   $query = "SELECT eg.tamanho, eg.estoque pares from estoque_grade eg
//   INNER JOIN produtos_grade pg ON (pg.id=eg.id_produto AND pg.tamanho=eg.tamanho)
//   WHERE eg.id_produto = {$idProduto} AND eg.id_responsavel = 1 GROUP BY eg.tamanho ORDER by eg.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

function existeProdutoCadastrado($descricao)
{
    $query = "SELECT descricao FROM produtos WHERE descricao='{$descricao}';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['descricao'] ?? false;
}

function buscaProdutosCategoria($id_categoria)
{
    $query = 'SELECT id,descricao FROM produtos where 1=1';
    if ($id_categoria != '') {
        $query .= " AND id_categoria={$id_categoria};";
    }
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function buscaFotoProduto(int $id)
{
    $query = "SELECT COALESCE(caminho,'')caminho from produtos_foto WHERE id={$id} LIMIT 1";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['caminho'] ?? '';
}

function buscaFotoProdutoCalcado(int $id)
{
    $query = "SELECT COALESCE(caminho,'')caminho from produtos_foto WHERE id={$id} and tipo_foto = 'MD' LIMIT 1;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['caminho'] ?? '';
}

// function buscaProduto($id_produto)
// {
//   $query = "SELECT
//             p.valor_venda_cpf preco_cpf,
//             p.valor_venda_cnpj preco_cnpj,
//             p.porcentagem_comissao,
//             p.porcentagem_comissao_cnpj,
//               p.*,
//               min(pg.tamanho) grade_min,
//               max(pg.tamanho) grade_max,
//               p.id_fornecedor,
//               CASE
//               WHEN p.preco_promocao > 0 AND p.preco_promocao < 100 THEN
//               COALESCE(
//                 ( SELECT TIMEDIFF(promocoes.data_fim, now()) FROM promocoes WHERE p.id = promocoes.id_produto AND TIMEDIFF(promocoes.data_fim, now()) > 0 ORDER BY promocoes.data_fim ASC )
//               ,0)
//                 ELSE
//                   0
//               END
//                 tempo_restante
//             FROM   produtos p INNER JOIN produtos_grade pg ON (pg.id = p.id)
//             WHERE  p.id ={$id_produto}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch(PDO::FETCH_ASSOC);
// }

// function buscaPreco($id_produto, $id_cliente)
// {
//   $query = "SELECT produtos.descricao, retornaValorCalculadoCpfOuCnpj(" . $id_produto . ", " . $id_cliente . ", '0') preco
//             FROM produtos
//             WHERE produtos.id = " . $id_produto;
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch();
// }

//function buscaProdutosPromocaoEspecial($id)
//{
//  $query = "SELECT
//                p.id,
//                ( p.valor_custo_produto * ( 1 + ( p.porcentagem_comissao / ( 100 - p.porcentagem_comissao ) ) ) ) as preco_cpf,
//                ( p.valor_custo_produto * ( 1 + ( p.porcentagem_comissao_cnpj / ( 100 - p.porcentagem_comissao_cnpj ) ) ) ) as preco_cnpj,
//                p.valor_venda_cpf preco_cpf_promocao,
//                p.valor_venda_cnpj preco_cnpj_promocao,
//                TIMEDIFF(pt.end_date, now()) tempo_restante,
//                round((p.valor_custo_produto - p.preco_promocao) / (p.valor_custo_produto / 100)) desconto,
//                SUM(eg.estoque) estoque,
//                min(pg.tamanho) grade_min,
//                max(pg.tamanho) grade_max
//            FROM  promocao_temporaria pt
//              INNER JOIN produtos p ON p.id = pt.id_produto
//              INNER JOIN produtos_grade pg ON (pg.id = pt.id_produto)
//              INNER JOIN estoque_grade eg ON eg.id_produto = pt.id_produto
//            WHERE TIMEDIFF(pt.end_date, now()) > 0";

//  if ($id) {
//    $query .= " AND pt.id_produto = {$id}";
//  }
//  $query .= " GROUP BY pt.id_produto;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll(PDO::FETCH_ASSOC);
//}

//function excluiTodosItensEmPromocaoPedido($id_cliente)
//{
//  $conexao = Conexao::criarConexao();
//  $sql = "SELECT pi.data_hora, pt.start_date, pi.uuid from pedido_item pi
//          inner join produtos p ON p.id = pi.id_produto
//          inner join promocao_temporaria pt ON pt.id_produto = pi.id_produto
//          where pi.id_cliente = {$id_cliente} AND pt.start_date < pi.data_hora;";
//  $resultado = $conexao->query($sql);
//  if ($uuids = $resultado->fetchAll(PDO::FETCH_ASSOC)) {
//    $ids = "";
//    foreach ($uuids as $key => $value) {
//      $ids .= " '{$value['uuid']}',";
//    }
//    $ids = substr($ids, 0, -1);
//    $sql = "DELETE FROM pedido_item where uuid in ( $ids )";
//    $stmt = $conexao->prepare($sql);
//    return $stmt->execute();
//  } else {
//    return false;
//  }
//}

// function excluiLinkVideoProduto($id_produto, $sequencia)
// {
//   $query = "DELETE from produtos_video where id ={$id_produto} and sequencia = '{$sequencia}'";
//   $conexao = Conexao::criarConexao();
//   $conexao->query($query);
//   return;
// }

// function totalEstoqueProduto($id_produto)
// {
//   $query = "SELECT SUM(estoque) estoque FROM estoque_grade WHERE id_produto={$id_produto} AND estoque_grade.id_responsavel = 1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['estoque'];
// }

// function buscaEstoqueProduto(int $id_produto)
// {
//   $query = "SELECT eg.id_produto produto, eg.tamanho tamanho, eg.estoque estoque, eg.vendido vendido,
//    p.tipo_grade tipo_grade, eg.nome_tamanho nome_tamanho,
//    (SELECT produtos_grade.comprimento_palmilha FROM produtos_grade WHERE produtos_grade.id = eg.id_produto AND produtos_grade.tamanho = eg.tamanho) comprimento_palmilha
//     FROM estoque_grade eg
//     INNER JOIN produtos p ON (p.id = eg.id_produto) WHERE eg.id_produto={$id_produto} AND eg.id_responsavel = 1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaEstoqueProdutoMovto($id_produto)
// {
//   $query = "SELECT eg.id_produto, eg.tamanho, eg.estoque, (eg.vendido+eg.estoque) estoque_total,
//     (SELECT COUNT(pi.id_produto) FROM pedido_item pi WHERE pi.id_produto={$id_produto}
//     AND pi.tamanho=eg.tamanho AND pi.situacao=15 GROUP BY pi.tamanho)reservados FROM estoque_grade eg
//     INNER JOIN produtos p ON (p.id = eg.id_produto) WHERE eg.id_produto={$id_produto} AND eg.id_responsavel = 1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaGradeEstoque($id_produto)
// {
//   $query = "SELECT id_produto,tamanho,estoque FROM estoque_grade WHERE id_produto={$id_produto} AND id_responsavel = 1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   $grades = array();
//   foreach ($lista as $linha) :
//     $grade = new GradesTamanhos();
//     $grade->tamanho = $linha['tamanho'];
//     $grade->quantidade = $linha['estoque'];
//     $grade->produto = $linha['id_produto'];
//     array_push($grades, $grade);
//   endforeach;
//   return $grades;
// }

// function buscaGradeEstoquePedido($id_produto)
// {
//   estoque 2.0 $query = "SELECT id, tamanho, estoque FROM -- WHERE id_produto={$id_produto}";
//   $query = "SELECT id_produto, tamanho, estoque FROM estoque_grade WHERE id_produto={$id_produto} AND id_responsavel = 1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaProdutoPorCodigo(\PDO $conexao, string $codigoBarras):array
// {
//   $sql = $conexao->prepare(
//     "SELECT
//       produtos_grade.id,
//       produtos_grade.id_produto,
//       produtos_grade.nome_tamanho,
//       produtos.mostruario,
//       produtos.localizacao,
//       produtos.descricao,
//       produtos.descricao
//     FROM produtos_grade
//     INNER JOIN produtos ON produtos.id = produtos_grade.id_produto
//     WHERE produtos_grade.cod_barras = :codigo_barras;"
//   );
//   $sql->bindValue(":codigo_barras", $codigoBarras, PDO::PARAM_STR);
//   $sql->execute();
//   $linha = $sql->fetch(PDO::FETCH_ASSOC) ?? [];

//   return $linha;
// }

// function buscaProdutoPorCodigoJson($codigo_barras)
// {
//   $query = "SELECT pgcb.cod_barras, pg.*, p.descricao produto from produtos_grade_cod_barras pgcb
//   INNER JOIN produtos_grade pg on (pg.id = pgcb.id_produto and pg.tamanho = pgcb.tamanho)
//   INNER JOIN produtos p on (p.id = pgcb.id_produto)
//   WHERE pgcb.cod_barras='{$codigo_barras}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   if ($linha = $resultado->fetch()) {
//     $produto = array(
//       "id_produto" => $linha['id'],
//       "produto" => $linha['produto'],
//       "tamanho" => $linha['tamanho'],
//       "cod_barras" => $linha['cod_barras']
//     );
//     return $produto;
//   }
// }

// function buscaProdutoCodBarrasSeqTamanho($id_produto, $tamanho)
// {
//   $query = "SELECT MAX(pgcb.seq_tamanho) seq from produtos_grade_cod_barras pgcb
//   WHERE id_produto = {$id_produto} AND tamanho = {$tamanho}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['seq'];
// }

// function listaProdutosBusca($referencia, $categoria)
// {
//    $conexao = Conexao::criarConexao();
//   /*estoque 2.0
//   $query = "SELECT p.id, p.descricao, SUM(pg.estoque) estoque from produtos p
//   INNER JOIN -- pg ON (pg.id = p.id) where pg.estoque > 0";
//   */
//    $query = "SELECT p.id, p.descricao, SUM(pg.estoque) estoque from produtos p
//    INNER JOIN estoque_grade pg ON (pg.id_produto = p.id) where pg.estoque > 0 and pg.id_responsavel=1";
//    if ($referencia != null) {
//      $query .= " OR p.descricao LIKE '%{$referencia}%'";
//    }
//    if ($categoria != null) {
//      $query .= " AND p.id_categoria = {$categoria}";
//    }
//    $stmt = $conexao->prepare($query);
//    $stmt->execute();
//    return $stmt->fetchAll();
// }

//pedido-acidiona-produto.php
// function buscaCodBarras($id_produto, $nome_tamanho)
// {
//   $query = "SELECT COALESCE(produtos_grade.cod_barras, '')
//   FROM produtos_grade
//   WHERE produtos_grade.id_produto = $id_produto
//   AND produtos_grade.nome_tamanho = '$nome_tamanho';";
//   $conexao = Conexao::criarConexao();
//   $linha = $conexao->query($query)->fetch(PDO::FETCH_ASSOC);

//   return $linha['cod_barras'];
// }

// function buscaCodigoBarraProduto($id_produto, $tamanho)
// {
//   $query = "SELECT cod_barras,seq_tamanho FROM produtos_grade_cod_barras WHERE id_produto={$id_produto} AND tamanho={$tamanho};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaCodigoBarraProdutoPadrao($id_produto, $tamanho)
// {
//   $query = "SELECT CONCAT(cod_barras,'_','NA') cod_barras FROM produtos_grade_cod_barras
//   WHERE id_produto={$id_produto} AND tamanho={$tamanho} AND seq_tamanho=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['cod_barras'];
// }

function listaProdutos()
{
    $query = 'SELECT * FROM PRODUTOS ORDER BY ID LIMIT 50';
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}

// function insereProduto($produto, $idUsuario)
// {
//   if (produtoJaExiste($produto['descricao'])) {
//     return  false;
//   }

//   $forma = $produto['forma'] == '' ? 0 : $produto['forma'];
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = date('Y-m-d H:i:s');
//   $query = "INSERT INTO produtos (
//     id,
//     descricao,
//     usuario,
//     id_fornecedor,
//     id_fornecedor_origem,
//     bloqueado,
//     id_categoria,
//     data_entrada,
//     id_linha,
//     valor_custo_produto_fornecedor,
//     valor_custo_produto,
//     destaque,
//     material_cabedal,
//     material_solado,
//     altura_solado,
//     altura_caixa,
//     largura_caixa,
//     comprimento_caixa,
//     peso_caixa,
//     metro_cubico_caixa,
//     grade_min,
//     grade_max,
//     nome_comercial,
//     forma,
//     tipo_grade
//     ) VALUES (
//     :idProduto,
//     :descricao,
//     :idUsuario,
//     :idFornecedor,
//     :idFornecedorOrigem,
//     :bloqueado,
//     :idCategoria,
//     :data,
//     :idLinha,
//     :precoAquarius,
//     :preco,
//     :destaque,
//     :materialCabedal,
//     :materialSolado,
//     :alturaSolado,
//     :alturaCaixa,
//     :larguraCaixa,
//     :comprimentoCaixa,
//     :pesoCaixa,
//     :metroCubicoCaixa,
//     :gradeMin,
//     :gradeMax,
//     :nomeComercial,
//     :forma,
//     :tipoGrade);";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);

//   return $stmt->execute([
//     'idProduto' => (int) $produto['id'],
//     'descricao' => (string) $produto['descricao'],
//     'idUsuario' => (int) $idUsuario,
//     'idFornecedor' => (int) $produto['id_fornecedor'],
//     'idFornecedorOrigem' => (int) $produto['id_fornecedor_origem'],
//     'bloqueado' => $produto['bloqueado'],
//     'idCategoria' => (int) $produto['id_categoria'],
//     'data' => (string) $data,
//     'idLinha' => (int) $produto['id_linha'],
//     'precoAquarius' => (float) $produto['precoAquarios'],
//     'preco' => (float) $produto['preco'],
//     'destaque' => $produto['destaque'],
//     'materialCabedal' => $produto['material_cabedal'],
//     'materialSolado' => $produto['material_solado'],
//     'alturaSolado' => (float) $produto['altura_solado'],
//     'alturaCaixa' => (float) $produto['altura_caixa'],
//     'larguraCaixa' =>  (float) $produto['largura_caixa'],
//     'comprimentoCaixa' => (float) $produto['comprimento_caixa'],
//     'pesoCaixa' => (float) $produto['peso_caixa'],
//     'metroCubicoCaixa' => (float) $produto['metro_cubico_caixa'],
//     'gradeMin' => (int) $produto['grade_min'],
//     'gradeMax' => (int) $produto['grade_max'],
//     'nomeComercial' => $produto['nome_comercial'],
//     'forma' => $forma,
//     'tipoGrade' => $produto['tipo_grade']
//   ]);
// }

// function insereGradeMinMaxProduto($id, $grade_min, $grade_max)
// {
//   $query = "";
//   for ($i = $grade_min; $i <= $grade_max; $i++) {
//     $query .= "INSERT INTO produtos_grade(id,tamanho) VALUES ({$id}, {$i});";
//     $query .= "INSERT INTO estoque_grade (id_produto,tamanho) VALUES ({$id},{$i});";
//   }
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }
// function insereTamanhoPalminhaGradeProduto($id, $grades)
// {
//   $sql = '';
//   foreach ($grades as $key => $grade) {
//     $sql .= "UPDATE produtos_grade SET comprimento_palmilha = {$grade['valor']} WHERE id={$id} AND tamanho={$grade['tamanho']}; ";
//   }

//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($sql);
//   return $stmt->execute();
// }

// function atualizaGradeProduto($id, $grades)
// {
//   $conexao = Conexao::criarConexao();
//   $sql = "DELETE FROM produtos_grade WHERE id={$id};";
//   $sql .= " INSERT INTO produtos_grade(id, tamanho, comprimento_palmilha) VALUES";
//   foreach ($grades as $key => $grade) {
//     $sql .= " ({$id},{$grade['tamanho']},{$grade['valor']}),";
//     $query = "SELECT * from estoque_grade where id_produto = {$id} and tamanho = {$grade['tamanho']} and id_responsavel=1;";
//     $resultado = $conexao->query($query);
//     if (!$existe_tamanho = $resultado->fetch()) {
//       $query = "INSERT INTO estoque_grade (id_produto, tamanho) VALUES ({$id},{$grade['tamanho']});";
//       $stmt = $conexao->prepare($query);
//       $stmt->execute();
//     }
//   }
//   $sql = substr($sql, 0, -1);
//   $stmt = $conexao->prepare($sql);
//   return $stmt->execute();
// }
// function removeProdutoGradeECodBarras($id_produto, $tamanho)
// {
//   $query = "";
//   $query .= "DELETE FROM produtos_grade WHERE id={$id_produto} AND tamanho={$tamanho};";
//   $query .= "DELETE FROM produtos_grade_cod_barras
// WHERE id_produto={$id_produto} AND tamanho={$tamanho}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }
// function buscaTamanhoPorId($id, $tamanho)
// {
//   $query = "SELECT tamanho, comprimento_palmilha FROM produtos_grade WHERE id=$id AND tamanho=$tamanho;
//   SELECT id_produto,tamanho FROM produtos_grade_cod_barras WHERE id_produto=$id AND tamanho=$tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $tamanho_id = $resultado->fetch();
//   return $tamanho_id;
// }
// function insereNovotamanhoNaGrade($id, $tamanho, $solado, $id_fornecedor)
// {
//   $query = "INSERT INTO produtos_grade(id,tamanho, comprimento_palmilha)VALUES ({$id},{$tamanho},{$solado});
//           INSERT INTO produtos_grade_cod_barras(id_produto,tamanho, cod_barras,seq_tamanho)VALUES ({$id},{$tamanho},{$id_fornecedor}{$id}{$tamanho},1);
//           INSERT INTO estoque_grade (id_produto,tamanho) VALUES ({$id},{$tamanho});";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

/*
function insereGradeProduto($id,$grade){
    $tamanhos = buscaGradeTamanho($grade);
    $query="";
    foreach ($tamanhos as $tamanho):
      $query.="INSERT INTO PRODUTOS_GRADE (id,tamanho) VALUES ({$id},{$tamanho->tamanho});";
    endforeach;
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}*/
// function insereTamanhoSoladoProduto($id, $tamanho, $solado)
// {
//   $query = "UPDATE produtos_grade SET comprimento_palmilha ={$solado} WHERE id = {$id} AND tamanho ={$tamanho};";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// function insereGradeProdutoEstoque($id, $grade)
// {
//   $tamanhos = buscaGradeTamanho($grade);
//   $query = "";
//   foreach ($tamanhos as $tamanho) :
//     $query .= "INSERT INTO estoque_grade (id_produto,tamanho) VALUES ({$id},{$tamanho->tamanho});";
//   endforeach;
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }
/*
function insereGradeProdutoCodBarrasPadrao($id_fornecedor,$id,$grade){
    $tamanhos = buscaGradeTamanho($grade);
    $query="";
    foreach ($tamanhos as $tamanho):
      $query.="INSERT INTO PRODUTOS_GRADE_COD_BARRAS (id_produto,tamanho,cod_barras,seq_tamanho)
      VALUES ({$id},{$tamanho->tamanho},'{$id_fornecedor}{$id}{$tamanho->tamanho}',1);";
    endforeach;
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}*/

function removeProduto($id)
{
    $query = "DELETE FROM produtos WHERE ID={$id}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

// function alteraProduto($id, $produto, $idUsuario)
// {
//     $query = "UPDATE produtos SET
//              descricao='{$produto['descricao']}',
//              usuario = $idUsuario,
//              id_fornecedor={$produto['id_fornecedor']},
//              bloqueado={$produto['bloqueado']},
//              id_categoria={$produto['id_categoria']},
//              id_linha = {$produto['id_linha']},
//              destaque = {$produto['destaque']},
//              especial = {$produto['especial']},
//              material_cabedal = '{$produto['material_cabedal']}',
//              material_solado = '{$produto['material_solado']}',
//              altura_solado = '{$produto['altura_solado']}',
//              altura_caixa = '{$produto['altura_caixa']}',
//              largura_caixa = '{$produto['largura_caixa']}',
//              comprimento_caixa = '{$produto['comprimento_caixa']}',
//              peso_caixa = '{$produto['peso_caixa']}',
//              metro_cubico_caixa = '{$produto['metro_cubico_caixa']}',
//              nome_comercial = '{$produto['nome_comercial']}'
//              WHERE ID={$id};";
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->prepare($query);
//     return $stmt->execute();
// }

// function alteraProdutoAdmin($id, $produto, $idUsuario)
// {
//     $query = "UPDATE produtos SET
//            descricao='{$produto['descricao']}',
//            usuario = $idUsuario,
//            id_fornecedor='{$produto['id_fornecedor']}',
//            bloqueado='{$produto['bloqueado']}',
//            id_categoria='{$produto['id_categoria']}',
//            id_linha = '{$produto['id_linha']}',
//            destaque = '{$produto['destaque']}',
//            especial = '{$produto['especial']}',
//            valor_custo_produto_fornecedor = '{$produto['precoAquarios']}',
//            valor_custo_produto = '{$produto['preco']}',
//            material_cabedal = '{$produto['material_cabedal']}',
//            material_solado = '{$produto['material_solado']}',
//            altura_solado = '{$produto['altura_solado']}',
//            altura_caixa = '{$produto['altura_caixa']}',
//            largura_caixa = '{$produto['largura_caixa']}',
//            comprimento_caixa = '{$produto['comprimento_caixa']}',
//            peso_caixa = '{$produto['peso_caixa']}',
//            metro_cubico_caixa = '{$produto['metro_cubico_caixa']}',
//            nome_comercial = '{$produto['nome_comercial']}'
//            WHERE id={$id};";
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->prepare($query);
//     return $stmt->execute();
// }

// function buscaProdutoGrade($id)
// {
//   $query = "SELECT pg.* FROM produtos_grade pg WHERE pg.id={$id}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

// function buscaProdutoGradeTamanho(int $id)
// {
//   $query = "SELECT pg.*, eg.vendido,eg.estoque, cig.quantidade_total FROM produtos_grade pg
//   LEFT OUTER JOIN estoque_grade eg ON (eg.id_produto = pg.id AND eg.tamanho = pg.tamanho)
//   LEFT OUTER JOIN compras_itens_grade cig ON (cig.id_produto = pg.id AND cig.tamanho = pg.tamanho)
//   WHERE pg.id={$id} AND eg.id_responsavel = 1  GROUP BY pg.tamanho";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

// function buscaProdutoGradeEstoque($id)
// {
//   $query = "SELECT eg.*, eg.estoque+eg.vendido total FROM estoque_grade eg WHERE eg.id_produto={$id} AND eg.id_responsavel = 1";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

function listaProdutosFornecedor($fornecedor, $filtro)
{
    $ano = DATE('Y');
    $mes = DATE('m');
    $query = "SELECT p.* FROM produtos p WHERE p.id_fornecedor = {$fornecedor} {$filtro} AND p.valor_custo_produto>0 ORDER BY p.id DESC";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $lista = $stmt->fetchAll();
    return $lista;
}

// function listaGrades()
// {
//   $query = "SELECT * FROM grades ORDER BY nome";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

// function buscaProdutoEstoque($filtro, $filtro2, $filtro3, $pagina, $itens)
// {
//   $query = "SELECT CASE WHEN produtos.promocao = 1 THEN produtos.preco_promocao ELSE produtos.valor_venda_cpf END valor_1,
//     CASE WHEN produtos.promocao = 1 THEN produtos.preco_promocao ELSE produtos.valor_venda_cnpj END valor_2,
//     produtos.preco_promocao as valor_3,
//     produtos.id, produtos.descricao, produtos.destaque, produtos.localizacao, produtos.promocao,
//     (SELECT SUM(eg.estoque) estoque from estoque_grade eg WHERE eg.id_produto=produtos.id) estoque,
//     (SELECT pf.caminho FROM produtos_foto pf WHERE pf.id=produtos.id LIMIT 1)caminho FROM produtos
//     INNER JOIN estoque_grade ON (estoque_grade.id_produto = produtos.id)
//     INNER JOIN produtos_grade_cod_barras pgcb ON (pgcb.id_produto = produtos.id)
//     {$filtro} GROUP BY produtos.id {$filtro2}
//     ORDER BY produtos.destaque DESC, produtos.data_entrada {$filtro3},
//     produtos.descricao  LIMIT {$pagina},{$itens}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

// function buscaTotalProdutosEstoque($filtro, $filtro2)
// {
//   $query = "SELECT COUNT(produtos.id)quant FROM produtos
//   INNER JOIN categorias c ON (c.id=produtos.id_categoria)
//   INNER JOIN estoque_grade ON (estoque_grade.id_produto = produtos.id)
//   {$filtro} {$filtro2}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   $linha = $stmt->fetch();
//   return $linha['quant'];
// }

function removerProdutoFotos($id)
{
    $query = "DELETE FROM produtos_foto where id={$id}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

//function buscaProdutosPromocao()
//{
//  $query = "SELECT id, descricao from produtos WHERE produtos.promocao = 1 LIMIT 3";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}

function buscaProdutosNovidades()
{
    $query = 'SELECT id, descricao from produtos ORDER BY data ASC LIMIT 3';
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function filtraProdutos($filtro)
{
    $query =
        'SELECT produtos.id, produtos.descricao from produtos LEFT JOIN produtos_foto ON (produtos.id = produtos_foto.id)' .
        $filtro;
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll(PDO::FETCH_OBJ);
    return $lista;
}

// function filtraProdutosPagina($pagina, $itens, $filtro)
// {
//   $query = "SELECT
//     produtos.id,
//     produtos.descricao,
//     (SELECT razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor)fornecedor,
//     produtos.valor_custo_produto_fornecedor custo_fornecedor,
//     produtos.valor_custo_produto custo_produto,
//     produtos.valor_venda_cpf as preco_cpf,
//     produtos.valor_venda_cnpj as preco_cnpj,
//     produtos.promocao
//     from produtos " . $filtro . " ORDER BY id DESC LIMIT 25";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function filtraProdutosEstoque($filtro)
// {
//   $query = "SELECT p.id, p.localizacao, p.descricao, c.razao_social fornecedor from produtos p
//   INNER JOIN estoque_grade eg ON (eg.id_produto=p.id)
//   INNER JOIN colaboradores c ON (c.id=p.id_fornecedor)
//   {$filtro} AND eg.id_responsavel = 1 GROUP BY p.id ORDER BY data_entrada DESC, p.descricao";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function filtraProdutosEstoqueTotal($filtro)
// {
//   $query = "SELECT COUNT(p.id) pares from produtos p
//   INNER JOIN estoque_grade eg ON (eg.id_produto=p.id)
//   INNER JOIN  categorias ca ON (p.id_categoria=ca.id)
//   INNER JOIN colaboradores c ON (c.id=p.id_fornecedor)
//   {$filtro}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetch();
//   return $lista['pares'];
// }

function removeFotoProdutoSequencia($id_produto, $sequencia)
{
    $query = "DELETE FROM produtos_foto where id={$id_produto}
  and sequencia={$sequencia};";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

function buscaFotoProdutoSequencia($id_produto, $sequencia)
{
    $query = "SELECT caminho,nome_foto FROM produtos_foto WHERE id = {$id_produto}
  AND sequencia = {$sequencia}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $linha = $stmt->fetch();
    return $linha;
}

// function insereProdutoCodBarrasManual($id_produto, $tamanho, $cod_barras, $id_fornecedor, $seq)
// {
//   $query = "INSERT INTO produtos_grade_cod_barras (id_produto,tamanho,cod_barras,seq_tamanho)
//   VALUES ({$id_produto},{$tamanho},'{$cod_barras}',{$seq});";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// function removeProdutoCodigoBarrasManual($id_produto, $tamanho, $cod_barras)
// {
//   $query = "DELETE FROM produtos_grade_cod_barras where id_produto={$id_produto}
//   and tamanho ={$tamanho} and cod_barras='{$cod_barras}';";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// function buscaGradeProduto($id)
// {
//   $query = "SELECT id, tamanho, comprimento_palmilha FROM produtos_grade
//   WHERE id = {$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaEstoquePar($id_produto, $tamanho)
// {
//   $query = "SELECT estoque FROM estoque_grade
//   WHERE id_produto={$id_produto} AND tamanho={$tamanho};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['estoque'];
// }

// function buscaPrevisaoPar($id_produto, $tamanho)
// {
//   $query = "SELECT SUM(cig.quantidade_total) previsao FROM compras_itens_grade cig
//   INNER JOIN compras_itens ci ON (ci.id_compra = cig.id_compra AND ci.sequencia = cig.id_sequencia)
//   WHERE cig.id_produto={$id_produto} AND cig.tamanho={$tamanho} AND ci.id_situacao=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['previsao'];
// }

// function buscaReservadoPar($id_produto, $tamanho)
// {
//   $query = "SELECT COUNT(pi.uuid) reservado FROM pedido_item pi
//     WHERE pi.id_produto={$id_produto} AND pi.tamanho={$tamanho} AND pi.situacao=15;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['reservado'];
// }

function verificaComprasProduto($id_produto)
{
    $query = "SELECT * FROM compras_itens
    WHERE id_produto={$id_produto};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaProdutoDefeito($faturamento, $sequencia)
// {
//   $query = "SELECT di.*, p.valor_custo_produto custo, p.descricao referencia
//     FROM devolucao_item di INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.id_faturamento = {$faturamento} AND di.sequencia = {$sequencia} AND di.defeito=1";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   $linha = $stmt->fetch();
//   return $linha;
// }

// function buscaProdutosEstoqueMostruario($filtro)
// {
//   $query = "SELECT p.id, p.descricao referencia, p.mostruario, p.localizacao,
//     ti.preco atc_vista, ti2.preco atc_prazo, ti3.preco var_prazo FROM produtos p
//     INNER JOIN estoque_grade eg ON (eg.id_produto = p.id)
//     LEFT OUTER JOIN tabela_item ti ON (ti.id_tabela=p.id_tabela AND ti.id_tipo=2)
//     LEFT OUTER JOIN tabela_item ti2 ON (ti2.id_tabela=p.id_tabela AND ti2.id_tipo=1)
//     LEFT OUTER JOIN tabela_item ti3 ON (ti3.id_tabela=p.id_tabela AND ti3.id_tipo=3)
//     WHERE 1=1 AND eg.estoque>0 $filtro GROUP BY p.id ORDER BY p.data_mostruario DESC, p.mostruario DESC;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaTotalProdutosEstoqueMostruario($filtro)
// {
//   $query = "SELECT COUNT(p.id)quant FROM produtos p
//     INNER JOIN estoque_grade eg ON (eg.id_produto = p.id)
//     WHERE 1=1 AND eg.estoque>0 $filtro;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['quant'];
// }

function insereOrdemDeConferencia($id_produto, $usuario, $data_emissao)
{
    date_default_timezone_set('America/Sao_Paulo');
    $data = DATE('Y-m-d H:i:s');
    $query = "INSERT INTO ordem_correcao_estoque (id_produto,usuario,data_emissao)
    VALUES ({$id_produto},{$usuario},'{$data_emissao}');";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

function buscaOrdemCorrecao($id_produto)
{
    $query = "SELECT * FROM ordem_correcao_estoque WHERE id_produto={$id_produto};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaParesCorrigidosUltimos20Dias($id_cliente)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $data = DATE('Y-m-d H:i:s');
//   $data20Dias = DATE('Y-m-d H:i:s', strtotime("-20 days", strtotime($data)));
//   $query = "SELECT *,p.descricao referencia,u.nome usuario FROM pedido_item_corrigir pic
//   INNER JOIN usuarios u ON (u.id=pic.id_vendedor)
//   INNER JOIN produtos p ON (p.id=pic.id_produto)
//   WHERE DATE(pic.data_hora)>=DATE('$data20Dias') AND pic.id_cliente={$id_cliente};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaProdutoEstoqueGradeTamanho($id, $tamanho)
// {
//   $query = "SELECT eg.estoque+eg.vendido estoque, p.localizacao FROM estoque_grade eg
//     INNER JOIN produtos p ON (p.id=eg.id_produto)
//     WHERE eg.id_produto={$id} AND eg.tamanho={$tamanho};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// function buscaEstoqueProdutoGrade(\PDO $conexao, int $idProduto):array
// {
//   $sql = $conexao->prepare(
//     "SELECT
//       estoque_grade.id,
//       estoque_grade.id_produto,
//       estoque_grade.nome_tamanho,
//       (estoque_grade.estoque + estoque_grade.vendido) estoque,
//       (
//         SELECT produtos.localizacao
//         FROM produtos
//         WHERE produtos.id = estoque_grade.id_produto
//       ) localizacao
//     FROM estoque_grade
//     WHERE estoque_grade.id_produto = :id_produto
//     AND estoque_grade.id_responsavel = 1;"
//   );
//   $sql->bindValue(":id_produto", $idProduto, PDO::PARAM_INT);
//   $sql->execute();
//   $linhas = $sql->fetchAll(PDO::FETCH_ASSOC) ?? [];

//   return $linhas;
// }

// function buscaProdutosCatalogo($filtro, $filtro2, $filtro3, $pagina, $itens, $campos_tabela, $join_tabela, $numero_de_compras)
// {
//echo $query = "SELECT   p.id,
//          p.descricao,
//          p.data_primeira_entrada,
//          CASE WHEN p.preco_promocao > 0 AND p.preco_promocao <= 100 THEN  ( p.valor_custo_produto_historico * ( 1 + ( p.porcentagem_comissao / ( 100 - p.porcentagem_comissao ) ) ) ) ELSE p.valor_venda_cpf END preco_cpf,
//          CASE WHEN p.preco_promocao > 0 AND p.preco_promocao <= 100 THEN  ( p.valor_custo_produto_historico * ( 1 + ( p.porcentagem_comissao_cnpj / ( 100 - p.porcentagem_comissao_cnpj ) ) ) ) ELSE p.valor_venda_cnpj END preco_cnpj,
//          p.valor_venda_cpf preco_cpf_promocao,
//          p.valor_venda_cnpj preco_cnpj_promocao,
//          p.preco_promocao,
//          (SELECT SUM(eg.estoque) estoque
//          FROM   estoque_grade eg
//          WHERE  eg.id_produto = p.id)
//            estoque,
//          (SELECT pf.caminho
//          FROM   produtos_foto pf
//          WHERE  pf.id = p.id
//          LIMIT  1)
//            caminho,
//          p.destaque,
//          p.promocao,
//          p.preco_promocao desconto,
//          CASE WHEN p.data_primeira_entrada IS NOT NULL THEN DATEDIFF(CURDATE(), p.data_primeira_entrada) ELSE 100 END NUM_DIAS,
//          CASE
//            WHEN p.preco_promocao > 0 AND p.preco_promocao < 100 THEN
//              COALESCE(
//                ( SELECT TIMEDIFF(promocoes.data_fim, now()) FROM promocoes WHERE p.id = promocoes.id_produto AND TIMEDIFF(promocoes.data_fim, now()) > 0 ORDER BY promocoes.data_fim ASC )
//              ,0)
//                ELSE
//                  0
//            END
//              tempo_restante,
//              CASE
//          WHEN
//            DATE((SELECT promocoes.ultima_alteracao FROM promocoes WHERE p.id = promocoes.id_produto AND promocoes.status = 1 LIMIT 1 )) >
//            DATE(p.data_entrada )
//              THEN (SELECT promocoes.ultima_alteracao FROM promocoes WHERE p.id = promocoes.id_produto AND promocoes.status = 1 LIMIT 1 )
//                ELSE p.data_entrada
//            END data_entrada,
//          COALESCE(
//            ( SELECT promocoes.status FROM promocoes WHERE p.id = promocoes.id_produto LIMIT 1 )
//          ,0) primeira_promocao
//          {$campos_tabela}
//          FROM produtos p
//          INNER JOIN estoque_grade ON (estoque_grade.id_produto = p.id)
//          {$join_tabela}
//          WHERE
//          p.bloqueado=0 AND p.preco_promocao <> 100 {$filtro}
//          AND (p.especial = 0 OR 2 <= COALESCE((SELECT COUNT(faturamento.id) FROM faturamento WHERE faturamento.id_cliente = {$cliente} AND faturamento.situacao = 2),0)) {$filtro}
//          GROUP BY p.id {$filtro2}
//          ORDER BY tempo_restante DESC, {$filtro3} LIMIT {$pagina},{$itens};";
//   $query = "SELECT   p.id,
//             'ew' categoria,
//             p.descricao,
//             p.data_primeira_entrada,
//             p.valor_venda_cpf preco_cpf,
//             p.valor_venda_cnpj preco_cnpj,
//             p.valor_custo_produto_historico,
//             p.porcentagem_comissao,
//             p.porcentagem_comissao_cnpj,
//              SUM(estoque_grade.estoque) estoque,
//             (SELECT pf.caminho FROM   produtos_foto pf WHERE  pf.id = p.id LIMIT  1) caminho,
//             p.destaque,
//             p.promocao,
//             p.preco_promocao,
//             CASE WHEN p.data_primeira_entrada IS NOT NULL THEN DATEDIFF(CURDATE(), p.data_primeira_entrada) ELSE 100 END NUM_DIAS,
//             0 tempo_restante,
//             p.data_entrada,
//             0 primeira_promocao
//             {$campos_tabela}
//             FROM produtos p
//             INNER JOIN estoque_grade ON (estoque_grade.id_produto = p.id)
//             {$join_tabela}
//             WHERE
//             p.bloqueado=0 AND p.preco_promocao <> 100 {$filtro}
//             AND (p.especial = 0 OR 2 <= {$numero_de_compras}) {$filtro}
//             AND estoque_grade.id_responsavel = 1
//             GROUP BY p.id {$filtro2}
//             ORDER BY  {$filtro3} LIMIT {$pagina},{$itens};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaTotalProdutosCatalogo($filtro)
// {
//   $query = "SELECT COUNT(p.id) quantidade FROM produtos p
//   LEFT OUTER JOIN estoque_grade ON (estoque_grade.id_produto=p.id)
//   LEFT OUTER JOIN produtos_foto pf ON (pf.id=p.id AND pf.sequencia=1)
//   WHERE 1=1 {$filtro};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['quantidade'];
// }

// function buscaAmostraProduto($ref)
// {
//   $query = "SELECT mostruario FROM produtos
//   WHERE id={$ref};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['mostruario'];
// }

// function removeMostruarioComEstoqueZerado()
// {
//   if (DATE('H') == 11) {
//     //limpa mostruario
//     $query = "SELECT eg.id_produto, (SUM(eg.estoque)+SUM(eg.vendido)) estoque
//         FROM estoque_grade eg GROUP BY eg.id_produto
//         HAVING (SUM(eg.estoque)+SUM(eg.vendido))<=0 ORDER BY eg.id_produto;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();
//     $sql = "";
//     if ($linhas != null) {
//       foreach ($linhas as $key => $l) {
//         $sql .= "UPDATE produtos SET mostruario=0 WHERE id={$l['id_produto']};";
//       }
//     }
//     if ($sql != '') {
//       $conexao->exec($sql);
//     }

//     //limpa destaques
//     $query = "SELECT eg.id_produto, SUM(eg.estoque) estoque
//         FROM estoque_grade eg GROUP BY eg.id_produto
//         HAVING SUM(eg.estoque)<=0 ORDER BY eg.id_produto;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();
//     $sql = "";
//     if ($linhas != null) {
//       foreach ($linhas as $key => $l) {
//         $sql .= "UPDATE produtos SET destaque=0 WHERE id={$l['id_produto']};";
//       }
//     }
//     if ($sql != '') {
//       $conexao->exec($sql);
//     }
//   }
// }

function buscaReferenciasIguais(int $id, string $referencia, int $id_fornecedor)
{
    $query = "SELECT p.id, p.descricao referencia FROM produtos p
    WHERE p.id<>{$id} AND LOWER(SUBSTRING_INDEX(p.descricao,' ',1))=LOWER('{$referencia}')
    AND p.id_fornecedor = {$id_fornecedor}
    AND p.premio = 0";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linhas = $resultado->fetchAll();
    return $linhas;
}

// function buscaEstoqueProdutoTamanho($produto, $tamanho)
// {
//   $query = "SELECT tamanho FROM estoque_grade
//     WHERE id_produto={$produto} AND tamanho = {$tamanho} AND (estoque>0 || vendido >0) AND id_responsavel = 1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch();
// }

// function buscaCompraProdutoTamanho($produto, $tamanho, $compra)
// {
//   $query = "SELECT tamanho FROM compras_itens_grade
//   WHERE id_produto={$produto} AND tamanho = {$tamanho} AND id_compra={$compra} AND quantidade_total>0;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch();
// }

// function buscaTamanhoProdutoPorlinha($linha_parametro)
// {
//   $query = "SELECT linha.id cod_linha, linha.nome nome_linha, estoque_grade.tamanho FROM produtos
//   INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
//   INNER JOIN linha ON linha.id = produtos.id_linha
//   INNER JOIN grades ON grades.nome = linha.nome
//   WHERE estoque_grade.id_responsavel = 1 AND (estoque_grade.tamanho BETWEEN grades.min AND grades.max) AND ";
//   if ($linha_parametro) $query .= "produtos.id_linha IN (" . implode($linha_parametro) . ") AND ";
//   $query .= " produtos.bloqueado = 0 GROUP BY linha.nome, estoque_grade.tamanho
//   ORDER BY linha.nome, estoque_grade.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

function buscaReferenciaPesquisaAutoCompletaLog($pesquisa)
{
    $query = "SELECT CONCAT(produtos.id,' - ',produtos.descricao) nome FROM produtos
            WHERE produtos.bloqueado = 0
              AND (LOWER(produtos.descricao) LIKE LOWER('%{$pesquisa}%')
                   OR  produtos.id = $pesquisa) LIMIT 35";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaProdutosComCadastroIncompleto($id_fornecedor)
// {
//   $query = "SELECT DISTINCT id
//             FROM  produtos
//             WHERE  id_fornecedor = {$id_fornecedor}
//             AND( descricao = '' OR descricao IS NULL
//                   OR nome_comercial = '' OR nome_comercial IS NULL
//                   OR id_categoria = '' OR id_categoria IS NULL
//                   OR id_linha = '' OR id_linha IS NULL
//                   OR grade_min = '' OR grade_min IS NULL
//                   OR grade_max = '' OR grade_max IS NULL
//                   OR material_cabedal = '' OR material_cabedal IS NULL
//                   OR material_solado = '' OR material_solado IS NULL
//                   OR preco = '' OR preco IS NULL
//                   OR largura_caixa = '' OR largura_caixa IS NULL
//                   OR comprimento_caixa = '' OR comprimento_caixa IS NULL
//                   OR altura_caixa = '' OR altura_caixa IS NULL
//                   OR peso_caixa = '' OR peso_caixa IS NULL
//                 );";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscaReferenciaPesquisaAutoCompleta($pesquisa, $filtro, $filtro2)
// {
//   $query = "SELECT DISTINCT p.descricao, (SELECT pf.caminho FROM produtos_foto pf WHERE pf.id=p.id LIMIT 1) caminho FROM produtos p
//   INNER JOIN produtos_foto ON produtos_foto.id =  p.id
//   INNER JOIN estoque_grade ON (estoque_grade.id_produto = p.id)
//   WHERE p.bloqueado = 0 AND estoque_grade.id_responsavel = 1 AND LOWER(p.descricao) LIKE LOWER('{$pesquisa}%')
//   {$filtro}
//   GROUP  BY p.descricao
//   UNION
//   SELECT DISTINCT p.descricao, (SELECT pf.caminho FROM produtos_foto pf WHERE pf.id=p.id LIMIT 1) caminho FROM produtos p
//   INNER JOIN produtos_foto ON produtos_foto.id =  p.id
//   INNER JOIN estoque_grade ON (estoque_grade.id_produto = p.id)
//   WHERE p.bloqueado = 0 AND estoque_grade.id_responsavel = 1 AND LOWER(p.descricao) LIKE LOWER('%{$pesquisa}%')
//   {$filtro}
//   GROUP  BY p.descricao
//   {$filtro2}
//   LIMIT 35";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function addRemoveFavorito($produto, $usuario)
// {
//   $query = "CALL addRemoveFavorito({$produto},{$usuario});";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }

// function buscaPremio()
// {
//   $query = "SELECT SUM(eg.estoque) estoque,p.id,p.descricao,p.premio_pontos,p.premio
//   FROM produtos p
//   inner JOIN estoque_grade eg on (p.id = eg.id_produto)
//   where p.premio =1 AND estoque>0 AND p.bloqueado = 0 AND eg.id_responsavel = 1 GROUP by p.id ORDER BY p.premio_pontos DESC";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }
// function buscagradePremio($filtro)
// {
//   $query = "SELECT eg.id_produto, eg.nome_tamanho,eg.tamanho,SUM(eg.estoque) quantidade,p.descricao,p.premio_pontos,p.premio FROM estoque_grade eg
//   INNER join produtos p on (p.id=eg.id_produto)
//   WHERE eg.id_produto='{$filtro}' AND eg.id_responsavel = 1 AND p.premio = 1 AND eg.estoque >=1
//   GROUP by eg.id_produto, eg.tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaPrevisaoPares($id_produto, $tamanho)
// {
//   $query = "SELECT
//         DATE_FORMAT(compras.data_previsao, '%d/%m/%Y') data_previsao,
//         compras.id_fornecedor,
//         compras.data_emissao,
//         compras.situacao,
//         compras.edicao_fornecedor,
//         compras.lote
//     FROM compras_itens_grade
//     INNER JOIN compras ON compras.id = compras_itens_grade.id_compra
//     INNER JOIN compras_itens_caixas ON compras_itens_caixas.id_compra = compras_itens_grade.id_compra
//     WHERE compras_itens_grade.id_produto = {$id_produto}
//     AND compras_itens_grade.tamanho = {$tamanho}
//     AND compras_itens_grade.quantidade > 0
//     AND compras_itens_caixas.situacao = 1
//     /**AND DATE(compras.data_previsao) >= CURRENT_DATE */
//     GROUP BY compras.id
//     ORDER BY compras.data_previsao DESC LIMIT 1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
//   return $lista;
// }

// function buscaProdutosBloqueado()
// {
//   $query = "SELECT p.id,p.bloqueado, SUM(eg.estoque) total_estoque FROM produtos p
//   INNER JOIN estoque_grade eg on (p.id = eg.id_produto)WHERE p.bloqueado=1 AND eg.id_responsavel=1 GROUP by p.id HAVING total_estoque>0";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }
function desbloqueiaParComEstoque($id)
{
    $query = "UPDATE produtos SET bloqueado = 0 WHERE bloqueado = 1 AND id = '{$id}';";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}
function buscaProdutoLocalizacao($descricao)
{
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($descricao);
    $lista = $resultado->fetchALL();
    return $lista;
}

// function getAllTabelas()
// {
//   $conexao = Conexao::criarConexao();
//   $retorno = [];

//   $sql = "SELECT * FROM categorias ORDER BY nome;";
//   $resultado = $conexao->query($sql);
//   $retorno['categorias'] = $resultado->fetchALL(PDO::FETCH_ASSOC);

//   $sql = "SELECT * FROM linha ORDER BY nome;";
//   $resultado = $conexao->query($sql);
//   $retorno['linhas'] = $resultado->fetchALL(PDO::FETCH_ASSOC);

//   $sql = "SELECT * FROM tabelas ORDER BY nome";
//   $resultado = $conexao->query($sql);
//   $retorno['tabelas'] = $resultado->fetchALL(PDO::FETCH_ASSOC);

//   $retorno['tipos_grades'] = TiposGradeService::buscaTiposGradeSemFormatacaoJson($conexao);

//   $retorno['grades'] = listaGrades();

//   return $retorno;
// }

// function buscaTabelaPreco($preco)
// {
//   $conexao = Conexao::criarConexao();
//   $retorno = [];

//   $sql = "SELECT ti.id_tabela id from tabela_item ti
//           INNER JOIN produtos p on p.id_tabela = ti.id_tabela and p.consignado = 1
//           where ti.id_tipo = 1 and ti.preco = $preco;";
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchALL(PDO::FETCH_ASSOC);
// }

// function criarTabelaPreco($preco_a_vista, $preco_a_prazo)
// {
//   $conexao = Conexao::criarConexao();
//   $sql = "SELECT max(id)+1 as id from tabelas";
//   $resultado = $conexao->query($sql);
//   $result = $resultado->fetchALL(PDO::FETCH_ASSOC);
//   $id = $result[0]['id'];
//   $sql = "INSERT INTO tabelas(id, nome)
//           VALUES ($id,$preco_a_prazo);";
//   $stmt = $conexao->prepare($sql);
//   if ($stmt->execute()) {
//     $sql = "INSERT INTO tabela_item (id_tabela, id_tipo, preco)
//           VALUES ($id, 1, $preco_a_prazo), ($id, 2, $preco_a_vista), ($id, 3, $preco_a_prazo), ($id, 4, $preco_a_vista), ($id, 5, $preco_a_vista), ($id, 6, $preco_a_vista);";
//     $stmt = $conexao->prepare($sql);
//     if ($stmt->execute()) {
//       return $id;
//     } else {
//       $sql = "DELETE FROM tabelas where id = $id";
//       $stmt = $conexao->prepare($sql);
//       $stmt->execute();
//     }
//   }
//   return false;
// }

// function buscaConfigProdutos($filtros)
// {
//   $sql = "SELECT p.id,p.descricao, c.razao_social fornecedor, p.porcentagem_comissao,  p.porcentagem_comissao_cnpj from produtos p
//     inner join colaboradores c on c.id = p.id_fornecedor
//     where p.consignado = 1";

//   if (!empty($filtros['descricao'])) {
//     $sql .= " AND LOWER(p.descricao) like LOWER('%{$filtros['descricao']}%')";
//   }

//   if (!empty($filtros['fornecedor'])) {
//     $sql .= " AND p.id_fornecedor = {$filtros['fornecedor']}";
//   }
//   $sql .= " order by p.descricao LIMIT 100;";

//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscaUmProduto($id)
// {
//   $sql = "SELECT p.*, pf.caminho from produtos p
//           LEFT JOIN produtos_foto pf ON p.id = pf.id where p.id = {$id}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($sql);
//   $produto =  $resultado->fetchAll(PDO::FETCH_ASSOC);
//   return $produto ? $produto[0] : $produto;
// }

function buscaProdutoPelaDescricao($descricao)
{
    //Esta função se perdeu em um merge, voltei pois estava dando erro na index controler
    if (!$descricao) {
        return;
    }
    $descricao = utf8_encode($descricao);
    $sql = "SELECT p.id,p.descricao, pf.caminho from produtos p
LEFT JOIN produtos_foto pf ON p.id = pf.id
where LOWER(p.descricao) like LOWER('%$descricao%') order by p.descricao";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($sql);
    return $resultado->fetchAll(PDO::FETCH_ASSOC);
}

// function salvarComissaoProduto($config)
// {
//   if (!$config['tipo']) return false;
//   $sql = '';
//   switch ($config['tipo']) {
//     case '1':
//       $sql = "UPDATE produtos set porcentagem_comissao = {$config['comissao']}, porcentagem_comissao_cnpj = {$config['comissao_cnpj']} where id = {$config['id_produto']} and consignado = 1;";
//       break;
//     case '2':
//       $sql = "UPDATE produtos set porcentagem_comissao = {$config['comissao']}, porcentagem_comissao_cnpj = {$config['comissao_cnpj']} where id_fornecedor = {$config['id_fornecedor']} and consignado = 1;";
//       break;
//     case '3':
//       $sql = "UPDATE produtos set porcentagem_comissao = {$config['comissao']}, porcentagem_comissao_cnpj = {$config['comissao_cnpj']} where consignado = 1;";
//       break;
//   }

//   return false;
// }

function produtoJaExiste($descricao)
{
    return count(
        Conexao::criarConexao()
            ->query("SELECT 1 FROM produtos WHERE descricao = '$descricao'")
            ->fetchAll(PDO::FETCH_ASSOC)
    ) > 0;
}

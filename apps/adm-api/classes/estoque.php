<?php
require_once 'conexao.php';
require_once __DIR__ . '/../vendor/autoload.php';

// function criaCreditoClienteProdutoPago($uuid)
// {
//   $query = "SELECT * FROM garantir_pares WHERE uuid='{$uuid}';";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $produto = $resultado->fetch();
//   $data_emissao = DATE('Y-m-d H:i:s');
//   $cliente = $produto['id_cliente'];

//     $lancamento = new \MobileStock\model\Lancamento('P', 1, 'PA', intVal($cliente), $data_emissao, $produto['preco'], 1,12);
//     $lancamento->sequencia = 1;
//     $lancamento->valor_total = $produto['preco'];
//     LancamentoCrud::salva($conexao, $lancamento);
//   /*$query = "INSERT INTO lancamento_financeiro (sequencia,tipo,documento,situacao,origem,id_colaborador,data_emissao,data_vencimento,
//     valor,valor_total,id_usuario) 
//     VALUES (1,'P',12,1,'Pago Antecipado',{$produto['id_cliente']},'{$data_emissao}','{$data_emissao}',
//     {$produto['preco']},{$produto['preco']},1);";
//   $conexao->exec($query);*/
//   $query = "DELETE FROM garantir_pares WHERE uuid='{$uuid}';";
//   $conexao->exec($query);
// }

// function existeProdutoEstoque($produto)
// {
//   $query = "SELECT * from estoque_grade WHERE
//   tamanho = {$produto['tamanho']} AND
//   id_produto= {$produto['id']} AND
//   estoque > 0";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// function buscaProdutoEstoqueTamanho($id_produto, $tamanho)
// {
//   $query = "SELECT estoque from estoque_grade WHERE
//   id_produto = {$id_produto} AND tamanho = {$tamanho};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['estoque'];
// }

// function buscaProdutoAguardandoEntrada($id_produto, $tamanho)
// {
//   echo $query = "SELECT COALESCE(produtos_aguarda_entrada_estoque.qtd,0) estoque
//             FROM produtos_aguarda_entrada_estoque
//             WHERE produtos_aguarda_entrada_estoque.tamanho = {$tamanho}
//                   AND produtos_aguarda_entrada_estoque.id_produto = {$id_produto}
//                   AND produtos_aguarda_entrada_estoque.tipo_entrada = 'TR'
//                   AND produtos_aguarda_entrada_estoque.em_estoque = 'F';";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['estoque'];
// }

// function buscaProdutoClienteMaisAntigo($id_produto, $tamanho)
// {
//   $query = "SELECT uuid from pedido_item WHERE
//   id_produto = {$id_produto} AND tamanho = {$tamanho} AND situacao=6
//   ORDER BY data_hora ASC LIMIT 1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['uuid'];
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaGradeCaixaApi($id_compra, $sequencia)
//{
//  $query = "SELECT * from compras_itens_grade WHERE
//  id_compra = {$id_compra} AND id_sequencia= {$sequencia}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


function listaMovimentacoes($filtro)
{
  $query = "SELECT me.id, me.data, me.tipo, me.origem, u.nome usuario, lf.id_colaborador, (SELECT razao_social FROM colaboradores WHERE colaboradores.id=lf.id_colaborador LIMIT 1) razao_social from movimentacao_estoque me
  INNER JOIN usuarios u ON (u.id=me.usuario) 
  INNER JOIN movimentacao_estoque_item mei ON (mei.id_mov=me.id)
  INNER JOIN produtos p ON (p.id=mei.id_produto)
  INNER JOIN lancamento_financeiro_seller lf ON (me.id = lf.numero_movimento)  {$filtro}
  GROUP BY me.id ORDER BY me.data DESC LIMIT 50";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaEstoqueProdutos($id)
//{
//  $query = "SELECT * FROM estoque_grade WHERE id_produto={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll(PDO::FETCH_ASSOC);
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// function buscaEstoquePresencialGrade($id)
// {
//   $query = "SELECT 	estoque_grade.id_produto produto, 
//                 estoque_grade.tamanho tamanho,
//                 estoque_grade.estoque estoque, 
//                 estoque_grade.nome_tamanho nome_tamanho,  
//                 produtos.tipo_grade tipo_grade
//               FROM estoque_grade 
//               INNER JOIN produtos ON produtos.id = estoque_grade.id_produto
//               WHERE estoque_grade.id_produto= {$id} AND estoque_grade.estoque>0;";

//   //$query = "SELECT tamanho, estoque FROM estoque_grade WHERE id_produto={$id} AND estoque>0;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaTotalPresencial($id_produto)
//{
//  $query = "SELECT SUM(presencial) presencial from estoque_grade WHERE
//  id_produto = {$id_produto};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['presencial'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// function buscaProdutoGradeComEstoque($id)
// {
//   $query = "SELECT eg.id_produto, eg.tamanho, eg.estoque FROM estoque_grade eg 
//   WHERE eg.id_produto={$id} AND eg.estoque>0;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// function buscaTotalEstoqueProduto($id)
// {
//   $query = "SELECT (SUM(estoque)+SUM(vendido)) estoque FROM estoque_grade WHERE id_produto={$id} AND (estoque>0 OR vendido>0);";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $estoque = $resultado->fetch();
//   return $estoque['estoque'];
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function listaProdutosEstoque()
//{
//  $query = "SELECT * FROM produtos;";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  $stmt->execute();
//  return $stmt->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaTotalEstoqueVendido($id)
//{
//  $query = "SELECT SUM(vendido)quantidade FROM estoque_grade WHERE id_produto={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $estoque = $resultado->fetch();
//  return $estoque['quantidade'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaEstoqueTamanho($id_produto, $tamanho)
//{
//  $query = "SELECT Coalesce(estoque,0)estoque FROM estoque_grade WHERE id_produto={$id_produto} AND tamanho={$tamanho};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $estoque = $resultado->fetch();
//  return $estoque['estoque'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaProdutoReservado($id_produto, $tamanho)
//{
//  $query = "SELECT id_produto, tamanho, COUNT(id_produto) quantidade, uuid FROM pedido_item
//  WHERE situacao=15 AND id_produto={$id_produto} AND tamanho={$tamanho} GROUP BY tamanho;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaTotalEstoquePresencial($id_produto)
//{
//  $query = "SELECT SUM(presencial) presencial FROM estoque_grade WHERE
//  id_produto={$id_produto};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['presencial'];
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// function buscaEstoqueMostruario($id)
// {
//   $query = "SELECT tamanho FROM estoque_grade WHERE id_produto=$id AND mostruario>0 LIMIT 1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['tamanho'];
// }

function buscaProdutosComCorrecaoDeEstoque()
{
  $query = "SELECT p.*, u.nome usuario, oc.data_emissao FROM produtos p
    INNER JOIN ordem_correcao_estoque oc ON (oc.id_produto=p.id)
    INNER JOIN usuarios u ON (u.id=oc.usuario);";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

// function buscaEstoqueTotalGrade($id_produto)
// {
//   $query = "SELECT eg.tamanho, eg.estoque FROM estoque_grade eg
//   WHERE id_produto={$id_produto} AND eg.estoque>0;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaProdutosFaturamentoItem(int $faturamento)
//{
//  $query = "SELECT id_produto FROM faturamento_item
//  WHERE id_faturamento={$faturamento} GROUP BY id_produto;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// function buscaEstoqueTotalEVendido(int $id_produto)
// {
//   $query = "SELECT COALESCE(SUM(estoque),0)estoque, COALESCE(SUM(vendido),0)vendido FROM estoque_grade
//   WHERE id_produto={$id_produto};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch();
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function verificaProdutoEstoqueLocalizacao($id_produto)
//{
//  $query = "SELECT SUM(estoque)+SUM(vendido) estoque FROM estoque_grade WHERE id_produto={$id_produto}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  if ($linha['estoque'] <= 0) {
//    $query = "UPDATE produtos SET localizacao=null WHERE id={$id_produto}";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//  }
//  return false;
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaTotalEstoqueCusto()
//{
//  $query = "SELECT SUM(eg.estoque)estoque, SUM(eg.estoque*p.valor_custo_produto)custo FROM estoque_grade eg
//  INNER JOIN produtos p ON (p.id=eg.id_produto) GROUP BY p.id;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaProdutosPrecoZerado()
//{
//  $query = "SELECT id, descricao FROM produtos WHERE valor_custo_produto=0;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


function buscaEstoquePorQuantidade()
{
  $query = "SELECT p.id, p.descricao,c.razao_social, SUM(eg.estoque)estoque, SUM(eg.estoque*p.valor_custo_produto)custo FROM estoque_grade eg
  INNER JOIN produtos p ON (p.id=eg.id_produto)
  INNER JOIN colaboradores c ON (c.id=p.id_fornecedor)
   GROUP BY p.id ORDER BY estoque DESC;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

// --Commented out by Inspection START (18/08/2022 13:10):
//function buscaEstoqueSemLocalizacao()
//{
//  $query = "SELECT p.id, p.descricao, SUM(eg.estoque)pares,p.localizacao FROM estoque_grade eg
//  INNER JOIN produtos p ON (p.id=eg.id_produto) WHERE p.localizacao IS NULL
//  AND eg.estoque>0 GROUP BY p.id ORDER BY p.id;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)

//INSERT----------------------------------------------------------------------------------------------------------//

// function insereHistoricoEstoque($id, $tamanho, $estoque)
// {
//   $query = "INSERT INTO estoque_grade (id_produto,tamanho,estoque) VALUES ({$id},{$tamanho},{$estoque})";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

//UPDATE----------------------------------------------------------------------------------------------------------//

// function atualizaLocalDoProduto(int $id_produto)
// {
//   $query = "UPDATE produtos set localizacao=null WHERE id_produto={$id_produto};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function removeHistoricoEstoque($id)
//{
//  $query = "DELETE FROM estoque_grade WHERE id_produto={$id}";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function geraAtualizacaoDataEntrada($data, $id_produto)
//{
//  return "UPDATE produtos set produtos.data_entrada = '{$data}', bloqueado=0,
//          produtos.data_primeira_entrada = CASE
//                                    WHEN ((produtos.data_primeira_entrada IS NULL) OR (produtos.data_primeira_entrada = '0000-00-00')) THEN CURDATE()
//                                    ELSE produtos.data_primeira_entrada
//                                  END
//          WHERE id={$id_produto};";
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)

// function buscaMovimentacaoEstoqueVendido($idMovimentacao)
// {
//   $conexao = Conexao::criarConexao();


//   $query = "SELECT mei.id_produto,p.descricao produto, p.id id FROM movimentacao_estoque_item mei INNER join produtos p on (p.id=mei.id_produto)where mei.id_mov ='{$idMovimentacao}'";

//   $busca = $conexao->query($query)->fetch(PDO::FETCH_ASSOC);

//   $query = "SELECT sum(quantidade) historico,id_produto FROM movimentacao_estoque_item where id_produto = " . $busca['id_produto'] . " and compra >0";
//   $busca1 = $conexao->query($query)->fetch(PDO::FETCH_ASSOC);

//   $query = "SELECT sum(estoque) estoque,sum(vendido) vendidos from estoque_grade WHERE id_produto = " . $busca['id_produto'];
//   $busca2 = $conexao->query($query)->fetch(PDO::FETCH_ASSOC);

//   $resultado = array('id' => $busca['id'], "produto" => $busca['produto'], "historico" => $busca1['historico'], "estoque" => $busca2['estoque'], "vendidos" => $busca2['vendidos']);

//   return $resultado;
// }

// --Commented out by Inspection START (18/08/2022 13:10):
//function insereProdutoEstoqueTemporario(array $produto, int $usuario)
//{
//  $data_atual = DATE("Y-m-d H:i:s");
//  $sql = "INSERT INTO estoque_fotos (id_produto,tamanho,usuario,data_inserido) VALUES ({$produto['id_produto']},{$produto['tamanho']},{$usuario},'{$data_atual}');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($sql);
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// --Commented out by Inspection START (18/08/2022 13:10):
//function listaProdutosEstoqueTemporarios()
//{
//  $sql = "SELECT p.descricao referencia, ef.* FROM estoque_fotos
//  INNER JOIN produtos p ON (p.id = ef.id_produto);";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($sql);
//  $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  if ($linhas) {
//    return json_encode($linhas);
//  } else {
//    return json_encode([]);
//  }
//}
// --Commented out by Inspection STOP (18/08/2022 13:10)


// function buscaPrecoProduto(int $id): string
// {
//   $query = "SELECT valor_venda_cpf from produtos where id = '{$id}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $arr = $resultado->fetch(PDO::FETCH_ASSOC);
//   return $arr['valor_venda_cpf'];
// }

function buscalistaLocalizacaoVago()
{
  $query = "SELECT localizacao_estoque.local,
              localizacao_estoque.num_caixa,
              SUM(estoque_grade.estoque+estoque_grade.vendido) estoque,
              GROUP_CONCAT(DISTINCT produtos.descricao) produtos,
              (localizacao_estoque.num_caixa - SUM(estoque_grade.estoque+estoque_grade.vendido)) vago
              FROM produtos 
              INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
              INNER JOIN localizacao_estoque ON localizacao_estoque.local = produtos.localizacao
              WHERE estoque_grade.estoque+estoque_grade.vendido > 0
              GROUP BY localizacao_estoque.local 
              ORDER BY vago DESC ";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll(PDO::FETCH_ASSOC);
}

// function buscalistaLocalizacao()
// {
//   $query = "SELECT localizacao_estoque.tipo,
//               localizacao_estoque.local,
//               localizacao_estoque.num_caixa
//             FROM localizacao_estoque ORDER BY localizacao_estoque.local";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscalistaLocalizacaoLogs($pesquisa)
// {
//   $query = "SELECT log_produtos_localizacao.id_produto,
//               CONCAT(log_produtos_localizacao.id_produto,' - ', produtos.descricao,' ', produtos.cores) descricao,
//               log_produtos_localizacao.old_localizacao,
//               log_produtos_localizacao.new_localizacao,
//               log_produtos_localizacao.qtd_entrada,
//               (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = log_produtos_localizacao.usuario) usuario,              
//               DATE_FORMAT(log_produtos_localizacao.data_hora,'%d/%m/%Y %H:%i:%s') data_alteracao
//             FROM log_produtos_localizacao
//             INNER JOIN produtos ON produtos.id = log_produtos_localizacao.id_produto
//             WHERE produtos.descricao LIKE '%{$pesquisa}%' OR produtos.id = '{$pesquisa}' ORDER BY log_produtos_localizacao.data_hora DESC";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscalistaAguardaRetornoEstoque($id_produto)
// {
//   $query = "SELECT 
//               produtos.descricao nome_produto, 
//               MAX(produtos_aguarda_entrada_estoque.tamanho) tamanho,
//               CASE 
//                   WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'CO' THEN 'Compra'
//                   WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'FT' THEN 'Foto'
//                   WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'TR' THEN 'Troca'
//                   WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'PC' THEN 'Pedido Cancelado'            
//                   WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'SP' THEN 'Separar foto'
//                   ELSE 'NAO IDENTIFICADO'
//               END tipo_entrada,
//               SUM(produtos_aguarda_entrada_estoque.qtd) qtd,
//               group_concat(JSON_OBJECT(
// 					          'tamanho', produtos_aguarda_entrada_estoque.tamanho,
//                     'nome_tamanho', (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.id_produto = produtos_aguarda_entrada_estoque.id_produto AND estoque_grade.tamanho = produtos_aguarda_entrada_estoque.tamanho LIMIT 1),
//                     'id', produtos_aguarda_entrada_estoque.id
//               )) estoque
//             FROM produtos_aguarda_entrada_estoque
//             INNER JOIN produtos ON produtos.id = produtos_aguarda_entrada_estoque.id_produto
//             WHERE produtos.id = $id_produto 
// 	            AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
//             GROUP BY produtos_aguarda_entrada_estoque.tipo_entrada";
//   $conexao = \MobileStock\database\Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

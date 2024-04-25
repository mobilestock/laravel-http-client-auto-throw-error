<?php

use MobileStock\database\Conexao;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'produtos.php';

//lista as compras cadastradas
// function listaCompras($filtros, $pagina, $itens)
// {
//   $query = "SELECT
//                 c.id,
//                 c.data_emissao,
//                 p.id id_produto,
//                 c.data_previsao,
//                 c.situacao situacao,
//                 s.nome situacao_nome,
//                 col.razao_social fornecedor,
//                 (SELECT sum(valor_total) from compras_itens where id_compra = c.id) valor_total
//               FROM
//                 compras c
//                 INNER JOIN compras_itens ci ON (c.id = ci.id_compra)
//                 INNER JOIN situacao s ON (c.situacao = s.id)
//                 INNER JOIN compras_itens_grade cig ON (cig.id_compra = c.id)
//                 INNER JOIN produtos p ON (p.id = ci.id_produto)
//                 INNER JOIN colaboradores col ON (col.id = c.id_fornecedor)
//               WHERE 1=1";

//   if (isset($filtros['id']) && !empty($filtros['id'])) {
//     $query .= " AND c.id = {$filtros['id']}";
//   };

//   if (isset($filtros['fornecedor']) && !empty($filtros['fornecedor'])) {
//     $query .= " AND p.id_fornecedor = {$filtros['fornecedor']}";
//   };

//   if (isset($filtros['referencia']) && !empty($filtros['referencia'])) {
//     $referencia = strtolower($filtros['referencia']);
//     $query .= " AND LOWER(p.descricao) like '%{$referencia}%' or p.id like '%{$referencia}%'";
//   };

//   if (isset($filtros['tamanho']) && !empty($filtros['tamanho'])) {
//     $query .= " AND cig.tamanho = {$filtros['tamanho']}";
//   };

//   if (isset($filtros['situacao']) && !empty($filtros['situacao'])) {
//     $query .= " AND c.situacao = {$filtros['situacao']}";
//   };

//   if (isset($filtros['data_inicial_emissao']) && isset($filtros['data_fim_emissao']) && !empty($filtros['data_inicial_emissao']) && !empty($filtros['data_fim_emissao'])) {
//     $query .= " AND c.data_emissao BETWEEN '{$filtros['data_inicial_emissao']} 00:00:00' and '{$filtros['data_fim_emissao']} 23:59:00'";
//   };

//   if (isset($filtros['data_inicial_previsao']) && isset($filtros['data_fim_previsao']) && !empty($filtros['data_inicial_previsao']) && !empty($filtros['data_fim_previsao'])) {
//     $query .= " AND c.data_previsao BETWEEN '{$filtros['data_inicial_previsao']} 00:00:00' and '{$filtros['data_fim_previsao']} 23:59:00'";
//   };

//   $query .= " GROUP BY c.id ORDER BY c.id DESC LIMIT {$pagina},{$itens}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

function buscaTotalCompras($filtro)
{
    $query = "SELECT DISTINCT COUNT(compras.id) quantidade FROM compras
  LEFT OUTER JOIN compras_itens ON (compras.id = compras_itens.id_compra)
  LEFT OUTER JOIN produtos ON (produtos.id=compras_itens.id_produto)
  INNER JOIN compras_itens_grade ON (compras_itens_grade.id_compra = compras.id)
  {$filtro};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['quantidade'];
}

function buscaCompraProdutoVolume($compra, $sequencia, $volume)
{
    $query = "SELECT cig.*, cic.volume, ci.preco_unit FROM compras_itens_grade cig
  INNER JOIN compras_itens_caixas cic ON (cic.id_compra=cig.id_compra AND cic.id_sequencia= cig.id_sequencia)
  INNER JOIN compras_itens ci ON (ci.id_compra=cic.id_compra AND ci.sequencia=cic.id_sequencia)
  WHERE cig.id_compra = {$compra} AND cig.id_sequencia= {$sequencia} AND cic.volume={$volume} AND cig.quantidade > 0;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function listaComprasFornecedor($id_fornecedor)
{
    $query = "SELECT c.*, SUM(ci.quantidade_total)pares,
  cl.razao_social fornecedor,
  s.nome situacao_nome FROM compras c
  INNER JOIN compras_itens ci ON (ci.id_compra = c.id)
  INNER JOIN colaboradores cl ON (cl.id = c.id_fornecedor)
  INNER JOIN situacao s ON (s.id = c.situacao)
  WHERE c.id_fornecedor = {$id_fornecedor}
  AND (c.situacao=1 OR c.situacao=14)
  GROUP BY c.id
  ORDER BY c.id ASC";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function buscaCompraItemDetalhes($id, $id_fornecedor, $id_produto, $sequencia)
{
    $query = "SELECT cic.*, p.descricao, s.nome nome_situacao, u.nome nome_usuario, ci.preco_unit,
  (ci.preco_unit*ci.quantidade_total/ci.caixas) valor_total, (ci.quantidade_total/ci.caixas) pares
  FROM compras_itens_caixas cic
  INNER JOIN produtos p ON (p.id = cic.id_produto)
  INNER JOIN situacao s ON (s.id = cic.situacao)
  INNER JOIN compras_itens ci ON (ci.id_compra = cic.id_compra AND ci.sequencia = cic.id_sequencia)
  LEFT OUTER JOIN usuarios u ON (u.id = cic.usuario)
  WHERE cic.id_fornecedor = {$id_fornecedor} AND cic.id_compra = {$id}
  AND cic.id_produto = {$id_produto} AND cic.id_sequencia = {$sequencia}
  GROUP BY cic.volume ORDER BY cic.volume;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaCompraItemDetalhesGrade($id, $id_produto, $sequencia)
// {
//   $query = "SELECT cig.* FROM compras_itens_grade cig
//   WHERE cig.id_compra = {$id} AND cig.id_produto = {$id_produto}
//   AND cig.id_sequencia = {$sequencia}
//   GROUP BY cig.tamanho ORDER BY cig.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

//busca o id da ultima compra
// function buscaUltimaCompra()
// {
//   $query = "SELECT MAX(id) id FROM compras";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['id'];
// }

//busca as informações de cabeçalho da compra
function buscaCompra($id)
{
    $query = "SELECT compras.*, colaboradores.razao_social nome_fornecedor, situacao.nome nome_situacao,
    (Select SUM(quantidade_total) from compras_itens where compras_itens.id_compra={$id}) quantidade_total,
    (Select SUM(valor_total) from compras_itens where compras_itens.id_compra={$id}) valor_total,
    (Select SUM(caixas) from compras_itens where compras_itens.id_compra={$id}) caixas
    FROM compras
    inner join colaboradores on (colaboradores.id = compras.id_fornecedor)
    inner join situacao on (situacao.id = compras.situacao)
    where compras.id={$id}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha;
}

//gera campos padrão de nova compra
function buscaCompraVazia()
{
    date_default_timezone_set('America/Sao_Paulo');
    $compra = [
        'id' => 0,
        'id_fornecedor' => 1,
        'data_previsao' => date('Y-m-d H:i:s'),
        'data_emissao' => date('Y-m-d H:i:s'),
        'situacao' => 1,
        'nome_situacao' => 'Em aberto',
        'quantidade_total' => 0,
    ];
    return $compra;
}

// function buscaProdutosSeparados()
// {
//   $query = "SELECT count(pi.sequencia)separados FROM pedido_item pi WHERE (situacao = 6);";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['separados'];
// }

function buscaTotalComprasEmAberto()
{
    $query = "SELECT SUM(ci.quantidade_total)pares, MONTH(c.data_previsao)mes, YEAR(c.data_previsao)ano, SUM(ci.valor_total)valor
    FROM compras_itens ci
    INNER JOIN compras c ON (c.id=ci.id_compra)
    WHERE c.situacao=1 and c.data_previsao IS NOT NULL and c.data_previsao <> ''
    GROUP BY YEAR(c.data_previsao), MONTH(c.data_previsao)
    ORDER BY YEAR(c.data_previsao), MONTH(c.data_previsao) ;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function buscaComprasPorAnoMes($ano, $mes)
{
    $query = "SELECT c.id, f.razao_social fornecedor, c.data_previsao, SUM(ci.quantidade_total)pares, SUM(ci.valor_total)valor FROM compras c
  INNER JOIN colaboradores f ON (f.id=c.id_fornecedor)
  INNER JOIN compras_itens ci ON (ci.id_compra=c.id)
  WHERE c.situacao=1 AND YEAR(c.data_previsao)='{$ano}' AND MONTH(c.data_previsao)='{$mes}'
  GROUP BY c.id ORDER BY c.data_previsao;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

//insere uma nova compra
// function insereCompra($compra)
// {
//   $query = "INSERT INTO compras (id,id_fornecedor,data_emissao,data_previsao,situacao,lote) VALUES
//     ({$compra['id']},{$compra['id_fornecedor']},'{$compra['data_emissao']}','{$compra['data_previsao']}',{$compra['situacao']},{$compra['id']})";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

function atualizarSituacaoCompraProdutoVolume($compra, $sequencia, $volume, $usuario, $id_lancamento, $idMov)
{
    date_default_timezone_set('America/Sao_Paulo');
    $data = DATE('Y-m-d H:i:s');
    $query = "UPDATE compras_itens_caixas SET situacao=2, usuario = {$usuario}, data_baixa = '{$data}', id_lancamento={$id_lancamento}, numero_mov={$idMov}
    WHERE id_compra={$compra} AND id_sequencia={$sequencia} AND volume={$volume};";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

//insere um produto em uma compra
// function insereCompraProdutos($id, $compra, $sequencia, $preco_unit, $caixas, $situacao, $quantidade_total = 0, $valor_total = 0)
// {
//   $query = "INSERT INTO compras_itens (id_compra,sequencia,id_produto,preco_unit,caixas,quantidade_total,valor_total,id_situacao) VALUES
//     ({$compra},{$sequencia},{$id},{$preco_unit},{$caixas},$quantidade_total,$valor_total,{$situacao})";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

//insere a grade do produto da compra
// function insereCompraProdutosGrade($id_compra, $sequencia, $grades, $caixas)
// {
//   $query = "";
//   foreach ($grades as $grade) :
//     if (is_numeric($grade['quantidade'])) {
//       $quantidade = $grade['quantidade'];
//       $quantidadeTotal = $quantidade * $caixas;
//       $tamanho = $grade['tamanho'];
//       $produto = $grade['produto'];
//       $query .= "INSERT INTO compras_itens_grade (
//       id_compra,
//       id_sequencia,
//       tamanho,
//       quantidade,
//       quantidade_total,
//       id_produto
//     ) VALUES (
//       $id_compra,
//       $sequencia,
//       $tamanho,
//       $quantidade,
//       $quantidadeTotal,
//       $produto
//     );";
//     }
//   endforeach;
//   $conexao = Conexao::criarConexao();
//   return $conexao->prepare($query)->execute();
// }

// function atualizaCompraProdutosGrade($id_compra, $sequencia, $gradePreenchida, $caixas)
// {
//   $query = "";
//   foreach ($gradePreenchida as $grade) :
//     $query .= "UPDATE compras_itens_grade set quantidade={$grade->quantidade}, quantidade_total={$grade->quantidade}*{$caixas}, id_produto ={$grade->produto} WHERE
//     id_compra={$id_compra} and id_sequencia = {$sequencia} and tamanho ={$grade->tamanho};";
//   endforeach;
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

function alteraCompra($id, $compra)
{
    $previsao = $compra['data_previsao'];
    $query = "UPDATE compras set data_previsao='{$previsao}' where id={$id};";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

function buscaCompraProdutos($id)
{
    $query = "SELECT compras_itens.*, produtos.bloqueado,produtos.id id_produto, produtos.descricao desc_produto,
    situacao.nome nome_situacao FROM compras_itens
    inner join Produtos on (Produtos.id = compras_itens.id_produto)
    inner join Situacao on (Situacao.id = compras_itens.id_situacao)
    WHERE compras_itens.id_compra={$id} ORDER BY compras_itens.sequencia";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaCompraProduto($id_compra, $sequencia)
// {
//   $query = "SELECT compras_itens.*, produtos.descricao, produtos.bloqueado, compras_itens.preco_unit preco,
//     produtos.grade, produtos.id_categoria, produtos.id_fornecedor, produtos.id_tabela
//     FROM compras_itens
//     inner join Produtos on (Produtos.id = compras_itens.id_produto)
//     WHERE compras_itens.id_compra={$id_compra} and compras_itens.sequencia = {$sequencia}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetch();
// }

// function buscaCompraProdutoGrade($id_compra, $sequencia, $volume)
// {
//   $query = "SELECT cig.* ,
//   (SELECT p.tipo_grade FROM produtos p WHERE p.id = cig.id_produto) tipo_grade,
//   (SELECT es.nome_tamanho FROM estoque_grade es WHERE es.id_produto = cig.id_produto AND es.tamanho = cig.tamanho) nome_tamanho
//   FROM compras_itens_grade cig
//     INNER JOIN compras_itens_caixas cic ON (cic.id_sequencia=cig.id_sequencia)
//     WHERE cig.id_compra={$id_compra} AND cig.id_sequencia={$sequencia}
//     AND cic.volume={$volume} GROUP BY cig.tamanho ORDER BY cig.tamanho";

//   /*$query = "SELECT cig.* FROM compras_itens_grade cig
//   INNER JOIN compras_itens_caixas cic ON (cic.id_sequencia=cig.id_sequencia)
//   WHERE cig.id_compra={$id_compra} AND cig.id_sequencia={$sequencia}
//   AND cic.volume={$volume} GROUP BY cig.tamanho ORDER BY cig.tamanho";*/

//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// function buscaCompraProdutoGradeEditar($id_compra, $sequencia)
// {
//   $query = "SELECT cig.* FROM compras_itens_grade cig
//     INNER JOIN compras_itens_caixas cic ON (cic.id_sequencia=cig.id_sequencia)
//     WHERE cig.id_compra={$id_compra} AND cig.id_sequencia={$sequencia}
//     GROUP BY cig.tamanho ORDER BY cig.tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// function buscaCompraProdutoGradeRelatorio($id_compra, $sequencia)
// {
//   $query = "SELECT cig.*, (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.id_produto = cig.id_produto AND estoque_grade.tamanho = cig.tamanho) nome_tamanho FROM compras_itens_grade cig
//     INNER JOIN compras_itens_caixas cic ON (cic.id_sequencia=cig.id_sequencia)
//     WHERE cig.id_compra={$id_compra} AND cig.id_sequencia={$sequencia}
//     GROUP BY cig.tamanho ORDER BY cig.tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// function buscaUltimaSeqProdutoCompra($idCompra)
// {
//   $query = "SELECT MAX(sequencia) sequencia from compras_itens where id_compra={$idCompra}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['sequencia'];
// }

function existeCompra($id)
{
    $query = "SELECT * FROM compras WHERE id={$id}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha;
}

function atualizaCompraProdutos($id_produto, $id_compra, $sequencia, $preco_unit, $caixas, $total_pares)
{
    $query = "UPDATE compras_itens set preco_unit={$preco_unit},caixas={$caixas},
    quantidade_total={$total_pares},valor_total={$total_pares}*{$preco_unit} WHERE id_produto={$id_produto}
    and id_compra={$id_compra} and sequencia = {$sequencia}";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

function buscaTotalParesProdutoCompra($idCompra, $sequencia)
{
    $query = "SELECT SUM(quantidade_total) total from compras_itens_grade where id_compra={$idCompra} and id_sequencia={$sequencia};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['total'];
}

function buscaParesCaixa($id_compra, $sequencia)
{
    $query = "SELECT SUM(quantidade) quantidade from compras_itens_grade WHERE id_compra = {$id_compra} AND id_sequencia = {$sequencia}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['quantidade'];
}

function insereCompraProdutosCodigoBarras($id_fornecedor, $id_produto, $id_compra, $sequencia, $caixas)
{
    $query = '';
    for ($i = 1; $i <= $caixas; $i++) {
        $quantidade = buscaParesCaixa($id_compra, $sequencia);
        $query .=
            'INSERT INTO compras_itens_caixas (id_fornecedor,id_compra,id_produto,id_sequencia,volume,situacao,quantidade,codigo_barras) Values';
        $query .= "({$id_fornecedor},{$id_compra},{$id_produto},{$sequencia},{$i},1,{$quantidade},";
        $cb_fornecedor = str_pad($id_fornecedor, 5, 0, STR_PAD_LEFT);
        $cb_compra = str_pad($id_compra, 7, 0, STR_PAD_LEFT);
        $cb_produto = str_pad($id_produto, 6, 0, STR_PAD_LEFT);
        $cb_sequencia = str_pad($sequencia, 3, 0, STR_PAD_LEFT);
        $cb_volumes = str_pad($i, 3, 0, STR_PAD_LEFT);
        $query .= "'{$cb_fornecedor}{$cb_compra}{$cb_produto}{$cb_sequencia}{$cb_volumes}');";
    }
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

// function buscaCodigoBarrasCompra($id_compra)
// {
//     $query = "SELECT cic.*, CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) desc_produto,
//     COALESCE((SELECT pf.caminho caminho FROM produtos_foto pf WHERE pf.id = cic.id_produto AND pf.tipo_foto = 'MD' LIMIT 1),'') caminho,
//     compras.data_previsao previsao,
//     colaboradores.razao_social nome_fornecedor,
//     CASE WHEN produtos.tipo_grade = 4 THEN 13 ELSE 0 END tamanhoParaFoto,
//            CONCAT('[', (SELECT GROUP_CONCAT(JSON_OBJECT(
// 		'tamanho', compras_itens_grade.tamanho,
//     'nome_tamanho', (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.id_produto = compras_itens_grade.id_produto AND compras_itens_grade.tamanho = estoque_grade.tamanho AND estoque_grade.id_responsavel = 1)
// 	)) FROM compras_itens_grade WHERE compras_itens_grade.id_compra = cic.id_compra), ']') grade_autocomplete
//     FROM compras_itens_caixas cic
//     inner join produtos on (produtos.id = cic.id_produto)
//     inner join compras on (compras.id = cic.id_compra)
//     inner join colaboradores on (colaboradores.id = cic.id_fornecedor)
//     WHERE cic.id_compra={$id_compra}
//     ORDER BY cic.id_compra,cic.id_sequencia,cic.volume";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $fetch = array_filter((array) $resultado->fetchAll(PDO::FETCH_ASSOC));

//     return array_map(function (array $caixa) {
//         $caixa['grade_autocomplete'] = json_decode($caixa['grade_autocomplete'], true);
//         return $caixa;
//     }, $fetch);
// }

// function buscaEtiquetasColetivas($id_compra)
// {
//   date_default_timezone_set('America/Sao_Paulo');
//   $query = "SELECT cic.*, CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) desc_produto, CONCAT(compras.id,' - ',cic.id_sequencia,' - ',cic.volume) cod_compra,
//   DATE(compras.data_previsao) previsao, colaboradores.razao_social nome_fornecedor
//   FROM compras_itens_caixas cic inner join produtos on (produtos.id = cic.id_produto)
//   inner join compras on (compras.id = cic.id_compra)
//   inner join colaboradores on (colaboradores.id = cic.id_fornecedor)
//   WHERE cic.id_compra={$id_compra}
//   ORDER BY cic.id_compra,cic.id_sequencia,cic.volume";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);

//   $coletivas = array();
//   foreach ($linhas as $key => $linha) :
//     $coletiva = array();
//     $coletiva['referencia'] = $linha['desc_produto'];
//     $coletiva['codigo_barras'] = $linha['codigo_barras'];
//     $previsao = DATE_CREATE($linha['previsao']);
//     $previsaoFormatada = DATE_FORMAT($previsao, 'd-m-Y');
//     $coletiva['data_previsao'] = $previsaoFormatada;
//     $coletiva['cod_compra'] = $linha['cod_compra'];
//     $coletiva['grade'] = array();
//     $grades = buscaEtiquetasColetivasGrade($linha['codigo_barras']);
//     foreach ($grades as $key => $temp) :
//       $grade = array('codigo_barras' => $linha['codigo_barras'], 'tamanho' => $temp['tamanho'], 'nome_tamanho' => $temp['nome_tamanho'], 'quantidade' => $temp['quantidade']);
//       // $grade = array();
//       // $grade['codigo_barras'] = $linha['codigo_barras'];
//       // $grade['tamanho'] = $temp['tamanho'];
//       // $grade['tamanho'] = $temp['nome_tamanho'];
//       // $grade['quantidade'] = $temp['quantidade'];
//       array_push($coletiva['grade'], $grade);
//     endforeach;
//     array_push($coletivas, $coletiva);
//   endforeach;
//   return $coletivas;
// }

// function buscaEtiquetasColetivasGrade($codigo_barras)
// {
//   $conexao = Conexao::criarConexao();
//   $sql = $conexao->prepare(
//     "SELECT
//       cig.tamanho,
//       cig.quantidade,
//       (SELECT p.tipo_grade
//       FROM produtos p
//       WHERE p.id = cic.id_produto) tipo_grade,
//       (SELECT es.nome_tamanho
//       FROM estoque_grade es
//       WHERE es.id_produto = cic.id_produto AND es.tamanho = cig.tamanho) nome_tamanho
//     FROM compras_itens_grade cig
//     INNER JOIN compras_itens_caixas cic ON cic.id_compra=cig.id_compra AND cic.id_sequencia = cig.id_sequencia
//     WHERE cic.codigo_barras=$codigo_barras AND cig.quantidade>0"
//   );

//   $sql->execute();
//   $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
//   return $resultado;
// }

// function buscaEtiquetasUnitarias($id_compra)
// {

//   $query =
//         "SELECT cig.tamanho, SUM(cig.quantidade_total) quantidade,
//         produtos.id,
//         CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) desc_produto,  CONCAT(pgcb.cod_barras,'_',{$id_compra}) cod_barras, produtos.tipo_grade, estoque_grade.nome_tamanho,
//         COALESCE(produtos.localizacao, '') localizacao
//         FROM compras_itens_grade cig
//         INNER JOIN produtos ON (produtos.id = cig.id_produto)
//         INNER JOIN produtos_grade_cod_barras pgcb ON (pgcb.id_produto = cig.id_produto AND pgcb.tamanho = cig.tamanho)
//         INNER JOIN estoque_grade ON estoque_grade.id_produto = cig.id_produto AND estoque_grade.tamanho = cig.tamanho
//         WHERE cig.id_compra={$id_compra} AND pgcb.seq_tamanho=1 AND cig.quantidade > 0 AND estoque_grade.id_responsavel = 1
//         GROUP BY cig.id_produto,cig.tamanho
//         ORDER BY cig.id_produto,cig.tamanho;";

//   /*
//   $query = "SELECT cig.tamanho, SUM(cig.quantidade_total) quantidade, produtos.descricao desc_produto, pgcb.cod_barras cod_barras,
//   COALESCE(produtos.localizacao, '') localizacao
//   FROM compras_itens_grade cig inner join produtos on (produtos.id = cig.id_produto)
//   inner join produtos_grade_cod_barras pgcb ON (pgcb.id_produto = cig.id_produto AND pgcb.tamanho = cig.tamanho)
//   WHERE cig.id_compra={$id_compra} AND pgcb.seq_tamanho=1 GROUP BY cig.id_produto,cig.tamanho ORDER BY cig.id_produto,cig.tamanho;";
//   */

//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linhas = $resultado->fetchAll();
//   $unitarias = array();
//   foreach ($linhas as $key => $linha) :
//     for ($i = 1; $i <= $linha['quantidade']; $i++) {
//       if ($linha['quantidade'] > 0) {
//         $unitaria['referencia'] = $linha['id'] .' : '. $linha['desc_produto'];
//         $linha['tamanho']=$linha['nome_tamanho'];
//         $unitaria['tamanho'] = $linha['tamanho'];
//         $unitaria['cod_barras'] = $linha['cod_barras'];
//         $unitaria['localizacao'] = $linha['localizacao'];
//         $unitaria['consumidor'] = '';
//         $unitaria['referencia']=ConversorStrings::removeAcentos($unitaria['referencia']);
//         array_push($unitarias, $unitaria);
//       }
//     }
//   endforeach;
//   return $unitarias;
// }

//compra busca para inserir no estoque
function buscaCompraProdutosGrade($id_compra, $id_produto, $sequencia)
{
    $query = "SELECT * FROM compras_itens_grade WHERE id_compra={$id_compra}
  AND id_produto={$id_produto} AND id_sequencia={$sequencia}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

function buscaSituacaoCompra($id)
{
    $query = "SELECT situacao FROM compras WHERE id={$id}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['situacao'];
}

function buscaComprasPendentesProduto($id)
{
    $query = "SELECT * FROM compras
  INNER JOIN compras_itens ci ON (ci.id_compra = compras.id)
  WHERE compras.situacao = 1 and ci.id_produto={$id}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

function buscaCompraProdutoGradeId($id_compra, $id_produto)
{
    $query = "SELECT * FROM compras_itens_grade WHERE id_compra={$id_compra}
    and id_produto={$id_produto} ORDER BY tamanho";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

function insereDescricaoProdutoGrade($tamanho_informacao)
{
    $query = "$tamanho_informacao";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

// function buscaGradeEstoqueproduto($id)
// {
//   $query = "SELECT tamanho, estoque, vendido FROM estoque_grade WHERE id_produto ={$id} AND (estoque>0 OR vendido>0) AND id_responsavel=1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

// function buscaGradeCompraProduto($id, $id_compra)
// {
//   $query = "SELECT tamanho FROM `compras_itens_grade` WHERE id_produto={$id} AND quantidade_total>0 AND id_compra= {$id_compra} GROUP BY tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll();
// }

function buscaComprasPorDataEnvio($dataEnvio)
{
    $query = "SELECT co.id, c.email, c.razao_social fornecedor FROM compras co
    INNER JOIN colaboradores c ON (c.id=co.id_fornecedor)
    WHERE DATE(co.data_previsao)=DATE('{$dataEnvio}');";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}
function buscaAcessosProdutos($pesquisaPorData, $produtoFornecedor = '', $filtro12Pares = '', $pagina = 1)
{
    $offSet = $pagina ? $pagina * 10 - 10 : 0;
    $query = "SELECT
	colaboradores.id id_fornecedor,
    colaboradores.razao_social,
    produtos.id AS id_produto,
    produtos.descricao,
    paginas_acessadas.acessos,
    paginas_acessadas.adicionados,
    SUM(estoque_grade.estoque) estoqueTotal

  FROM colaboradores
  INNER JOIN produtos ON(produtos.id_fornecedor=colaboradores.id)
  INNER JOIN paginas_acessadas ON(paginas_acessadas.id_produto = produtos.id)
  INNER JOIN estoque_grade ON(estoque_grade.id_produto = produtos.id)

  WHERE $pesquisaPorData $produtoFornecedor AND estoque_grade.id_responsavel = 1

  GROUP BY produtos.id
  $filtro12Pares
  ORDER BY paginas_acessadas.acessos DESC LIMIT 10 OFFSET $offSet";

    // $query = "SELECT c.id AS id_fornecedor,c.razao_social,p.id AS id_produto,p.descricao,pa.acessos,pa.adicionados,SUM(eg.estoque)
    // FROM colaboradores c
    // INNER JOIN produtos p ON(p.id_fornecedor=c.id)
    // INNER JOIN paginas_acessadas pa ON(pa.id_produto = p.id)
    // INNER JOIN estoque_grade eg ON(eg.id_produto = p.id)
    // WHERE $pesquisaPorData $produtoFornecedor
    // GROUP BY p.id $filtro12Pares ORDER BY pa.acessos DESC $limite;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

// function listaEstoqueProdutosFornecedor($id_fornecedor, $filtro)
// {
//   $query = "SELECT eg.id_produto, eg.tamanho, eg.estoque FROM estoque_grade eg
//   INNER JOIN produtos p ON (p.id=eg.id_produto)
//   WHERE p.id_fornecedor={$id_fornecedor} AND p.valor_custo_produto>0 {$filtro} AND eg.id_responsavel=1 GROUP BY eg.id_produto, eg.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return json_encode($resultado->fetchAll());
// }

// function listaPrevisaoProdutosFornecedor($id_fornecedor, $filtro)
// {
//   $query = "SELECT cig.id_produto, cig.tamanho, SUM(cig.quantidade_total)previsao FROM compras_itens_grade cig
//   INNER JOIN compras_itens ci ON (ci.id_compra=cig.id_compra AND ci.sequencia=cig.id_sequencia)
//   INNER JOIN compras c ON (c.id=cig.id_compra)
//   INNER JOIN produtos p ON (p.id=ci.id_produto)
//   WHERE c.id_fornecedor = {$id_fornecedor} AND ci.id_situacao = 1 {$filtro}
//   GROUP BY cig.id_produto, cig.tamanho ORDER by cig.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   if ($resultado) {
//     return json_encode($resultado->fetchAll());
//   }
// }

// function listaReservadosFornecedor($id_fornecedor, $filtro)
// {
//   $query = "SELECT eg.id_produto, eg.tamanho, COUNT(pi.tamanho)reservado FROM estoque_grade eg
//   LEFT OUTER JOIN pedido_item pi ON (pi.id_produto=eg.id_produto AND pi.tamanho=eg.tamanho)
//   INNER JOIN produtos p ON (p.id=pi.id_produto)
//   WHERE p.id_fornecedor={$id_fornecedor} AND pi.situacao=15 AND p.valor_custo_produto>0 {$filtro} AND eg.id_responsavel=1 GROUP BY eg.id_produto, eg.tamanho;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   if ($resultado) {
//     return json_encode($resultado->fetchAll());
//   }
// }

function desbloqueiaProdutoNaCompra($produto)
{
    $query = "UPDATE produtos SET bloqueado=0 WHERE bloqueado=1 AND id= {$produto['id_produto']}; ";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

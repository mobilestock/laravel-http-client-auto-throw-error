<?php
// require_once 'Database.php';
// require_once '../regras/alertas.php';
// require_once '../classes/conexao.php';
// require_once '../classes/estoque.php';
// require_once '../funcoes/f-estante.php';
// require_once '../classes/estante.php';
// require_once '../classes/log.php';
// require_once '../model/estoque.php';

// header('Content-Type: application/json');

// $db = new Database();
// $uri = $_GET['url'];

// if ($_SERVER['REQUEST_METHOD'] == 'GET'){
//     if($uri==""){
//         echo json_encode($db->query("SELECT * FROM compras_itens_caixas"));
//     }else{
//         $sql = "SELECT compras_itens_caixas.id_compra compra,
//         colaboradores.id id_fornecedor,
//         colaboradores.razao_social fornecedor,
//         produtos.id id_produto,
//         produtos.descricao produto,
//         compras_itens_caixas.volume volume,
//         situacao.nome situacao,
//         compras_itens_caixas.codigo_barras codigo,
//         compras_itens_caixas.situacao id_situacao,
//         compras_itens_caixas.quantidade quantidade,
//         compras_itens_caixas.id_sequencia sequencia,
//         compras_itens.preco_unit preco
//         FROM compras_itens_caixas
//         INNER JOIN colaboradores ON (colaboradores.id = compras_itens_caixas.id_fornecedor)
//         INNER JOIN produtos ON (produtos.id = compras_itens_caixas.id_produto)
//         INNER JOIN situacao ON (situacao.id = compras_itens_caixas.situacao)
//         INNER JOIN compras_itens ON (compras_itens.id_compra = compras_itens_caixas.id_compra
//           and compras_itens.sequencia = compras_itens_caixas.id_sequencia)
//         WHERE compras_itens_caixas.codigo_barras = '{$uri}'";

//         if($db->query($sql)==null){
//             echo 'Código inválido';
//             http_response_code(405);
//         }else{
//             echo json_encode($db->query($sql));
//             http_response_code(200);
//         }
//     }
// } else if ($_SERVER['REQUEST_METHOD'] == 'POST'){
//     if($uri=='atualiza'){
//       $json = file_get_contents('php://input');
//       $caixas = json_decode($json,true);
//       foreach ($caixas as $caixa) :
//           $sql = "SELECT cig.*,cic.situacao from compras_itens_grade cig
//           INNER JOIN compras_itens_caixas cic ON (cic.id_compra = cig.id_compra AND cic.id_sequencia = cig.id_sequencia)
//           WHERE cic.situacao=1 AND cic.codigo_barras='{$caixa['codigo']}';";
//           $conexao = Conexao::criarConexao();
//           $resultado = $conexao->query($sql);
//           $volumes= $resultado->fetchAll();
//           $sql = "";
//           $id_produto_temp = "";
//           foreach ($volumes as $key => $volume):
//             if($id_produto_temp!=$volume['id_produto']){
//               $id_produto_temp = $volume['id_produto'];
//               date_default_timezone_set('America/Sao_Paulo');
//               $data = DATE('Y-m-d H:i:s');
//               $sql = geraAtualizacaoDataEntrada($data,$id_produto_temp);
//               $conexao = Conexao::criarConexao();
//               $conexao->exec($sql);
//             }
//             if($volume['situacao']==1){

//               $produto = ['id_produto'=>$volume['id_produto'],'tamanho'=>$volume['tamanho']];
//               $estoque = new Estoque($produto,'Entrada compra mobile',$volume['quantidade']);
//               $estoque->entradaEstoquePar();
              
//             }
//           endforeach;

//       endforeach;

//       $sql="";

//       foreach ($caixas as $caixa) :
//           $sql .= "UPDATE compras_itens_caixas set situacao=2 WHERE codigo_barras='{$caixa['codigo']}';";
//       endforeach;
//       $conexao = Conexao::criarConexao();
//       $conexao->exec($sql);

//     }
// }else{
//   http_response_code(405);
// }

 ?>

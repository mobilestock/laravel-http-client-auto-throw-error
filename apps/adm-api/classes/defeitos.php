<?php
require_once 'conexao.php';
// function buscaDefeitosEmAbertoFornecedor($id_fornecedor){
//     $query = "SELECT di.*,u.nome, p.descricao referencia, p.valor_custo_produto custo FROM devolucao_item di
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     INNER JOIN usuarios u ON (u.id=di.id_vendedor)
//     WHERE p.id_fornecedor={$id_fornecedor} AND di.defeito=1 AND di.abatido = 0";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }
// function buscaDefeitosEmAbertoFornecedor($id_fornecedor)
// {
//     $query = "SELECT
//      d.id,
//      d.id_fornecedor,
//      d.id_cliente,
//      d.id_produto,
//      d.descricao referencia,
//      d.descricao_defeito,
//      d.data_hora,
//      d.tamanho,
//      d.preco,
//      d.sequencia,
//      d.abater,
//      d.uuid,
//      u.nome,
//      p.valor_custo_produto custo,
//      d.status
//     FROM defeitos d
//     inner join produtos p on(p.id=d.id_produto)
//     Inner JOIN usuarios u ON (u.id = d.id_vendedor)
//     WHERE d.id_fornecedor = {$id_fornecedor} AND abater = 0
//     GROUP BY d.uuid";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

// function listaDefeitosEmAbertoFornecedor()
// {
//     $query = " SELECT sum(d.preco) valor, c.razao_social fornecedor, d.id_fornecedor, count(DISTINCT d.uuid) pares
//                 from defeitos d
//                 inner join colaboradores c on d.id_fornecedor = c.id
//                 where d.abater = 0 and d.status = 'A' group by d.id_fornecedor order by c.razao_social";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

// function insereDevolucaoItemManual($id_produto, $sequencia, $tamanho, $data)
// {
//     $query = "INSERT INTO devolucao_item (id_faturamento,id_cliente,id_produto,
//     sequencia, tamanho, tipo_cobranca, id_tabela, preco, situacao,
//     data_hora, cod_barras, uuid, troca_pendente,defeito) VALUES (1,12,{$id_produto},
//     {$sequencia},{$tamanho},1,1,0,2,'{$data}','Manual','Manual',0,1)";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function insereDefeitoItemManual($produto, $sequencia, $tamanho, $data, $descricao_defeito)
// {
//     $uuid = uniqid(rand(), true);
//     $usuario = $_SESSION["id_usuario"];
//     $query = "INSERT INTO defeitos(
//                 id_fornecedor,id_vendedor,id_cliente,id_produto,descricao,
//                 descricao_defeito,tamanho,preco,sequencia,data_hora,abater,uuid,status
//             ) VALUES (
//                 '{$produto['id_fornecedor']}','{$usuario}','0','{$produto['id']}',
//                 '{$produto['descricao']}','{$descricao_defeito}',$tamanho,{$produto['preco']},{$sequencia},'{$data}',0,'{$uuid}','A' )";

//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function atualizaDevolucaoDefeito($uuid, $id_lanc)
// {
//     $query = "UPDATE devolucao_item SET abatido = 1, lancamento_financeiro={$id_lanc}
//               WHERE uuid = '{$uuid}'";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

function atualizaDefeitoAbatido($uuid)
{
    $query = "UPDATE defeitos SET abater = 1
    WHERE uuid = '{$uuid}'";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

// function buscaDefeitosDevolvidosManual($id)
// {
//     $query = "SELECT di.*, p.descricao referencia from devolucao_item di
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.lancamento_financeiro={$id} AND di.abatido=1";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

function buscaAcertosDeDefeito($filtroAcerto)
{
    $query = "SELECT a.*, c.razao_social fornecedor, ad.valor, u.nome usuario FROM acertos a
    INNER JOIN acertos_documentos ad ON (a.id=ad.id_acerto)
    INNER JOIN usuarios u ON (u.id=a.id_colaborador)
    INNER JOIN colaboradores c ON (c.id=a.id_colaborador)
    {$filtroAcerto} AND ad.documento=10 AND ad.tipo='P'";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

// function buscaDefeitosDevolvidosAcerto($id)
// {
//     $query = "SELECT di.*, p.descricao referencia from devolucao_item di
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.acerto={$id} AND di.abatido=1";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

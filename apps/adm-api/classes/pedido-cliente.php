<?php
require_once 'conexao.php';

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaUltimaSequenciaProdutoPedidoCliente($cliente)
//{
//    $query = "SELECT MAX(sequencia) seq FROM pedido_item WHERE id_cliente={$cliente};";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['seq'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaClienteVinculadoUsuario($usuario)
// {
//     $query = "SELECT c.razao_social cliente,
//     c.id id_cliente,
//     c.endereco,
//     c.numero,
//     c.bairro,
//     c.cep,
//     c.cidade,
//     c.uf,
//     c.email,
//     c.telefone,
//     c.telefone2,
//     p.sinalizado,
//     c.total_pontos,
//     c.regime
//     FROM usuarios u
//     INNER JOIN colaboradores c ON (c.id=u.id_colaborador)
//     LEFT OUTER JOIN pedido p ON (p.id_cliente=c.id)
//     WHERE u.nivel_acesso=10 and u.id={$usuario}";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linha = $resultado->fetch();
//     return $linha;
// }
//function buscaSellerComNivelDeAcesso2($usuario)
//{
//    $query = "SELECT c.razao_social cliente,
//    c.id id_cliente,
//    c.endereco,
//    c.numero,
//    c.bairro,
//    c.cep,
//    c.cidade,
//    c.uf,
//    c.email,
//    c.telefone,
//    c.telefone2,
//    p.sinalizado,
//    c.total_pontos,
//    c.regime
//    FROM usuarios u
//    INNER JOIN colaboradores c ON (c.id=u.id_colaborador)
//    LEFT OUTER JOIN pedido p ON (p.id_cliente=c.id)
//    WHERE u.nivel_acesso = 30 and u.id={$usuario}";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha;
//}

// function buscaPedidoItemCliente($id_cliente)
// {
//     $query = "SELECT pedido_item.data_hora,pedido_item.premio, DATE(pedido_item.data_vencimento) data_vencimento, COALESCE(pf.caminho,'camera-solid.svg')caminho,
//     pedido_item.id_produto, produtos.descricao produto, pedido_item.preco, SUM(pedido_item.preco) valor, pedido_item.cliente, pedido_item.garantido_pago,
//     pedido_item.sequencia, COUNT(pedido_item.id_produto) quantidade, pedido_item.situacao, situacao.nome nome_situacao, pedido_item.id_garantido,
//     COALESCE((SELECT ac.id_zoop FROM api_colaboradores ac WHERE ac.id_colaborador = produtos.id_fornecedor),NULL) id_mobile_pay,
//     produtos.consignado
//     from pedido_item
//     INNER JOIN produtos ON (produtos.id = pedido_item.id_produto)
//     INNER JOIN situacao ON (situacao.id = pedido_item.situacao)
//     LEFT OUTER JOIN produtos_foto pf ON (pf.id=produtos.id and pf.sequencia=1)
//     WHERE pedido_item.id_cliente={$id_cliente} AND pedido_item.situacao=6
//     GROUP BY produtos.id, DATE(pedido_item.data_vencimento), pedido_item.cliente, pedido_item.premio, pedido_item.id_garantido, pedido_item.garantido_pago
//     ORDER BY  pedido_item.premio ASC, pedido_item.garantido_pago ASC, pedido_item.id_garantido ASC, pedido_item.data_hora DESC, pedido_item.cliente;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaGradePedidoItemCliente($id_cliente, $id_produto, $data, $id_garantido, $garantido_pago, $cliente, $vencimento)
//{
//    // if ($cliente == null) {
//    //     $nome_cliente = "(cliente is null OR cliente='')";
//    // } else {
//    //     $nome_cliente = "cliente = '{$cliente}'";
//    // }
//    // $query =
//    //         "SELECT pedido_item.tamanho tamanho, count(pedido_item.tamanho) quantidade, pedido_item.premio premio, produtos.tipo_grade tipo_grade, estoque_grade.nome_tamanho nome_tamanho
//    //         FROM pedido_item
//    //         INNER JOIN produtos ON produtos.id = pedido_item.id_produto
//    //         INNER JOIN estoque_grade ON estoque_grade.id_produto = pedido_item.id_produto AND estoque_grade.tamanho = pedido_item.tamanho
//    //         WHERE pedido_item.id_cliente={$id_cliente}
//    //         AND pedido_item.id_produto={$id_produto}
//    //         AND DATE(pedido_item.data_hora)=DATE('{$data}')
//    //         AND {$nome_cliente}
//    //         AND (pedido_item.situacao=6 OR pedido_item.situacao=9 OR pedido_item.situacao=10 OR pedido_item.situacao=11 OR pedido_item.situacao=16) AND pedido_item.id_garantido={$id_garantido} AND pedido_item.garantido_pago={$garantido_pago}
//    //         AND date(pedido_item.data_vencimento) = '{$vencimento}'
//    //         AND estoque_grade.id_responsavel = 1
//    //         GROUP BY DATE(pedido_item.data_vencimento), pedido_item.id_produto, pedido_item.tamanho, pedido_item.situacao, pedido_item.premio, pedido_item.id_garantido, pedido_item.garantido_pago
//    //         ORDER BY pedido_item.tamanho;";
//
//
//    /*"SELECT tamanho, count(tamanho) quantidade, premio FROM pedido_item
//    WHERE id_cliente={$id_cliente} AND id_produto={$id_produto} AND DATE(data_hora)=DATE('{$data}') AND {$nome_cliente}
//    AND (situacao=6 OR situacao=9 OR situacao=10 OR situacao=11 OR situacao=16) AND id_garantido={$id_garantido} AND garantido_pago={$garantido_pago}
//    AND date(data_vencimento) = '{$vencimento}'
//    GROUP BY DATE(data_vencimento), id_produto, tamanho, situacao, premio, id_garantido, garantido_pago ORDER BY tamanho;";
//   */
//
//    // $conexao = Conexao::criarConexao();
//    // $resultado = $conexao->query($query);
//    // $lista = $resultado->fetchAll();
//    // return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)



// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaPedidoItemDetalhesCliente($id_cliente, $id_produto, $data, $cliente, $premio, $id_garantido, $garantido_pago, $vencimento)
//{
//    // if ($cliente == null) {
//    //     $nome_cliente = "(cliente is null OR cliente='')";
//    // } else {
//    //     $nome_cliente = "cliente = '{$cliente}'";
//    // }
//
//    // $query =
//    //         "SELECT
//    //             p.id, pi.premio, pi.sequencia, pi.id_cliente, pi.cliente, pi.separado,
//    //             pi.data_hora, pi.tamanho, pi.preco, pi.situacao, pi.uuid, pi.id_garantido, pi.garantido_pago,
//    //             pi.data_vencimento, p.descricao produto, s.nome nome_situacao,  p.tipo_grade, es.nome_tamanho
//    //         FROM pedido_item pi
//    //         INNER JOIN produtos p ON (p.id = pi.id_produto)
//    //         INNER JOIN situacao s ON (s.id = pi.situacao)
//    //         INNER JOIn estoque_grade es ON es.id_produto = pi.id_produto AND es.tamanho =pi.tamanho
//    //         WHERE
//    //             pi.id_cliente={$id_cliente}
//    //             AND pi.id_produto={$id_produto}
//    //             AND (situacao=6 OR situacao=9 OR situacao=10 OR situacao=11 OR situacao=16)
//    //             AND DATE(pi.data_hora)=DATE('{$data}') AND {$nome_cliente} AND pi.premio={$premio}
//    //             AND pi.id_garantido={$id_garantido}
//    //             AND pi.garantido_pago={$garantido_pago}
//    //             AND DATE(pi.data_vencimento) ='{$vencimento}';";
//
//
//    /*$query = "SELECT p.id, pi.premio, pi.sequencia, pi.id_cliente, pi.cliente, pi.separado,
//    pi.data_hora, pi.tamanho, pi.preco, pi.situacao, pi.uuid, pi.id_garantido, pi.garantido_pago,
//    pi.data_vencimento, p.descricao produto, s.nome nome_situacao FROM pedido_item pi
//    INNER JOIN produtos p ON (p.id = pi.id_produto) INNER JOIN situacao s ON (s.id = pi.situacao)
//    WHERE pi.id_cliente={$id_cliente} AND pi.id_produto={$id_produto} AND
//    (situacao=6 OR situacao=9 OR situacao=10 OR situacao=11 OR situacao=16)
//    AND DATE(pi.data_hora)=DATE('{$data}') AND {$nome_cliente} AND pi.premio={$premio}
//    AND pi.id_garantido={$id_garantido} AND pi.garantido_pago={$garantido_pago} AND DATE(pi.data_vencimento) ='{$vencimento}';";
//    */
//
//    // $conexao = Conexao::criarConexao();
//    // $resultado = $conexao->query($query);
//    // $lista = $resultado->fetchAll();
//    // return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function removePedidoClienteProdutoUnidade($uuid)
// {
//     $query = "DELETE FROM pedido_item WHERE uuid='{$uuid}';";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function excluirProdutoPedidoClienteTotal($id_cliente, $id_produto, $data_hora, $situacao)
//{
//    $query = "DELETE FROM pedido_item WHERE id_cliente={$id_cliente} AND situacao={$situacao}
//    AND DATE(data_hora)=DATE('{$data_hora}') AND id_produto={$id_produto}
//    AND situacao = 6;";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaProdutosPedidoCliente($id_cliente, $id_produto, $data_hora, $situacao)
// {
//     $query = "SELECT pi.id_produto, pi.tamanho, pi.situacao
//     FROM pedido_item pi
//     WHERE pi.id_cliente={$id_cliente} AND pi.id_produto={$id_produto}
//     AND DATE(pi.data_hora)=DATE('{$data_hora}') AND pi.situacao={$situacao};";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

// function sinalizaConfirmacaoPedido($id_cliente)
// {
//     $data = DATE('Y-m-d');
//     $query = "UPDATE pedido set sinalizado=1,data_sinalizado='{$data}' WHERE id_cliente={$id_cliente};";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function naoSinalizaConfirmacaoPedido($id_cliente)
//{
//    $query = "UPDATE pedido set sinalizado=0 WHERE id_cliente={$id_cliente};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)

//function buscaPrecoTabela($id_tabela, $tipo)
//{
//    $query = "SELECT preco FROM tabela_item WHERE id_tabela={$id_tabela} AND id_tipo={$tipo};";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['preco'];
//}

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaFotoProdutoCliente($id)
//{
//    $query = "SELECT COALESCE(caminho,'camera-solid.svg')caminho from produtos_foto WHERE id={$id} LIMIT 1";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['caminho'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaPedidoItemReservadoCliente($id_cliente)
// {
//     $query = "SELECT pi.data_hora, DATE(pi.data_vencimento) data_vencimento, COALESCE(pf.caminho,'camera-solid.svg')caminho,
//     pi.id_produto, produtos.descricao produto, pi.preco, SUM(pi.preco) valor, pi.cliente, pi.id_garantido, pi.garantido_pago,
//     pi.sequencia, COUNT(pi.id_produto) quantidade, pi.situacao, situacao.nome nome_situacao, pi.premio, pi.tamanho
//     from pedido_item pi INNER JOIN produtos ON (produtos.id = pi.id_produto)
//     INNER JOIN situacao ON (situacao.id = pi.situacao)
//     LEFT OUTER JOIN produtos_foto pf ON (pf.id=produtos.id and pf.sequencia=1)
//     WHERE pi.id_cliente={$id_cliente} AND situacao=15
//     GROUP BY date(pi.data_hora), produtos.descricao, pi.situacao , pi.cliente
//     ORDER BY pi.sequencia DESC,produtos.descricao";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaGradePedidoItemReservadoCliente($id_cliente, $id_produto, $data, $cliente)
//{
//    if ($cliente == null) {
//        $nome_cliente = "(cliente is null OR cliente='')";
//    } else {
//        $nome_cliente = "cliente = '{$cliente}'";
//    }
//    $query = "SELECT tamanho, count(tamanho) quantidade FROM pedido_item
//    WHERE id_cliente={$id_cliente} AND id_produto={$id_produto} AND DATE(data_hora)=DATE('{$data}') AND {$nome_cliente}
//    AND situacao=15 GROUP BY DATE(data_vencimento), id_produto, tamanho, situacao ORDER BY tamanho";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $lista = $resultado->fetchAll();
//    return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaPedidoItemDetalhesReservadoCliente($id_cliente, $id_produto, $data, $cliente)
// {
//     if ($cliente == null) {
//         $nome_cliente = "(cliente is null OR cliente='')";
//     } else {
//         $nome_cliente = "cliente = '{$cliente}'";
//     }
//     $query = "SELECT p.id, pi.sequencia, pi.id_cliente, pi.cliente,
//     pi.data_hora, pi.tamanho, pi.preco, pi.situacao, pi.uuid,
//     pi.data_vencimento, p.descricao produto, s.nome nome_situacao
//     FROM pedido_item pi
//     INNER JOIN produtos p ON (p.id = pi.id_produto) INNER JOIN situacao s ON (s.id = pi.situacao)
//     WHERE pi.id_cliente={$id_cliente} AND pi.id_produto={$id_produto} AND situacao=15
//     AND DATE(pi.data_hora)=DATE('{$data}') AND {$nome_cliente}";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaTotalParesCliente($id_cliente)
//{
//    $query = "SELECT COUNT(id_cliente)pares, SUM(preco)valor_total
//    from pedido_item WHERE id_cliente={$id_cliente} AND (situacao=6 OR situacao=9 OR situacao=10 OR situacao=11 OR situacao=16);";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaTotalReservadosCliente($id_cliente)
//{
//    $query = "SELECT COUNT(id_cliente)pares
//    from pedido_item WHERE id_cliente={$id_cliente} AND situacao=15;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


function verificaAcesso($ano, $mes, $id_produto)
{
    $query = "SELECT * FROM paginas_acessadas WHERE ano = {$ano} AND mes = {$mes} AND id_produto={$id_produto};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linhas = $resultado->fetchAll();
    return $linhas;
}

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaPedidosCliente($id_cliente, $filtro)
//{
//    $query = "SELECT f.data_emissao, f.id, COUNT(fi.id_produto) pares,
//    c.razao_social, f.valor_total, f.situacao FROM faturamento f
//    INNER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//    INNER JOIN produtos p ON (p.id=fi.id_produto)
//    INNER JOIN colaboradores c ON (c.id=f.id_cliente)
//    WHERE f.id_cliente = {$id_cliente} {$filtro}
//    GROUP BY f.id ORDER BY f.data_emissao DESC;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linhas = $resultado->fetchAll();
//    return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaPedidosClienteHistorico($id_cliente, $filtro)
//{
//    $query =
//        "SELECT f1.descricao AS status,
//    f1.faturamento as id,
//    f1.data_hora as data,
//    f.status_separacao,
//    f.tabela_preco tipo,
//    IF(f1.descricao = 'Excluiu faturamento.', (SELECT count(id) FROM historico_pedido_item WHERE id_pedido = f1.faturamento),
//    (SELECT count(id_produto)
//        FROM faturamento_item
//      WHERE id_faturamento = f1.faturamento)
//      ) as pares,
//       IF(f1.descricao = 'Tipo Pagamento%', (SELECT count(id) FROM historico_pedido_item WHERE id_pedido = f1.faturamento),
//    (SELECT count(id_produto)
//        FROM faturamento_item
//      WHERE id_faturamento = f1.faturamento)
//      ) as pares,
//    IF(f.situacao is null, '1', f.situacao) as situacao
//    FROM historico_pedido f1
//    LEFT JOIN historico_pedido_item pi ON pi.id_pedido = f1.faturamento
//    LEFT JOIN produtos p ON p.id = pi.id_produto
//    LEFT JOIN faturamento f ON f.id = f1.faturamento
//    WHERE f1.id in (SELECT max(id) from historico_pedido group by faturamento)
//    AND f1.id_cliente = {$id_cliente} {$filtro}
//    group by f1.faturamento ORDER BY f1.faturamento DESC LIMIT 50;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);
//    return $linhas;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaProdutosFaturamentoVendidos($id)
//{
//    $query = "SELECT fi.id_produto,fi.situacao, fi.preco, SUM(fi.preco)valor, COUNT(fi.id_produto)pares, p.descricao referencia, fi.cliente,
//    fi.premio, fi.garantido_pago FROM faturamento_item fi INNER JOIN produtos p ON (p.id=fi.id_produto)
//    WHERE fi.id_faturamento={$id} AND fi.situacao<>8 GROUP BY fi.id_produto, fi.cliente;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $arr = $resultado->fetchAll(PDO::FETCH_ASSOC);
//    return $arr;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaProdutosFaturamentoDevolucoes($id)
// {
//     $query = "SELECT di.id_produto, di.preco, SUM(di.preco)valor, COUNT(di.id_produto)pares, p.descricao referencia FROM devolucao_item di
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.id_faturamento={$id} GROUP BY di.id_produto, di.preco;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);
//     return $linhas;
// }

// function buscaGradeFaturamentoHistorico($id, $id_produto, $cliente)
// {
//     if ($cliente == null) {
//         $nome_cliente = "(cliente is null OR cliente='')";
//     } else {
//         $nome_cliente = "cliente = '{$cliente}'";
//     }

//     $query =
//             "SELECT COUNT(fi.id_produto) pares, fi.tamanho, produtos.tipo_grade, estoque_grade.nome_tamanho
//             FROM faturamento_item fi
//             INNER JOIN produtos ON produtos.id = fi.id_produto
//             INNER JOIN estoque_grade On estoque_grade.id_produto = fi.id_produto AND estoque_grade.tamanho = fi.tamanho
//             WHERE
//                 fi.id_produto={$id_produto}
//                 AND fi.id_faturamento={$id}
//                 AND {$nome_cliente}
//                 AND estoque_grade.id_responsavel = 1
//             GROUP BY fi.tamanho;";

//     /*
//     $query = "SELECT COUNT(fi.id_produto) pares, fi.tamanho FROM faturamento_item fi
//     WHERE fi.id_produto={$id_produto} AND fi.id_faturamento={$id} AND {$nome_cliente} GROUP BY fi.tamanho;";
//     */

//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();
//     return $linhas;
// }

// function buscaGradeFaturamentoCompra($id, $id_produto)
// {
//     $query = "SELECT COUNT(fi.id_produto) pares, fi.tamanho FROM faturamento_item fi
//     WHERE fi.id_produto={$id_produto} AND fi.id_faturamento={$id} GROUP BY fi.tamanho;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();
//     return $linhas;
// }
// function buscaGradeFaturamentoDevolucao($id, $id_produto)
// {
//     $query = "SELECT COUNT(di.id_produto) pares, di.tamanho FROM devolucao_item di
//     WHERE di.id_produto={$id_produto} AND di.id_faturamento={$id} GROUP BY di.tamanho;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linhas = $resultado->fetchAll();
//     return $linhas;
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaValorFrete($id)
//{
//    $query = "SELECT COALESCE(valor_frete,0)as valor_frete FROM faturamento WHERE id={$id};";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch(PDO::FETCH_ASSOC);
//    return $linha['valor_frete'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaFaturamentoCliente($id)
// {
//     $query = "SELECT

//     f.tipo_frete,
//     f.entregue,
//     f.transportadora,
//     f.data_emissao,
//     f.id_cliente,
//     f.nota_fiscal,
//     f.expedido,
//     f.separado,
//     f.conferido,
//     f.situacao,
//     c1.razao_social nome_transportadora,
//     c1.telefone,
//     c1.telefone2,
//     transportadoras.link_rastreio,
//     c2.razao_social
//     FROM faturamento f
//     LEFT OUTER JOIN colaboradores c1 ON (f.transportadora=c1.id)
//     INNER JOIN colaboradores c2 ON (f.id_cliente=c2.id)
//     LEFT OUTER JOIN transportadoras ON transportadoras.id_colaborador = c1.id
//     WHERE f.id ={$id};";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linha = $resultado->fetch();
//     return $linha;
// }

// function removeExpiradosHistorico($id_cliente, $id_produto, $sequencia)
// {
//     $query = "DELETE FROM pares_expirados
//     WHERE  id_cliente =$id_cliente
//     AND  id_produto=$id_produto
//     AND sequencia = $sequencia";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function buscaProdutoPedidoUuid($uuid)
// {
//     $query = "SELECT id_cliente, situacao, id_produto, tamanho, separado, uuid, separado, garantido_pago FROM pedido_item WHERE uuid='{$uuid}';";
//     $conexao = Conexao::criarConexao(PDO::FETCH_ASSOC);
//     $resultado = $conexao->query($query);
//     return $resultado->fetch();
// }

// function buscaPrevisaoParesPedidoCliente($id_produto, $tamanho)
// {
//     $query = "SELECT min(c.data_previsao) data_previsao FROM compras c
//     inner join compras_itens_grade cig on(cig.id_compra=c.id)
//     WHERE cig.id_produto = $id_produto and cig.tamanho = $tamanho
//     and c.situacao=1 and cig.quantidade<> 0 GROUP by cig.tamanho";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linha = $resultado->fetch();
//     return $linha['data_previsao'];
// }

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaValorCreditos(int $id_cliente)
//{
//    $conexao = Conexao::criarConexao();
//    $query = $conexao->prepare("SELECT COALESCE(SUM(lf.valor),0) valor FROM lancamento_financeiro lf WHERE lf.id_colaborador=$id_cliente AND lf.situacao=1 AND lf.tipo='P' AND lf.status_estorno !='R';");
//    $query->execute();
//    $linha = $query->fetch();
//    return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaLancamentosGarantidosCliente(int $id_cliente)
//{
//    $conexao = Conexao::criarConexao();
//    $query = "SELECT lf.id, lf.id_garantido, lf.valor, lf.data_emissao, lf.situacao FROM lancamento_financeiro lf
//    WHERE lf.id_colaborador={$id_cliente} AND lf.id_garantido>0
//    ORDER BY lf.data_emissao DESC LIMIT 10;";
//    $resultado = $conexao->query($query);
//    $lista = $resultado->fetchAll();
//    return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// function buscaProdutosGarantidos($id_garantido)
// {
//     $conexao = Conexao::criarConexao();
//     $query = "SELECT gp.*, p.descricao referencia FROM garantir_pares gp INNER JOIN produtos p ON (p.id=gp.id_produto) WHERE gp.id={$id_garantido};";
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return $lista;
// }

//function buscaNumeroTotalProdutosComprados($idCliente)
//{
//    $query = "SELECT count(fi.id_faturamento) quantidadeTotalPares, month(fi.data_hora) mes, year(fi.data_hora) ano FROM `faturamento_item` fi
//    INNER JOIN faturamento f on (fi.id_faturamento = f.id)
//        where f.situacao = 2 and fi.id_cliente = $idCliente
//        group by month(fi.data_hora), year(fi.data_hora)
//    ORDER BY `fi`.`data_hora`  ASC";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);
//    return $linhas;
//}

// --Commented out by Inspection START (12/08/2022 15:59):
//function buscaQuantidadeFaturamentosClientes($idCliente)
//{
//    $query = "SELECT count(*) total FROM `faturamento`WHERE id_cliente ='{$idCliente}'";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch(PDO::FETCH_ASSOC);
//    if ($linha['total'] > 0) {
//        return true;
//    } else {
//        return false;
//    }
//}
// --Commented out by Inspection STOP (12/08/2022 15:59)


// --Commented out by Inspection START (17/08/2022 17:19):
//function buscaProdutosConferidosFaturamento($id_faturamento, $sequencia)
//{
//    $query = "SELECT fi.id_produto,p.premio_pontos, fi.id_cliente
//    from faturamento_item fi
//    INNER JOIN produtos p ON(fi.id_produto = p.id)
//    where fi.id_faturamento = $id_faturamento and
//    fi.premio = 1 and
//    fi.sequencia = $sequencia";
//    $conexao = Conexao::criarConexao();
//    $stmt = $conexao->query($query);
//    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//    return $result;
//}
// --Commented out by Inspection STOP (17/08/2022 17:19)


// function buscaDetalhesDaGradeCompradaPeloCliente(int $id_faturamento, int $id_produto)
// {
//     $query = "SELECT
//     fi.tamanho,
//     fi.id_produto,
//     fi.preco,
//     (SELECT produtos.descricao FROM produtos WHERE produtos.id = fi.id_produto ) referencia,
//     fi.cliente,
//     fi.premio,
//     fi.garantido_pago,
//     COALESCE((
//       SELECT
//         med_venda_produtos_consumidor_final.valor
//       FROM
//         med_venda_produtos_consumidor_final
//       WHERE
//           med_venda_produtos_consumidor_final.uuid_pedido_item = fi.uuid LIMIT 1
//     ), 0 ) preco_meustockdigital
//   FROM
//     faturamento_item fi
//   WHERE
//     fi.id_faturamento = $id_faturamento
//     AND fi.id_produto = $id_produto";
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->query($query);
//     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//     return $result;
// }

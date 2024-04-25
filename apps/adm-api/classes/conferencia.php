<?php /*

use MobileStock\database\Conexao;

require_once 'conexao.php';

// --Commented out by Inspection START (12/08/2022 14:41):
//function buscaLancamentosRetirada($id_faturamento)
//{
//    $query = "SELECT lf.id from lancamento_financeiro lf
//    where lf.numero_documento={$id_faturamento} AND lf.documento=9;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// function atualizaImpressaoEtiquetaRetirada($faturamento, $usuario)
// {
//     date_default_timezone_set('America/Sao_Paulo');
//     $data = DATE('Y-m-d H:i:s');
//     $query = "UPDATE faturamento set retirada_apos_pagamento = $usuario, data_emitiu_etiqueta_retirada= '{$data}' 
//     WHERE id={$faturamento};";
//     $conexao = Conexao::criarConexao();
//     return $conexao->exec($query);
// }

// function listaConferencia($filtro)
// {
//     $query = "SELECT f.*, c.razao_social cliente, u.nome vendedor, c2.razao_social transportadora, s.nome situacao from faturamento f
//     INNER JOIN colaboradores c ON (c.id = f.id_cliente)
//     INNER JOIN usuarios u ON (u.id = f.vendedor)
//     INNER JOIN situacao_pedido s ON (s.id=f.situacao)
//     LEFT OUTER JOIN colaboradores c2 ON (c2.id = f.transportadora)
//     LEFT OUTER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//     LEFT OUTER JOIN produtos p ON (fi.id_produto=p.id)
//     WHERE f.conferido=0 AND f.separado=1 {$filtro} 
//     GROUP BY f.id ORDER BY f.data_emissao DESC LIMIT 50;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetchAll();
// }

// --Commented out by Inspection START (12/08/2022 14:41):
//function buscaListaConferencia($filtros)
//{
//    $query = "SELECT  f.*, c.id as id_colab ,c.razao_social cliente, u.nome vendedor,
//                        c2.razao_social transportadora, s.nome situacao,
//                        u.nome as separador, (SELECT COUNT(*) FROM faturamento WHERE faturamento.id_cliente= id_colab) as tCompras
//            from faturamento f
//            INNER JOIN colaboradores c ON (c.id = f.id_cliente)
//            INNER JOIN situacao_pedido s ON (s.id=f.situacao)
//            LEFT OUTER JOIN colaboradores c2 ON (c2.id = f.transportadora)
//            LEFT OUTER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
//            INNER JOIN usuarios u ON (u.id = f.id_separador)
//            LEFT OUTER JOIN produtos p ON (fi.id_produto=p.id)
//            WHERE f.conferido=0 AND f.separado=1 AND fi.situacao = 6";
//
//    if (isset($filtros['faturamento']) && !empty($filtros['faturamento'])) {
//        $query .= " AND f.id = {$filtros['faturamento']}";
//    };
//
//    if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
//        $query .= " AND f.id_cliente = {$filtros['cliente']}";
//    };
//
//    if (isset($filtros['transportadora']) && !empty($filtros['transportadora'])) {
//        $query .= " AND f.transportadora = {$filtros['transportadora']}";
//    };
//
//    if (isset($filtros['referencia']) && !empty($filtros['referencia'])) {
//        $query .= " AND LOWER(p.descricao) LIKE LOWER('%" . $filtros['referencia'] . "%')";
//    };
//
//    if ($filtros['data_inicial'] && $filtros['data_fim'] && !empty($filtros['data_inicial']) && !empty($filtros['data_fim'])) {
//        $query .= " AND f.data_separacao BETWEEN '{$filtros['data_inicial']} 00:00:00' and '{$filtros['data_fim']} 23:59:00'";
//    };
//
//    $query .= " GROUP BY f.id ORDER BY f.data_emissao DESC LIMIT 50;";
//
//    $listaConferencia = [];
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    if ($listaConferencia = $resultado->fetchAll(PDO::FETCH_ASSOC)) {
//        foreach ($listaConferencia as $key => $value) {
//        }
//    }
//
//    return $listaConferencia;
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


function listaConferenciaConferidos($filtro)
{
    $query = "SELECT f.id, f.data_conferencia, c.razao_social cliente,
    c2.razao_social transportadora from faturamento f
    INNER JOIN colaboradores c ON (c.id = f.id_cliente)
    LEFT OUTER JOIN colaboradores c2 ON (c2.id = f.transportadora)
    LEFT OUTER JOIN faturamento_item fi ON (fi.id_faturamento=f.id)
    LEFT OUTER JOIN produtos p ON (fi.id_produto=p.id)
    WHERE f.conferido=1 {$filtro}
    GROUP BY f.id ORDER BY f.data_conferencia DESC LIMIT 50;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

// function buscaParesConferenciaExclusaoCliente($id_cliente)
// {
//     $query = "SELECT pie.*, p.descricao referencia FROM pedido_item_exclusao pie
//     INNER JOIN produtos p ON (p.id = pie.id_produto)
//     WHERE pie.id_cliente={$id_cliente};";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetchAll();
// }

// function buscaConferenciaFaturamento($id)
// {
//     $query = "SELECT
//             faturamento.id,
//             faturamento.tipo_frete id_tipo_frete,
//             faturamento.data_conferencia,
//             faturamento.pares,
//             faturamento.situacao,
//             faturamento.entregue,
//             faturamento.observacao,
//             faturamento.observacao2,
//             COALESCE(faturamento.id_responsavel_estoque, 1) id_responsavel_estoque,
//             cliente.id id_cliente,
//             cliente.razao_social cliente,
//             COALESCE(freteiro.nome,'') freteiro,
//             COALESCE(vendedor.nome, '') nome_vendedor,
//             transportadora.razao_social nome_transportadora,
//             conferidor.nome conferidor,
//             tipo_frete.nome tipo_frete,
//             (
//                 SELECT COUNT(faturamento_item.id_faturamento)
//                 FROM faturamento_item
//                 WHERE faturamento_item.situacao = 6 AND faturamento_item.id_faturamento = faturamento.id and conferido = 1
//             ) conferidos,
//             CASE
//                 WHEN(faturamento.tipo_frete = 1) THEN faturamento.freteiro
//                 WHEN(faturamento.tipo_frete = 2) THEN faturamento.transportadora
//                 WHEN(faturamento.tipo_frete = 3) THEN 0
//                 WHEN(faturamento.tipo_frete = 4) THEN 12
//                 WHEN(faturamento.tipo_frete = 5) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
//                 WHEN(faturamento.tipo_frete = 6) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
//                 WHEN(faturamento.tipo_frete = 7) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
//                 WHEN(faturamento.tipo_frete = 8) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
//             END transporte
//         FROM faturamento
//         INNER JOIN colaboradores cliente ON cliente.id = faturamento.id_cliente
//         LEFT OUTER JOIN usuarios vendedor ON vendedor.id = faturamento.vendedor
//         LEFT OUTER JOIN usuarios conferidor ON conferidor.id = faturamento.id_conferidor
//         LEFT OUTER JOIN colaboradores transportadora ON transportadora.id = faturamento.transportadora
//         LEFT OUTER JOIN freteiro freteiro ON faturamento.freteiro=freteiro.id
//         LEFT OUTER JOIN tipo_frete ON faturamento.tipo_frete=tipo_frete.id
//         WHERE faturamento.id = $id;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetch(PDO::FETCH_ASSOC);
// }

// function buscaParesConferenciaFaturadosCliente($id_faturamento)
// {

//     $procedure = "CALL med_gera_link_vazio_conferencia($id_faturamento)";
//     Conexao::criarConexao()->prepare($procedure);

//     $query = "SELECT fi.id_produto,
//     fi.id_faturamento,
//     fi.sequencia, 
//     (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.tamanho = fi.tamanho AND estoque_grade.id_produto = fi.id_produto LIMIT 1) nome_tamanho,
//     fi.tamanho, 
//     fi.conferido,
//     p.localizacao,
//     CONCAT(p.id, ' - ', p.descricao, ' ', COALESCE(p.cores, '')) referencia, 
//     COALESCE((SELECT fi.uuid FROM med_venda_produtos_consumidor_final WHERE med_venda_produtos_consumidor_final.uuid_pedido_item = fi.uuid),NULL) med,
//     COALESCE((SELECT fi.uuid FROM pedido_item_meu_look WHERE pedido_item_meu_look.uuid = fi.uuid),NULL) ml,
//     u.nome separador,
//      COALESCE(
// 		(SELECT DISTINCT med_configuracao_vendedor.nome from med_configuracao_vendedor where med_configuracao_vendedor.id_cliente = u1.id_colaborador LIMIT 1),
//         u1.nome
// 	) vendedor,
//     CASE
//     	( SELECT 1 FROM pedido_item_meu_look WHERE pedido_item_meu_look.uuid = fi.uuid )
//         WHEN 1 THEN ( SELECT colaboradores.razao_social FROM pedido_item_meu_look INNER JOIN colaboradores ON colaboradores.id = pedido_item_meu_look.id_cliente WHERE pedido_item_meu_look.uuid = fi.uuid )
//         ELSE COALESCE(fi.cliente, '')
//     END cliente,
//     p.localizacao,
//     fi.uuid
//     FROM faturamento_item fi
//     INNER JOIN faturamento f ON (fi.id_faturamento =f.id)
//     INNER JOIN produtos p ON (p.id = fi.id_produto)
//     LEFT OUTER JOIN usuarios u ON (u.id = f.id_separador)
//     LEFT OUTER JOIN usuarios u1 ON (u1.id = fi.id_vendedor)
// 	WHERE fi.situacao = 6 AND fi.id_faturamento in (SELECT id from faturamento where id = $id_faturamento)
//     ORDER BY uuid;";
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->query($query);
//     $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     $conexao = Conexao::criarConexao();

//     foreach ($resultado as $key => $item) :
//         $conjugado = [];
//         if (sizeof($conjugado = explode('/', $item['nome_tamanho'])) < 2) :
//             $conjugado = explode('-', $item['nome_tamanho']);
//         endif;
//         if ($item['nome_tamanho'] === 'Unico' && sizeof($conjugado) === 1) {
//             $resultado[$key]['tamanho_real'] = $resultado[$key]['tamanho'];
//             $resultado[$key]['tamanho'] = 'Unico';
//         } else if (sizeof($conjugado) > 1 && is_numeric($conjugado[0])) {
//             $resultado[$key]['tamanho_real'] = $resultado[$key]['tamanho'];
//             $resultado[$key]['tamanho'] = $conjugado[0] . '/' . $conjugado[1];
//         } else if ($resultado[$key]["nome_tamanho"]) {
//             $resultado[$key]['tamanho_real'] = $resultado[$key]['tamanho'];
//             $resultado[$key]['tamanho'] = $resultado[$key]['nome_tamanho'];
//         } else {
//             $resultado[$key]['tamanho_real'] = $resultado[$key]['tamanho'];
//         }
//         if ($item['med']) :
//             $prepareMed = $conexao
//                 ->prepare("SELECT 
//                 DISTINCT group_concat(
//                 '[' , 
//                 JSON_OBJECT(
//                     'med', 1,
//                     'informacoes_consumidor_final', (
//                     SELECT 
//                     DISTINCT 
//                     GROUP_CONCAT(COALESCE(med_consumidor_final.id_cliente, ''), ',', COALESCE( med_consumidor_final.nome, ''), ',', COALESCE(med_consumidor_final.telefone, '')) 
//                     from med_consumidor_final 
//                     where med_consumidor_final.id = med_venda_produtos_consumidor_final.id_consumidor_final),
//                     'informacoes_vendedor', (
//                     SELECT DISTINCT group_concat( 
//                             '[', JSON_OBJECT(
//                                 'nome', med_configuracao_vendedor.nome,
//                                     'link', (
//                                     SELECT DISTINCT group_concat( '{$_ENV['URL_MEUESTOQUEDIGITAL']}lk/', med_link_consumidor_final.cod_uuid LIMIT 1) 
//                                     from med_link_consumidor_final where 
//                                     med_link_consumidor_final.id_cliente = med_venda_produtos_consumidor_final.id_cliente 
//                                     order by data desc 
//                                     LIMIT 1)
//                                 ) ,']'
//                         ) from med_configuracao_vendedor where med_configuracao_vendedor.id_cliente = med_venda_produtos_consumidor_final.id_cliente)
//                     ), 
//                 ']'
//                 ) med
//                 FROM med_venda_produtos_consumidor_final 
//                 WHERE med_venda_produtos_consumidor_final.uuid_pedido_item = :uuid");
//             $prepareMed->bindParam(":uuid", $item['med']);
//             $prepareMed->execute();
//             $itemMed = $prepareMed->fetch(PDO::FETCH_ASSOC);
//             if ($itemMed) :
//                 $resultado[$key]['med'] = json_decode($itemMed['med'], true)[0];
//                 $resultado[$key]['med']['informacoes_vendedor'] = json_decode(json_decode($itemMed['med'], true)[0]['informacoes_vendedor'], true)[0];
//             endif;
//         endif;
//         if ($item['ml']) :
//             $prepareMed = $conexao
//                 ->prepare(
//                     "SELECT DISTINCT group_concat(
//                         '[' ,JSON_OBJECT(
//                             'med', 1,
//                             'informacoes_consumidor_final', (
//                                 SELECT DISTINCT group_concat(
//                                     COALESCE(colaboradores.id, ''),',', COALESCE(colaboradores.razao_social, ''), ',', COALESCE(colaboradores.telefone, '')) 
//                                 FROM colaboradores 
//                                 WHERE colaboradores.id = pedido_item_meu_look.id_cliente
//                             ),
//                             'informacoes_vendedor', (
//                             SELECT DISTINCT group_concat( 
//                                     '[', JSON_OBJECT(
//                                         'nome', colaboradores.razao_social,
//                                         'link', CONCAT( '{$_ENV['URL_MEULOOK']}produto/', 
//                                                 COALESCE((SELECT 
//                                                     publicacoes_produtos.id 
//                                                 FROM 
//                                                     publicacoes_produtos
//                                                 WHERE 
//                                                     publicacoes_produtos.id_produto = pedido_item_meu_look.id_produto AND 
//                                                     publicacoes_produtos.id_publicacao = pedido_item_meu_look.id_publicacao
//                                                 LIMIT 1),0)
//                                                 ,'?t=',COALESCE(pedido_item_meu_look.tamanho,0),'&w=',pedido_item_meu_look.uuid)
//                                         ) ,']'
//                                 ) from colaboradores where colaboradores.id = pedido_item_meu_look.id_colaborador_criador_publicacao)
//                             ), 
//                         ']'
//                         ) ml
//                     FROM pedido_item_meu_look 
//                     WHERE pedido_item_meu_look.uuid = :uuid"
//                 );
//             $prepareMed->bindParam(":uuid", $item['ml']);
//             $prepareMed->execute();
//             $itemMed = $prepareMed->fetch(PDO::FETCH_ASSOC);
//             if ($itemMed) :
//                 $resultado[$key]['med'] = json_decode($itemMed['ml'], true)[0];
//                 $resultado[$key]['med']['informacoes_vendedor'] = json_decode(json_decode($itemMed['ml'], true)[0]['informacoes_vendedor'], true)[0];
//             //unset($resultado[$key]['ml']);
//             endif;
//         endif;
//         if ($resultado[$key]["cliente"] !== '' && ($itemMed || $itemMed !== '')) :
//             $resultado[$key]["cliente"] = sanitizeString($resultado[$key]["cliente"]);
//             $resultado[$key]["referencia"] = sanitizeString($resultado[$key]["referencia"]);
//             $resultado[$key]["separador"] = sanitizeString($resultado[$key]["separador"]);
//             if ($resultado[$key]["med"] || $resultado[$key]["ml"]) :
//                 $dadosCliente = explode(',', $resultado[$key]["med"]["informacoes_consumidor_final"]);
//                 $dadosCliente[1] = sanitizeString($dadosCliente[1]);
//                 $dadosCliente[1] = explode(' ', $dadosCliente[1])[0];
//                 $dadosCliente[2] = formataTelefone($dadosCliente[2]);
//                 if (verificaOrigem($resultado[$key]["med"]["informacoes_vendedor"]["link"])) {
//                     $resultado[$key]["med"]["informacoes_consumidor_final"] = $dadosCliente[1] . ' ' . $dadosCliente[2];
//                 } else {
//                     if (strlen($dadosCliente[1]) > 19) {
//                         $nomeCliente = substr($dadosCliente[1], 0, 18);
//                     } else {
//                         $nomeCliente = $dadosCliente[1];
//                     }
//                     $resultado[$key]["med"]["informacoes_consumidor_final"] = $dadosCliente[0] . '-' . $nomeCliente . $dadosCliente[2];
//                 }
//                 $resultado[$key]["med"]["informacoes_vendedor"]["nome"] = sanitizeString($resultado[$key]["med"]["informacoes_vendedor"]["nome"]);
//             endif;
//         endif;

//     endforeach;

//     return $resultado;
// }

// --Commented out by Inspection START (12/08/2022 14:41):
//function buscaProdutosFaturadosSemConferencia($id_faturamento)
//{
//    $query = "SELECT fi.*,p.descricao referencia FROM faturamento_item fi
//    INNER JOIN produtos p ON (p.id=fi.id_produto)
//    WHERE fi.id_faturamento={$id_faturamento} AND fi.conferido=0;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// function buscaProdutosExclusaoSemConferencia($id_cliente)
// {
//     $query = "SELECT pie.*, p.descricao referencia FROM pedido_item_exclusao pie
//     INNER JOIN produtos p ON (p.id = pie.id_produto)
//     WHERE pie.id_cliente={$id_cliente} AND pie.conferido=0;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetchAll();
// }

// function buscaTotalParesConferenciaExcluidosCliente($id_cliente)
// {
//     $query = "SELECT COUNT(pie.id_cliente) pares FROM pedido_item_exclusao pie
//     WHERE pie.id_cliente={$id_cliente};";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linha = $resultado->fetch();
//     return $linha['pares'];
// }

// --Commented out by Inspection START (12/08/2022 14:41):
//function buscaTotalProdutosFaturadosConferidos($id_faturamento)
//{
//    $query = "SELECT COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//    WHERE fi.id_faturamento={$id_faturamento} AND fi.conferido=1;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function buscaTotalParesConferenciaFaturadosCliente($id_faturamento)
//{
//    $query = "SELECT COUNT(fi.id_faturamento) pares FROM faturamento_item fi
//    WHERE fi.id_faturamento in (SELECT id from faturamento where id = {$id_faturamento});";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['pares'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function atualizaConferidoFaturamento($id, $sequencia, $usuario)
//{
//    date_default_timezone_set('America/Sao_Paulo');
//    $data = DATE('Y-m-d H:i:s');
//    $query = "UPDATE faturamento_item set conferido=1, data_conferencia = '{$data}', id_conferidor = {$usuario}
//    WHERE id_faturamento={$id} AND sequencia={$sequencia};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


//function atualizaFaturamentoConferido($faturamento, $conferente, $expedido, $id_expedidor)
//{
//    date_default_timezone_set('America/Sao_Paulo');
//    $data = DATE('Y-m-d H:i:s');
//    $query = "UPDATE faturamento set id_conferidor={$conferente}, data_conferencia = '{$data}', conferido=1, id_expedidor={$id_expedidor}, expedido={$expedido},";
//    if ($expedido == 1) {
//        $query .= "data_expedicao = '{$data}'";
//    } else {
//        $query .= "data_expedicao = NULL";
//    }
//    $query .= " WHERE id in (SELECT id from faturamento where id = {$faturamento} )";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}

// --Commented out by Inspection START (12/08/2022 14:41):
//function limpaConferenciaFaturamentoItem($faturamento)
//{
//    $query = "UPDATE faturamento_item set conferido=0, data_conferencia = NULL, id_conferidor = 0
//    WHERE id_faturamento={$faturamento};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function limpaConferenciaFaturamento($faturamento)
//{
//    $query = "UPDATE faturamento set data_conferencia = NULL, id_conferidor = 0 , conferido=0, data_expedicao = NULL, id_expedidor = 0, expedido=0,
//    data_entrega=NULL,id_entregador=0,entregue=0
//    WHERE id={$faturamento};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function atualizaVolumesExpedicaoFaturamento($id_faturamento, $volumes)
//{
//    $query = "UPDATE faturamento set volumes={$volumes} WHERE id={$id_faturamento};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function atualizaFaturamentoConferidoItens($id_faturamento)
//{
//    $query = "UPDATE faturamento_item set conferido=1 WHERE id_faturamento in (SELECT id from faturamento where id = {$id_faturamento}";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// function buscaCodigoBarrasTamanho($id_produto, $tamanho)
// {
//     $query = "SELECT CONCAT(pgcb.cod_barras,'_','NA')cod_barras FROM produtos_grade_cod_barras pgcb WHERE pgcb.id_produto={$id_produto} AND pgcb.tamanho={$tamanho};";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

// --Commented out by Inspection START (12/08/2022 14:41):
//function buscaIdClienteNaConferencia($id)
//{
//    $query = "SELECT id_cliente FROM faturamento WHERE id={$id}; ";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $linha = $resultado->fetch();
//    return $linha['id_cliente'];
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function sanitizeString($str)
//{
//    if (!$str) return null;
//    $str = preg_replace('/[áàãâä]/ui', 'a', $str);
//    $str = preg_replace('/[éèêë]/ui', 'e', $str);
//    $str = preg_replace('/[íìîï]/ui', 'i', $str);
//    $str = preg_replace('/[óòõôö]/ui', 'o', $str);
//    $str = preg_replace('/[úùûü]/ui', 'u', $str);
//    $str = preg_replace('/[ç]/ui', 'c', $str);
//    $str = preg_replace('/[^a-zA-Z0-9\,\-\s]/i', '_', $str);
//    return $str;
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function formataTelefone($phone)
//{
//    $formatedPhone = preg_replace('/[^0-9]/', '', $phone);
//    $matches = [];
//    preg_match('/^([0-9]{2})([0-9]{4,5})([0-9]{4})$/', $formatedPhone, $matches);
//    if ($matches) {
//        return '(' . $matches[1] . ') ' . $matches[2] . '-' . $matches[3];
//    }
//
//    return $phone; // return number without format
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)


// --Commented out by Inspection START (12/08/2022 14:41):
//function verificaOrigem($url)
//{
//    $urlArr = explode('/', $url);
//    $isMed = false;
//    for ($i = 0; $i < sizeOf($urlArr); $i++) {
//        if (!$isMed && $urlArr[$i] === "meuestoquedigital") {
//            $isMed = true;
//        } elseif (!$isMed) {
//            $isMed = false;
//        }
//    }
//    return $isMed;
//}
// --Commented out by Inspection STOP (12/08/2022 14:41)

//function atualizaProdutosFaturamentoConferido($id_faturamento, $usuario, $expedidor)
//{
//    date_default_timezone_set('America/Sao_Paulo');
//    $data = DATE('Y-m-d H:i:s');
//    $query = "UPDATE faturamento_item set 
//    conferido=1, 
//    data_conferencia = '{$data}', 
//    id_conferidor = {$usuario} 
//    WHERE id_faturamento in (SELECT id from faturamento where id = {$id_faturamento} );";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
*/
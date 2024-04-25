<?php
require_once 'conexao.php';
require_once __DIR__ . '/../vendor/autoload.php';


require_once __DIR__ . '/../vendor/autoload.php';
//function guardaLancamento($lancamento, $usuario)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = DATE('Y-m-d H:i:s');
//  $query = "UPDATE lancamento_financeiro SET guardou = {$usuario}, data_guardou='{$data}',
//  id_usuario_edicao={$usuario} WHERE id={$lancamento} AND guardou = 0;";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

// --Commented out by Inspection START (12/08/2022 15:58):
//function listarLancamentos($filtro)
//{
//  $query = "SELECT lf.*, c.razao_social, s.nome nome_situacao from lancamento_financeiro lf
//    LEFT OUTER JOIN colaboradores c on (c.id=lf.id_colaborador)
//    LEFT OUTER JOIN situacao_lancamento s on (s.id=lf.situacao)
//    WHERE 1=1 {$filtro}
//    ORDER BY lf.id DESC LIMIT 100";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaContasAhPagarEmAberto($filtro)
//{
//  $query = "SELECT lf.*,colaboradores.razao_social,
//  situacao_lancamento.nome nome_situacao from lancamento_financeiro lf
//  INNER JOIN colaboradores on (colaboradores.id=lf.id_colaborador)
//  INNER JOIN situacao_lancamento on (situacao_lancamento.id=lf.situacao)
//  WHERE 1=1 {$filtro} AND lf.tipo='P' and lf.situacao=1
//  ORDER BY lf.data_vencimento DESC, lf.situacao ASC";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function listarLancamentosPagina($filtro, $pagina, $itens, $limite = 200)
// {
//   if (!$filtro) {
//     $sqlLimite = "LIMIT $limite";
//   } else {
//     $sqlLimite = "LIMIT 1000";
//   }
//   $query = "SELECT lancamento_financeiro.*,
//                 colaboradores.razao_social,
//                 IF(lancamento_financeiro.situacao = 2,'Pago','Em Aberto') nome_situacao,
//                 '' pagamento,
//                 '' cod_transacao,
//                 DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y') data_pagamento_faturamento
//                 from lancamento_financeiro 
//                 INNER JOIN colaboradores on (colaboradores.id=lancamento_financeiro.id_colaborador)
//             WHERE 1=1 {$filtro}
//             ORDER BY lancamento_financeiro.id DESC $sqlLimite";
//   //{$pagina},{$itens}
//   $conexao = Conexao::criarConexao();

//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

/*function TotalLancamentosCaixaDocumento($filtro, $documento)
{
  $query = "SELECT count(DISTINCT c.id) total_cliente, count(DISTINCT numero_documento) total_pedido
    FROM acertos_documentos ad
    INNER JOIN acertos a ON (a.id=ad.id_acerto)
    INNER JOIN colaboradores c ON (c.id=a.id_colaborador)
    INNER JOIN usuarios u ON (u.id=ad.usuario)
    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=ad.conta_bancaria)
    WHERE 1=1 {$filtro} AND ad.documento = {$documento} AND ad.caixa=1 AND c.tipo='C'
    ORDER BY ad.data_pagamento";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetch();
  return $lista;
}*/


// --Commented out by Inspection START (12/08/2022 15:58):
//function TotalLancamentosCaixaDocumento($filtro, $documento)
//{
//  $query = "SELECT count(DISTINCT c.id) total_cliente, count(DISTINCT lf.pedido_origem) total_pedido
//    FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    INNER JOIN usuarios u ON (u.id=lf.id_usuario)
//    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=lf.conta_deposito)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.bloqueado=0 AND lf.situacao = 2 AND c.tipo='C'
//    ORDER BY lf.data_pagamento";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarDepositoLancamentosCaixaDocumento($filtro, $documento)
//{
//  $query = "SELECT MAX(lf.data_pagamento)data_pagamento, COUNT(lf.pedido_origem) pedidos, SUM(lf.valor_pago)valor, cb.nome conta_bancaria
//    FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    INNER JOIN usuarios u ON (u.id=lf.id_usuario)
//    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=lf.conta_deposito)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.bloqueado=0 AND c.tipo='C' AND lf.situacao = 2
//    GROUP BY cb.nome";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)



/*function listarLancamentosCaixaDocumento($filtro, $documento)
{
  $query = "SELECT ad.*, a.numero_documento, c.razao_social cliente, u.nome nome_usuario, cb.nome conta_bancaria
    FROM acertos_documentos ad
    INNER JOIN acertos a ON (a.id=ad.id_acerto)
    INNER JOIN colaboradores c ON (c.id=a.id_colaborador)
    INNER JOIN usuarios u ON (u.id=ad.usuario)
    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=ad.conta_bancaria)
    WHERE 1=1 {$filtro} AND ad.documento = {$documento} AND ad.caixa=1 AND c.tipo='C'
    ORDER BY ad.data_pagamento";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}*/

// --Commented out by Inspection START (12/08/2022 15:58):
//function listarLancamentosCaixaDocumento($filtro, $documento)
//{
//  $query = "SELECT lf.*, lf.numero_documento, c.razao_social cliente, u.nome nome_usuario, cb.nome conta_bancaria
//    FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    INNER JOIN usuarios u ON (u.id=lf.id_usuario)
//    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=lf.conta_deposito)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.bloqueado=0 AND lf.situacao = 2 AND c.tipo='C'
//    ORDER BY lf.data_pagamento";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarTotalClienteCaixaDocumentoDinheiro($filtro, $documento)
//{
//  $query = "SELECT COUNT(DISTINCT c.id) total_cliente, count(DISTINCT pedido_origem
//  ) total_pedido
//    FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    INNER JOIN usuarios u ON (u.id=lf.id_usuario)
//    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=lf.conta_bancaria)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.situacao=2 AND lf.bloqueado=0 AND c.id = lf.id_colaborador
//  ";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch(PDO::FETCH_ASSOC);
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

// --Commented out by Inspection START (12/08/2022 15:58):
//function listarLancamentosCaixaDocumentoDinheiro($filtro, $documento)
//{
//  $query = "SELECT lf.*, lf.numero_documento, c.razao_social cliente, u.nome nome_usuario, cb.nome as conta_bancaria
//    FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    INNER JOIN usuarios u ON (u.id=lf.id_usuario)
//    LEFT OUTER JOIN contas_bancarias cb ON (cb.id=lf.conta_deposito)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.bloqueado=0 AND lf.situacao = 2
//    ORDER BY lf.data_pagamento";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarTotalLancamentosCaixaDocumento($filtro, $documento)
//{
//  $query = "SELECT SUM(lf.valor_pago)total, c.razao_social cliente FROM lancamento_financeiro lf
//    LEFT OUTER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.bloqueado=0 AND c.tipo='C' AND lf.situacao = 2
//    ORDER BY lf.data_pagamento";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['total'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarLancamentosGuardar($filtro, $documento)
//{
//  $query = "SELECT lf.*, c.razao_social cliente, r.nome representante, COALESCE(fr.nome,'') freteiro, s.nome nome_situacao
//    FROM lancamento_financeiro lf
//    LEFT OUTER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    LEFT OUTER JOIN representantes r ON (r.id_colaborador=c.id)
//    LEFT OUTER JOIN freteiro fr ON (fr.id=lf.freteiro)
//    INNER JOIN situacao_lancamento s ON (s.id=lf.situacao)
//    WHERE (1=1 {$filtro} AND lf.documento = {$documento}) OR (1=1 AND lf.documento = {$documento} AND lf.guardou=0) AND lf.tipo='R'
//    ORDER BY lf.id";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarLancamentosGuardarFreteiro($filtro, $documento)
//{
//  $query = "SELECT lf.*, c.razao_social cliente,r.nome representante, COALESCE(fr.nome,'') freteiro FROM lancamento_financeiro lf
//    LEFT OUTER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    LEFT OUTER JOIN representantes r ON (r.id_colaborador=c.id)
//    LEFT OUTER JOIN freteiro fr ON (fr.id=lf.freteiro)
//    WHERE lf.situacao = 1 and lf.documento={$documento}
//    ORDER BY lf.id";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarTotalLancamentosGuardar($filtro, $documento)
//{
//  $query = "SELECT SUM(lf.valor) total, c.razao_social cliente FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//    WHERE 1=1 {$filtro} AND lf.documento = {$documento}
//    ORDER BY lf.id";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['total'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarTotalLancamentosGuardarFreteiro($filtro, $documento)
//{
//  $query = "SELECT SUM(lf.valor) total, c.razao_social cliente FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//    WHERE lf.situacao=1 AND lf.documento = {$documento}
//    ORDER BY lf.id";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['total'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listarTotalNotinhasGuardar($filtro, $documento, $tipo)
//{
//  $query = "SELECT SUM(lf.valor) total FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//    WHERE ((1=1 {$filtro} AND lf.documento = {$documento}) OR (1=1 AND lf.documento = {$documento} AND lf.guardou=0)) AND lf.tipo='{$tipo}'
//    ORDER BY lf.id";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['total'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalCaixaDocumento($filtro, $documento)
//{
//  $query = "SELECT SUM(ad.valor) valor FROM acertos_documentos ad
//  WHERE 1=1 {$filtro} AND ad.documento = {$documento};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalCaixaLancamentos($filtro, $documento)
//{
//  $query = "SELECT SUM(lf.valor_pago) valor FROM lancamento_financeiro lf
//  WHERE 1=1 {$filtro} AND lf.documento = {$documento} AND lf.situacao=2;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


/*function buscaTotalCaixaDocumentoDinheiro($filtro, $tipo, $documento)
{
  $query = "SELECT SUM(ad.valor) valor, c.razao_social cliente FROM acertos_documentos ad
  INNER JOIN acertos a ON (a.id=ad.id_acerto)
  INNER JOIN colaboradores c ON (c.id = a.id_colaborador)
  WHERE 1=1 {$filtro}
  AND ad.documento = {$documento} AND ad.tipo = '{$tipo}' AND ad.caixa=1;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['valor'];
}*/

// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalCaixaDocumentoDinheiro($filtro, $tipo, $documento)
//{
//  $query = "SELECT SUM(lf.valor_pago) valor, c.razao_social cliente FROM lancamento_financeiro lf
//  INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//  WHERE 1=1 {$filtro}
//  AND lf.documento = {$documento} AND lf.tipo = '{$tipo}' AND lf.bloqueado=0 AND lf.situacao=2;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch(PDO::FETCH_ASSOC);
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalCaixa($filtro)
//{
//  $query = "SELECT SUM(lf.valor_pago) valor FROM lancamento_financeiro lf
//  WHERE 1=1 {$filtro};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


/*function buscaCaixaSaldoAnterior($data, $tipo)
{
  $query = "SELECT SUM(ad.valor) valor FROM acertos_documentos ad
  WHERE DATE(ad.data_pagamento)<'{$data}' AND ad.documento=1 and ad.tipo='{$tipo}' AND ad.caixa=1;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['valor'];
}*/

// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaCaixaSaldoAnterior($data, $tipo)
//{
//  $query = "SELECT SUM(lf.valor_pago) valor FROM lancamento_financeiro lf
//  WHERE DATE(lf.data_pagamento)<'{$data}' AND lf.documento=1 and lf.tipo='{$tipo}' AND lf.bloqueado=0 AND lf.situacao = 2;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaNotinhasEmAbertoDoCliente($id)
//{
//  $query = "SELECT COALESCE(COUNT(lf.id_colaborador),0) notinhas FROM lancamento_financeiro lf
//  WHERE lf.id_colaborador={$id} AND lf.situacao=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['notinhas'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


//function inserirLancamentoAtendimento($tipo, $pedido_origem, $id_colaborador, $valor, $usuario, $observacao, $status_estorno)
//{
//  $id_lancamento = buscaUltimoLancamento();
//  $id_lancamento++;
//  $conexao = \MobileStock\database\Conexao::criarConexao();
//  $lancamento = new LancamentoAlias(
//    $tipo,
//    1,
//    'AT',
//    intVal($id_colaborador),
//    null,
//    $valor,
//    intVal($usuario),
//    0
//  );
//  $lancamento->pedido_origem = intVal($pedido_origem);
//  $lancamento->numero_documento = intVal($pedido_origem);
//  $lancamento->observacao = $observacao;
//  $lancamento->status_estorno = $status_estorno;
//  $lancamento->atendimento = 'S';
//  $lancamento->id_usuario_edicao = intVal($usuario);
//  LancamentoCrud::salva($conexao, $lancamento);
//
//  /*$query = "INSERT INTO lancamento_financeiro (id,tipo,situacao,pedido_origem, numero_documento,
//  id_colaborador, data_emissao,valor,id_usuario,observacao,documento,status_estorno,atendimento,id_usuario_edicao)
//  VALUES ({$id_lancamento},'{$tipo}',1,'{$pedido_origem}','{$pedido_origem}',{$id_colaborador},NOW(),
//  '{$valor}',{$usuario},'{$observacao}',0,'{$status_estorno}','S',{$usuario});";
//
//  $conexao->exec($query);*/
//  return $id_lancamento;
//}

// --Commented out by Inspection START (12/08/2022 15:58):
//function insereAcertoDocumentos($num_acerto, $sequencia, $tipo, $documento, $valor, $data, $usuario, $motivo, $conta_bancaria, $numero, $tesouraria, $responsavel, $caixa)
//{
//  $query = "INSERT INTO acertos_documentos (id_acerto,sequencia,tipo,documento,valor,data_pagamento,usuario,motivo,conta_bancaria,numero,tesouraria,responsavel,caixa)
//  VALUES ({$num_acerto},{$sequencia},'{$tipo}',{$documento},'{$valor}','{$data}',{$usuario},'{$motivo}',{$conta_bancaria},{$numero},{$tesouraria},{$responsavel},{$caixa});";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaUltimaSeqLancamento($numero)
//{
//  $query = "SELECT COALESCE(MAX(sequencia),0)id FROM lancamento_financeiro where id={$numero}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['id'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function insereLancamentoDocumentoPago($numero, $tipo, $lancamento, $valor, $documento, $motivo)
//{
//  $query = "INSERT INTO lancamento_financeiro_documento (id,tipo,fk_lancamento,valor,documento,motivo)
//  VALUES ({$numero},'{$tipo}',{$lancamento},'{$valor}',{$documento},'{$motivo}');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function insereAcerto($id, $tipo, $origem, $id_colaborador, $usuario, $numero_documento, $desconto, $observacao)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_acerto = Date('Y-m-d H:i:s');
//  $query = "INSERT INTO acertos (id,tipo,origem,id_colaborador,usuario,numero_documento,data_acerto,desconto,observacao_acerto)
//  VALUES ({$id},'{$tipo}','{$origem}',{$id_colaborador},{$usuario},{$numero_documento},'{$data_acerto}',{$desconto},'{$observacao}');";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


function buscaUltimoLancamento()
{
  $query = "SELECT COALESCE(MAX(id),0)id FROM lancamento_financeiro";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['id'];
}

// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaUltimoAcerto()
//{
//  $query = "SELECT COALESCE(MAX(id),0)id FROM acertos";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['id'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaUltimoLancamentoDocumento()
//{
//  $query = "SELECT COALESCE(MAX(id),0)id FROM lancamento_financeiro_documento";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['id'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamento($id)
//{
//  $query = "SELECT lc.*, c.razao_social, s.nome nome_situacao, u.nome usuario, f.cod_transacao FROM lancamento_financeiro lc
//      LEFT OUTER JOIN colaboradores c ON (c.id=lc.id_colaborador)
//      LEFT OUTER JOIN usuarios u ON (u.id=lc.id_usuario)
//      LEFT OUTER JOIN situacao_lancamento s ON (s.id=lc.situacao)
//      LEFT OUTER JOIN faturamento f ON (f.id=lc.pedido_origem)
//      WHERE lc.id = {$id}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosFaturamento($id)
//{
//  $query = "SELECT lf.*,c.razao_social cliente, r.nome representante, d.nome nome_documento, s.nome situacao_nome,
//  cb.nome conta_bancaria, f.nome nome_freteiro, tt.nome nome_tabela
//  from lancamento_financeiro lf
//  INNER JOIN situacao_lancamento s ON(s.id=lf.situacao)
//  INNER JOIN colaboradores c ON(c.id=lf.id_colaborador)
//  LEFT OUTER JOIN representantes r ON(r.id=lf.id_representante)
//  INNER JOIN documentos d ON(d.id = lf.documento)
//  LEFT OUTER JOIN contas_bancarias cb ON (cb.id= lf.conta_bancaria)
//  LEFT OUTER JOIN freteiro f ON (f.id= lf.freteiro)
//  LEFT OUTER JOIN tipo_tabela tt ON (lf.tabela= tt.id)
//  where lf.numero_documento={$id} AND lf.tipo='R'";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function buscaDevolucoesLancamento($id)
// {
//   $query = "SELECT COUNT(di.id_produto)quantidade, di.preco, di.sequencia, di.desconto, di.situacao, 
//     p.descricao produto, di.id_produto, c.razao_social cliente,
//     di.data_hora, di.id_tabela, s.nome nome_situacao, SUM(di.preco)valor_total
//     FROM devolucao_item di
//     INNER JOIN colaboradores c ON (c.id=di.id_cliente)
//     LEFT OUTER JOIN situacao s ON (s.id=di.situacao)
//     INNER JOIN produtos p ON (p.id=di.id_produto)
//     WHERE di.id_faturamento = {$id}
//     GROUP BY di.id_produto, di.situacao, di.preco ORDER BY di.id_produto ASC";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaDocumentosPagamento($id_lancamento)
//{
//  $query = "SELECT lfd.*, d.nome nome_documento FROM lancamento_financeiro_documento lfd
//  INNER JOIN documentos d ON (d.id=lfd.documento)
//  WHERE lfd.fk_lancamento={$id_lancamento}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function buscaDevolucaoItemGradeLancamento($id_faturamento, $id_produto, $situacao)
// {
//   $query = "SELECT di.tamanho, count(di.tamanho) quantidade
//     FROM devolucao_item di
//     WHERE di.id_faturamento = {$id_faturamento} AND
//     di.id_produto = {$id_produto} AND
//     di.situacao = {$situacao}
//     GROUP BY di.situacao, di.tamanho ORDER BY di.tamanho";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosEmAberto()
//{
//  $query = "SELECT lf.*, c.razao_social FROM lancamento_financeiro lf
//    INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//    WHERE lf.situacao=1";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosEmAbertoAcerto($filtro)
//{
//  $query = "SELECT DISTINCT lf.id_colaborador id_cliente, SUM(lf.valor) valor_total,
//    MIN(lf.data_emissao) data_emissao, MIN(lf.data_vencimento) data_vencimento,
//    c.razao_social cliente, c.telefone, c.telefone2 from lancamento_financeiro lf
//    INNER JOIN colaboradores c ON(c.id = lf.id_colaborador) WHERE lf.situacao = 1 AND lf.tipo='R'  {$filtro}
//    GROUP BY lf.id_colaborador, data_emissao, data_vencimento ORDER BY lf.data_vencimento ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosVencidosEmAberto($filtro)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d');
//  $query = "SELECT lf.id_colaborador id_cliente, SUM(lf.valor)valor_total, lf.data_vencimento, lf.data_emissao,
//    c.razao_social cliente, c.telefone from lancamento_financeiro lf
//    INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//    WHERE lf.situacao = 1 AND Date(lf.data_vencimento)<='{$data_atual}'
//    {$filtro}
//    GROUP BY lf.id_colaborador
//    ORDER BY lf.data_vencimento ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosAVencerEmAberto($filtro)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d');
//  $query = "SELECT lf.id_colaborador id_cliente, SUM(lf.valor)valor_total, lf.data_vencimento, lf.data_emissao,
//    c.razao_social cliente, c.telefone, lf.guardou from lancamento_financeiro lf
//    INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//    WHERE lf.situacao = 1 AND Date(lf.data_vencimento)>'{$data_atual}'
//    {$filtro}
//    GROUP BY lf.id_colaborador
//    ORDER BY lf.data_vencimento ASC LIMIT 15;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosVencidosEmAbertoCliente($cliente)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d');
//  $query = "SELECT lf.*, c.razao_social cliente, c.telefone from lancamento_financeiro lf
//    INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//    WHERE lf.situacao = 1 and lf.tipo='R'
//    AND Date(lf.data_vencimento)<='{$data_atual}'
//    AND lf.id_colaborador = {$cliente}
//    ORDER BY lf.data_vencimento ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function buscaLancamentosEmAbertoCliente($cliente)
// {
//   $query = "SELECT lf.*, c.razao_social cliente, c.telefone, tt.nome nome_tabela
//     from lancamento_financeiro lf
//     INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//     INNER JOIN tipo_tabela tt ON (tt.id=lf.tabela)
//     WHERE lf.situacao = 1 and lf.tipo='R'
//     AND lf.id_colaborador = {$cliente}
//     ORDER BY lf.data_vencimento ASC;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalLancamentosVencidosEmAbertoCliente($cliente)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d');
//  $query = "SELECT sum(lf.valor)valor_total from lancamento_financeiro lf
//    INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//    WHERE lf.situacao = 1 and lf.tipo='R'
//    AND Date(lf.data_vencimento)<='{$data_atual}'
//    AND lf.id_colaborador = {$cliente};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor_total'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosAVencerEmAbertoCliente($cliente)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d');
//  $query = "SELECT lf.*, c.razao_social cliente, c.telefone
//    from lancamento_financeiro lf
//    INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//    WHERE lf.situacao = 1 and lf.tipo='R'
//    AND Date(lf.data_vencimento)>'{$data_atual}'
//    AND lf.id_colaborador = {$cliente}
//    ORDER BY lf.data_vencimento ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalLancamentosAVencerEmAbertoCliente($cliente)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d');
//  $query = "SELECT sum(lf.valor) valor_total
//  from lancamento_financeiro lf
//  INNER JOIN colaboradores c ON(c.id = lf.id_colaborador)
//  WHERE lf.situacao = 1  and lf.tipo='R'
//  AND Date(lf.data_vencimento)>'{$data_atual}'
//  AND lf.id_colaborador = {$cliente}
//  ORDER BY lf.data_vencimento ASC;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor_total'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function atualizaSituacaoLancamentos($lancamento, $valor, $num_acerto, $id_usuario_pag)
//{
//  //  date_default_timezone_set('America/Sao_Paulo');
//  //  $data_atual = Date('Y-m-d H:i:s');
//  //  $query = "UPDATE lancamento_financeiro SET valor_total = valor-desconto,
//  //  valor_pago={$valor}, acerto={$num_acerto}, data_pagamento='{$data_atual}', id_usuario_pag={$id_usuario_pag},
//  //  situacao=2 WHERE id={$lancamento};";
//  //  $conexao = Conexao::criarConexao();
//  //  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function removeLancamento($tipo, $origem, $id_faturamento)
//{
//  $query = "DELETE FROM lancamento_financeiro WHERE numero_documento = {$id_faturamento}
//  AND tipo='{$tipo}' AND origem='{$origem}';";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosFaturamentoExcluir($id_faturamento)
//{
//  $query = "SELECT lf.* from lancamento_financeiro lf
//  WHERE lf.documento = {$id_faturamento}
//  AND lf.origem = 'Faturamento';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function removeLancamentosFaturamento($id_faturamento)
//{
//  $query = "DELETE FROM lancamento_financeiro WHERE documento = {$id_faturamento}
//  AND origem='Faturamento';";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function removeLancamentoId($id)
//{
//  $query = "DELETE FROM lancamento_financeiro WHERE id = {$id};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function verificaSeExisteLancamentosEmAberto($id)
//{
//  $query = "SELECT lf.* from lancamento_financeiro lf
//  WHERE lf.situacao = 1 and lf.id_colaborador={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function verificaSeExisteLancamentosVencidos($id)
//{
//  $data = DATE('Y-m-d H:m:s');
//  $query = "SELECT lf.* from lancamento_financeiro lf
//  WHERE lf.situacao = 1 and DATE(lf.data_vencimento)<DATE('$data') and lf.id_colaborador={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function existeLancamentosFaturados($id)
//{
//  $query = "SELECT lf.* from lancamento_financeiro lf
//  WHERE lf.numero_documento={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

//ADICIONADO A VERIFICAÇÃO PARA BARRAR PEDIDOS DE REEMBOLSOS COMO CREDITO PENDENTE
// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaCreditoCliente($id)
//{
//  $query = "SELECT lf.* from lancamento_financeiro lf
//  WHERE lf.id_colaborador={$id} AND lf.tipo='P' AND lf.situacao=1 AND lf.status_estorno!='R';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

//ADICIONADO A VERIFICAÇÃO PARA BARRAR PEDIDOS DE REEMBOLSOS COMO CREDITO PENDENTE
// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalCreditoCliente($id)
//{
//
//  $query = "SELECT SUM(lf.valor)valor  from lancamento_financeiro lf
//  WHERE lf.id_colaborador={$id} AND lf.tipo='P' AND lf.situacao=1 AND lf.status_estorno!='R';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

//
//function atualizaCreditoLancamento($faturamento, $credito, $id_usuario_pag)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d H:i:s');
//  $query = "UPDATE lancamento_financeiro SET valor_total = valor-desconto, id_usuario_edicao={$id_usuario_pag}
//  valor_pago=valor_total, data_pagamento='{$data_atual}', id_usuario_pag={$id_usuario_pag}, situacao=2, credito_usado={$faturamento} WHERE id={$credito};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

//function removeCreditoUsado($faturamento)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data_atual = Date('Y-m-d H:i:s');
//  $query = "UPDATE lancamento_financeiro SET valor_total = valor-desconto, id_usuario_edicao=0
//  valor_pago=0, data_pagamento=NULL, id_usuario_pag=0, situacao=1, credito_usado=0 WHERE credito_usado={$faturamento};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}

// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaCreditoUsadoCliente($id)
//{
//  $query = "SELECT lf.* FROM lancamento_financeiro lf WHERE lf.credito_usado={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalCreditoUsadoCliente($id)
//{
//  $query = "SELECT SUM(lf.valor) valor FROM lancamento_financeiro lf WHERE lf.credito_usado={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['valor'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listaLancamentosEmAbertoFornecedor($filtro)
//{
//  $query = "SELECT lf.id_colaborador, c.razao_social fornecedor, COUNT(lf.id) entradas,
//  SUM(lf.valor)valor, u.nome usuario, MAX(data_emissao) data_emissao
//  FROM lancamento_financeiro lf
//  INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//  INNER JOIN usuarios u ON (u.id = lf.id_usuario)
//  WHERE lf.tipo='P' AND lf.situacao=1 AND c.tipo='F' {$filtro}
//  GROUP BY lf.id_colaborador ORDER BY data_emissao DESC LIMIT 20";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosFornecedorEmAberto($id_fornecedor)
//{
//  $query = "SELECT lf.id, lf.compras, lf.valor, lf.id_colaborador, c.razao_social fornecedor, lf.pares,
//  u.nome usuario, lf.data_emissao, lf.data_vencimento, lf.numero_movimento nMov FROM lancamento_financeiro lf
//  INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//  INNER JOIN usuarios u ON (u.id = lf.id_usuario)
//  WHERE lf.tipo='P' AND lf.situacao=1
//  AND lf.id_colaborador={$id_fornecedor} GROUP BY nMov;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaValorTotalLancamentosAbertoFornecedor()
//{
//  $query = "SELECT SUM(lf.valor) valor, SUM(lf.pares) pares FROM lancamento_financeiro lf
//  INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//  LEFT OUTER JOIN movimentacao_estoque m ON (m.id = lf.numero_documento)
//  WHERE lf.tipo='P' AND lf.situacao=1 AND c.tipo='F';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function buscaDefeitoPagamento($uuid, $sequencia)
// {
//   $query = "SELECT *,u.nome FROM defeitos Inner JOIN usuarios u ON (u.id = id_vendedor) WHERE uuid= '{$uuid}' AND sequencia= '{$sequencia}'AND abater=0;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// --Commented out by Inspection START (12/08/2022 15:58):
//function atualizaDefeitosPagamentoFornecedor($uuid, $sequencia)
//{
//  $query = "UPDATE defeitos SET abater = 1
//  WHERE uuid='{$uuid}' AND sequencia='{$sequencia}' AND abater=0;";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function atualizaChequesPagamentoFornecedor($id_acerto, $passado_para, $cheque)
//{
//  $query = "UPDATE cheques SET situacao = 2, passado_para={$passado_para}, acerto_pagar={$id_acerto} WHERE id={$cheque};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function buscaLancamentosPorAcerto($id_acerto)
// {
//   $query = "SELECT lf.id, lf.compras, lf.valor, lf.id_colaborador, c.razao_social fornecedor, cic.quantidade, 
//   cic.volume, u.nome usuario, lf.data_emissao, lf.data_vencimento, m.id nMov FROM lancamento_financeiro lf 
//   INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
//   INNER JOIN usuarios u ON (u.id = lf.id_usuario)
//   INNER JOIN movimentacao_estoque m ON (m.id = lf.numero_movimento)
//   INNER JOIN compras_itens_caixas cic ON (cic.id_lancamento=lf.id)
//   WHERE lf.acerto={$id_acerto} GROUP BY m.id;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// --Commented out by Inspection START (12/08/2022 15:58):
//function insereLancamentoDeDevolucao($idLanc, $id_fornecedor, $valor_selecionado, $pares, $usuario, $nota_fiscal)
//{
//  date_default_timezone_set('America/Sao_Paulo');
//  $data = DATE('Y-m-d H:i:s');
//  $venc = date('Y-m-d H:i:s', strtotime("+30 days", strtotime($data)));
//  $conexao = Conexao::criarConexao();
//  $lancamento = new LancamentoAlias(
//    'R',
//    1,
//    'DF',
//    intVal($id_fornecedor),
//    $venc,
//    $valor_selecionado,
//    intVal($usuario),
//    10
//  );
//  $lancamento->tabela = 1;
//  $lancamento->pares = intVal($pares);
//  $lancamento->devolucao = 1;
//  $lancamento->nota_fiscal = intVal($nota_fiscal);
//  LancamentoCrud::salva($conexao, $lancamento);
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalLancamentosParesGarantidos()
//{
//  $query = "SELECT COUNT(lf.id)total FROM lancamento_financeiro lf WHERE  lf.situacao=1;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  if ($linha) {
//    return $linha['total'];
//  } else {
//    return false;
//  }
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

//ADICIONADO A VERIFICAÇÃO PARA BARRAR PEDIDOS DE REEMBOLSOS COMO CREDITO PENDENTE
// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaLancamentosCreditoCliente($id_cliente)
//{
//  $query = "SELECT id
//                FROM lancamento_financeiro
//                  WHERE id_colaborador={$id_cliente}
//                    AND situacao=1
//                    AND tipo='P'
//                    AND status_estorno!='R';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

//ADICIONADO A VERIFICAÇÃO PARA BARRAR PEDIDOS DE REEMBOLSOS COMO CREDITO PENDENTE
// --Commented out by Inspection START (12/08/2022 15:58):
//function buscaTotalLancamentosCreditoCliente($id_cliente)
//{
//  $query = "SELECT SUM(valor)as valor
//              FROM lancamento_financeiro
//                WHERE id_colaborador={$id_cliente}
//                  AND situacao=1
//                  AND tipo='P'
//                  AND status_estorno!='R';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


//function existeCreditoAbertoID($id_faturamento, $valor)
//{
//  $query = "SELECT COUNT(*) AS Existe
//              FROM lancamento_financeiro
//                WHERE lancamento_financeiro.pedido_origem = '{$id_faturamento}'
//                  AND lancamento_financeiro.situacao=1
//                  AND lancamento_financeiro.tipo='P'
//                  AND lancamento_financeiro.status_estorno='C'
//                  AND lancamento_financeiro.atendimento ='S'
//                  AND lancamento_financeiro.valor='{$valor}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch();
//  return $lista['Existe'];
//}


/* STATUS ESTORNO

'A' -> EM ABERTO (Não foi escolhido ainda pelo cliente o que ele deseja)
'C' -> CRÉDITO (Aceitou o crédito gerado)
'R' -> REEMBOLSO (Solicitou Reembolso)
'P' -> Pago (Pagamento realizado)
*/

// --Commented out by Inspection START (12/08/2022 15:58):
//function listaSolicitaReembolso()
//{
//
//  $query = "SELECT lancamento_financeiro.*,
//          (
//            SELECT colaboradores.razao_social
//              FROM colaboradores
//                WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//          )AS nome,
//          (
//           SELECT usuarios.nome
//            FROM usuarios
//              WHERE usuarios.id = lancamento_financeiro.id_usuario
//          )AS usuario
//            FROM lancamento_financeiro
//              WHERE lancamento_financeiro.situacao = 1
//                AND lancamento_financeiro.tipo = 'P'
//                AND lancamento_financeiro.status_estorno='R';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listaSolicitaReembolsoStatusAberto()
//{
//
//  $query = "SELECT lancamento_financeiro.*,
//          (
//            SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//          ) AS nome,
//          (
//            SELECT usuarios.nome FROM usuarios WHERE usuarios.id = lancamento_financeiro.id_usuario
//          ) AS usuario
//            FROM lancamento_financeiro
//              WHERE lancamento_financeiro.tipo = 'P'
//                AND lancamento_financeiro.situacao = 1
//                AND lancamento_financeiro.status_estorno='A';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)

// --Commented out by Inspection START (12/08/2022 15:58):
//function listaSolicitaReembolsoStatus($status)
//{
//
//  $query = "SELECT reembolso.*,
//          (
//            SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = reembolso.id_recebedor
//          ) AS nome
//            FROM reembolso
//              WHERE reembolso.situacao='{$status}';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listaSolicitaReembolsoNomeStatus($status, $nome_cliente)
//{
//
//  $query = "SELECT reembolso.*,
//          (
//            SELECT colaboradores.razao_social
//              FROM colaboradores
//                WHERE colaboradores.id = reembolso.id_recebedor
//          )as nome
//            FROM reembolso
//              LEFT JOIN colaboradores ON(reembolso.id_recebedor=colaboradores.id)
//                WHERE reembolso.situacao='{$status}'
//
//                  AND colaboradores.razao_social LIKE '%{$nome_cliente}%';";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function listaSolicitaReembolsoNome($nome_cliente)
//{
//
//  $query = "SELECT reembolso.*,
//          colaboradores.razao_social AS nome,
//          colaboradores.id
//            FROM reembolso
//              LEFT JOIN colaboradores ON(reembolso.id_recebedor= colaboradores.id)
//                WHERE colaboradores.razao_social LIKE '%{$nome_cliente}%';";
//
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// function ListaProdutoAtendimento($id_cliente, $id, $id_produto, $numero)
// {
//   $query = "SELECT faturamento_item.id_faturamento, faturamento_item.preco, produtos.descricao,
//           faturamento_item.data_conferencia,faturamento_item.tamanho,
//           (
//             SELECT MAX(produtos_foto.caminho)
//               FROM produtos_foto
//                 WHERE produtos_foto.id=$id_produto
//                   AND produtos_foto.foto_calcada=0
//                     GROUP BY produtos_foto.id
//           )AS foto
//               FROM faturamento_item
//               JOIN produtos
//                 WHERE faturamento_item.id_faturamento = {$id}
//                   AND faturamento_item.id_produto = {$id_produto}
//                   AND faturamento_item.tamanho='{$numero}'
//                   AND faturamento_item.id_cliente =  {$id_cliente}
//                   AND produtos.id = faturamento_item.id_produto";

//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function ListaProdutosCorrigidos($id_cliente, $id_faturamento)
// {
//   $query = "SELECT faturamento_item.id_produto,faturamento_item.id_faturamento, faturamento_item.tamanho,faturamento_item.preco, faturamento_item.id_cliente, 
//               produtos.descricao,faturamento.data_fechamento as data_emissao,
//               (SELECT MAX(produtos_foto.caminho)
//                   FROM produtos_foto
//               WHERE produtos_foto.id=produtos.id
//                 AND produtos_foto.foto_calcada=0
//             GROUP BY produtos_foto.id
//               ) AS foto
//             FROM faturamento_item
//             INNER JOIN faturamento ON faturamento.id = faturamento_item.id_faturamento
//             INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id )
//             WHERE faturamento_item.id_cliente = {$id_cliente}
//             AND faturamento_item.id_faturamento = {$id_faturamento}
//             AND faturamento_item.situacao = 19 ";

//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
//   return $lista;
// }


// function ListaProdutosCorrigidosReembolsoCredito($id_cliente, $lancamento)
// {
//   $query = "SELECT pedido_origem from lancamento_financeiro WHERE id = {$lancamento} LIMIT 1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $fa = $resultado->fetch(PDO::FETCH_ASSOC);
//   $result = ListaProdutosCorrigidos($id_cliente, $fa['pedido_origem']);
//   return $result;
// }


/*
*Função atualiza a tabela Lancamento_financeiro, marcando o status estorno como C
indentificando que o cliente escolheu ficar com o crédito gerado.
 */
//function UpdateClienteAceitaCreditoParCorrigido($id_cliente, $status)
//{
//
//  $query = "UPDATE lancamento_financeiro SET lancamento_financeiro.status_estorno = '{$status}'
//            WHERE lancamento_financeiro.id_colaborador = {$id_cliente}
//              AND lancamento_financeiro.tipo = 'P'
//              AND lancamento_financeiro.situacao=1";
//
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
//
//function AtualizaClienteCreditoReembolso($id_cliente, $id_faturamento, $status)
//{
//
//  $query = "UPDATE lancamento_financeiro SET lancamento_financeiro.status_estorno = '{$status}'
//            WHERE lancamento_financeiro.id_colaborador = {$id_cliente}
//              AND lancamento_financeiro.tipo = 'P'
//              AND lancamento_financeiro.situacao=1
//              AND lancamento_financeiro.pedido_origem = {$id_faturamento}";
//
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}

// --Commented out by Inspection START (12/08/2022 15:58):
//function ExisteParCorrigido($id_faturamento)
//{
//  $query = "SELECT COUNT(*) AS existe FROM faturamento_item WHERE id_faturamento={$id_faturamento} AND situacao = 19";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetch(PDO::FETCH_ASSOC);
//  return $lista['existe'];
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


// --Commented out by Inspection START (12/08/2022 15:58):
//function ListaParCorrigidoFaturamento($id_faturamento)
//{
//  $query = "SELECT faturamento_item.* ,produtos.descricao, (
//            SELECT MAX(produtos_foto.caminho)
//              FROM produtos_foto
//                WHERE produtos_foto.id=produtos.id
//                  AND produtos_foto.foto_calcada=0
//                    GROUP BY produtos_foto.id
//            ) AS foto
//              FROM faturamento_item
//                INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id)
//                  WHERE faturamento_item.id_faturamento={$id_faturamento}
//                  AND situacao = 19";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
//  return $lista;
//}
// --Commented out by Inspection STOP (12/08/2022 15:58)


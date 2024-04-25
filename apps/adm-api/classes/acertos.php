<?php
require_once 'conexao.php';

function buscaListaAcertos($filtro){
  $query = "SELECT 
  acertos.*,
  colaboradores.razao_social, 
  (SELECT SUM(acertos_documentos.valor) FROM acertos_documentos WHERE acertos_documentos.id_acerto = acertos.id) valor_total
  FROM acertos 
    INNER JOIN colaboradores ON (colaboradores.id=acertos.id_colaborador)
    INNER JOIN acertos_documentos ON (acertos_documentos.id_acerto=acertos.id)
    INNER JOIN lancamento_financeiro ON (lancamento_financeiro.acerto=acertos.id) 
  {$filtro} GROUP BY acertos.id 
  ORDER BY acertos.id DESC
  LIMIT 25";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

function buscaAcerto($id){
  $query = "SELECT a.*, u.nome usuario FROM acertos a 
  INNER JOIN usuarios u ON (u.id=a.usuario) 
  WHERE a.id={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

function buscaLancamentosAcerto($id){
  $query = "SELECT lf.*,c.razao_social FROM lancamento_financeiro lf
  INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
  INNER JOIN acertos a ON (lf.acerto=a.id)
  WHERE acerto={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

function buscaDocumentosAcerto($id){
  $query = "SELECT ad.*,c.razao_social,d.nome documento_nome, cb.nome nome_conta_bancaria FROM acertos_documentos ad
  INNER JOIN acertos a ON (ad.id_acerto=a.id)
  INNER JOIN documentos d ON (ad.documento=d.id)
  INNER JOIN colaboradores c ON (c.id=a.id_colaborador)
  LEFT OUTER JOIN contas_bancarias cb ON (cb.id=ad.conta_bancaria)
  WHERE ad.id_acerto={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

// function buscaCreditosAcerto($id_acerto){
//   $query = "SELECT lf.*,c.razao_social FROM lancamento_financeiro lf
//   INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//   WHERE lf.acerto={$id_acerto} AND lf.tipo='P';";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

function existeAcertoFaturado($id){
  $query = "SELECT a.* FROM acertos a WHERE a.numero_documento={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

function removeAcerto($acerto){
  $query = "DELETE FROM acertos WHERE id={$acerto};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function removeAcertoDocumentos($acerto){
  $query = "DELETE FROM acertos_documentos WHERE id_acerto={$acerto};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

// function buscaDinheiroAcerto($id_acerto){
//   $query = "SELECT SUM(ad.valor) valor FROM acertos_documentos ad WHERE ad.documento=1 and ad.id_acerto={$id_acerto};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

// function buscaDefeitosPorAcerto($id_acerto){
//   $query = "SELECT p.descricao referencia, di.tamanho, di.id_faturamento, p.valor_custo_produto, di.sequencia FROM devolucao_item di
//   INNER JOIN produtos p ON (p.id=di.id_produto)
//   WHERE di.acerto={$id_acerto} AND di.defeito=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaTotalDefeitosAcerto($id_acerto){
//   $query = "SELECT SUM(p.valor_custo_produto) valor FROM devolucao_item di
//   INNER JOIN produtos p ON (p.id=di.id_produto)
//   WHERE di.acerto={$id_acerto} AND di.defeito=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

function listaLancamentosTesouraria($filtro){
  $query = "SELECT t.*, u.nome usuario FROM tesouraria t
  INNER JOIN usuarios u ON (u.id=t.usuario)
  {$filtro} ORDER BY t.data_emissao DESC;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

function buscaSaldoAnterior($filtro,$tipo){
  $query = "SELECT SUM(t.valor) saldo FROM tesouraria t
  {$filtro} AND t.tipo='{$tipo}';";
    $query = "SELECT SUM(t.valor) saldo FROM tesouraria t
    {$filtro} AND t.tipo='{$tipo}';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['saldo'];
}

function excluirAcertoFornecedor($acerto){
    $query = "DELETE FROM acertos WHERE id={$acerto};";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}
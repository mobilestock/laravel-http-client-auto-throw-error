<?php
require_once 'conexao.php';

/*function insereNotificacao($id,$assunto,$mensagem,$data){
  $query = "INSERT INTO notificacoes (id,assunto,mensagem,data)
  VALUES ({$id},'{$assunto}','{$mensagem}','{$data}');";
  $conexao = Conexao::criarConexao();
  return $query->execute() or die(print_r($query->errorInfo(),true));
}*/

function buscaUltimaNotificacao()
{
    $query = 'SELECT COALESCE(MAX(id),0) FROM notificacoes;';
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $linha = $stmt->fetch();
    return $linha['id'];
}
function buscaNotificacoesTroca($id_usuario)
{
    if (isset($id_usuario)) {
        $query = "SELECT * FROM notificacoes
              WHERE recebida = 0 AND mensagem='TROCA' AND id_cliente =(
                SELECT id_colaborador FROM usuarios
                  WHERE id = {$id_usuario});";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $notificacao = $resultado->fetchAll();
        return $notificacao;
    } else {
        return null;
    }
}

function buscaNotificacaoFiscal($id_usuario)
{
    if (isset($id_usuario)) {
        $query = "SELECT * FROM notificacoes
              WHERE notificacoes.recebida = 0 AND notificacoes.id_cliente = {$id_usuario} AND notificacoes.tipo_mensagem='F';";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $notificacao = $resultado->fetch(PDO::FETCH_ASSOC);
        return $notificacao;
    } else {
        return null;
    }
}

function buscaNotificacoesEntrega($id_usuario)
{
    if (isset($id_usuario)) {
        $query = "SELECT * FROM notificacoes
              WHERE notificacoes.recebida = 0 AND notificacoes.mensagem='ENTREGAS' AND notificacoes.id_cliente =(
                SELECT usuarios.id_colaborador FROM usuarios
                  WHERE usuarios.id = {$id_usuario});";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $notificacao = $resultado->fetch();
        return $notificacao;
    } else {
        return null;
    }
}

function setNotificacaoRecebida($id_usuario)
{
    $query = "UPDATE notificacoes SET recebida = 1
            WHERE id_cliente = (
              SELECT id_colaborador
                FROM usuarios
                  WHERE id = {$id_usuario}) AND notificacoes.mensagem!='CORRIGIDO' AND mensagem!='PAGO';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return null;
}

function exibeProdutosTroca($id_usuario)
{
    $query = "SELECT troca_pendente_item.data_hora,
            (
              SELECT MAX(produtos_foto.caminho) FROM produtos_foto WHERE produtos_foto.id=troca_pendente_item.id_produto group by produtos_foto.id
            ) AS caminho,
            (
              SELECT produtos.descricao FROM produtos WHERE produtos.id = troca_pendente_item.id_produto
            ) descricao
              FROM troca_pendente_item
                WHERE troca_pendente_item.id_cliente = (SELECT id_colaborador FROM usuarios WHERE id= {$id_usuario})
                  ORDER BY troca_pendente_item.data_hora DESC LIMIT 1";

    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $produtos = $resultado->fetchAll();
    return $produtos;
}

function buscaNotificacoesPedidoPago($id_usuario)
{
    if (isset($id_usuario)) {
        $query = "SELECT * FROM notificacoes
              WHERE recebida = 0 AND mensagem='PAGO' AND id_cliente =(
                SELECT id_colaborador FROM usuarios
                  WHERE id = {$id_usuario});";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $notificacao = $resultado->fetch();
        return $notificacao;
    } else {
        return null;
    }
}

function setNotificacaoRecebidaParCorrigido($id_usuario)
{
    $query = "UPDATE notificacoes SET recebida = 1
            WHERE id_cliente = {$id_usuario} AND notificacoes.mensagem='CORRIGIDO';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return null;
}
function setNotificacaoRecebidaPago($id_usuario)
{
    $query = "UPDATE notificacoes SET recebida = 1
            WHERE id_cliente = (
              SELECT id_colaborador
                FROM usuarios
                  WHERE id = {$id_usuario}) AND notificacoes.mensagem='PAGO';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return null;
}

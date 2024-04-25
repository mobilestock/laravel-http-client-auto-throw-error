<?php

use MobileStock\helper\DB;

require_once 'conexao.php';
require_once __DIR__ . '/../vendor/autoload.php';

function listaUsuarios($filtro)
{
  $query = "SELECT u.* FROM usuarios u
  LEFT OUTER JOIN colaboradores c ON (c.id=u.id_colaborador)
  " . $filtro . " ORDER BY u.id;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

// --Commented out by Inspection START (23/08/2022 14:58):
//function listaUsuariosVendedores()
//{
//  $query = "SELECT * FROM usuarios WHERE (nivel_acesso=51 OR nivel_acesso=55) ORDER BY nome;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// --Commented out by Inspection START (23/08/2022 14:58):
//function listaUsuariosFinanceiro()
//{
//  $query = "SELECT * FROM usuarios WHERE (nivel_acesso=56 OR nivel_acesso=57) ORDER BY nome;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// --Commented out by Inspection START (23/08/2022 14:58):
//function listaUsuariosDoSistema()
//{
//  $query = "SELECT * FROM usuarios WHERE (nivel_acesso=51 OR nivel_acesso=52 OR nivel_acesso=53 OR nivel_acesso=54 OR nivel_acesso=55 OR nivel_acesso=56 OR nivel_acesso=57) ORDER BY nome;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  return $resultado->fetchAll();
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


function listaUsuariosPagina($pagina, $itens, $filtro)
{
  $query = "SELECT u.*, c.razao_social FROM usuarios u
  LEFT OUTER JOIN colaboradores c ON (c.id=u.id_colaborador)
  " . $filtro . " ORDER BY id LIMIT {$pagina},{$itens};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

function buscaUltimoUsuario()
{
  $query = "SELECT MAX(id) id FROM usuarios;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['id'];
}

// function insereUsuarioFornecedor($id_usuario, $usuario, $cnpj, $id)
// {
//   $senhaMd5 = md5($cnpj);
//   $query = "INSERT INTO usuarios (id,nome,senha,nivel_acesso,bloqueado,id_colaborador)
//   VALUES ({$id_usuario},'{$usuario}','{$senhaMd5}',30,0,{$id});";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function insereUsuarioTransportadora($id_usuario, $usuario, $cnpj, $id)
// {
//   $senhaMd5 = md5($cnpj);
//   $query = "INSERT INTO usuarios (id,nome,senha,nivel_acesso,bloqueado,id_colaborador)
//   VALUES ({$id_usuario},'{$usuario}','{$senhaMd5}',20,0,{$id});";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// function insereUsuarioCliente(int $id_usuario, string $usuario, string $senha, int $id_colaborador, string $email, string $cnpjCliente, string $telefone)
// {
//   $senhaMd5 = md5($senha);
//   $query = "INSERT INTO usuarios (id,nome,senha,nivel_acesso,bloqueado,id_colaborador,email,cnpj,telefone)
//   VALUES ({$id_usuario},'{$usuario}','{$senhaMd5}',10,0,{$id_colaborador},'{$email}','{$cnpjCliente}','{$telefone}');";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

function alteraUsuarioFornecedor($id, $nome, $senha, $colaborador)
{
  $senhaMd5 = md5($senha);
  $query = "UPDATE usuarios SET nome='{$nome}', senha='{$senhaMd5}',
  id_colaborador={$colaborador} WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

// function alteraUsuarioTransportadora($id, $nome, $senha, $colaborador)
// {
//   $senhaMd5 = md5($senha);
//   $query = "UPDATE usuarios SET nome='{$nome}', senha='{$senhaMd5}',
//   id_colaborador={$colaborador} WHERE id={$id};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (23/08/2022 14:58):
//function alteraUsuarioCliente($id, $nome, $senha, $colaborador)
//{
//  $senhaMd5 = md5($senha);
//  $query = "UPDATE usuarios SET nome='{$nome}', senha='{$senhaMd5}',
//  id_colaborador={$colaborador} WHERE id={$id};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// function buscaIdUsuario($id)
// {
//   $query = "SELECT id FROM usuarios WHERE id_colaborador={$id};";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['id'];
// }

function buscaCadastroUsuario($id)
{
  $query = "SELECT * FROM usuarios WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

// --Commented out by Inspection START (23/08/2022 14:58):
//function buscaNomeUsuario($id)
//{
//  $query = "SELECT nome FROM usuarios WHERE id={$id};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['nome'] ?? '';
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// --Commented out by Inspection START (23/08/2022 14:58):
//function validaUsuario($nome, $senha)
//{
//  $senhaMd5 = md5($senha);
//  $query = "SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE nome=:nome and senha=:senha and bloqueado=0";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  $stmt->bindValue(':nome', $nome);
//  $stmt->bindValue(':senha', $senhaMd5);
//  $stmt->execute();
//  return $stmt->fetch();
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)



function insereUsuario($id, $nome, $senha, $acesso, $bloqueado, $colaborador, $tipos)
{
  $senhaMd5 = md5($senha);
  $query = "INSERT INTO usuarios (id,nome,senha,nivel_acesso,bloqueado,id_colaborador, tipos)
  VALUES ({$id},'{$nome}','{$senhaMd5}',{$acesso},{$bloqueado},{$colaborador}, '{$tipos}');";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function insereUsuarioSolicitacao($id, $nome, $senha, $email, $telefone)
{
  $senhaMd5 = md5($senha);
  $query = "INSERT INTO usuarios (id,nome,senha,nivel_acesso,bloqueado,email,telefone)
  VALUES ({$id},'{$nome}','{$senhaMd5}',10,1,'{$email}','{$telefone}');";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function alteraUsuario($id, $nome, $senha, $acesso, $bloqueado, $colaborador, $tipos)
{
  $senhaMd5 = md5($senha);
  return DB::exec("UPDATE usuarios SET nome = :nome,
    senha = :senhaMd5,
    nivel_acesso = :acesso,
    bloqueado = :bloqueado,
    id_colaborador = :colaborador,
    tipos = :tipos
    WHERE id = :id;", [
    ':nome' => $nome,
    ':senhaMd5' => $senhaMd5,
    ':acesso' => $acesso,
    ':bloqueado' => $bloqueado,
    ':colaborador' => $colaborador,
    ':tipos' => $tipos,
    ':id' => $id
  ]);
}

function alteraUsuarioSemSenha($id, $nome, $acesso, $bloqueado, $colaborador, $tipos)
{
  return DB::exec('UPDATE usuarios
            SET nome = :nome,
            nivel_acesso = :acesso,
            bloqueado = :bloqueado,
            id_colaborador = :colaborador,
            tipos = :tipos
    WHERE id = :id;', [
    ':nome' => $nome,
    ':acesso' => $acesso,
    ':bloqueado' => $bloqueado,
    ':colaborador' => $colaborador,
    ':tipos' => $tipos,
    ':id' => $id
  ]);
}


function removeUsuario($id)
{
  $query = "DELETE FROM usuarios WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function alterarBloqueio($id, $bloqueado)
{
  $query = "UPDATE usuarios SET bloqueado={$bloqueado}
  WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

// --Commented out by Inspection START (23/08/2022 14:58):
//function buscaUsuariosOnline()
//{
//  $query = "SELECT * FROM usuarios WHERE online=1 ORDER BY nome;";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// --Commented out by Inspection START (23/08/2022 14:58):
//function buscaUsuariosConferentes()
//{
//  $query = "SELECT * FROM usuarios WHERE nivel_acesso=51 OR nivel_acesso=54";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


function buscaIdClienteVinculadoUsuario($usuario)
{
  $query = "SELECT id_colaborador cliente FROM usuarios WHERE id={$usuario}";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha['cliente'];
}

// function buscaUsuarioComNome($nome)
// {
//   $conexao = Conexao::criarConexao();
//   $query = "SELECT nome FROM usuarios WHERE LOWER(nome)=LOWER('{$nome}');";
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['nome'];
// }

function buscaUsuarioPorEmail($email)
{
  $query = "SELECT * FROM usuarios WHERE email='{$email}'";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

function buscaUsuarioPorCNPJ($cnpj)
{
  $query = "SELECT * FROM usuarios WHERE cnpj='{$cnpj}'";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch(PDO::FETCH_ASSOC);
  return $linha;
}

// --Commented out by Inspection START (23/08/2022 14:58):
//function armazenaTokenUsuario($id, $token)
//{ /*Comentada por jose 04-06-2021*/
//  /*$query = "UPDATE usuarios SET token='{$token}' WHERE id={$id};";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);*/
//  return false;
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


function existeToken($token)
{
  $query = "SELECT * FROM usuarios WHERE token='{$token}'";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

function alteraSenhaUsuario($id, $senha)
{
  $senhaMd5 = md5($senha);
  $query = "UPDATE usuarios SET senha='{$senhaMd5}', token=null WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}
// --Commented out by Inspection START (23/08/2022 14:58):
//function coletaEstatisticaIndicacao($id, $dado_coletado)
//{
//  $query = "INSERT INTO estatistica_indicacao(id_usuario, dado_coletado,data_hora)
//  VALUES({$id},'{$dado_coletado}',NOW())";
//  $conexao = Conexao::criarConexao();
//  return $conexao->exec($query);
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)

// function buscaEstatisticaIndicacao($intervalo = 1)
// {
//   $query = "SELECT 
//     (SELECT COUNT(dado_coletado) FROM estatistica_indicacao  WHERE dado_coletado='cliente' AND data_hora>=DATE_SUB(NOW(), INTERVAL $intervalo MONTH)) cliente,
//     (SELECT COUNT(dado_coletado) FROM estatistica_indicacao  WHERE dado_coletado='facebook' AND data_hora>=DATE_SUB(NOW(), INTERVAL $intervalo MONTH)) facebook,
//     (SELECT COUNT(dado_coletado) FROM estatistica_indicacao  WHERE dado_coletado='google' AND data_hora>=DATE_SUB(NOW(), INTERVAL $intervalo MONTH)) google,
//     (SELECT COUNT(dado_coletado) FROM estatistica_indicacao  WHERE dado_coletado='outros' AND data_hora>=DATE_SUB(NOW(), INTERVAL $intervalo MONTH)) outros,
//     (SELECT COUNT(dado_coletado) FROM estatistica_indicacao  WHERE dado_coletado='instagram' AND data_hora>=DATE_SUB(NOW(), INTERVAL $intervalo MONTH)) instagram
//     FROM estatistica_indicacao LIMIT 1";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha;
// }

// --Commented out by Inspection START (23/08/2022 14:58):
//function buscaDataDeCadastro($id_usuario)
//{
//  $query = "SELECT data_cadastro FROM `colaboradores` WHERE id = '{$id_usuario}'";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha[0];
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// --Commented out by Inspection START (23/08/2022 14:58):
//function buscaBotaoIlimitado($id)
//{
//  $query = "SELECT data_painel_ilimitado FROM colaboradores WHERE id=$id";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha[0];
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// --Commented out by Inspection START (23/08/2022 14:58):
//function buscaIdTelefoneCliente($id_cliente)
//{
//  $query = "SELECT colaboradores.telefone FROM colaboradores WHERE colaboradores.id = {$id_cliente}";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch();
//  return $linha['telefone'];
//}
// --Commented out by Inspection STOP (23/08/2022 14:58)


// function buscaSituacaoPoliticaEmpresa($id_cliente)
// {
//   $query = "SELECT colaboradores.politica_empresa FROM colaboradores WHERE colaboradores.id = {$id_cliente}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['politica_empresa'];
// }
// function confirmaPoliticaEmpres($id_cliente)
// {
//   $query = "UPDATE colaboradores SET colaboradores.politica_empresa = 'T' 
//           WHERE colaboradores.id = {$id_cliente};";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

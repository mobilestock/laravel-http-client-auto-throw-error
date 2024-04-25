<?php
require_once 'conexao.php';
require_once 'estado.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MobileStock\database\Conexao as ConexaoDatabase;

// --Commented out by Inspection START (23/08/2022 15:10):
//function atualizaTabelaPrecoCliente($id_cliente, $tipo_cobranca)
//{
//  $query = "UPDATE colaboradores set tipo_tabela = $tipo_cobranca WHERE id={$id_cliente};";
//  $conexao = Conexao::criarConexao();
//  $conexao->exec($query);
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// --Commented out by Inspection START (23/08/2022 15:10):
//function buscaColaboradorCaixa()
//{
//  $query = "SELECT colaborador_caixa FROM configuracoes;";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  $stmt->execute();
//  $linha = $stmt->fetch();
//  return $linha['colaborador_caixa'];
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// --Commented out by Inspection START (23/08/2022 15:10):
//function buscaTransportadoras()
//{
//  $query = "SELECT * FROM colaboradores WHERE tipo='T' ORDER BY razao_social";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// function buscaUltimoIdColaborador()
// {
//   $query = "SELECT MAX(id) id FROM colaboradores";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   $linha = $stmt->fetch();
//   return $linha['id'];
// }

// function buscaColaboradorCNPJ($cnpj)
// {
//   $query = "SELECT id FROM colaboradores WHERE cnpj='{$cnpj}';";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   if ($linha = $stmt->fetch()) {
//     return $linha['id'];
//   }
//   return;
// }

// function buscaColaboradorCPF($cpf)
// {
//   $query = "SELECT id FROM colaboradores WHERE cpf='{$cpf}';";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   if ($linha = $stmt->fetch()) {
//     return $linha['id'];
//   }
//   return;
// }

// function insereColaborador(
//   $id,
//   $regime,
//   $cnpj,
//   $cpf,
//   $razao_social,
//   $inscricao,
//   $rg,
//   $endereco,
//   $numero,
//   $complemento,
//   $cep,
//   $bairro,
//   $cidade,
//   $uf,
//   $telefone,
//   $telefone2,
//   $email,
//   $observacao,
//   $tipo,
//   $bloqueado,
//   $vendedor,
//   $tipo_tabela,
//   $tipo_documento,
//   $cond_pagamento
// ) {
//   $conexao = Conexao::criarConexao();
//   $query = $conexao->prepare('INSERT INTO COLABORADORES (id, regime, cnpj, cpf, razao_social, inscricao, rg, cep, endereco,
//     numero,complemento,bairro,cidade,uf,telefone,telefone2,email,observacao,bloqueado,tipo,vendedor,tipo_tabela,tipo_documento,cond_pagamento, data_cadastro) VALUES
//     (:id, :regime, :cnpj, :cpf, :razao_social, :inscricao, :rg, :cep, :endereco, :numero, :complemento, :bairro, :cidade, :uf, :telefone,
//     :telefone2, :email, :observacao, :bloqueado, :tipo, :vendedor, :tipo_tabela, :tipo_documento, :cond_pagamento, NOW())'); //adicionei hora de cadasto atual
//   $query->bindParam('id', $id, PDO::PARAM_INT);
//   $query->bindParam('regime', $regime, PDO::PARAM_STR, 1);
//   $query->bindParam('cnpj', $cnpj, PDO::PARAM_STR, 14);
//   $query->bindParam('cpf', $cpf, PDO::PARAM_STR, 11);
//   $query->bindParam('razao_social', $razao_social, PDO::PARAM_STR, 500);
//   $query->bindParam('inscricao', $inscricao, PDO::PARAM_STR, 20);
//   $query->bindParam('rg', $rg, PDO::PARAM_STR, 20);
//   $query->bindParam('endereco', $endereco, PDO::PARAM_STR, 1000);
//   $query->bindParam('numero', $numero, PDO::PARAM_STR, 10);
//   $query->bindParam('complemento', $complemento, PDO::PARAM_STR, 500);
//   $query->bindParam('cep', $cep, PDO::PARAM_STR, 8);
//   $query->bindParam('bairro', $bairro, PDO::PARAM_STR, 200);
//   $query->bindParam('cidade', $cidade, PDO::PARAM_STR, 100);
//   $query->bindParam('uf', $uf, PDO::PARAM_STR, 2);
//   $query->bindParam('telefone', $telefone, PDO::PARAM_STR, 12);
//   $query->bindParam('telefone2', $telefone2, PDO::PARAM_STR, 12);
//   $query->bindParam('email', $email, PDO::PARAM_STR, 500);
//   $query->bindParam('observacao', $observacao, PDO::PARAM_LOB);
//   $query->bindParam('bloqueado', $bloqueado, PDO::PARAM_INT);
//   $query->bindParam('tipo', $tipo, PDO::PARAM_STR, 1);
//   $query->bindParam('vendedor', $vendedor, PDO::PARAM_INT);
//   $query->bindParam('tipo_tabela', $tipo_tabela, PDO::PARAM_INT);
//   $query->bindParam('tipo_documento', $tipo_documento, PDO::PARAM_INT);
//   $query->bindParam('cond_pagamento', $cond_pagamento, PDO::PARAM_INT);
//   return $query->execute() or die(print_r($query->errorInfo(), true));
// }

// function alteraColaborador(
//   $id,
//   $regime,
//   $cnpj,
//   $cpf,
//   $razao_social,
//   $inscricao,
//   $rg,
//   $endereco,
//   $numero,
//   $complemento,
//   $cep,
//   $bairro,
//   $cidade,
//   $uf,
//   $telefone,
//   $telefone2,
//   $email,
//   $observacao,
//   $tipo,
//   $bloqueado
// ) {
//   $conexao = Conexao::criarConexao();
//   $query = $conexao->prepare('UPDATE colaboradores SET regime=:regime, cnpj=:cnpj, cpf=:cpf, razao_social=:razao_social, inscricao=:inscricao,
//              rg=:rg, cep=:cep, endereco=:endereco, numero=:numero, complemento=:complemento,bairro=:bairro, cidade=:cidade, uf=:uf, telefone=:telefone,
//              telefone2=:telefone2, email=:email, observacao= :observacao, bloqueado=:bloqueado, tipo=:tipo WHERE id=:id');
//   $query->bindParam('id', $id, PDO::PARAM_INT);
//   $query->bindParam('regime', $regime, PDO::PARAM_STR, 1);
//   $query->bindParam('cnpj', $cnpj, PDO::PARAM_STR, 14);
//   $query->bindParam('cpf', $cpf, PDO::PARAM_STR, 11);
//   $query->bindParam('razao_social', $razao_social, PDO::PARAM_STR, 500);
//   $query->bindParam('inscricao', $inscricao, PDO::PARAM_STR, 20);
//   $query->bindParam('rg', $rg, PDO::PARAM_STR, 20);
//   $query->bindParam('endereco', $endereco, PDO::PARAM_STR, 1000);
//   $query->bindParam('numero', $numero, PDO::PARAM_STR, 10);
//   $query->bindParam('complemento', $complemento, PDO::PARAM_STR, 500);
//   $query->bindParam('cep', $cep, PDO::PARAM_STR, 8);
//   $query->bindParam('bairro', $bairro, PDO::PARAM_STR, 200);
//   $query->bindParam('cidade', $cidade, PDO::PARAM_STR, 100);
//   $query->bindParam('uf', $uf, PDO::PARAM_STR, 2);
//   $query->bindParam('telefone', $telefone, PDO::PARAM_STR, 12);
//   $query->bindParam('telefone2', $telefone2, PDO::PARAM_STR, 12);
//   $query->bindParam('email', $email, PDO::PARAM_STR, 500);
//   $query->bindParam('observacao', $observacao, PDO::PARAM_LOB);
//   $query->bindParam('bloqueado', $bloqueado, PDO::PARAM_INT);
//   $query->bindParam('tipo', $tipo, PDO::PARAM_STR, 1);
//   return $query->execute() or die(print_r($query->errorInfo(), true));
// }

// function insereFornecedor(
//   $id,
//   $regime,
//   $cnpj,
//   $cpf,
//   $razao_social,
//   $inscricao,
//   $rg,
//   $endereco,
//   $numero,
//   $cep,
//   $bairro,
//   $cidade,
//   $uf,
//   $telefone,
//   $telefone2,
//   $email,
//   $observacao,
//   $tipo,
//   $bloqueado,
//   $usuario
// ) {
//   $conexao = Conexao::criarConexao();
//   $query = $conexao->prepare('INSERT INTO COLABORADORES (id, regime, cnpj, cpf, razao_social, inscricao, rg, cep, endereco,
//     numero,bairro,cidade,uf,telefone,telefone2,email,observacao,bloqueado,tipo,usuario) VALUES
//     (:id, :regime, :cnpj, :cpf, :razao_social, :inscricao, :rg, :cep, :endereco, :numero, :bairro, :cidade, :uf, :telefone,
//     :telefone2, :email, :observacao, :bloqueado, :tipo, :usuario)');
//   $query->bindParam('id', $id, PDO::PARAM_INT);
//   $query->bindParam('regime', $regime, PDO::PARAM_STR, 1);
//   $query->bindParam('cnpj', $cnpj, PDO::PARAM_STR, 14);
//   $query->bindParam('cpf', $cpf, PDO::PARAM_STR, 11);
//   $query->bindParam('razao_social', $razao_social, PDO::PARAM_STR, 500);
//   $query->bindParam('inscricao', $inscricao, PDO::PARAM_STR, 20);
//   $query->bindParam('rg', $rg, PDO::PARAM_STR, 20);
//   $query->bindParam('endereco', $endereco, PDO::PARAM_STR, 1000);
//   $query->bindParam('numero', $numero, PDO::PARAM_STR, 10);
//   $query->bindParam('cep', $cep, PDO::PARAM_STR, 8);
//   $query->bindParam('bairro', $bairro, PDO::PARAM_STR, 200);
//   $query->bindParam('cidade', $cidade, PDO::PARAM_STR, 100);
//   $query->bindParam('uf', $uf, PDO::PARAM_STR, 2);
//   $query->bindParam('telefone', $telefone, PDO::PARAM_STR, 12);
//   $query->bindParam('telefone2', $telefone2, PDO::PARAM_STR, 12);
//   $query->bindParam('email', $email, PDO::PARAM_STR, 500);
//   $query->bindParam('observacao', $observacao, PDO::PARAM_LOB);
//   $query->bindParam('bloqueado', $bloqueado, PDO::PARAM_INT);
//   $query->bindParam('tipo', $tipo, PDO::PARAM_STR, 1);
//   $query->bindParam('usuario', $usuario, PDO::PARAM_STR, 255);
//   return $query->execute() or die(print_r($query->errorInfo(), true));
// }

// --Commented out by Inspection START (23/08/2022 15:10):
//function alteraFornecedor(
//  $id,
//  $regime,
//  $cnpj,
//  $cpf,
//  $razao_social,
//  $inscricao,
//  $rg,
//  $endereco,
//  $numero,
//  $cep,
//  $bairro,
//  $cidade,
//  $uf,
//  $telefone,
//  $telefone2,
//  $email,
//  $observacao,
//  $tipo,
//  $bloqueado,
//  $usuario
//) {
//  $conexao = Conexao::criarConexao();
//  $query = $conexao->prepare('UPDATE colaboradores SET regime=:regime, cnpj=:cnpj, cpf=:cpf, razao_social=:razao_social, inscricao=:inscricao,
//             rg=:rg, cep=:cep, endereco=:endereco, numero=:numero,bairro=:bairro, cidade=:cidade, uf=:uf, telefone=:telefone,
//             telefone2=:telefone2, email=:email, observacao= :observacao, bloqueado=:bloqueado, tipo=:tipo, usuario=:usuario WHERE id=:id');
//  $query->bindParam('id', $id, PDO::PARAM_INT);
//  $query->bindParam('regime', $regime, PDO::PARAM_STR, 1);
//  $query->bindParam('cnpj', $cnpj, PDO::PARAM_STR, 14);
//  $query->bindParam('cpf', $cpf, PDO::PARAM_STR, 11);
//  $query->bindParam('razao_social', $razao_social, PDO::PARAM_STR, 500);
//  $query->bindParam('inscricao', $inscricao, PDO::PARAM_STR, 20);
//  $query->bindParam('rg', $rg, PDO::PARAM_STR, 20);
//  $query->bindParam('endereco', $endereco, PDO::PARAM_STR, 1000);
//  $query->bindParam('numero', $numero, PDO::PARAM_STR, 10);
//  $query->bindParam('cep', $cep, PDO::PARAM_STR, 8);
//  $query->bindParam('bairro', $bairro, PDO::PARAM_STR, 200);
//  $query->bindParam('cidade', $cidade, PDO::PARAM_STR, 100);
//  $query->bindParam('uf', $uf, PDO::PARAM_STR, 2);
//  $query->bindParam('telefone', $telefone, PDO::PARAM_STR, 12);
//  $query->bindParam('telefone2', $telefone2, PDO::PARAM_STR, 12);
//  $query->bindParam('email', $email, PDO::PARAM_STR, 500);
//  $query->bindParam('observacao', $observacao, PDO::PARAM_LOB);
//  $query->bindParam('bloqueado', $bloqueado, PDO::PARAM_INT);
//  $query->bindParam('tipo', $tipo, PDO::PARAM_STR, 1);
//  $query->bindParam('usuario', $usuario, PDO::PARAM_STR, 255);
//  return $query->execute() or die(print_r($query->errorInfo(), true));
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// function insereTransportadora(
//   $razao_social,
//   $endereco,
//   $numero,
//   $cep,
//   $bairro,
//   $cidade,
//   $uf,
//   $telefone,
//   $telefone2,
//   $email,
//   $observacao,
//   $linkRastreio,
//   $tipo,
//   $bloqueado,
//   $usuario,
//   $notaFiscal,
//   $tipoEnvio,
//   $tipoPagFrete
// ) {
//   $conexao = Conexao::criarConexao();
//   $query = $conexao->prepare('INSERT INTO COLABORADORES (razao_social, cep, endereco,
//     numero,bairro,cidade,uf,telefone,telefone2,email,observacao,link_rastreio,bloqueado,tipo,usuario, emite_nota, tipo_envio, tipo_pagamento_frete, regime) VALUES
//     (:id, :razao_social, :cep, :bairro, :numero, :bairro, :cidade, :uf, :telefone,
//     :telefone2, :email, :observacao,:link_rastreio, :bloqueado, :tipo, :usuario, :emite_nota, :tipo_envio, :tipo_pagamento_frete, :regime)');
//   $query->bindParam('razao_social', $razao_social, PDO::PARAM_STR, 500);
//   $query->bindParam('endereco', $endereco, PDO::PARAM_STR, 1000);
//   $query->bindParam('numero', $numero, PDO::PARAM_STR, 10);
//   $query->bindParam('cep', $cep, PDO::PARAM_STR, 8);
//   $query->bindParam('bairro', $bairro, PDO::PARAM_STR, 200);
//   $query->bindParam('cidade', $cidade, PDO::PARAM_STR, 100);
//   $query->bindParam('uf', $uf, PDO::PARAM_STR, 2);
//   $query->bindParam('telefone', $telefone, PDO::PARAM_STR, 12);
//   $query->bindParam('telefone2', $telefone2, PDO::PARAM_STR, 12);
//   $query->bindParam('email', $email, PDO::PARAM_STR, 500);
//   $query->bindParam('observacao', $observacao, PDO::PARAM_LOB);
//   $query->bindParam('link_rastreio', $linkRastreio, PDO::PARAM_STR, 1000);
//   $query->bindParam('bloqueado', $bloqueado, PDO::PARAM_INT);
//   $query->bindParam('tipo', $tipo, PDO::PARAM_STR, 1);
//   $query->bindParam('usuario', $usuario, PDO::PARAM_STR, 255);
//   $query->bindParam('emite_nota', $notaFiscal, PDO::PARAM_INT);
//   $query->bindParam('tipo_envio', $tipoEnvio, PDO::PARAM_INT);
//   $query->bindParam('tipo_pagamento_frete', $tipoPagFrete, PDO::PARAM_INT);
//   $query->bindParam('regime', 3, PDO::PARAM_INT);

//   return $query->execute() or die(print_r($query->errorInfo(), true));
// }

// function alteraTransportadora(
//   $id,
//   $razao_social,
//   $endereco,
//   $numero,
//   $cep,
//   $bairro,
//   $id_cidade,
//   $uf,
//   $telefone,
//   $telefone2,
//   $email,
//   $observacao,
//   $linkRastreio,
//   $tipo,
//   $bloqueado,
//   $usuario,
//   $notaFiscal,
//   $tipoEnvio,
//   $tipoPagFrete
// ) {
//   $conexao = Conexao::criarConexao();
//   $query = $conexao->prepare('UPDATE colaboradores SET razao_social=:razao_social, cep=:cep, endereco=:endereco, numero=:numero,
//              bairro=:bairro, id_cidade=:cidade, telefone=:telefone, telefone2=:telefone2, email=:email, observacao= :observacao,link_rastreio=:link_rastreio,
//              bloqueado=:bloqueado, tipo=:tipo, usuario=:usuario, emite_nota=:emite_nota, tipo_envio=:tipo_envio, tipo_pagamento_frete=:tipo_pagamento_frete WHERE id=:id');
//   $query->bindParam('id', $id, PDO::PARAM_INT);
//   $query->bindParam('razao_social', $razao_social, PDO::PARAM_STR, 500);
//   $query->bindParam('endereco', $endereco, PDO::PARAM_STR, 1000);
//   $query->bindParam('numero', $numero, PDO::PARAM_STR, 10);
//   $query->bindParam('cep', $cep, PDO::PARAM_STR, 8);
//   $query->bindParam('bairro', $bairro, PDO::PARAM_STR, 200);
//   $query->bindParam('cidade', $id_cidade, PDO::PARAM_STR, 100);
//   $query->bindParam('telefone', $telefone, PDO::PARAM_STR, 12);
//   $query->bindParam('telefone2', $telefone2, PDO::PARAM_STR, 12);
//   $query->bindParam('email', $email, PDO::PARAM_STR, 500);
//   $query->bindParam('observacao', $observacao, PDO::PARAM_LOB);
//   $query->bindParam('link_rastreio', $linkRastreio, PDO::PARAM_STR, 1000);
//   $query->bindParam('bloqueado', $bloqueado, PDO::PARAM_INT);
//   $query->bindParam('tipo', $tipo, PDO::PARAM_STR, 1);
//   $query->bindParam('usuario', $usuario, PDO::PARAM_STR, 255);
//   $query->bindParam('emite_nota', $notaFiscal, PDO::PARAM_INT);
//   $query->bindParam('tipo_envio', $tipoEnvio, PDO::PARAM_INT);
//   $query->bindParam('tipo_pagamento_frete', $tipoPagFrete, PDO::PARAM_INT);
//   return $query->execute() or die(print_r($query->errorInfo(), true));
// }

// function inserePainel($id)
// {
//   $query = "INSERT INTO pedido (id_cliente) VALUES ({$id});";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

// --Commented out by Inspection START (23/08/2022 15:10):
//function listaColaboradores()
//{
//  $query = "SELECT * FROM colaboradores ORDER BY razao_social";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $lista = $resultado->fetchAll();
//  return $lista;
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// function listaColaboradoresPorTipo($tipo)
// {
//   $query = "SELECT id, razao_social FROM colaboradores WHERE tipo = '{$tipo}' ORDER BY razao_social";
//   $conexao = ConexaoDatabase::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado->fetchAll(PDO::FETCH_ASSOC);
// }

function listaPessoas()
{
    $query = 'SELECT * FROM colaboradores ORDER BY razao_social';
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}

function listaFornecedores()
{
    $query = "SELECT * FROM colaboradores WHERE TIPO='F' ORDER BY razao_social";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}
// --Commented out by Inspection START (23/08/2022 15:10):
//function listaFornecedoresComIDZoop()
//{
//  $query = "SELECT
//    colaboradores.id,
//      colaboradores.razao_social,
//      COALESCE( ( SELECT 1 FROM api_colaboradores WHERE api_colaboradores.id_colaborador = colaboradores.id ),0) zoop
//  FROM colaboradores WHERE TIPO='F' ORDER BY razao_social";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  $stmt->execute();
//  return $stmt->fetchAll(PDO::FETCH_ASSOC);
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// function listaTransportadoras()
// {
//   $query = "SELECT * FROM colaboradores WHERE TIPO='T' ORDER BY RAZAO_SOCIAL";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
//   return $stmt->fetchAll();
// }

// function filtrarFornecedoresPagina($pagina, $itens, $filtro)
// {
//   $query = "SELECT * from colaboradores " . $filtro . " AND tipo='F' ORDER BY razao_social LIMIT {$pagina},{$itens}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function filtrarTransportadorasPagina($pagina, $itens, $filtro)
// {
//   $query = "SELECT * from colaboradores " . $filtro . " AND tipo='T' ORDER BY razao_social LIMIT {$pagina},{$itens}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

function buscaColaborador($id)
{
    $query = "SELECT * FROM colaboradores WHERE id={$id}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    if ($stmt->execute()) {
        return $stmt->fetch();
    }
    return;
}

// --Commented out by Inspection START (23/08/2022 15:10):
//function listaVendedores()
//{
//  $query = "SELECT * FROM usuarios
//    WHERE (nivel_acesso=51 OR nivel_acesso=56 OR nivel_acesso=57);";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  $stmt->execute();
//  return $stmt->fetchAll();
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

function buscaCliente($cliente)
{
    $query = "SELECT * FROM colaboradores WHERE id={$cliente}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    return $stmt->fetch();
}

function buscaClienteBloqueado($cliente)
{
    $query = "SELECT em_uso from colaboradores WHERE id={$cliente}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $linha = $stmt->fetch();
    return $linha['em_uso'];
}

function bloqueiaCliente($id_cliente, $usuario)
{
    $query = "UPDATE colaboradores SET em_uso={$usuario} WHERE id={$id_cliente}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
}

function desbloqueiaClientes(int $usuario)
{
    $query = "UPDATE colaboradores SET em_uso=0 WHERE em_uso={$usuario}";
    $conexao = ConexaoDatabase::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
}

function buscaNomeUsuarioClienteBloqueado($usuario)
{
    $query = "SELECT c.em_uso, u.nome usuario from colaboradores c
  INNER JOIN usuarios u ON (u.id = c.em_uso)
  WHERE u.id={$usuario}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $linha = $stmt->fetch();
    return $linha['usuario'];
}

// --Commented out by Inspection START (23/08/2022 15:10):
//function buscaTipoCobranca($cliente)
//{
//  $query = "SELECT c.tipo_tabela tipo_cobranca from colaboradores c
//  WHERE c.id={$cliente}";
//  $conexao = Conexao::criarConexao();
//  $stmt = $conexao->prepare($query);
//  $stmt->execute();
//  $linha = $stmt->fetch();
//  return $linha['tipo_cobranca'];
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// function filtrarColaboradores($filtro)
// {
//   $query = "SELECT * from colaboradores " . $filtro . " AND tipo='C'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function filtrarColaboradoresPagina($pagina, $itens, $filtro)
// {
//   $query = "SELECT * from colaboradores " . $filtro . " AND tipo = 'C' ORDER BY id DESC LIMIT {$pagina},{$itens}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function existeCNPJCadastrado($cnpj)
// {
//   $query = "SELECT cnpj FROM colaboradores where cnpj='{$cnpj}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $cnpj = $resultado->fetch();
//   return $cnpj['cnpj'];
// }
// function existeCPFCadastrado($cpf)
// {
//   $query = "SELECT cpf FROM colaboradores where cpf='{$cpf}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $cpf = $resultado->fetch();
//   return $cpf['cpf'];
// }
// function existeRazaoSocialCadastrado($razao_social)
// {
//   $query = "SELECT razao_social FROM colaboradores where razao_social='{$razao_social}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $razao_social = $resultado->fetch();
//   return $razao_social['razao_social'];
// }
// function existeTelefoneCadastrado($telefone)
// {
//   $query = "SELECT telefone FROM colaboradores where telefone='{$telefone}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $telefone = $resultado->fetch();
//   return $telefone['telefone'];
// }
// function existeTelefone2Cadastrado($telefone2)
// {
//   $query = "SELECT telefone2 FROM colaboradores where telefone2='{$telefone2}'";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $telefone2 = $resultado->fetch();
//   return $telefone2['telefone2'];
// }

// function buscaTabelaCliente($id_cliente)
// {
//   $query = "SELECT tipo_tabela FROM colaboradores where id={$id_cliente}";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $id_tabela = $resultado->fetch();
//   return $id_tabela['tipo_tabela'];
// }

// function atualizarEnderecoCliente($id_cliente, $endereco, $numero, $cep, $bairro, $cidade, $uf, $telefone, $telefone2, $email)
// {
//   $query = "UPDATE colaboradores SET
//   endereco='{$endereco}',
//   numero='{$numero}',
//   cep='{$cep}',
//   bairro='{$bairro}',
//   cidade='{$cidade}',
//   uf='{$uf}',
//   telefone='{$telefone}',
//   telefone2='{$telefone2}',
//   email='{$email}' WHERE id={$id_cliente}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   $stmt->execute();
// }

// --Commented out by Inspection START (23/08/2022 15:10):
//function buscaIdFornecedorProduto($id_produto)
//{
//  $query = "SELECT id_fornecedor, descricao  FROM produtos WHERE id={$id_produto};";
//  $conexao = Conexao::criarConexao();
//  $resultado = $conexao->query($query);
//  $fornecedor = $resultado->fetch();
//  return $fornecedor;
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

// --Commented out by Inspection START (23/08/2022 15:10):
//class ColaboradoresHistorico
//{
//  private $id;
//  private $campo;
//  public function setCampo($id, $campo = null)
//  {
//    $this->id = $id;
//    $this->campo = $campo;
//  }
//  public function buscaCampo()
//  {
//    $query = "SELECT * FROM colaboradores_temp WHERE id_colaborador = '{$this->id}' AND campo ='$this->campo' ORDER BY data_edicao DESC LIMIT 1;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $colaborador = $resultado->fetch();
//    return $colaborador;
//  }
//  public function buscaEditados($id)
//  {
//    $query = "SELECT campo FROM colaboradores_temp WHERE id_colaborador = '{$id}';";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $colaborador = $resultado->fetch();
//    return $colaborador;
//  }
//}
// --Commented out by Inspection STOP (23/08/2022 15:10)

function EstatisticaClienteNovo($ano = 0, $mes = 0)
{
    if ($ano == 0 && $mes == 0) {
        $data = date('Y-m');
    } else {
        $data = "$ano-$mes";
    }

    $query = "SELECT COUNT(data_cadastro) users FROM colaboradores WHERE data_cadastro>='$data-01' AND data_cadastro<='$data-31'";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $colaborador = $resultado->fetch();
    return $colaborador;
}

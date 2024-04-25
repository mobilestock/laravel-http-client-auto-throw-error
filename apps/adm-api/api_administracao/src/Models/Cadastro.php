<?php

namespace api_administracao\Models;
use MobileStock\model\Usuario;
use pdo;

class Cadastro
{
    public static $consultaBancoPainel = [];

    public static function buscaPermissaoAcesso(PDO $conexao, int $idUsuario)
    {
        $sql = "SELECT usuarios.id, usuarios.permissao, usuarios.data_atualizacao
					FROM usuarios
						WHERE usuarios.id=$idUsuario";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado;
    }
    public static function hasPermissaoAcesso(PDO $conexao, int $idUsuario, string $permissao)
    {
        $sql = "SELECT usuarios.id
					FROM usuarios
						WHERE usuarios.id=$idUsuario AND permissao LIKE '%$permissao%' ";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? true : false;
    }
    public static function alteraPermissaoAcesso(PDO $conexao, int $idUsuario, string $permissao)
    {
        $sql = "UPDATE usuarios SET usuarios.permissao = '{$permissao}' WHERE usuarios.id=$idUsuario";
        $stmt = $conexao->prepare($sql);
        return $stmt->execute();
    }
    public static function buscaCadastros(PDO $conexao)
    {
        $sql = "SELECT 	usuarios.id,
						colaboradores.razao_social,
						colaboradores.regime,
						colaboradores.cpf,
						colaboradores.cnpj,
						colaboradores.tipo,
						usuarios.email,
						usuarios.telefone,
						usuarios.id_colaborador,
						usuarios.token,
						usuarios.nome,
						usuarios.nivel_acesso,
						DATE_FORMAT(usuarios.data_atualizacao,'%d/%m/%Y %H:%i:%s')data_atualizacao,
						DATE_FORMAT(usuarios.data_cadastro,'%d/%m/%Y %H:%i:%s')data_cadastro,
						usuarios.permissao
							FROM usuarios INNER JOIN colaboradores ON(usuarios.id_colaborador =  colaboradores.id)
								WHERE colaboradores.bloqueado = 0 LIMIT 20";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }

    public static function buscaAcessos(PDO $conexao, $lista)
    {
        $sql = "SELECT 	nivel_permissao.id,
						nivel_permissao.nome,
						nivel_permissao.nivel_value,
						nivel_permissao.categoria,
						nivel_permissao.subacesso
						FROM nivel_permissao
							WHERE nivel_permissao.nivel_value NOT IN {$lista}";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $permissao = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $permissao;
    }

    public static function editaTipoAcessoPrincipal(PDO $conexao, int $idCliente, string $tipo)
    {
        $query = "UPDATE colaboradores SET colaboradores.tipo = '{$tipo}' WHERE colaboradores.id = $idCliente";
        $stmt = $conexao->prepare($query);
        $resposta = $stmt->execute();
        return $resposta;
    }

    public static function infoIuguInativo(PDO $conexao, int $idColaborador, int $idConta)
    {
        $sql = "SELECT api_colaboradores_inativo.id_zoop,
					api_colaboradores_inativo.id_iugu,
					api_colaboradores_inativo.iugu_token_user,
					api_colaboradores_inativo.iugu_token_teste,
					api_colaboradores_inativo.iugu_token_live
						FROM api_colaboradores_inativo
							WHERE api_colaboradores_inativo.id_colaborador = $idColaborador
								AND api_colaboradores_inativo.id = $idConta";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id_zoop'];
    }

    public static function buscaIdIugu(PDO $conexao, int $idColaborador)
    {
        $sql = "SELECT api_colaboradores.id_iugu FROM api_colaboradores WHERE api_colaboradores.id_colaborador = $idColaborador AND api_colaboradores.id_iugu <> '' AND api_colaboradores.id_iugu IS NOT NULL";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id_iugu'];
    }

    public static function buscaContaVerificada(PDO $conexao, int $idColaborador)
    {
        $sql = "SELECT api_colaboradores.conta_iugu_verificada FROM api_colaboradores WHERE api_colaboradores.conta_iugu_verificada = 'T' AND api_colaboradores.id_colaborador = $idColaborador AND api_colaboradores.id_iugu <> '' AND api_colaboradores.id_iugu IS NOT NULL";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['conta_iugu_verificada'];
    }

    public static function buscaContaPrincipal_(PDO $conexao, int $idContaBancaria, int $idColaborador): array
    {
        $permissaoFornecedor = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;

        $sql = "SELECT
					conta_bancaria_colaboradores.conta,
					conta_bancaria_colaboradores.agencia,
					conta_bancaria_colaboradores.cpf_titular,
					conta_bancaria_colaboradores.nome_titular,
					conta_bancaria_colaboradores.id,
					conta_bancaria_colaboradores.prioridade,
				(
					SELECT bancos.nome
						FROM bancos
							WHERE bancos.cod_banco = conta_bancaria_colaboradores.id_banco
				)banco,
				EXISTS(
					SELECT 1
					FROM usuarios
					WHERE usuarios.id_colaborador = :idColaborador
					AND usuarios.permissao REGEXP '$permissaoFornecedor'
                ) AS `eh_fornecedor`
					FROM conta_bancaria_colaboradores
						WHERE conta_bancaria_colaboradores.id = :idContaBancaria
						 ORDER BY id DESC LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->bindValue(':idContaBancaria', $idContaBancaria, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $resultado['eh_fornecedor'] = (bool) $resultado['eh_fornecedor'];
        return $resultado;
    }
}
?>

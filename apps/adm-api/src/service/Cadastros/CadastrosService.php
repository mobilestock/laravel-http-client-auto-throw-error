<?php

namespace MobileStock\service\Cadastros;

use Exception;
use PDO;

class CadastrosService
{
    public static function cadastraSenhaTemporaria(PDO $conexao, int $idColaborador, string $senha): void
    {
        $senha = password_hash($senha, PASSWORD_ARGON2ID);
        $sql = $conexao->prepare(
            "UPDATE usuarios SET
                usuarios.senha_temporaria = :senha_temporaria,
                usuarios.data_senha_temporaria = NOW()
            WHERE usuarios.id_colaborador = :id_colaborador;"
        );
        $sql->bindValue(':senha_temporaria', $senha, PDO::PARAM_STR);
        $sql->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() < 1) {
            throw new Exception('Cadastro de senha temporária falhou, consulte a equipe de T.I.');
        }
    }
    public static function existeEmail(PDO $conexao, string $email): bool
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM colaboradores
            WHERE colaboradores.email = :email
            LIMIT 1;"
        );
        $sql->bindValue(':email', $email);
        $sql->execute();
        $existe = (bool) $sql->fetchColumn();

        return !$existe;
    }
    public static function existeUser(PDO $conn, string $cnpj)
    {
        $sql = "SELECT usuarios.nome,
                            usuarios.id,
                            usuarios.id_colaborador
                                FROM usuarios
                                    WHERE usuarios.cnpj = '{$cnpj}' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id'] ? $resultado : 0;
    }
    // public static function existeUserID(PDO $conn, int $id){
    //     $sql= "SELECT usuarios.nome,
    //                         usuarios.id,
    //                         usuarios.id_colaborador
    //                             FROM usuarios
    //                                 WHERE usuarios.id_colaborador = $id LIMIT 1";
    //     $stmt = $conn->prepare($sql);
    //     $stmt->execute();
    //     $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return ($resultado['id'] ? $resultado : 0);
    // }
    public static function existeColaborador(PDO $conn, string $cnpj)
    {
        $sql = "SELECT colaboradores.razao_social,
                        (
                            SELECT api_colaboradores.first_name
                                FROM api_colaboradores
                                    WHERE api_colaboradores.id_colaborador=colaboradores.id
                        )nome,
                        colaboradores.id,
                        colaboradores.cnpj,
                        colaboradores.cpf
                            FROM colaboradores
                                WHERE colaboradores.cnpj = '{$cnpj}' or colaboradores.cnpj = '{$cnpj}' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id'] ? $resultado : 0;
    }
    public static function existeApiColaborador(PDO $conn, string $cnpj)
    {
        $sql = "SELECT api_colaboradores.first_name,
                        api_colaboradores.last_name,
                        api_colaboradores.id_colaborador,
                        api_colaboradores.ein,
                        api_colaboradores.taxpayer_id
                            FROM api_colaboradores
                                WHERE api_colaboradores.ein = '{$cnpj}' or
                                    api_colaboradores.taxpayer_id = '{$cnpj}' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['id_colaborador'] ? $resultado : 0;
    }

    public static function editPassword(PDO $conn, string $password, int $idUser): bool
    {
        $hashPass = password_hash($password, PASSWORD_ARGON2ID);

        $sql = $conn->prepare(
            "UPDATE usuarios
            SET usuarios.senha = :hashPass
            WHERE usuarios.id = :id_user"
        );

        $sql->bindValue(':id_user', $idUser, PDO::PARAM_INT);
        $sql->bindValue(':hashPass', $hashPass, PDO::PARAM_STR);

        $sql->execute();

        if ($sql->rowCount() === 0) {
            throw new Exception('Erro ao alterar a senha');
        }
        return true;
    }

    public static function buscaNome(PDO $conn, int $id)
    {
        $query = "SELECT razao_social FROM colaboradores WHERE colaboradores.id = $id";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado;
    }

    public static function verificarDadosFaltantes(PDO $conexao, int $idColaborador): array
    {
        $stmt = $conexao->prepare(
            "SELECT usuarios.senha IS NULL `falta_senha`,
                COALESCE(colaboradores.email, usuarios.email, '') = '' `falta_email`,
                COALESCE(colaboradores.cnpj, usuarios.cnpj, '') = '' `falta_cnpj`,
                COALESCE(colaboradores.cpf, '') = '' `falta_cpf`
            FROM colaboradores
            INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
            WHERE colaboradores.id = :idColaborador"
        );
        $stmt->bindValue(':idColaborador', $idColaborador, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($resultado)) {
            throw new Exception('Usuário não encontrado');
        }
        $resultado['falta_email'] = (bool) $resultado['falta_email'];
        $resultado['falta_senha'] = (bool) $resultado['falta_senha'];
        $resultado['falta_cpf'] = (bool) $resultado['falta_cpf'];
        $resultado['falta_cnpj'] = (bool) $resultado['falta_cnpj'];
        return $resultado;
    }
}

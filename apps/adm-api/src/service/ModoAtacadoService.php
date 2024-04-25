<?php

namespace MobileStock\service;

use MobileStock\repository\ColaboradoresRepository;
use PDO;

class ModoAtacadoService
{
    public static function gerenciaModoAtacado(PDO $conexao, int $idUsuario, bool $ativar): void
    {
        $ativar
            ? ColaboradoresRepository::adicionaPermissaoUsuario($conexao, $idUsuario, [13])
            : ColaboradoresRepository::removePermissaoUsuario($idUsuario, [13]);
    }

    public static function verificaModoAtacadoAtivado(PDO $conexao, int $idUsuario): bool
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM usuarios
            WHERE usuarios.id = :idUsuario
            AND usuarios.permissao REGEXP '13'"
        );
        $sql->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
        $estaAtivado = $sql->fetchColumn();
        return $estaAtivado;
    }
}

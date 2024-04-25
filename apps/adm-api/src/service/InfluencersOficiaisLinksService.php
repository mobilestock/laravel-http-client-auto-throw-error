<?php

namespace MobileStock\service;

use Exception;
use PDO;

class InfluencersOficiaisLinksService
{
    // public static function toggleSituacaoUsuario(PDO $conexao, $idUsuario) {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             influencers_oficiais_links.id,
    //             influencers_oficiais_links.situacao
    //         FROM influencers_oficiais_links
    //         WHERE influencers_oficiais_links.id_usuario = :idUsuario"
    //     );
    //     $stmt->execute([':idUsuario' => $idUsuario]);
    //     $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (empty($usuario)) throw new Exception('Registro de usuário inexistente');

    //     $novaSituacao = 'RE';
    //     if ($usuario['situacao'] === 'RE') $novaSituacao = 'CR';

    //     $stmt = $conexao->prepare(
    //         "UPDATE influencers_oficiais_links
    //         SET influencers_oficiais_links.situacao = :novaSituacao
    //         WHERE influencers_oficiais_links.id_usuario = :idUsuario"
    //     );

    //     $stmt->execute([
    //         ':idUsuario' => $idUsuario,
    //         ':novaSituacao' => $novaSituacao
    //     ]);

    //     if ($stmt->rowCount() === 0) throw new Exception('Falha ao atualizar!');
    //     return true;
    // }

    public static function buscaDadosInfluencerOficialPorHash(\PDO $conexao, $hash)
    {
        $stmt = $conexao->prepare(
            "SELECT
                usuarios.id,
                colaboradores.razao_social,
                colaboradores.usuario_meulook,
                COALESCE(colaboradores.foto_perfil, '" . $_ENV['URL_MOBILE'] . "images/avatar-padrao-mobile.jpg') foto,
                LENGTH(COALESCE(colaboradores.email, '')) > 0 AND LENGTH(COALESCE(usuarios.email, '')) > 0 possui_email,
                LENGTH(COALESCE(usuarios.senha, '')) > 0 possui_senha
            FROM usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            INNER JOIN influencers_oficiais_links ON influencers_oficiais_links.id_usuario = usuarios.id
            WHERE influencers_oficiais_links.hash = :hash"
        );
        $stmt->execute([':hash' => $hash]);

        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$consulta) throw new Exception("Hash inválido!");

        $consulta['possui_email'] = (bool) $consulta['possui_email'];
        $consulta['possui_senha'] = (bool) $consulta['possui_senha'];

        return $consulta;
    }

    // public static function criarLink(\PDO $conexao, int $idUsuarioPonto, string $usuarioMeulook)
    // {
    //     $randomHash = "{$usuarioMeulook}_" . bin2hex($idUsuarioPonto);
    //     $model = new InfluencersOficiaisLinks();
    //     $geradorSQL = new GeradorSql($model->hidratar(['id_usuario' => $idUsuarioPonto, 'hash' => $randomHash]));
    //     $conexao->prepare($geradorSQL->insert())->execute($geradorSQL->bind);
    //     return $randomHash;
    // }
}

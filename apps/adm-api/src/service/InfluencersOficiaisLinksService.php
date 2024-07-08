<?php

namespace MobileStock\service;

use Exception;
use PDO;

class InfluencersOficiaisLinksService
{
    public static function buscaDadosInfluencerOficialPorHash(PDO $conexao, $hash)
    {
        $stmt = $conexao->prepare(
            "SELECT
                usuarios.id,
                colaboradores.razao_social,
                colaboradores.usuario_meulook,
                COALESCE(colaboradores.foto_perfil, '" .
                $_ENV['URL_MOBILE'] .
                "images/avatar-padrao-mobile.jpg') foto,
                LENGTH(COALESCE(colaboradores.email, '')) > 0 AND LENGTH(COALESCE(usuarios.email, '')) > 0 possui_email,
                LENGTH(COALESCE(usuarios.senha, '')) > 0 possui_senha
            FROM usuarios
            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
            INNER JOIN influencers_oficiais_links ON influencers_oficiais_links.id_usuario = usuarios.id
            WHERE influencers_oficiais_links.hash = :hash"
        );
        $stmt->execute([':hash' => $hash]);

        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$consulta) {
            throw new Exception('Hash inválido!');
        }

        $consulta['possui_email'] = (bool) $consulta['possui_email'];
        $consulta['possui_senha'] = (bool) $consulta['possui_senha'];

        return $consulta;
    }
}

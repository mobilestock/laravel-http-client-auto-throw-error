<?php

namespace MobileStock\service;

class CartoesSenhasService
{
    public static function consultaChaveAtual(\PDO $conexao): array
    {
        return $conexao->query(
            "SELECT 
                cartoes_senhas.chave_publica,
                cartoes_senhas.chave_privada
            FROM cartoes_senhas
            ORDER BY cartoes_senhas.id DESC
            LIMIT 1;"
        )->fetch(\PDO::FETCH_ASSOC);
    }

    public static function buscaChavePrivadaPorChavePublica(\PDO $conexao, string $chavePublica): string
    {
        $stmt = $conexao->prepare(
            "SELECT
                cartoes_senhas.chave_privada
            FROM cartoes_senhas
            WHERE cartoes_senhas.chave_publica = :chave_publica
            LIMIT 1"
        );
        
        $stmt->execute([
            ':chave_publica' => $chavePublica
        ]);

        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC)['chave_privada'];

        if(is_null($resultado)) {
            throw new \DomainException('Cartão inválido');
        }
        return $resultado;
    }
}

<?php

namespace MobileStock\repository;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampanhasRepository
{
    public static function criarCampanha(string $urlPagina, string $urlImagem): void
    {
        $idUsuario = Auth::user()->id;
        DB::insert(
            'INSERT INTO campanhas (
                campanhas.url_pagina,
                campanhas.url_imagem,
                campanhas.id_usuario
            ) VALUES (
                :urlPagina,
                :urlImagem,
                :idUsuario
            );',
            ['urlPagina' => $urlPagina, 'urlImagem' => $urlImagem, 'idUsuario' => $idUsuario]
        );
    }

    public static function deletarCampanha(int $idCampanha): void
    {
        $rowCount = DB::delete('DELETE FROM campanhas WHERE id = ?', [$idCampanha]);

        if ($rowCount !== 1) {
            throw new \DomainException('Ocorreu um problema ao deletar campanha');
        }
    }

    public static function buscaUltimaCampanha(): ?array
    {
        $campanha = DB::selectOne(
            "SELECT campanhas.id,
                campanhas.url_pagina,
                campanhas.url_imagem
            FROM campanhas
            ORDER BY campanhas.id DESC
            LIMIT 1"
        );
        if (empty($campanha)) {
            return null;
        }
        return $campanha;
    }
}

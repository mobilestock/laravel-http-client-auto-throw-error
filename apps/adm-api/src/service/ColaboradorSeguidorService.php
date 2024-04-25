<?php
/*

namespace MobileStock\service;

use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\GeradorSql;
use MobileStock\model\ColaboradorSeguidor;
use PDO;

class ColaboradorSeguidorService extends ColaboradorSeguidor
{
    public function salva(\PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
		$sql = $geradorSql->insertFromDual(['id_colaborador_seguindo', 'id_colaborador']);

		$conexao->exec($sql);

		$this->id = $conexao->lastInsertId();
    }

    public function deleta(\PDO $conexao): void
    {
        $conexao->exec(
            "DELETE FROM colaboradores_seguidores 
            WHERE colaboradores_seguidores.id_colaborador = {$this->id_colaborador}
            AND colaboradores_seguidores.id_colaborador_seguindo = {$this->id_colaborador_seguindo}"
        );
    }

    public function usuarioJaEstaSeguindo(\PDO $conexao): bool
    {
        return !empty(
            $conexao->query(
                "SELECT 1 
                FROM colaboradores_seguidores 
                WHERE colaboradores_seguidores.id_colaborador = {$this->id_colaborador}
                AND colaboradores_seguidores.id_colaborador_seguindo = {$this->id_colaborador_seguindo}"
            )->fetch(PDO::FETCH_ASSOC)
        );
    }
}
*/
<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizado;
use MobileStock\model\Origem;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @deprecated
 * @see Usar: MobileStock\model\CatalogoPersonalizadoModel
 */
class CatalogoPersonalizadoService extends CatalogoPersonalizado
{
    const TIPO_CATALOGO_PUBLICO = 'PUBLICO';

    public function salvar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        $this->id = $conexao->lastInsertId();
    }

    public static function buscarListaCatalogosPublicos(?string $origem): array
    {
        $whereOrigem = '';
        $binds = [':tipoCatalogo' => self::TIPO_CATALOGO_PUBLICO];
        if (!empty($origem)) {
            $whereOrigem = 'AND catalogo_personalizado.plataformas_filtros REGEXP :origem';
            $binds[':origem'] = $origem;
        }
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos `json_produtos`
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.tipo = :tipoCatalogo
                AND catalogo_personalizado.esta_ativo = 1
                $whereOrigem
            ORDER BY catalogo_personalizado.nome",
            $binds
        );
        $catalogos = array_map(function (array $catalogo): array {
            $catalogo['quantidade_produtos'] = sizeof($catalogo['produtos']);
            return $catalogo;
        }, $catalogos);
        return $catalogos;
    }

    public function editar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->update();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
        if ($stmt->rowCount() === 0) {
            throw new Exception('Nenhum dado foi alterado');
        }
    }

    public function deletar(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->deleteSemGetter();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);
    }
}

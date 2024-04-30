<?php

namespace MobileStock\model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 * @property int $id
 * @property int $id_colaborador
 * @property string $nome
 * @property string $tipo
 * @property bool $esta_ativo
 * @property string $produtos
 * @property string $plataformas_filtros
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class CatalogoPersonalizadoModel extends Model
{
    protected $table = 'catalogo_personalizado';

    protected $fillable = ['id_colaborador', 'nome', 'tipo', 'esta_ativo', 'produtos', 'plataformas_filtros'];

    public static function consultaCatalogoPersonalizadoPorId(int $idCatalogo): self
    {
        $catalogoPersonalizado = self::fromQuery(
            "SELECT
                catalogo_personalizado.id,
                catalogo_personalizado.id_colaborador,
                catalogo_personalizado.nome,
                catalogo_personalizado.tipo,
                catalogo_personalizado.esta_ativo,
                catalogo_personalizado.produtos `json_produtos`,
                catalogo_personalizado.plataformas_filtros
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id = :id_catalogo",
            ['id_catalogo' => $idCatalogo]
        )->first();
        if (empty($catalogoPersonalizado)) {
            throw new NotFoundHttpException('Catalogo personalizado não encontrado.');
        }

        return $catalogoPersonalizado;
    }

    public static function buscarListaCatalogosColaborador(int $idColaborador): array
    {
        $catalogos = DB::select(
            "SELECT catalogo_personalizado.id,
                catalogo_personalizado.nome,
                catalogo_personalizado.produtos `json_produtos`
            FROM catalogo_personalizado
            WHERE catalogo_personalizado.id_colaborador = :idCliente
            ORDER BY catalogo_personalizado.nome",
            [':idCliente' => $idColaborador]
        );
        $catalogos = array_map(function ($catalogo) {
            return [
                'id' => $catalogo['id'],
                'nome' => $catalogo['nome'],
                'quantidade_produtos' => sizeof($catalogo['produtos']),
            ];
        }, $catalogos);
        return $catalogos;
    }
}

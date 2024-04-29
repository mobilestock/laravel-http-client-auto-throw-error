<?php

namespace MobileStock\model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 * @property int $id
 * @property int $id_colaborador
 * @property string $nome
 * @property string $tipo
 * @property bool $ativo
 * @property string $produtos
 * @property string $plataformas_filtros
 * @property string $data_criacao
 * @property string $data_atualizacao
 */
class CatalogoPersonalizadoModel extends Model
{
    protected $table = 'catalogo_personalizado';

    protected $fillable = ['id_colaborador', 'nome', 'tipo', 'ativo', 'produtos', 'plataformas_filtros'];

    protected $casts = [
        'ativo' => 'bool',
    ];

    public static function consultaCatalogoPersonalizadoPorId(int $idCatalogo): self
    {
        $catalogoPersonalizado = self::fromQuery(
            "SELECT
                catalogo_personalizado.id,
                catalogo_personalizado.id_colaborador,
                catalogo_personalizado.nome,
                catalogo_personalizado.tipo,
                catalogo_personalizado.ativo,
                catalogo_personalizado.produtos,
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

    public static function ativarDesativarCatalogoPersonalizado(int $idCatalogo)
    {
        $catalogo = self::find($idCatalogo);
        $catalogo->ativo = !$catalogo->ativo;
        $catalogo->update();
    }
}

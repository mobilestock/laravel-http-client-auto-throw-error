<?php

namespace MobileStock\model;

/**
 * https://github.com/mobilestock/web/issues/2903
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

    public static function buscaCatalogoPorId(int $id): ?self
    {
        return self::find($id);
    }

    public function ativarDesativarCatalogoPersonalizado(int $idCatalogo)
    {
        $catalogo = self::buscaCatalogoPorId($idCatalogo);
        $catalogo->ativo = !$catalogo->ativo;
        $catalogo->update();
    }
}

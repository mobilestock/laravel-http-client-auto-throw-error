<?php

namespace MobileStock\model;

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

    public static function ativarDesativarCatalogoPersonalizado(int $idCatalogo)
    {
        $catalogo = self::find($idCatalogo);
        $catalogo->ativo = !$catalogo->ativo;
        $catalogo->update();
    }
}

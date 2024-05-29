<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use MobileStock\service\CatalogoFixoService;
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

    public static function buscaTipoCatalogo(): string
    {
        $porcentagem = ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador)
            ->porcentagem_compras_moda;

        switch (true) {
            case $porcentagem > 80:
                return CatalogoFixoService::TIPO_MODA_100;
            case $porcentagem > 60:
                return CatalogoFixoService::TIPO_MODA_80;
            case $porcentagem > 40:
                return CatalogoFixoService::TIPO_MODA_60;
            case $porcentagem > 20:
                return CatalogoFixoService::TIPO_MODA_40;
            case $porcentagem > 0:
                return CatalogoFixoService::TIPO_MODA_20;
            default:
                return CatalogoFixoService::TIPO_MODA_GERAL;
        }
    }
}

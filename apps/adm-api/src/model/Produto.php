<?php

namespace MobileStock\model;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property string $descricao
 * @property int $id_forncedor
 * @property bool $bloqueado
 * @property int $id_linha
 * @property string $data_entrada
 * @property bool $promocao
 * @property int $grade
 * @property string $forma
 * @property string $nome_comercial
 * @property float $preco_promocao
 * @property float $valor_custo_produto
 * @property int $id_usuario
 * @property int $tipo_grade
 * @property string $sexo
 * @property string $cores
 * @property bool $fora_de_linha
 * @property string $embalagem
 * @property string $outras_informacoes
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class Produto extends Model
{
    protected $fillable = [
        'descricao',
        'id_forncedor',
        'bloqueado',
        'id_linha',
        'data_entrada',
        'promocao',
        'outras_informacoes',
        'forma',
        'embalagem',
        'nome_comercial',
        'preco_promocao',
        'valor_custo_produto',
        'id_usuario',
        'tipo_grade',
        'sexo',
        'cores',
        'fora_de_linha',
        'permitido_reposicao',
        'eh_moda',
    ];
    protected $casts = [
        'eh_moda' => 'boolean',
        'promocao' => 'boolean',
    ];
    public $timestamps = false;

    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE = 82044;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_EXPRESSO = 82042;
}

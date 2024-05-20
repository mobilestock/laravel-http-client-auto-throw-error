<?php

namespace MobileStock\model;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 *
 * @property int $id
 * @property bool $permitido_reposicao
 */
class ProdutoModel extends Model
{
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

    public $timestamps = false;
    protected $table = 'produtos';
    protected $fillable = ['permitido_reposicao'];
}

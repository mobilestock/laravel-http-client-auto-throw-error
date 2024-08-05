<?php

namespace MobileStock\model;

/**
 * @property int id_produto
 * @property string nome_tamanho
 * @property string situacao
 * @property string origem
 * @property string sku
 */
class ProdutoLogistica extends Model
{
    protected $table = 'produtos_logistica';
    protected $fillable = ['id_produto', 'nome_tamanho', 'situacao', 'origem'];

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function (self $model): void {
            # TODO: Pensar em algoritmo mais eficiente
            do {
                $codigo = random_int(100000000000, 999999999999);
            } while (self::where('sku', $codigo)->exists());
            $model->sku = $codigo;
        });
    }
}

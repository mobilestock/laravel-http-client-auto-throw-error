<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use InvalidArgumentException;

/**
 * App\Models\LojaPreco
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LojaPreco newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LojaPreco newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LojaPreco query()
 *
 * @mixin \Eloquent
 */
class LojaPreco extends Model
{
    protected $table = 'lojas_precos';

    protected $fillable = ['id_revendedor', 'ate', 'remarcacao', 'id'];

    protected function ate(): Attribute
    {
        return Attribute::make(
            get: fn(string|null $value) => is_null($value) ? 'MAXIMO' : (float) $value,
            set: function (string|null $value) {
                if (!is_numeric($value) && $value !== 'MAXIMO' && is_null($value)) {
                    throw new InvalidArgumentException('O valor de "até" deve ser numérico ou nulo');
                }
                if ($value === 'MAXIMO') {
                    return null;
                }

                return $value;
            }
        );
    }

    protected static function booted()
    {
        static::addGlobalScope(function (Builder $builder): void {
            $builder->whereRaw('ate IS NOT NULL');
        });
    }
}

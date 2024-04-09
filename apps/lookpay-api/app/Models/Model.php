<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Ramsey\Uuid\Uuid;

/**
 * @method static \Illuminate\Database\Eloquent\Collection<static> fromQuery($query, $bindings = [])
 */
abstract class Model extends Eloquent
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $incrementing = false;
    protected $keyType = 'string';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->mergeCasts([
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ]);
    }
    protected static function boot(): void
    {
        parent::boot();

        self::creating(fn(self $model) => $model->isFillable('id') ? ($model->id ??= (string) Uuid::uuid4()) : null);
    }
}

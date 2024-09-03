<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->mergeCasts([
            'data_criacao' => 'datetime:Y-m-d H:i:s',
            'data_atualizacao' => 'datetime:Y-m-d H:i:s',
        ]);
    }

    const CREATED_AT = 'data_criacao';

    const UPDATED_AT = 'data_atualizacao';
}

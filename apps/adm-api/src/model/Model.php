<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use MobileStock\Shared\Model\Model as SharedModel;

class Model extends SharedModel
{
    const CREATED_AT = 'data_criacao';
    const UPDATED_AT = 'data_atualizacao';
    public function __construct(array $attributes = [])
    {
        // https://github.com/mobilestock/web/issues/2871
        date_default_timezone_set('America/Sao_Paulo');
        parent::__construct($attributes);
        $this->mergeCasts([
            'data_criacao' => 'datetime:Y-m-d H:i:s',
            'data_atualizacao' => 'datetime:Y-m-d H:i:s',
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        self::creating([self::class, 'atualizaIdUsuario']);
        self::updating([self::class, 'atualizaIdUsuario']);
    }

    public static function atualizaIdUsuario(self $model): void
    {
        if (!$model->isFillable('id_usuario')) {
            return;
        }

        $model->id_usuario ??= Auth::id();
    }
}

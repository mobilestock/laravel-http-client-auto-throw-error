<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property int $id_fornecedor
 * @property string $data_previsao
 * @property string $situacao
 */
class Reposicao extends Model
{
    protected $table = 'reposicoes';
    protected $fillable = ['id_fornecedor', 'data_previsao', 'id_usuario', 'situacao'];
}

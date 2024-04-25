<?php

namespace MobileStock\model;

/**
 * https://github.com/mobilestock/web/issues/2903
 * @property int $id_cliente
 * @property bool $defeito
 */
class TrocaPendenteItemModel extends Model
{
    protected $table = 'troca_pendente_item';

    protected $fillable = ['id_cliente', 'id_produto', 'nome_tamanho', 'uuid', 'preco', 'defeito'];

    protected $casts = [
        'defeito' => 'bool',
    ];

    public $timestamps = false;
}

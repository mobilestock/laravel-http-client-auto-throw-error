<?php

namespace MobileStock\model\Entrega;

use MobileStock\model\Model;

/**
 * https://github.com/mobilestock/backend/issues/131
 * @property string $uuid_produto
 * @property int $id_usuario
 * @property string $situacao
 */
class EntregasDevolucoesItemModel extends Model
{
    protected $table = 'entregas_devolucoes_item';
    protected $fillable = ['situacao', 'situacao_envio', 'id_usuario'];
    protected $primaryKey = 'uuid_produto';
    protected $keyType = 'string';
}

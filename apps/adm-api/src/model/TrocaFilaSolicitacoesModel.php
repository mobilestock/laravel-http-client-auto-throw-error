<?php

namespace MobileStock\model;

/**
 * @issue https://github.com/mobilestock/backend/issues/131
 * @property int $id
 * @property string $situacao
 */
class TrocaFilaSolicitacoesModel extends Model
{
    const CANCELADO_PELO_CLIENTE = 'CANCELADO_PELO_CLIENTE';
    protected $table = 'troca_fila_solicitacoes';
    protected $fillable = ['id', 'situacao'];
}

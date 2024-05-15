<?php

namespace MobileStock\model;

use DB;

/**
 * https://github.com/mobilestock/backend/issues/131
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
    public static function trocaEstaConfirmada(string $uuidProduto): bool
    {
        $estaConfirmada = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM troca_pendente_item
                WHERE troca_pendente_item.uuid = :uuid_produto
            ) AS `esta_confirmada`;",
            [':uuid_produto' => $uuidProduto]
        );

        return $estaConfirmada;
    }
}

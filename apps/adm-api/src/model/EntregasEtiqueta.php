<?php

namespace MobileStock\model;

/**
 *  @property int $id
 *  @property int $id_entrega
 *  @property int $volume
 *  @property string $uuid_volume
 *  @property string $data_criacao
 *  @property string $data_atualizacao
 *  @property int $id_usuario
 */
class EntregasEtiqueta extends Model
{
    /**
     * padrão da etiqueta de volume {ID_CLIENTE}_{ID_ENTREGA}_{VOLUME}
     */
    public const REGEX_VOLUME = "/^[0-9]+_[0-9]+_[0-9]+$/";
    /**
     * padrão da etiqueta de volume {UUID_ENTREGA}_{UUID_VOLUME}
     * @deprecated Utilizar REGEX_VOLUME
     */
    public const REGEX_VOLUME_LEGADO = "/^[A-z0-9\-]{36}_[A-z0-9\-]{36}$/";
}

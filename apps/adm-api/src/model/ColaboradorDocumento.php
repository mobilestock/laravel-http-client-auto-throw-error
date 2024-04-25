<?php

namespace MobileStock\model;

/**
 * @property int $id_colaborador
 * @property string $tipo_documento
 * @property string $url_documento
 */
class ColaboradorDocumento extends Model
{
    protected $table = 'colaboradores_documentos';
    protected $fillable = ['id_colaborador', 'tipo_documento', 'url_documento'];
    public $timestamps = false;
}

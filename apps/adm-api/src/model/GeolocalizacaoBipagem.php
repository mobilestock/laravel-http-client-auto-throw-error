<?php

namespace MobileStock\model;

/**
* @property int $id
* @property int $id_usuario
* @property int $latitude
* @property int $longitude
* @property string $motivo
* @property string $data_criacao
*/

class GeolocalizacaoBipagem
{
    public string $nome_tabela = "geolocalizacao_bipagem";

    public function __get($atrib) 
    {
        return $this->$atrib;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    protected function validaId()
    {
        $this->id = intval($this->id);
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
    public function extrair(): array
    {
        $dados = [
            'id' => $this->id ?: 0,
            'id_usuario' => $this->id_usuario ?: 0,
            'latitude' => $this->latitude ?: 0,
            'longitude' => $this->longitude ?: 0,
            'motivo' => $this->motivo ?: '',
            'data_criacao' => $this->data_criacao ?: ''
        ];
        $dados = array_filter($dados, fn ($value) => !empty($value));
        return $dados;
    }
}
    
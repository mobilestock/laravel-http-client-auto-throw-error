<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property string $data
 * @property int $id_usuario
 * @property string $data_criacao
 */

class DiasNaoTrabalhados implements \JsonSerializable
{


    public string $nome_tabela = 'dias_nao_trabalhados';

    public function __set($atrib, $value)
    {
        $this->$atrib = $value;
    }

    public function extrair(): array
    {
        $extrair = get_object_vars($this);
        return $extrair;
    }
    public function jsonSerialize()
    {
        $dados = get_object_vars($this); 
            
        $dados['data'] =  date('d/m/Y', strtotime($dados['data']));
        return $dados;
    }
}
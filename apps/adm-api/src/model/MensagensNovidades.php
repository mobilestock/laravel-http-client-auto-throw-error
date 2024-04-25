<?php

namespace MobileStock\model;

use Exception;

/**
 * @property int $id
 * @property string $json_texto
 * @property string $situacao
 * @property string $data_criacao
 * @property string $data_atualizacao
 * @property string $categoria
 */

class MensagensNovidades
{
    public string $nome_tabela = "mensagens_novidades";

    public function __set($name, $value)
    {
        if(!$value) $this->$name = '';
        if ($name === 'situacao' && !in_array($value, ['PE', 'EV'])) throw new Exception("Situação Inválida!");
        if($name === 'json_texto' && preg_match('/null/',  $value)) throw new Exception("Mensagem Incorreta");

        $this->$name = $value;
    }

    public function __construct()
    {
        $this->id = 0;
    }

    public function extrair(): array
    {
        return get_object_vars($this);
    }
}
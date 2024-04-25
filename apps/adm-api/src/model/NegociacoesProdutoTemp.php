<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property string $uuid_produto
 * @property array $itens_oferecidos
 */
class NegociacoesProdutoTemp
{
    public string $nome_tabela = 'negociacoes_produto_temp';
    public function __set($atrib, $value)
    {
        $entrada = $value;
        if ($atrib === 'itens_oferecidos') {
            if (is_string($value)) {
                $entrada = json_decode($value, true);
            }
        }

        $this->{$atrib} = $entrada;
    }
    public function extrair(): array
    {
        $extraido = get_object_vars($this);
        if (!empty($extraido['itens_oferecidos'])) {
            $extraido['itens_oferecidos'] = json_encode($extraido['itens_oferecidos']);
        }

        return $extraido;
    }
}

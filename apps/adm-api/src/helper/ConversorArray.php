<?php

namespace MobileStock\helper;

use MobileStock\database\PDO;

abstract class ConversorArray
{
    public const CAMPO_PADRAO_CRIA_BIND_VALUES = 'doekwko12k3ok21ko,dl23e.12.3.21321p3l21lp';

    public static function mapEnvolvePorString(string $string, ?string $index = null): callable
    {
        return function ($item) use ($string, $index) {
            if (is_array($item)) {
                $item = $item[$index];
            } elseif (is_object($item) && property_exists($item, $index)) {
                $item = $item->$index;
            }

            return "$string$item$string";
        };
    }

    public static function filterWhere($key, $operator = null, $value = null): callable
    {
        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }

    /**
     * @param array $listaItens [
     *      'id_produto' => 1,
     *      'id_cliente' => 1
     * ]
     *
     * @return array<string|array> [
     *      0 => ':bind1,:bind2,:bind3',
     *      1 => []
     * ]
     */
    public static function criaBindValues(array $listaItens, string $indexBValue = 'default_index'): array
    {
        $bind  = [];
        $itens = implode(
            ',',
            array_map(function ($index, $item) use (&$bind, $indexBValue) {
                if ($item === self::CAMPO_PADRAO_CRIA_BIND_VALUES) {
                    return "DEFAULT($index)";
                }
                $bind[":{$indexBValue}_$index"] = $item;

                return ":{$indexBValue}_$index";
            }, array_keys($listaItens), $listaItens)
        );

        return [$itens, $bind];
    }

    /**
     * Essa função agrupa e soma a grade de itens de um produto
     * @param array $listaItens [
     *     [ 'nome_tamanho' => 'P', 'estoque' => 10 ],
     *     [ 'nome_tamanho' => 'P', 'estoque' => 5 ], // <- Repetido pois é fulfillment/externo
     *     [ 'nome_tamanho' => 'M', 'estoque' => 8 ],
     * ]
     * @return array [
     *     [ 'nome_tamanho' => 'P', 'estoque' => 15 ],
     *     [ 'nome_tamanho' => 'M', 'estoque' => 8 ],
     * ]
     */
    public static function geraEstruturaGradeAgrupadaCatalogo(array $listaItens): array
    {
        return array_values(array_reduce(
            $listaItens,
            function($total, $item) {
                if ($item['estoque'] > 0) {
                    if (isset($total[$item['nome_tamanho']])) {
                        $total[$item['nome_tamanho']]['estoque'] += $item['estoque'];
                    } else {
                        $total[$item['nome_tamanho']] = $item;
                    }
                }
                return $total;
            },
            []
        ));
    }
}

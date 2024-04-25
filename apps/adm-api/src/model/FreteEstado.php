<?php

namespace MobileStock\model;

use MobileStock\helper\Globals;
use MobileStock\helper\Validador;

/**
 * @property int $id
 * @property string $estado
 * @property float $valor_frete
 * @property float $valor_adicional
 */
class FreteEstado
{
    public string $nome_tabela = 'frete_estado';

    public function __set($atrib, $value)
    {
        if ($atrib === 'estado') {
            $ufs = array_column(Globals::ESTADOS, 'sigla');
            Validador::validar(
                ['uf' => $value],
                ['uf' => Validador::ENUM($ufs)]
            );
        }
        $this->$atrib = $value;
    }

    public function extrair(): array
    {
        return [
            'id' => $this->id ?? '',
            'estado' => $this->estado ?? '',
            'valor_frete' => $this->valor_frete ?? '',
            'valor_adicional' => $this->valor_adicional ?? ''
        ];
    }
}

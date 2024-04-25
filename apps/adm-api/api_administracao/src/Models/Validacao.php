<?php

namespace api_administracao\Models;

use MobileStock\helper\DB;
class Validacao{
    public static function valida_cpf_cnpj($var)
    {
        $var = preg_replace('/[^0-9]/is', '', $var);
        if (strlen($var) == 11) {
            $cpf = $var;
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return false;
                }
            }
            return $cpf;
        } else if (strlen($var) == 14) {
            $cnpj = $var;

            for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
                $soma += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            $resto = $soma % 11;
            if ($cnpj[12] != ($soma < 2 ? 0 : 11 - $resto))
                return  false;

            for ($i = 0, $j = 6, $soma =  0; $i < 13; $i++) {
                $soma += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            $resto = $soma % 11;
            $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);

            return $cnpj;
        }
    }
}
?>
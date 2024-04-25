<?php

class ValidadorPagamento
{

}

interface ValidaCampos{
    public function validaCampos(string $type, array $fields): bool;
}

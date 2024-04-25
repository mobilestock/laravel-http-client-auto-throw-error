<?php

use Illuminate\Support\Str;
use test\TestCase;

class FormatadorTelefoneTest extends TestCase
{
    public function listaTelefonesFormatar()
    {
        return [
            'Telefone com formatacao invalido' => ['(68) 98383-04141', '(68) 98383-0414'],
            'Telefone invalido com formatação' => ['(71) 98716-854', '(71) 9871-6854'],
            'Nao é telefone' => ['churros', ''],
            'Telefone 0 caracteres' => ['', ''],
            'Telefone 1 caracteres' => ['1', ''],
            'Telefone 2 caracteres' => ['12', ''],
            'Telefone 3 caracteres' => ['123', ''],
            'Telefone 4 caracteres' => ['1234', ''],
            'Telefone 5 caracteres' => ['12345', ''],
            'Telefone 6 caracteres' => ['123456', ''],
            'Telefone 7 caracteres' => ['1234567', ''],
            'Telefone 8 caracteres' => ['12345678', ''],
            'Telefone 9 caracteres' => ['123456789', ''],
            'Telefone 10 caracteres' => ['1234567890', '(12) 3456-7890'],
            'Telefone 11 caracteres' => ['12345678901', '(12) 34567-8901'],
            'Telefone 12 caracteres' => ['123456789013', '(12) 34567-8901'],
        ];
    }
    /**
     * @dataProvider listaTelefonesFormatar
     */
    public function testFormatarTelefone(string $telefone, string $telefoneEsperado): void
    {
        $this->assertEquals($telefoneEsperado, Str::formatarTelefone($telefone));
    }
}

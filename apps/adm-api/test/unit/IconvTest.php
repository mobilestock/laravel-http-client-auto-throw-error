<?php

use Illuminate\Support\Str;
use test\TestCase;

class IconvTest extends TestCase
{
    public function testVerificaString(): void
    {
        $this->assertEquals("Zlutoucky kun\n", Str::toUtf8("Žluťoučký kůň\n"));
        $this->assertEquals('aaaaa', Str::toUtf8('áàãâä'));
        $this->assertEquals('eeee', Str::toUtf8('éèêë'));
        $this->assertEquals('iiii', Str::toUtf8('íìîï'));
        $this->assertEquals('uuuu', Str::toUtf8('úùûü'));
        $this->assertEquals('ooooo', Str::toUtf8('óòõôö'));
    }
    public function testVerificaExecucaoEmCli(): void
    {
        $_ENV['INSTANCIA_DE_EXECUCAO'] = 'CLI';
        $this->expectDeprecation();
        Str::toUtf8('');
    }
}

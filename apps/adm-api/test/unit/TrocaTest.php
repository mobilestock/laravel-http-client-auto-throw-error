<?php

use MobileStock\jobs\Troca;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use test\TestCase;

class TrocaTest extends TestCase
{
    public function testAoBiparTrocaDevePagarValorBloqueadoTransacao()
    {
        $transacaoMock = $this->createMock(TransacaoFinanceiraModel::class);
        $transacao1 = $this->createPartialMock(TransacaoFinanceiraModel::class, ['save'])->fill([
            'valor_credito_bloqueado' => 100,
            'preco' => 50,
        ]);
        $transacao2 = $this->createPartialMock(TransacaoFinanceiraModel::class, ['save'])->fill([
            'valor_credito_bloqueado' => 100,
        ]);
        $retorno = [[$transacao1, $transacao2], 50];
        $transacaoMock->method('buscaTransacoesPendentesTroca')->willReturn($retorno);

        $troca = new Troca('uuid-teste');
        $troca->handle($transacaoMock);

        $this->assertEquals(50, $transacao1->valor_credito_bloqueado);
        $this->assertEquals(100, $transacao2->valor_credito_bloqueado);
    }
}

<?php

namespace MobileStock\helper;

use Closure;
use JsonSerializable;
use PDO;

/**
 * Classe para simular o calculo de uma transacao
 */
class CalculadorTransacao implements JsonSerializable
{
    private PDO $conexao;
    private float $valor;
    private string $metodo_pagamento;
    private int $numero_parcelas;

    public Closure $getValorParcela;
    public Closure $getTaxaBoleto;

    public function __construct(
        float $valor,
        string $metodoPagamento,
        int $numero_parcelas
    )
    {
        $this->valor = $valor;
        $this->metodo_pagamento = $metodoPagamento;
        $this->numero_parcelas = $numero_parcelas;
    }

    public function calcula()
    {
        if ($this->metodo_pagamento === 'BL') {
            $this->valor += ($this->getTaxaBoleto)();
        }

        if ($this->metodo_pagamento === 'CA') {
            $taxa = (($this->getValorParcela)($this->numero_parcelas) / 100);
            $this->valor = round($this->valor + ($this->valor * $taxa), 2);
            $this->valor_parcela = round($this->valor / $this->numero_parcelas, 2);
        }

    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['getValorParcela']);
        unset($vars['getTaxaBoleto']);
        return $vars;
    }
}
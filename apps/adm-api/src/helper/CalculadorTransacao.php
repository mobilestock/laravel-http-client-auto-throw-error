<?php

namespace MobileStock\helper;

use JsonSerializable;
use MobileStock\model\TaxasModel;

/**
 * Classe para simular o calculo de uma transacao
 */
class CalculadorTransacao implements JsonSerializable
{
    private float $valor;
    private string $metodo_pagamento;
    private int $numero_parcelas;
    public float $valor_parcela;
    public float $valor_taxa;
    public array $parcelas;
    public int $parcelas_padrao;

    public function __construct(float $valor, string $metodoPagamento, int $numero_parcelas)
    {
        $this->valor = $valor;
        $this->metodo_pagamento = $metodoPagamento;
        $this->numero_parcelas = $numero_parcelas;
    }

    public function calcula()
    {
        if ($this->metodo_pagamento === 'BL') {
            $this->valor += TaxasModel::consultaValorBoleto();
        }

        if ($this->metodo_pagamento === 'CA') {
            $this->valor_taxa = TaxasModel::consultaValorTaxaParcela($this->numero_parcelas) / 100;
            $this->valor = round($this->valor + $this->valor * $this->valor_taxa, 2);
            $this->valor_parcela = round($this->valor / $this->numero_parcelas, 2);
        }
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }
}

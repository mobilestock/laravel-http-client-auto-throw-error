<?php

namespace MobileStock\model;

class TaxaDevolucao
{

    /**
     * Class TaxaDevolucao
     * @package MobileStock\Model
     * @property-write float $taxa
     * @property-write float $preco
     * @property-write string $datahora
     * @property-write string $obs
     * @property-write boolean $agendar
     */

    private $preco;
    private $taxa;
    private $datahora;
    private $obs;

    function __construct(float $preco, string $datahora)
    {
        $this->preco = $preco;
        $this->taxa = 2.0;
        $this->datahora = $datahora;
        $this->obs = '';
        $this->calculaData();
    }

    public function calculaDefeito(): void
    {
        $this->taxa = 0;
        $this->observacao .= 'Produtos com defeito não possuem taxa de troca.';
    }

    public function calculaData(): void
    {
        $days = date_diff(date_create($this->datahora), date_create(Date('d-m-Y')),)->days;
        if ($days >= 90) {
            $this->taxa += $this->preco / 2;
            $this->obs = '50% descontado pois passou 90 dias desde a compra do produto. ';
        }
    }

    public function getTaxa()
    {
        return $this->taxa;
    }

    public function getObs()
    {
        return $this->obs;
    }

    public function getPrecoComTaxa()
    {
        return ceil(($this->preco - $this->taxa) / 100) * 100;
    }

    public function setAgendamento()
    {
        $this->taxa -= 1.0;
    }
}

<?php

namespace MobileStock\service;

class TaxasConsultasService
{
    private \PDO $conexao;

    public function __construct(\PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function consultaValorBoleto(): float
    {
        return $this->conexao->query(
            'SELECT taxas.boleto FROM taxas LIMIT 1'
        )->fetch(\PDO::FETCH_ASSOC)['boleto'];
    }

    public function consultaValorTaxaParcela(int $parcela): float
    {
        return $this->conexao->query(
            'SELECT taxas.juros FROM taxas WHERE taxas.numero_de_parcelas = ' . $parcela
        )->fetch(\PDO::FETCH_ASSOC)['juros'];
    }

    public function consultaTaxaPix(): float
    {
        return $this->conexao->query(
            "SELECT COALESCE((SELECT taxas.pix FROM taxas WHERE taxas.numero_de_parcelas = 1),1) taxa"
        )->fetch(\PDO::FETCH_ASSOC)['taxa'];
    }
}
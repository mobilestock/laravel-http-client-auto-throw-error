<?php

namespace MobileStock\service\Lancamento;

use SplQueue;

class ProcessoAbate
{
    public SplQueue $filaPositiva;
    public SplQueue $filaNegativa;
    protected string $tipoLancamento;

    public function __construct(string $tipoLancamento)
    {
        $this->filaPositiva = new SplQueue();
        $this->filaNegativa = new SplQueue();
        $this->tipoLancamento = $tipoLancamento;
    }

    public function abate(): string
    {
        $sql = '';
        while (
            !$this->filaNegativa->isEmpty() &&
            !$this->filaPositiva->isEmpty() &&
            ($positivo = $this->filaPositiva->dequeue())
        ) {
            $negativo = $this->filaNegativa->shift();

            $valorRestanteAbate = round($positivo['valor'] + $negativo['valor'], 2);

            $sinal = $valorRestanteAbate <=> 0;

            $novo = null;
            if ($sinal === -1) {
                $novo = $negativo;
                $novaFila = $this->filaNegativa;
                $positivo['valor'] = round(abs($negativo['valor'] - $valorRestanteAbate), 2);
            } elseif ($sinal === 1) {
                $novo = $positivo;
                $novaFila = $this->filaPositiva;
                $positivo['valor'] = round($positivo['valor'] - $valorRestanteAbate, 2);
            }

            if ($novo) {
                $novo['valor'] = $valorRestanteAbate;
                $novaFila->unshift($novo);
            }
            $sql .= ",({$negativo['id']}, {$positivo['id']}, {$positivo['valor']}, '$this->tipoLancamento')";
        }
        $sql = ltrim($sql, ',');

        return $sql;
    }
}

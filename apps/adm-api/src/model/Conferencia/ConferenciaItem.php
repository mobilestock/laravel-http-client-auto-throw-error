<?php

namespace MobileStock\model\Conferencia;

use Error;

class ConferenciaItem
{
    protected string $uuid;
    protected $status; /* disponivel, conferido, corrigido */
    protected int $conferido;
    protected int $situacao;


    public function __set($atrib, $value)
    {
        if ($value) {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'status':
                    $this->converteDeStatus();
                    break;
            }
        }else{
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    protected function converteDeStatus()
    {
        switch ($this->status) {
            case 'disponivel':
                $this->situacao = 6;
                $this->conferido = 0;
                break;
            case 'conferido':
                $this->situacao = 6;
                $this->conferido = 1;
                break;
            case 'corrigido':
                $this->situacao = 19;
                $this->conferido = 1;
                break;           
            default:
                throw new Error("Status do produto incorreto");
                break;
        }
    }
    public static function buscaSituacao(string $status)
    {
        if ($status === 'conferido') {
            return 6;
        } else if ($status === 'corrigido') {
            return 19;
        } else {
            throw new Error("Status do produto incorreto");
        }
    }
    public function buscaNomeStatus(int $situacao, int $conferido)
    {
        switch (true) {
            case ($situacao == 6 && $conferido == 0):
                $this->status = 'disponivel';
                break;
            case ($situacao == 6 && $conferido == 1):
                $this->status = 'conferido';
                break;
            case ($situacao == 19):
                $this->status = 'corrigido';
                break;
        }
    }
}

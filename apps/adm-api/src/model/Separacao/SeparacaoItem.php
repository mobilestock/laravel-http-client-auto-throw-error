<?php

namespace MobileStock\model\Separacao;

use Error;

class SeparacaoItem
{
    protected $uuid;
    protected $situacao;
    protected $separado; 
    protected $status; /* indisponival, disponivel, separado, correcao */
    protected string $nome_tamanho;
    protected $descricao;


    public function __set($atrib, $value)
    {
        if ($value) {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'status':
                    $this->converteStatus();
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

    protected function converteStatus()
    {
        switch ($this->status) {
            case 'disponivel':
                $this->situacao = 6;
                $this->separado = 0;
                break;
            case 'separado':
                $this->situacao = 6;
                $this->separado = 1;
                break;
            case 'corrigido':
                $this->situacao = 19;
                $this->separado = 1;
                break;           
            default:
                throw new Error("Status do produto incorreto");
                break;
        }
    }
    public function buscaNomeStatus(int $situacao, int $separado)
    {
        switch (true) {
            case ($situacao == 6 && $separado == 0):
                $this->status = 'disponivel';
                break;
            case ($situacao == 6 && $separado == 1):
                $this->status = 'separado';
                break;
            case ($situacao == 19):
                $this->status = 'corrigido';
                break;
        }
    }
}

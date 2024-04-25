<?php

namespace MobileStock\model\Separacao;


class Separacao
{
    protected $id;
    protected $situacao;
    protected $status;
    protected $data_emissao;
    protected $razao_social;
    protected $data_fechamento;
    protected $separado;
    protected $prioridade;
    protected $pares;
    protected $valor;
    protected $id_separador;
    protected $consulta;
    protected $transporte;


    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'consulta':
                    $this->converteConsulta();
                    break;
                case 'id':
                    $this->validaId();
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

    protected function converteConsulta()
    {
        $this->id = ($this->consulta)?intval($this->consulta):false;
        $this->razao_social = ($this->consulta);
    }
    protected function validaId()
    {
        $this->id = intval($this->id);
    }
}

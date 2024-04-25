<?php

namespace MobileStock\model\NotaFiscalSaida;


class NotaFiscalSaida
{
    protected $id;
    protected $data_cadastro;
    protected $id_fornecedor;
    protected $id_cliente;
    protected $id_transportadora;
    protected $frete;
    protected $pares;
    protected $valor;
    protected $peso;
    protected $volumes;
    protected $status_fiscal;
    protected $id_faturamento;
    protected $nota_fiscal;
    protected $data_emissao;
    protected $anexo_pdf;
    protected $anexo_xml;
    protected $bloqueado;

  

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
        } else {
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    protected function validaId()
    {
        $this->id = intval($this->id);
    }

}

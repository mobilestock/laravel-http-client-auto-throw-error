<?php /*

namespace MobileStock\model;

class Faturamento
{   
    protected $id;
    protected $situacao;
	protected $id_cliente;
	protected $observacao;
	protected $valor_frete;
	protected $tipo_frete;
	protected $pares;
    protected $data_fechamento;
    protected $id_usuario;
    protected $origem_faturamento;
    protected $id_responsavel_estoque;

    public function __set($atrib, $value)
    {
        $this->$atrib = $value;
        switch ($atrib) {
            case 'id':
                $this->validaId();
                break;
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

}*/
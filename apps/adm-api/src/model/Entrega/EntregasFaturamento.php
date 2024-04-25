<?php /*

namespace MobileStock\model\Entrega;

class EntregasFaturamento
{
    protected $id;
	protected $id_faturamento;
	protected $id_entregas;
	protected $qtd_itens;
    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;     
            switch ($atrib) {
                case 'id'||'id_faturamento'||'$id_entregas':
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

    protected function validaId()
    {
        $this->id = intval($this->id);
    }
}
    */
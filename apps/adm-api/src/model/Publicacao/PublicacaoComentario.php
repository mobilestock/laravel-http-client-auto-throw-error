<?php /*

namespace MobileStock\model\Publicacao;

class PublicacaoComentario implements \JsonSerializable
{
    protected int $id;
    protected ?int $id_colaborador = null;
    protected int $id_publicacao;
    protected string $comentario;

    protected function validaId($campo,$valor)
    {
        $this->$campo = intval($valor);
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'id':
                    $this->validaId('id', $value);
                    break;
                case 'id_colaborador':
                    $this->validaId('id_colaborador', $value);
                    break;
            }
        }else{
            $this->$atrib = null;
        }
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
} */
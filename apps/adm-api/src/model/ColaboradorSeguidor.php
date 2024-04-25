<?php

namespace MobileStock\model;

class ColaboradorSeguidor
{
	public $nome_tabela = 'colaboradores_seguidores';
    private $id;
    private $id_colaborador;
    private $id_colaborador_seguindo;

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;     
        }else{
            $this->$atrib = null;
        }
    } 

    public function __get($atrib) 
    {
        return $this->$atrib;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getIdColaborador()
    {
        return $this->id_colaborador;
    }

    public function getIdColaboradorSeguindo()
    {
        return $this->id_colaborador_seguindo;
    }

    public function extrair()
    {
        return [
            'id_colaborador' => $this->id_colaborador,
            'id_colaborador_seguindo' => $this->id_colaborador_seguindo
        ];
    }
}

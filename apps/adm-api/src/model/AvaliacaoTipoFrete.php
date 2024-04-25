<?php

namespace MobileStock\model;

class AvaliacaoTipoFrete {

    private int $id;
    private int $id_colaborador;
    private int $id_tipo_frete;
    private int $nota_atendimento;
    private int $nota_localizacao;
    private string $comentario = '';
    private string $visualizado_em;

    public function __set($atrib, $value)
    {
        if ($value || $value == '0' || $value == '') {
            $this->$atrib = $value;
        } else {
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    public function extrair(): array
    {
        return [
            'id' => $this->id ?? '',
            'id_colaborador' => $this->id_colaborador ?? 0,
            'id_tipo_frete' => $this->id_tipo_frete ?? '',
            'nota_atendimento' => $this->nota_atendimento ?? '',
            'nota_localizacao' => $this->nota_localizacao ?? '',
            'comentario' => $this->comentario ?? '',
            'visualizado_em' => $this->visualizado_em ?? ''
        ];
    }
}
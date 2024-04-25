<?php

namespace MobileStock\model\Ranking;

class RankingVencedoresItens implements \JsonSerializable
{
    protected int $id;
    protected string $uuid_produto;
    protected int $id_lancamento_pendente;
    protected int $id_lancamento;
    protected string $data_criacao;

    public function __construct()
    {
        $this->id = 0;
    }

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
        } else {
            if ($atrib === 'data_criacao') return;
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    public function extrair()
    {
        return [
            'id' => $this->id || '',
            'uuid_produto' => $this->uuid_produto || '',
            'id_lancamento_pendente' => $this->id_lancamento_pendente || '',
            'id_lancamento' => $this->id_lancamento || '',
            'data_criacao' => $this->data_criacao || '',
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->extrair();
    }

}
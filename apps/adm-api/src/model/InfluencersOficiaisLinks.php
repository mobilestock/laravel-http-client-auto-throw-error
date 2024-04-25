<?php
/*
namespace MobileStock\model;

use Exception;
use MobileStock\model\ModelInterface;

class InfluencersOficiaisLinks implements ModelInterface {
    public string $nome_tabela = 'influencers_oficiais_links';
    public int $id;
    public int $id_usuario;
    public string $hash;
    public string $situacao;
    public string $data_criacao;

    public static function hidratar(array $dados): ModelInterface
    {
        $influencerOficial = new self();
        foreach ($dados as $key => $value) {
            if ($key === 'situacao' && !in_array($value, ['CR', 'RE'])) throw new Exception('Situação Inválida!');
            $influencerOficial->$key = $value;
        }
        return $influencerOficial;
    }

    public function extrair(): array
    {
        return [
            'id' => $this->id ?? '',
            'id_usuario' => $this->id_usuario ?? '',
            'hash' => $this->hash ?? '',
            'situacao' => $this->situacao ?? '',
            'data_criacao' => $this->data_criacao ?? ''
        ];
    }
}
*/
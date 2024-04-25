<?php

namespace MobileStock\model;

class Transportes
{
    public string $nome_tabela = 'transportes';
    public int $id_colaborador;
    public string $situacao;
    public string $link_rastreio;
    public string $tipo_transporte = '';
    public int $tipo_envio;
    public string $data_criacao;

    /**
     * @inheritDoc
     */
    public function extrair(): array
    {
        $dados = [
            'id_colaborador' => $this->id_colaborador ?? 0,
            'situacao' => $this->situacao ?? '',
            'link_rastreio' => $this->link_rastreio ?? '',
            'tipo_transporte' => $this->tipo_transporte ?? '',
            'tipo_envio' => $this->tipo_envio ?? '',
            'data_criacao' => $this->data_criacao ?? ''
        ];
        $dados = array_filter($dados, function ($value) {
            return !empty($value);
        });
        return $dados;
    }
}
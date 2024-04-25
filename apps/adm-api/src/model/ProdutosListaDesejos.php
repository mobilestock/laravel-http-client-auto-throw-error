<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property int $id_colaborador
 * @property int $id_produto
 * @property string $data_criacao
 */

class ProdutosListaDesejos
{
    public string $nome_tabela = "produtos_lista_desejos";

    public function extrair(): array
    {
        return [
            'id' => (int) $this->id ?? '',
            'id_colaborador' => (int) $this->id_colaborador ?? '',
            'id_produto' => (int) $this->id_produto ?? '',
            'data_criacao' => $this->data_criacao ?? ''
        ];
    }
}
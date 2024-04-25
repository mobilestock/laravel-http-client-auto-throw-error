<?php

namespace MobileStock\model;

class EstoqueGrade
{
    public int $id_produto;
    // public int $tamanho;
    public int $id_responsavel;
    public string $tipo_movimentacao;
    public string $descricao;
    public string $nome_tamanho;
}
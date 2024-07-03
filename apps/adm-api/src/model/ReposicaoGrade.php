<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property int $id_reposicao
 * @property int $id_produto
 * @property string $nome_tamanho
 * @property float $preco_custo_produto
 * @property int $quantidade_entrada
 * @property int $quantidade_total
 * @property int $id_usuario
 * @property string $data_alteracao
 */
class ReposicaoGrade extends Model
{
    protected $table = 'reposicoes_grades';
    protected $fillable = [
        'id_reposicao',
        'id_produto',
        'nome_tamanho',
        'preco_custo_produto',
        'quantidade_entrada',
        'quantidade_total',
        'id_usuario',
        'data_alteracao',
    ];
    public $timestamps = false;

    public static function sqlCalculoPrecoTotalReposicao(): string
    {
        $sql = 'SUM(reposicoes_grades.preco_custo_produto * reposicoes_grades.quantidade_total)';
        return $sql;
    }
}

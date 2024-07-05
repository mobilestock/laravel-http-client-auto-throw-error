<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;

/**
 * @property int $id
 * @property int $id_reposicao
 * @property int $id_produto
 * @property string $nome_tamanho
 * @property float $preco_custo_produto
 * @property int $quantidade_entrada
 * @property int $quantidade_total
 */
class ReposicaoGrade extends Model
{
    const CREATED_AT = null;
    protected $table = 'reposicoes_grades';
    protected $fillable = [
        'id_reposicao',
        'id_produto',
        'nome_tamanho',
        'preco_custo_produto',
        'quantidade_entrada',
        'quantidade_total',
        'id_usuario',
    ];

    protected static function boot(): void
    {
        parent::boot();
        self::updated(function (self $model): void {
            if (!$model->isDirty('quantidade_entrada')) {
                return;
            }

            $dadosReposicao = DB::selectOne(
                "SELECT
                    reposicoes.situacao,
                    SUM(reposicoes_grades.quantidade_entrada) AS `total_estocado`,
                    SUM(reposicoes_grades.quantidade_total) AS `total_prometido_em_reposicao`
                FROM reposicoes_grades
                INNER JOIN reposicoes ON reposicoes.id = reposicoes_grades.id_reposicao
                WHERE reposicoes_grades.id_reposicao = :id_reposicao
                GROUP BY reposicoes_grades.id_reposicao",
                ['id_reposicao' => $model->id_reposicao]
            );

            if ($dadosReposicao['total_estocado'] === $dadosReposicao['total_prometido_em_reposicao']) {
                $situacao = 'ENTREGUE';
            } elseif (
                $dadosReposicao['total_estocado'] !== $dadosReposicao['total_prometido_em_reposicao'] &&
                $dadosReposicao['total_estocado'] > 0
            ) {
                $situacao = 'PARCIALMENTE_ENTREGUE';
            }

            if (empty($situacao) || $dadosReposicao['situacao'] === $situacao) {
                return;
            }

            $reposicao = new Reposicao();
            $reposicao->exists = true;
            $reposicao->id = $model->id_reposicao;
            $reposicao->situacao = $situacao;
            $reposicao->save();
        });
    }

    public static function sqlCalculoPrecoTotalReposicao(): string
    {
        $sql = 'SUM(reposicoes_grades.preco_custo_produto * reposicoes_grades.quantidade_total) AS `preco_total`';
        return $sql;
    }

    public static function atualizaEntradaGrades(int $idReposicao, array $grades): void
    {
        $idsGrades = array_column($grades, 'id_grade');
        [$binds, $valores] = ConversorArray::criaBindValues($idsGrades);

        $gradesAtuais = DB::select(
            "SELECT
                reposicoes_grades.id,
                reposicoes_grades.quantidade_entrada
            FROM reposicoes_grades
            WHERE reposicoes_grades.id IN ($binds)",
            $valores
        );

        $gradesAtuais = array_column($gradesAtuais, 'quantidade_entrada', 'id');

        foreach ($grades as $grade) {
            $somaDaGrade = $gradesAtuais[$grade['id_grade']] + $grade['qtd_entrada'];

            $reposicaoGrade = new ReposicaoGrade();
            $reposicaoGrade->exists = true;
            $reposicaoGrade->id = $grade['id_grade'];
            $reposicaoGrade->id_reposicao = $idReposicao;
            $reposicaoGrade->quantidade_entrada = $somaDaGrade;

            $fillable = $reposicaoGrade->getFillable();
            unset($fillable[array_search('id_reposicao', $fillable)]);
            $reposicaoGrade->fillable($fillable);

            $reposicaoGrade->save();
        }
    }
}

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
    public $timestamps = false;
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

    public static function atualizaEntradaGrades(array $grades): void
    {
        $idsGrades = array_column($grades, 'id_grade');
        [$binds, $valores] = ConversorArray::criaBindValues($idsGrades);

        $reposicoesGrades = self::fromQuery(
            "SELECT
                reposicoes_grades.id,
                reposicoes_grades.id_reposicao,
                reposicoes_grades.quantidade_entrada
            FROM reposicoes_grades
            WHERE reposicoes_grades.id IN ($binds)",
            $valores
        );

        $grades = array_column($grades, 'qtd_entrada', 'id_grade');
        foreach ($reposicoesGrades as $reposicaoGrade) {
            $qtdEntrada = $grades[$reposicaoGrade->id];
            $reposicaoGrade->quantidade_entrada += $qtdEntrada;
            $reposicaoGrade->save();
        }
    }

    public static function buscaPrevisaoProdutosFornecedor(int $idFornecedor): array
    {
        $lista = DB::select(
            "SELECT
                reposicoes_grades.id_produto,
                SUM(reposicoes_grades.quantidade_total) AS `qtd_prevista`,
                reposicoes_grades.nome_tamanho
            FROM reposicoes_grades
            INNER JOIN reposicoes ON reposicoes.id_fornecedor = :id_fornecedor
                AND reposicoes.id = reposicoes_grades.id_reposicao
            WHERE reposicoes.situacao IN ('EM_ABERTO', 'PARCIALMENTE_ENTREGUE')
            GROUP BY reposicoes_grades.id_reposicao, reposicoes_grades.nome_tamanho",
            ['id_fornecedor' => $idFornecedor]
        );

        $resultado = [];
        foreach ($lista as $item) {
            $resultado[$item['id_produto']][$item['nome_tamanho']] = $item['qtd_prevista'];
        }

        return $resultado;
    }

    public static function buscaReposicoesDoProduto(int $idProduto, bool $verApenasPendentes): array
    {
        $where = '';
        if ($verApenasPendentes) {
            $where = ' AND reposicoes.situacao IN ("EM_ABERTO", "PARCIALMENTE_ENTREGUE") ';
        }

        $reposicoes = DB::select(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes_grades.id_produto,
                reposicoes.id_fornecedor,
                DATE_FORMAT(reposicoes.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                reposicoes.id_usuario,
                reposicoes.situacao
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE reposicoes_grades.id_produto = :id_produto
                $where
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC",
            [':id_produto' => $idProduto]
        );

        return $reposicoes;
    }
}

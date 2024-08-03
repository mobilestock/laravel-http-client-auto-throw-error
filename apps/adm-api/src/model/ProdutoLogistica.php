<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProdutoLogistica extends Model
{
    public static function buscaEtiquetasReposicaoAguardandoEntrada(int $idProduto): array
    {
        $produto = DB::selectOne(
            "SELECT
                produtos.id_fornecedor,
                produtos.id AS `id_produto`,
                CONCAT(colaboradores.id, '-', colaboradores.razao_social) AS `fornecedor`,
                CONCAT(produtos.descricao, ' ', produtos.cores) AS `descricao`,
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                        ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                        LIMIT 1
                    ),
                    '{$_ENV['URL_MOBILE']}/images/img-placeholder.png'
                ) AS `foto`,
                CONCAT(
                    '[',
                        (
                            SELECT GROUP_CONCAT(DISTINCT JSON_OBJECT(
                                'nome_tamanho', produtos_grade.nome_tamanho,
                                'estoque', COALESCE(estoque_grade.estoque, 0),
                                'quantidade_etiquetas', COALESCE(
                                    (
                                        SELECT COUNT(DISTINCT produtos_logistica.id)
                                        FROM produtos_logistica
                                        WHERE produtos_logistica.id_produto = produtos_grade.id_produto
                                            AND produtos_logistica.nome_tamanho = produtos_grade.nome_tamanho
                                            AND produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
                                            AND origem = 'REPOSICAO'
                                        GROUP BY produtos_logistica.id_produto
                                    ), 0
                                )
                            ) ORDER BY IF(produtos_grade.nome_tamanho REGEXP '[0-9]', produtos_grade.nome_tamanho, produtos_grade.sequencia))
                            FROM produtos_grade
                            LEFT JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                                AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                                AND estoque_grade.id_responsavel = 1
                            WHERE produtos_grade.id_produto = produtos.id
                        ),
                    ']'
                ) AS `json_grades`
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            WHERE produtos.id = :id_produto",
            ['id_produto' => $idProduto]
        );

        if (!Gate::allows('ADMIN') && $produto['id_fornecedor'] != Auth::user()->id_colaborador) {
            throw new \Exception('Você não tem permissão para acessar essas informações');
        }

        return $produto;
    }
}

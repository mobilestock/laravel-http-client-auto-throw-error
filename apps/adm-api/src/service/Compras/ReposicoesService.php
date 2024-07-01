<?php

namespace MobileStock\service\Compras;

use Error;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use MobileStock\repository\ProdutosRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReposicoesService
{
    public static function insereNovaReposicao(int $idFornecedor, string $dataPrevisao, float $valorTotal): int
    {
        $idReposicao = DB::table('reposicoes')->insertGetId([
            'id_fornecedor' => $idFornecedor,
            'data_previsao' => $dataPrevisao,
            'valor_total' => $valorTotal,
            'data_criacao' => now(),
            'id_usuario' => Auth::id(),
            'situacao' => 'EM_ABERTO',
        ]);

        return $idReposicao;
    }

    public static function insereNovaReposicaoGrade(
        int $idReposicao,
        int $idProduto,
        string $nomeTamanho,
        float $valorProduto,
        int $quantidadeTotal
    ): void {
        $sql = "
            INSERT INTO reposicoes_grades (
                reposicoes_grades.id_reposicao,
                reposicoes_grades.id_produto,
                reposicoes_grades.nome_tamanho,
                reposicoes_grades.valor_produto,
                reposicoes_grades.quantidade_entrada,
                reposicoes_grades.quantidade_total
            ) VALUES (
                :id_reposicao,
                :id_produto,
                :nome_tamanho,
                :valor_produto,
                0,
                :quantidade_total
            )";

        $inseriu = DB::insert($sql, [
            'id_reposicao' => $idReposicao,
            'id_produto' => $idProduto,
            'nome_tamanho' => $nomeTamanho,
            'valor_produto' => $valorProduto,
            'quantidade_total' => $quantidadeTotal,
        ]);

        if (!$inseriu) {
            throw new HttpException('Erro ao inserir nova grade na reposição');
        }
    }

    public static function buscaPrevisaoProdutosFornecedor(int $idFornecedor): array
    {
        $sql = "SELECT
                    reposicoes_grades.id_produto,
                    SUM(reposicoes_grades.quantidade_total) previsao,
                    reposicoes_grades.nome_tamanho
                FROM reposicoes_grades
                INNER JOIN reposicoes ON reposicoes.id_fornecedor = :id_fornecedor
                    AND reposicoes.id = reposicoes_grades.id_reposicao
                WHERE (reposicoes.situacao = 'EM_ABERTO' OR reposicoes.situacao = 'PARCIALMENTE_ENTREGUE')
                GROUP BY reposicoes_grades.id_reposicao, reposicoes_grades.nome_tamanho";

        $lista = DB::select($sql, ['id_fornecedor' => $idFornecedor]);

        $resultado = [];
        if (!empty($lista)) {
            foreach ($lista as $item) {
                $resultado[$item['id_produto']][$item['nome_tamanho']] = (int) $item['previsao'];
            }
        }

        return $resultado;
    }

    public static function verificaSePermitido(int $idProduto): bool
    {
        $verificacao = DB::table('produtos')->where('id', $idProduto)->where('permitido_reposicao', 1)->doesntExist();

        if ($verificacao) {
            throw new Error('Esse produto não tem permissão para repor no Mobile Stock');
        }

        return $verificacao;
    }

    public static function consultaListaReposicoes(array $filtros): array
    {
        $where = '';
        if ($filtros['itens'] < 0) {
            $itens = (int) PHP_INT_MAX;
            $offset = (int) 0;
        } else {
            $itens = (int) $filtros['itens'];
            $offset = (int) ($filtros['pagina'] - 1) * $itens;
        }
        if ($filtros['id_reposicao']) {
            Validador::validar($filtros, [
                'id_reposicao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND reposicoes.id =  :id_reposicao';
        }
        if ($filtros['id_fornecedor']) {
            Validador::validar($filtros, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND reposicoes.id_fornecedor = :id_fornecedor';
        }
        if ($filtros['referencia']) {
            Validador::validar($filtros, [
                'referencia' => [Validador::OBRIGATORIO],
            ]);

            $where .= " AND EXISTS(
                SELECT 1
                FROM produtos
                WHERE produtos.id = reposicoes_grades.id_produto
                    AND (produtos.descricao REGEXP :referencia OR produtos.id REGEXP :referencia)
            )";
        }
        if ($filtros['nome_tamanho']) {
            Validador::validar($filtros, [
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);

            $where .= " AND EXISTS(
                SELECT 1
                FROM reposicoes_grades
                WHERE reposicoes_grades.id_reposicao = reposicoes.id
                    AND reposicoes_grades.nome_tamanho = :nome_tamanho
            )";
        }
        if ($filtros['situacao']) {
            Validador::validar($filtros, [
                'situacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND reposicoes.situacao = :situacao';
        }
        if ($filtros['data_inicial_emissao'] && $filtros['data_fim_emissao']) {
            Validador::validar($filtros, [
                'data_fim_emissao' => [Validador::OBRIGATORIO],
                'data_inicial_emissao' => [Validador::OBRIGATORIO],
            ]);

            $where .=
                " AND reposicoes.data_criacao BETWEEN DATE_FORMAT(:data_emissao_inicial, '%Y-%m-%d %H:%i:%s') AND CONCAT(:data_emissao_final, ' 23:59:59')";
        }
        if ($filtros['data_inicial_previsao'] && $filtros['data_fim_previsao']) {
            Validador::validar($filtros, [
                'data_inicial_previsao' => [Validador::OBRIGATORIO],
                'data_fim_previsao' => [Validador::OBRIGATORIO],
            ]);

            $where .=
                " AND reposicoes.data_previsao BETWEEN DATE_FORMAT(:data_previsao_inicial, '%Y-%m-%d %H:%i:%s') AND CONCAT(:data_previsao_final, ' 23:59:59')";
        }
        $sql = "SELECT
                reposicoes.id,
                reposicoes.data_criacao AS `data_emissao`,
                reposicoes.data_previsao,
                reposicoes.situacao,
                GROUP_CONCAT(DISTINCT reposicoes_grades.id_produto) AS `id_produto`,
                reposicoes.valor_total,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = reposicoes.id_fornecedor
                ) AS `fornecedor`
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE 1 = 1 $where
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC
            LIMIT $itens OFFSET $offset;";

        $bindings = [];
        if ($filtros['id_reposicao']) {
            $bindings[':id_reposicao'] = (int) $filtros['id_reposicao'];
        }
        if ($filtros['id_fornecedor']) {
            $bindings[':id_fornecedor'] = (int) $filtros['id_fornecedor'];
        }
        if ($filtros['referencia']) {
            $bindings[':referencia'] = (string) $filtros['referencia'];
        }
        if ($filtros['nome_tamanho']) {
            $bindings[':nome_tamanho'] = (string) $filtros['nome_tamanho'];
        }
        if ($filtros['situacao']) {
            $bindings[':situacao'] = (int) $filtros['situacao'];
        }
        if ($filtros['data_inicial_emissao'] && $filtros['data_fim_emissao']) {
            $bindings[':data_emissao_inicial'] = (string) $filtros['data_inicial_emissao'];
            $bindings[':data_emissao_final'] = (string) $filtros['data_fim_emissao'];
        }
        if ($filtros['data_inicial_previsao'] && $filtros['data_fim_previsao']) {
            $bindings[':data_previsao_inicial'] = (string) $filtros['data_inicial_previsao'];
            $bindings[':data_previsao_final'] = (string) $filtros['data_fim_previsao'];
        }

        $compras = DB::select($sql, $bindings);

        return $compras ?? [];
    }

    public static function listaReposicoesEmAbertoAppInterno(int $idProduto)
    {
        $consultaFotoReferencia = "
                SELECT
                    COALESCE((
                            SELECT produtos_foto.caminho
                            FROM produtos_foto
                            WHERE produtos_foto.id = produtos.id
                                AND produtos_foto.tipo_foto <> 'SM'
                            ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                            LIMIT 1
                        ), '{$_ENV['URL_MOBILE']}images/img-placeholder.png'
                    ) AS caminho_foto,
                    GROUP_CONCAT(DISTINCT CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, ''))) AS referencia,
                    (SELECT colaboradores.razao_social
                     FROM colaboradores
                     WHERE colaboradores.id = produtos.id_fornecedor
                     ) AS fornecedor,
                    produtos.localizacao
                FROM produtos
                WHERE produtos.id = :id_produto";

        $resultadoReferencias = DB::selectOne($consultaFotoReferencia, ['id_produto' => $idProduto]);

        $sqlReposicoesEmAberto = "SELECT
                                    reposicoes.id AS `id_reposicao`,
                                    reposicoes.data_criacao AS `data_emissao`,
                                    reposicoes.data_previsao,
                                    reposicoes.situacao,
                                    reposicoes.valor_total,
                                    (
                                        SELECT colaboradores.razao_social
                                        FROM colaboradores
                                        WHERE colaboradores.id = reposicoes.id_fornecedor
                                    ) AS `fornecedor`,
                                    CONCAT(
                                            '[',
                                                GROUP_CONCAT(DISTINCT
                                                    JSON_OBJECT(
                                                        'id_reposicao', reposicoes.id,
                                                        'id_grade', reposicoes_grades.id,
                                                        'id_produto', reposicoes_grades.id_produto,
                                                        'cod_barras', (
                                                            SELECT produtos_grade.cod_barras
                                                            FROM produtos_grade
                                                            WHERE produtos_grade.id_produto = reposicoes_grades.id_produto
                                                                AND produtos_grade.nome_tamanho = reposicoes_grades.nome_tamanho
                                                        ),
                                                        'referencia', (
                                                            SELECT CONCAT(produtos.descricao , produtos.cores)
                                                            FROM produtos
                                                            WHERE produtos.id = reposicoes_grades.id_produto
                                                        ),
                                                        'qtd_falta_entrar', reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada,
                                                        'nome_tamanho', reposicoes_grades.nome_tamanho
                                                    ) ORDER BY reposicoes_grades.nome_tamanho ASC
                                                ),
                                            ']'
                                    ) AS `json_produtos`
                                FROM reposicoes
                                    INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
                                WHERE reposicoes_grades.id_produto = :id_produto
                                  AND (reposicoes.situacao = 'EM_ABERTO' OR reposicoes.situacao = 'PARCIALMENTE_ENTREGUE')
                                GROUP BY reposicoes.id
                                ORDER BY reposicoes.id DESC";

        $resultadoReposicoesEmAberto = DB::select($sqlReposicoesEmAberto, ['id_produto' => $idProduto]);
        if (empty($resultadoReposicoesEmAberto)) {
            throw new NotFoundHttpException('Nenhuma reposicao em aberto encontrada para este produto');
        }

        $resposta = [
            'fornecedor' => $resultadoReferencias['fornecedor'],
            'localizacao' => $resultadoReferencias['localizacao'],
            'caminho_foto' => $resultadoReferencias['caminho_foto'],
            'referencia' => $resultadoReferencias['referencia'],
            'reposicoes_em_aberto' => $resultadoReposicoesEmAberto,
        ];

        return $resposta;
    }

    public static function buscaProdutosCadastradosPorFornecedor(
        int $idFornecedor,
        string $pesquisa = '',
        int $pagina = 1
    ): array {
        $where = '';
        if (!empty($pesquisa)) {
            $where = " AND LOWER(CONCAT_WS(
                ' - ',
                produtos.id,
                produtos.nome_comercial,
                produtos.descricao
            )) REGEXP :pesquisa ";
        }

        $itensPorPagina = 20;
        $offset = ($pagina - 1) * $itensPorPagina;
        $limit = ' LIMIT :itens_por_pag OFFSET :offset ';

        $sql = "SELECT
            COALESCE(produtos.nome_comercial, produtos.descricao) nome_comercial,
                produtos.id,
                produtos.cores,
                CAST(produtos.valor_custo_produto AS DECIMAL(10,2)) valor_custo_produto,
                    CASE
                        WHEN COALESCE(produtos.descricao, '') = '' THEN 1
                        WHEN COALESCE(produtos.nome_comercial, '') = '' THEN 1
                        WHEN COALESCE(produtos.id_linha, '') = '' THEN 1
                        WHEN COALESCE(produtos.valor_custo_produto, '') = '' THEN 1
                        WHEN COALESCE(produtos.cores, '') = '' THEN 1
                        WHEN COALESCE(produtos.sexo, '') = '' THEN 1
                        WHEN COALESCE(produtos.tipo_grade, '') = '' THEN 1
                        WHEN NOT EXISTS(
                            SELECT 1
                            FROM produtos_categorias
                            INNER JOIN categorias ON categorias.id = produtos_categorias.id_categoria
                                AND (
                                    categorias.id_categoria_pai IS NULL
                                    OR categorias.id_categoria_pai IS NOT NULL
                                )
                            WHERE produtos_categorias.id_produto = produtos.id
                        ) THEN 1
                        WHEN NOT EXISTS(
                            SELECT 1
                            FROM produtos_grade
                            WHERE produtos_grade.id_produto = produtos.id
                        ) THEN 1
                        ELSE 0
                    END incorreto,
            CONCAT('[', (
                SELECT GROUP_CONCAT(DISTINCT JSON_OBJECT(
                    'nome_tamanho', produtos_grade.nome_tamanho,
                    'estoque', COALESCE(estoque_grade.estoque, 0),
                    'reservado', COALESCE(
                        (
                            SELECT COUNT(DISTINCT pedido_item.uuid)
                            FROM pedido_item
                            WHERE pedido_item.id_produto = produtos_grade.id_produto
                                AND pedido_item.nome_tamanho = produtos_grade.nome_tamanho
                                AND pedido_item.id_responsavel_estoque = 1
                            GROUP BY pedido_item.id_produto
                        ), 0
                    )
                ) ORDER BY produtos_grade.sequencia ASC)
                FROM produtos_grade
                LEFT JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                    AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                    AND estoque_grade.id_responsavel = 1
                WHERE produtos_grade.id_produto = produtos.id
            ), ']') grades,
            (
                SELECT produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id = produtos.id
                ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                LIMIT 1
            ) AS `caminho_foto`
            FROM produtos
            WHERE produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                AND produtos.permitido_reposicao = 1
                AND produtos.id_fornecedor = :id_fornecedor
                $where
            GROUP BY produtos.id
            ORDER BY produtos.id DESC
            $limit";

        $bindings = [
            'id_fornecedor' => $idFornecedor,
        ];

        if (!empty($pesquisa)) {
            $bindings['pesquisa'] = mb_strtolower($pesquisa);
        }

        $bindings['itens_por_pag'] = $itensPorPagina;
        $bindings['offset'] = $offset;

        $produtos = DB::select($sql, $bindings);
        if (empty($produtos)) {
            return [];
        }

        $previsoes = self::buscaPrevisaoProdutosFornecedor($idFornecedor);
        $produtos = collect($produtos)
            ->map(function ($produto) use ($previsoes) {
                $produto['grades'] = (array) json_decode($produto['grades'], true);
                $produto['id'] = (int) $produto['id'];
                $produto['estoque_total'] = $produto['estoque_total'] ?? 0;
                $produto['reservado_total'] = $produto['reservado_total'] ?? 0;
                $produto['saldo_total'] = $produto['saldo_total'] ?? 0;

                $previsao = $previsoes[$produto['id']] ?? [];

                $produto['grades'] = collect($produto['grades'])
                    ->map(function ($grade) use ($previsao) {
                        $grade['previsao'] = $previsao[$grade['nome_tamanho']] ?? 0;
                        $grade['total'] = $grade['estoque'] - $grade['reservado'] - $grade['previsao'];

                        return $grade;
                    })
                    ->toArray();

                $produto['nome_comercial'] = trim(trim($produto['nome_comercial']) . ' ' . trim($produto['cores']));
                $produto['valor_custo_produto'] = (float) $produto['valor_custo_produto'];
                $produto['incorreto'] = (bool) $produto['incorreto'];

                $produto['estoque_total'] += array_sum(array_column($produto['grades'], 'estoque'));
                $produto['reservado_total'] += array_sum(array_column($produto['grades'], 'reservado'));
                $produto['saldo_total'] += array_sum(array_column($produto['grades'], 'total'));
                unset($produto['cores']);

                return $produto;
            })
            ->sortByDesc('saldo_total')
            ->values()
            ->all();

        $resultado = [
            'mais_pags' => false,
            'produtos' => $produtos,
        ];

        $checkBinding = ['id_fornecedor' => $idFornecedor, 'itens_por_pag' => $itensPorPagina];
        if (!empty($pesquisa)) {
            $checkBinding['pesquisa'] = mb_strtolower($pesquisa);
        }

        $totalPags = DB::selectOneColumn(
            "
            SELECT CEIL(COUNT(DISTINCT produtos.id) / :itens_por_pag) qtd_paginas
            FROM produtos
            WHERE produtos.bloqueado = 0
                    AND produtos.fora_de_linha = 0
                    AND produtos.permitido_reposicao = 1
                    AND produtos.id_fornecedor = :id_fornecedor
                    {$where}",
            $checkBinding
        );

        $totalPags = ceil($totalPags / $itensPorPagina);
        $resultado['mais_pags'] = (bool) ($totalPags - $pagina > 0);

        return $resultado;
    }

    public static function buscaReposicao(int $idReposicao): array
    {
        $sql = "
            SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes.id_fornecedor,
                reposicoes.id_usuario,
                reposicoes.data_criacao AS `data_emissao`,
                reposicoes.data_previsao,
                reposicoes.situacao,
                reposicoes.valor_total,
                (SELECT
                     SUM(reposicoes_grades.quantidade_total)
                 FROM reposicoes_grades
                 WHERE reposicoes_grades.id_reposicao = reposicoes.id) AS `quantidade_total`
            FROM reposicoes
            WHERE reposicoes.id = :id_reposicao
        ";

        $dadosReposicao = DB::selectOne($sql, ['id_reposicao' => $idReposicao]);

        $sqlProdutos = "
        SELECT
            reposicoes_grades.id AS `id_grade`,
            reposicoes_grades.id_produto,
            (SELECT
                 produtos_foto.caminho
             FROM produtos_foto
             WHERE produtos_foto.id = reposicoes_grades.id_produto
             LIMIT 1) AS `caminho_foto`,
            reposicoes_grades.nome_tamanho,
            reposicoes_grades.valor_produto,
            reposicoes_grades.quantidade_entrada,
            reposicoes_grades.quantidade_total,
            (SELECT
                MAX(estoque_grade.estoque)
            FROM estoque_grade
            WHERE estoque_grade.id_produto = reposicoes_grades.id_produto
            AND estoque_grade.nome_tamanho = reposicoes_grades.nome_tamanho
            AND estoque_grade.id_responsavel = 1
            ) AS `estoque`
        FROM reposicoes_grades
        WHERE reposicoes_grades.id_reposicao = :id_reposicao
    ";

        $produtos = DB::select($sqlProdutos, ['id_reposicao' => $idReposicao]);

        $produtosOrganizados = [];
        foreach ($produtos as $produto) {
            if (!isset($produtosOrganizados[$produto['id_produto']])) {
                $produtosOrganizados[$produto['id_produto']] = [
                    'id_produto' => $produto['id_produto'],
                    'caminho_foto' => $produto['caminho_foto'],
                    'valor_produto' => $produto['valor_produto'],
                    'grades' => [],
                ];
            }

            $produtosOrganizados[$produto['id_produto']]['grades'][] = [
                'id_grade' => $produto['id_grade'],
                'nome_tamanho' => $produto['nome_tamanho'],
                'quantidade_entrada' => $produto['quantidade_entrada'],
                'quantidade_total' => $produto['quantidade_total'],
                'falta_entregar' => $produto['quantidade_total'] - $produto['quantidade_entrada'],
                'em_estoque' => $produto['estoque'] ?? 0,
            ];

            $quantidadeTotalEntrada = array_sum(
                array_column($produtosOrganizados[$produto['id_produto']]['grades'], 'quantidade_entrada')
            );
            $quantidadeTotalProdutos = array_sum(
                array_column($produtosOrganizados[$produto['id_produto']]['grades'], 'quantidade_total')
            );
            $valorTotalReposicaoProduto =
                array_sum(array_column($produtosOrganizados[$produto['id_produto']]['grades'], 'quantidade_total')) *
                $produto['valor_produto'];

            $produtosOrganizados[$produto['id_produto']]['quantidade_entrada_grade'] = $quantidadeTotalEntrada;
            $produtosOrganizados[$produto['id_produto']]['quantidade_total_grade'] = $quantidadeTotalProdutos;
            $produtosOrganizados[$produto['id_produto']]['valor_total_grade'] = $valorTotalReposicaoProduto;
            $produtosOrganizados[$produto['id_produto']]['situacao_grade'] =
                $quantidadeTotalEntrada === $quantidadeTotalProdutos ? 'Já entregue' : 'Em aberto';
        }

        $dadosReposicao['produtos'] = array_values($produtosOrganizados);

        return $dadosReposicao;
    }

    public static function atualizaReposicao(
        int $idReposicao,
        int $idFornecedor,
        string $dataPrevisao,
        float $valorTotal,
        array $produtos
    ) {
        $sql = "
            UPDATE reposicoes_grades
            SET reposicoes_grades.quantidade_total = :quantidade_total,
                reposicoes_grades.valor_produto = :valor_produto
            WHERE id = :id_grade";

        foreach ($produtos as $produto) {
            foreach ($produto['grades'] as $grade) {
                DB::update($sql, [
                    'quantidade_total' => $grade['quantidade_total'],
                    'valor_produto' => $produto['valor_unitario'],
                    'id_grade' => $grade['id_grade'],
                ]);
            }
        }

        $totalProdutosPrometidos = 0;
        $totalProdutosNaoBipados = 0;

        foreach ($produtos as $produto) {
            if (isset($produto['grades'])) {
                $totalProdutosPrometidos += array_sum(array_column($produto['grades'], 'quantidade_total'));
                $totalProdutosNaoBipados += array_sum(array_column($produto['grades'], 'falta_entregar'));
            }
        }

        $situacao = '';
        if ($totalProdutosNaoBipados === 0) {
            $situacao = 'ENTREGUE';
        } elseif ($totalProdutosNaoBipados !== $totalProdutosPrometidos && $totalProdutosNaoBipados > 0) {
            $situacao = 'PARCIALMENTE_ENTREGUE';
        } elseif ($totalProdutosPrometidos === $totalProdutosNaoBipados && $totalProdutosPrometidos > 0) {
            $situacao = 'EM_ABERTO';
        }

        $sql = "
            UPDATE reposicoes
            SET reposicoes.id_fornecedor = :id_fornecedor,
                reposicoes.data_previsao = :data_previsao,
                reposicoes.valor_total = :valor_total,
                reposicoes.situacao = :situacao,
                reposicoes.data_atualizacao = NOW(),
                reposicoes.id_usuario = :id_usuario
            WHERE reposicoes.id = :id_reposicao
        ";

        DB::update($sql, [
            'id_reposicao' => $idReposicao,
            'id_fornecedor' => $idFornecedor,
            'data_previsao' => $dataPrevisao,
            'valor_total' => $valorTotal,
            'situacao' => $situacao,
            'id_usuario' => Auth::id(),
        ]);
    }

    public static function atualizaEntradaGrades(array $grades): void
    {
        $idsGrades = array_column($grades, 'id_grade');
        [$binds, $valores] = ConversorArray::criaBindValues($idsGrades);
        $sqlGrades = "
            SELECT
                reposicoes_grades.id,
                reposicoes_grades.quantidade_entrada
            FROM reposicoes_grades
            WHERE reposicoes_grades.id IN ($binds)";
        $gradesAtuais = DB::select($sqlGrades, $valores);

        $gradesAtuais = array_column($gradesAtuais, 'quantidade_entrada', 'id');

        $sqlAtualizarGrade = "
            UPDATE reposicoes_grades
            SET reposicoes_grades.quantidade_entrada = :quantidade_entrada
            WHERE reposicoes_grades.id = :id_grade
        ";

        foreach ($grades as $grade) {
            $somaDaGrade = $gradesAtuais[$grade['id_grade']] + $grade['qtd_entrada'];
            DB::update($sqlAtualizarGrade, ['id_grade' => $grade['id_grade'], 'quantidade_entrada' => $somaDaGrade]);
        }
    }

    public static function atualizaSituacaoReposicao(int $idReposicao): void
    {
        $gradeTotais = DB::select(
            '
            SELECT
                reposicoes_grades.quantidade_entrada,
                reposicoes_grades.quantidade_total
            FROM reposicoes_grades
            WHERE reposicoes_grades.id_reposicao = :id_reposicao
            ',
            ['id_reposicao' => $idReposicao]
        );
        $totalEstocado = array_sum(array_column($gradeTotais, 'quantidade_entrada'));
        $totalPrometidoEmReposicao = array_sum(array_column($gradeTotais, 'quantidade_total'));

        if ($totalEstocado === $totalPrometidoEmReposicao) {
            $situacao = 'ENTREGUE';
        } elseif ($totalEstocado !== $totalPrometidoEmReposicao && $totalEstocado > 0) {
            $situacao = 'PARCIALMENTE_ENTREGUE';
        }

        DB::update('UPDATE reposicoes SET reposicoes.situacao = :situacao WHERE reposicoes.id = :id_reposicao', [
            'situacao' => $situacao,
            'id_reposicao' => $idReposicao,
        ]);
    }

    public static function atualizaAguardandoEntrada(
        int $idProduto,
        int $localizacao,
        int $idReposicao,
        array $grades
    ): void {
        $sqlProdutosAguardaEntrada = "
            INSERT INTO produtos_aguarda_entrada_estoque
            (
                produtos_aguarda_entrada_estoque.id_produto,
                produtos_aguarda_entrada_estoque.nome_tamanho,
                produtos_aguarda_entrada_estoque.localizacao,
                produtos_aguarda_entrada_estoque.tipo_entrada,
                produtos_aguarda_entrada_estoque.em_estoque,
                produtos_aguarda_entrada_estoque.identificao,
                produtos_aguarda_entrada_estoque.data_hora,
                produtos_aguarda_entrada_estoque.usuario,
                produtos_aguarda_entrada_estoque.qtd,
                produtos_aguarda_entrada_estoque.usuario_resp
             )
            VALUES
            (
                :id_produto,
                :nome_tamanho,
                :localizacao,
                'CO',
                'T',
                :id_reposicao,
                NOW(),
                :id_usuario,
                1,
                2
             )
        ";
        foreach ($grades as $grade) {
            for ($i = 0; $i < $grade['qtd_entrada']; $i++) {
                DB::insert($sqlProdutosAguardaEntrada, [
                    'id_produto' => $idProduto,
                    'nome_tamanho' => $grade['nome_tamanho'],
                    'localizacao' => $localizacao,
                    'id_reposicao' => $idReposicao,
                    'id_usuario' => Auth::id(),
                ]);
            }
        }
        ProdutosRepository::atualizaDataEntrada(DB::getPdo(), $idProduto);
    }

    public static function buscaHistoricoEntradas(string $dataInicio, string $dataFim, ?int $idProduto): array
    {
        $sql = "SELECT
                    produtos_aguarda_entrada_estoque.identificao AS `id_reposicao`,
                    produtos_aguarda_entrada_estoque.id_produto,
                    DATE_FORMAT(produtos_aguarda_entrada_estoque.data_hora, '%d/%m/%Y - %H:%i:%s') AS `data_entrada`,
                    produtos_aguarda_entrada_estoque.localizacao,
                    produtos_aguarda_entrada_estoque.nome_tamanho,
                    usuarios.nome AS `usuario`
                FROM produtos_aguarda_entrada_estoque
                LEFT JOIN usuarios ON usuarios.id = produtos_aguarda_entrada_estoque.usuario
                WHERE produtos_aguarda_entrada_estoque.data_hora BETWEEN :data_inicio AND :data_fim";

        $bindings = [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];

        if ($idProduto) {
            $sql .= ' AND produtos_aguarda_entrada_estoque.id_produto = :id_produto';
            $bindings['id_produto'] = $idProduto;
        }

        $sql .=
            ' ORDER BY produtos_aguarda_entrada_estoque.data_hora, produtos_aguarda_entrada_estoque.nome_tamanho ASC';

        $historico = DB::select($sql, $bindings);

        return $historico;
    }
}

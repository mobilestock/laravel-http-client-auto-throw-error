<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutoModel;
use MobileStock\model\Reposicao;
use MobileStock\model\ReposicaoGrade;
use MobileStock\repository\ProdutosRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReposicoesService
{
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
            WHERE (reposicoes.situacao = 'EM_ABERTO' OR reposicoes.situacao = 'PARCIALMENTE_ENTREGUE')
            GROUP BY reposicoes_grades.id_reposicao, reposicoes_grades.nome_tamanho",
            ['id_fornecedor' => $idFornecedor]
        );

        $resultado = [];
        if (!empty($lista)) {
            foreach ($lista as $item) {
                $resultado[$item['id_produto']][$item['nome_tamanho']] = $item['qtd_prevista'];
            }
        }

        return $resultado;
    }

    public static function consultaListaReposicoes(array $filtros): array
    {
        $where = '';
        $bindings = [];

        if ($filtros['itens'] < 0) {
            $itens = PHP_INT_MAX;
            $offset = 0;
        } else {
            $itens = $filtros['itens'];
            $offset = ($filtros['pagina'] - 1) * $itens;
        }

        if (!empty($filtros['id_reposicao'])) {
            Validador::validar($filtros, [
                'id_reposicao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND reposicoes.id =  :id_reposicao';
            $bindings[':id_reposicao'] = $filtros['id_reposicao'];
        }

        if (!empty($filtros['id_fornecedor'])) {
            Validador::validar($filtros, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND reposicoes.id_fornecedor = :id_fornecedor';
            $bindings[':id_fornecedor'] = $filtros['id_fornecedor'];
        }

        if (!empty($filtros['referencia'])) {
            Validador::validar($filtros, [
                'referencia' => [Validador::OBRIGATORIO],
            ]);

            $where .= " AND EXISTS(
                SELECT 1
                FROM produtos
                WHERE produtos.id = reposicoes_grades.id_produto
                    AND CONCAT_WS(' - ', produtos.id, produtos.descricao, produtos.cores) LIKE :referencia
            )";
            $bindings[':referencia'] = '%' . $filtros['referencia'] . '%';
        }

        if (!empty($filtros['nome_tamanho'])) {
            Validador::validar($filtros, [
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);

            $where .= ' AND reposicoes_grades.nome_tamanho = :nome_tamanho';
            $bindings[':nome_tamanho'] = $filtros['nome_tamanho'];
        }

        if (!empty($filtros['situacao'])) {
            Validador::validar($filtros, [
                'situacao' => [
                    Validador::OBRIGATORIO,
                    Validador::ENUM('EM_ABERTO', 'ENTREGUE', 'PARCIALMENTE_ENTREGUE'),
                ],
            ]);

            $where .= ' AND reposicoes.situacao = :situacao';
            $bindings[':situacao'] = $filtros['situacao'];
        }

        if (!empty($filtros['data_inicial_emissao']) && !empty($filtros['data_fim_emissao'])) {
            Validador::validar($filtros, [
                'data_fim_emissao' => [Validador::OBRIGATORIO, Validador::DATA],
                'data_inicial_emissao' => [Validador::OBRIGATORIO, Validador::DATA],
            ]);

            $where .= ' AND reposicoes.data_criacao BETWEEN :data_emissao_inicial AND :data_emissao_final';
            $bindings[':data_emissao_inicial'] = $filtros['data_inicial_emissao'];
            $bindings[':data_emissao_final'] = $filtros['data_fim_emissao'];
        }

        if (!empty($filtros['data_inicial_previsao']) && !empty($filtros['data_fim_previsao'])) {
            Validador::validar($filtros, [
                'data_inicial_previsao' => [Validador::OBRIGATORIO, Validador::DATA],
                'data_fim_previsao' => [Validador::OBRIGATORIO, Validador::DATA],
            ]);

            $where .= ' AND reposicoes.data_previsao BETWEEN :data_previsao_inicial AND :data_previsao_final';
            $bindings[':data_previsao_inicial'] = $filtros['data_inicial_previsao'];
            $bindings[':data_previsao_final'] = $filtros['data_fim_previsao'];
        }

        $sqlCalculoPrecoTotal = ReposicaoGrade::sqlCalculoPrecoTotalReposicao();

        $reposicoes = DB::select(
            "SELECT
                reposicoes.id,
                reposicoes.data_criacao AS `data_emissao`,
                reposicoes.data_previsao,
                reposicoes.situacao,
                $sqlCalculoPrecoTotal,
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
            LIMIT $itens OFFSET $offset",
            $bindings
        );

        return $reposicoes;
    }

    public static function listaReposicoesEmAbertoAppInterno(int $idProduto)
    {
        $sqlCalculoPrecoTotal = ReposicaoGrade::sqlCalculoPrecoTotalReposicao();

        $resultadoReposicoesEmAberto = DB::select(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes.data_criacao AS `data_emissao`,
                reposicoes.data_previsao,
                reposicoes.situacao,
                $sqlCalculoPrecoTotal,
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
                                    LIMIT 1
                                ),
                                'referencia', (
                                    SELECT CONCAT(produtos.descricao, ' ', produtos.cores)
                                    FROM produtos
                                    WHERE produtos.id = reposicoes_grades.id_produto
                                    LIMIT 1
                                ),
                                'quantidade_falta_entrar', reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada,
                                'nome_tamanho', reposicoes_grades.nome_tamanho
                            ) ORDER BY reposicoes_grades.nome_tamanho ASC
                        ),
                    ']'
                ) AS `json_produtos`
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE reposicoes_grades.id_produto = :id_produto
                AND reposicoes.situacao IN ('EM_ABERTO', 'PARCIALMENTE_ENTREGUE')
                AND (reposicoes_grades.quantidade_total - reposicoes_grades.quantidade_entrada) > 0
            GROUP BY reposicoes.id
            ORDER BY reposicoes.id DESC",
            ['id_produto' => $idProduto]
        );

        if (empty($resultadoReposicoesEmAberto)) {
            throw new NotFoundHttpException('Nenhuma reposicao em aberto encontrada para este produto');
        }

        $produtoReferencias = ProdutoModel::obtemReferencias($idProduto);

        $resposta = [
            'nome_fornecedor' => $produtoReferencias['nome_fornecedor'],
            'localizacao' => $produtoReferencias['localizacao'],
            'foto' => $produtoReferencias['foto'],
            'referencia' => $produtoReferencias['referencia'],
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

        $bindings = [
            'id_fornecedor' => $idFornecedor,
        ];

        if (!empty($pesquisa)) {
            $bindings['pesquisa'] = mb_strtolower($pesquisa);
        }

        $bindings['itens_por_pag'] = $itensPorPagina;
        $bindings['offset'] = $offset;

        $produtos = DB::select(
            "SELECT
                CONCAT(produtos.descricao, ' ', produtos.cores) nome_comercial,
                produtos.id,
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
                    END esta_incorreto,
                CONCAT(
                    '[',
                        (
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
                        ),
                    ']'
                ) json_grades,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`
            FROM produtos
            WHERE produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                AND produtos.permitido_reposicao = 1
                AND produtos.id_fornecedor = :id_fornecedor
                $where
            GROUP BY produtos.id
            ORDER BY produtos.id DESC
            $limit",
            $bindings
        );

        if (empty($produtos)) {
            return [];
        }

        $previsoes = self::buscaPrevisaoProdutosFornecedor($idFornecedor);
        $produtos = array_map(function ($produto) use ($previsoes) {
            $previsao = $previsoes[$produto['id']] ?? [];

            $produto['grades'] = array_map(function ($grade) use ($previsao) {
                $grade['previsao'] = $previsao[$grade['nome_tamanho']] ?? 0;
                $grade['total'] = $grade['estoque'] - $grade['reservado'] - $grade['previsao'];

                return $grade;
            }, $produto['grades']);

            $produto['estoque_total'] = array_sum(array_column($produto['grades'], 'estoque'));
            $produto['reservado_total'] = array_sum(array_column($produto['grades'], 'reservado'));
            $produto['saldo_total'] = array_sum(array_column($produto['grades'], 'total'));

            return $produto;
        }, $produtos);

        $resultado = [
            'mais_pags' => false,
            'produtos' => $produtos,
        ];

        $checkBinding = ['id_fornecedor' => $idFornecedor];
        if (!empty($pesquisa)) {
            $checkBinding['pesquisa'] = mb_strtolower($pesquisa);
        }

        $totalPags = DB::selectOneColumn(
            "SELECT
                COUNT(produtos.id)
            FROM produtos
            WHERE produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                AND produtos.permitido_reposicao = 1
                AND produtos.id_fornecedor = :id_fornecedor
                {$where}",
            $checkBinding
        );

        $totalPags = ceil($totalPags / $itensPorPagina);
        $resultado['mais_pags'] = $totalPags - $pagina > 0;

        return $resultado;
    }

    public static function buscaReposicao(int $idReposicao): array
    {
        $sqlCalculoPrecoTotal = ReposicaoGrade::sqlCalculoPrecoTotalReposicao();

        $dadosReposicao = DB::selectOne(
            "SELECT
                reposicoes.id AS `id_reposicao`,
                reposicoes.id_fornecedor,
                reposicoes.id_usuario,
                reposicoes.data_criacao AS `data_emissao`,
                reposicoes.data_previsao,
                reposicoes.situacao,
                $sqlCalculoPrecoTotal,
                SUM(reposicoes_grades.quantidade_total) AS `quantidade_total`
            FROM reposicoes
            INNER JOIN reposicoes_grades ON reposicoes_grades.id_reposicao = reposicoes.id
            WHERE reposicoes.id = :id_reposicao",
            ['id_reposicao' => $idReposicao]
        );

        $produtos = DB::select(
            "SELECT
                reposicoes_grades.id AS `id_grade`,
                reposicoes_grades.id_produto,
                (
                    SELECT
                        produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = reposicoes_grades.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`,
                reposicoes_grades.nome_tamanho,
                reposicoes_grades.preco_custo_produto,
                reposicoes_grades.quantidade_entrada,
                reposicoes_grades.quantidade_total,
                (
                    SELECT
                        MAX(estoque_grade.estoque)
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = reposicoes_grades.id_produto
                    AND estoque_grade.nome_tamanho = reposicoes_grades.nome_tamanho
                    AND estoque_grade.id_responsavel = 1
                ) AS `estoque`
            FROM reposicoes_grades
            WHERE reposicoes_grades.id_reposicao = :id_reposicao",
            ['id_reposicao' => $idReposicao]
        );

        $produtosOrganizados = [];
        foreach ($produtos as $produto) {
            if (!isset($produtosOrganizados[$produto['id_produto']])) {
                $produtosOrganizados[$produto['id_produto']] = [
                    'id_produto' => $produto['id_produto'],
                    'foto' => $produto['foto'],
                    'preco_custo_produto' => $produto['preco_custo_produto'],
                    'grades' => [],
                ];
            }

            $produtosOrganizados[$produto['id_produto']]['grades'][] = [
                'id_grade' => $produto['id_grade'],
                'nome_tamanho' => $produto['nome_tamanho'],
                'quantidade_entrada' => $produto['quantidade_entrada'],
                'quantidade_total' => $produto['quantidade_total'],
                'quantidade_falta_entregar' => $produto['quantidade_total'] - $produto['quantidade_entrada'],
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
                $produto['preco_custo_produto'];

            $produtosOrganizados[$produto['id_produto']]['quantidade_entrada_grade'] = $quantidadeTotalEntrada;
            $produtosOrganizados[$produto['id_produto']]['quantidade_total_grade'] = $quantidadeTotalProdutos;
            $produtosOrganizados[$produto['id_produto']]['preco_total_grade'] = $valorTotalReposicaoProduto;
            $produtosOrganizados[$produto['id_produto']]['situacao_grade'] =
                $quantidadeTotalEntrada === $quantidadeTotalProdutos ? 'Já entregue' : 'Em aberto';
        }

        $dadosReposicao['produtos'] = array_values($produtosOrganizados);

        return $dadosReposicao;
    }

    public static function atualizaEntradaGrades(array $grades): void
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
            DB::update(
                "UPDATE reposicoes_grades
                SET reposicoes_grades.quantidade_entrada = :quantidade_entrada
                WHERE reposicoes_grades.id = :id_grade",
                ['id_grade' => $grade['id_grade'], 'quantidade_entrada' => $somaDaGrade]
            );
        }
    }

    public static function atualizaSituacaoReposicao(int $idReposicao): void
    {
        $totaisGrades = DB::selectOne(
            "SELECT
                SUM(reposicoes_grades.quantidade_entrada) AS `total_estocado`,
                SUM(reposicoes_grades.quantidade_total) AS `total_prometido_em_reposicao`
            FROM reposicoes_grades
            WHERE reposicoes_grades.id_reposicao = :id_reposicao
            GROUP BY reposicoes_grades.id_reposicao",
            ['id_reposicao' => $idReposicao]
        );

        if ($totaisGrades['total_estocado'] === $totaisGrades['total_prometido_em_reposicao']) {
            $situacao = 'ENTREGUE';
        } elseif (
            $totaisGrades['total_estocado'] !== $totaisGrades['total_prometido_em_reposicao'] &&
            $totaisGrades['total_estocado'] > 0
        ) {
            $situacao = 'PARCIALMENTE_ENTREGUE';
        }

        $reposicao = new Reposicao();
        $reposicao->exists = true;
        $reposicao->id = $idReposicao;
        $reposicao->situacao = $situacao;
        $reposicao->save();
    }

    public static function atualizaAguardandoEntrada(
        int $idProduto,
        int $localizacao,
        int $idReposicao,
        array $grades
    ): void {
        foreach ($grades as $grade) {
            for ($i = 0; $i < $grade['qtd_entrada']; $i++) {
                DB::insert(
                    "INSERT INTO produtos_aguarda_entrada_estoque
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
                    )",
                    [
                        'id_produto' => $idProduto,
                        'nome_tamanho' => $grade['nome_tamanho'],
                        'localizacao' => $localizacao,
                        'id_reposicao' => $idReposicao,
                        'id_usuario' => Auth::id(),
                    ]
                );
            }
        }
        ProdutosRepository::atualizaDataEntrada(DB::getPdo(), $idProduto);
    }
}

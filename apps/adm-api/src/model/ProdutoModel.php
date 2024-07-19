<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\service\CatalogoFixoService;
use MobileStock\service\ConfiguracaoService;
use InvalidArgumentException;
use MobileStock\helper\ConversorArray;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 *
 * @property int $id
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class ProdutoModel extends Model
{
    protected $table = 'produtos';
    protected $fillable = ['permitido_reposicao', 'eh_moda', 'valor_custo_produto'];
    protected $casts = [
        'eh_moda' => 'boolean',
    ];
    public $timestamps = false;

    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_PADRAO = 82044;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_EXPRESSO = 82042;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_EXPRESSO_VOLUME = 99265;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const IDS_PRODUTOS_FRETE = [
        self::ID_PRODUTO_FRETE_PADRAO,
        self::ID_PRODUTO_FRETE_EXPRESSO,
        self::ID_PRODUTO_FRETE_EXPRESSO_VOLUME,
    ];

    /**
     * @issue https://github.com/mobilestock/backend/issues/431
     */
    protected static function boot()
    {
        parent::boot();

        self::deleting(function (self $produto): void {
            $stmt = DB::getPdo()->prepare(
                "DELETE FROM estoque_grade WHERE estoque_grade.id_produto = :id_produto;
                DELETE FROM produtos_grade WHERE produtos_grade.id_produto = :id_produto;
                UPDATE produtos_foto SET produtos_foto.id = 0 WHERE produtos_foto.id = :id_produto;
                DELETE FROM produtos_foto WHERE produtos_foto.id = 0;"
            );

            $stmt->execute([':id_produto' => $produto->id]);

            while ($stmt->nextRowset());
        });
    }

    public static function buscarProdutoPorId(int $idProduto): self
    {
        $produto = self::fromQuery(
            "SELECT
                produtos.id,
                produtos.eh_moda,
                produtos.permitido_reposicao
            FROM produtos
            WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }

    public static function buscaEstoqueFulfillmentParado(bool $catalogo = false): array
    {
        $configuracoes = ConfiguracaoService::buscaFatoresEstoqueParado();
        $qtdDiasParado = $configuracoes['qtd_maxima_dias'];

        $binds = [
            'dias_parado' => $qtdDiasParado,
            'dias_baixar_preco' => $qtdDiasParado + $configuracoes['dias_carencia'],
        ];
        $select = ",
            produtos.nome_comercial,
            SUM(estoque_grade.estoque) AS `quantidade_estoque`,
            DATE_FORMAT(_logistica_item.data, '%d/%m/%Y %H:%i') AS `data_ultima_venda`,
            DATE_FORMAT(_log_estoque_movimentacao.data, '%d/%m/%Y %H:%i') AS `data_ultima_entrada`,
            colaboradores.telefone,
            produtos.valor_custo_produto,
            produtos.promocao AS `esta_em_promocao`,
            DATE(GREATEST(
                COALESCE(_logistica_item.data, 0),
                _log_estoque_movimentacao.data
            )) <= CURRENT_DATE() - INTERVAL :dias_baixar_preco DAY AS `deve_baixar_preco`";
        $whereIdsExistentes = '';

        if ($catalogo) {
            $binds['dias_parado'] = $binds['dias_baixar_preco'];
            unset($binds['dias_baixar_preco']);
            $select = ",
                produtos.nome_comercial AS `nome_produto`,
                produtos.id_fornecedor,
                produtos.valor_venda_ml,
                IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0) AS `valor_venda_ml_historico`,
                produtos.valor_venda_ms,
                IF(produtos.promocao > 0, produtos.valor_venda_ms_historico, 0) AS `valor_venda_ms_historico`,
                SUM(estoque_grade.id_responsavel = 1) > 0 AS `possui_fulfillment`,
                produtos.quantidade_vendida";

            $idsProdutosParadosNoCatalogo = DB::selectColumns(
                "SELECT catalogo_fixo.id_produto
                FROM catalogo_fixo
                WHERE catalogo_fixo.tipo = :tipo_liquidacao",
                ['tipo_liquidacao' => CatalogoFixoService::TIPO_LIQUIDACAO]
            );

            if ($idsProdutosParadosNoCatalogo) {
                [$referenciasSql, $referenciasBind] = ConversorArray::criaBindValues($idsProdutosParadosNoCatalogo);
                $whereIdsExistentes = " AND estoque_grade.id_produto NOT IN ($referenciasSql)";
                $binds = array_merge($binds, $referenciasBind);
            }
        }

        $produtos = DB::select(
            "SELECT
                estoque_grade.id_produto,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = estoque_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`
                $select
            FROM estoque_grade
            INNER JOIN produtos ON produtos.id_fornecedor NOT IN (12, 6984)
                AND produtos.id = estoque_grade.id_produto
                AND produtos.valor_custo_produto > 1
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            INNER JOIN (
                SELECT
                    log_estoque_movimentacao.id_produto,
                    MAX(log_estoque_movimentacao.data) AS `data`
                FROM log_estoque_movimentacao
                WHERE log_estoque_movimentacao.tipo_movimentacao = 'E'
                    AND log_estoque_movimentacao.id_responsavel_estoque = 1
                    AND log_estoque_movimentacao.oldEstoque = 0
                GROUP BY log_estoque_movimentacao.id_produto
            ) AS `_log_estoque_movimentacao` ON _log_estoque_movimentacao.id_produto = estoque_grade.id_produto
            LEFT JOIN (
                SELECT
                    logistica_item.id_produto,
                    MAX(logistica_item.data_criacao) AS `data`
                FROM logistica_item
                GROUP BY logistica_item.id_produto
            ) AS `_logistica_item` ON _logistica_item.id_produto = estoque_grade.id_produto
            WHERE estoque_grade.id_responsavel = 1
                AND estoque_grade.estoque > 0
                AND DATE(GREATEST(
                    COALESCE(_logistica_item.data, 0),
                    _log_estoque_movimentacao.data
                )) <= CURRENT_DATE() - INTERVAL :dias_parado DAY
                $whereIdsExistentes
            GROUP BY estoque_grade.id_produto
            HAVING `foto_produto` IS NOT NULL;",
            $binds
        );

        if ($catalogo) {
            $produtos = array_map(function (array $produto): array {
                $produto['tipo'] = CatalogoFixoService::TIPO_LIQUIDACAO;
                return $produto;
            }, $produtos);
        }

        return $produtos;
    }

    public static function buscarCatalogoLiquidacao(int $pagina, string $origem): array
    {
        $itensPorPagina = 100;
        $offset = ($pagina - 1) * $itensPorPagina;

        $chaveValor = 'produtos.valor_venda_ms';
        $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
        if ($origem === Origem::ML) {
            $chaveValor = 'produtos.valor_venda_ml';
            $chaveValorHistorico = 'produtos.valor_venda_ml_historico';
        }

        $produtos = DB::select(
            "SELECT produtos.id `id_produto`,
                produtos.nome_comercial `nome_produto`,
                $chaveValor `valor_venda`,
                IF (produtos.promocao > 0, $chaveValorHistorico, 0) `valor_venda_historico`,
                produtos.quantidade_vendida,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto_produto`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) `json_grade_estoque`
            FROM catalogo_fixo
            INNER JOIN produtos ON produtos.id = catalogo_fixo.id_produto
            INNER JOIN estoque_grade ON estoque_grade.id_produto = catalogo_fixo.id_produto
                AND estoque_grade.id_responsavel = 1
                AND estoque_grade.estoque > 0
            WHERE produtos.bloqueado = 0
                AND catalogo_fixo.tipo = :tipo_liquidacao
            GROUP BY catalogo_fixo.id_produto
            ORDER BY SUM(estoque_grade.estoque) DESC
            LIMIT $itensPorPagina OFFSET $offset",
            [':tipo_liquidacao' => CatalogoFixoService::TIPO_LIQUIDACAO]
        );

        if (!empty($produtos)) {
            # @issue: https://github.com/mobilestock/backend/issues/397
            $produtos = array_map(function ($item) {
                $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grade_estoque']);
                $categoria = (object) ['tipo' => '', 'valor' => ''];

                $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda']);
                return [
                    'id_produto' => $item['id_produto'],
                    'nome' => $item['nome_produto'],
                    'preco' => $item['valor_venda'],
                    'preco_original' => $item['valor_venda_historico'],
                    'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                    'valor_parcela' => $valorParcela,
                    'quantidade_vendida' => $item['quantidade_vendida'],
                    'foto' => $item['foto_produto'],
                    'grades' => $grades,
                    'categoria' => $categoria,
                ];
            }, $produtos);
        }

        return $produtos;
    }

    public static function verificaExistenciaProduto(int $idProduto, ?string $nomeTamanho): void
    {
        $innerJoin = '';
        $bindings = ['id_produto' => $idProduto];
        if ($nomeTamanho) {
            $innerJoin = 'INNER JOIN produtos_grade ON produtos_grade.id_produto = produtos.id
                AND produtos_grade.nome_tamanho = :nome_tamanho';
            $bindings['nome_tamanho'] = $nomeTamanho;
        }
        $existeProduto = DB::selectOneColumn(
            "SELECT EXISTS (
                SELECT 1
                FROM produtos
                $innerJoin
                WHERE produtos.id = :id_produto
            ) AS existe_produto",
            $bindings
        );
        if (empty($existeProduto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }
    }

    /**
     * @param array<int> $idsProdutos
     */
    public static function buscaProdutosSalvaReposicao(array $idsProdutos): array
    {
        [$referenciaSql, $binds] = ConversorArray::criaBindValues($idsProdutos, 'id_produto');
        $produtos = DB::select(
            "SELECT
                produtos.id,
                produtos.valor_custo_produto AS `preco_custo`
            FROM produtos
            WHERE produtos.id IN ($referenciaSql)
            AND produtos.permitido_reposicao = 1",
            $binds
        );

        if (count($produtos) !== count($idsProdutos)) {
            throw new InvalidArgumentException(
                'Pelo menos um dos produtos não tem permissão para reposição fulfillment.'
            );
        }

        return $produtos;
    }

    public static function obtemReferencias(int $idProduto): array
    {
        $resultadoReferencias = DB::selectOne(
            "SELECT
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ), '{$_ENV['URL_MOBILE']}images/img-placeholder.png'
                ) AS `foto`,
                GROUP_CONCAT(DISTINCT CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, ''))) AS `referencia`,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) AS `nome_fornecedor`,
                produtos.localizacao
            FROM produtos
            WHERE produtos.id = :id_produto",
            ['id_produto' => $idProduto]
        );

        return $resultadoReferencias;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/438
     */
    public static function buscaProdutosCadastradosPorFornecedor(
        int $idFornecedor,
        string $pesquisa,
        int $pagina
    ): array {
        $where = '';
        $bindings[':id_fornecedor'] = $idFornecedor;

        if (!empty($pesquisa)) {
            $where = "AND CONCAT_WS(
                        ' - ',
                        produtos.id,
                        produtos.nome_comercial,
                        produtos.descricao
                    ) LIKE :pesquisa ";

            $bindings[':pesquisa'] = "%$pesquisa%";
            $pageBinding[':pesquisa'] = "%$pesquisa%";
        }

        $itensPorPagina = 20;
        $offset = ($pagina - 1) * $itensPorPagina;

        $bindings[':itens_por_pag'] = $itensPorPagina;
        $bindings[':offset'] = $offset;

        $produtos = DB::select(
            "SELECT
                CONCAT(produtos.descricao, ' ', produtos.cores) AS `nome_comercial`,
                produtos.id AS `id_produto`,
                produtos.valor_custo_produto,
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
                                        GROUP BY pedido_item.id_produto
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
                ) AS `json_grades`,
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
            LIMIT :itens_por_pag OFFSET :offset",
            $bindings
        );

        if (empty($produtos)) {
            return ['produtos' => [], 'mais_pags' => false];
        }

        $produtos = array_map(function ($produto): array {
            $produto['grades'] = array_map(function ($grade): array {
                $grade['total'] = $grade['estoque'] - $grade['reservado'];

                return $grade;
            }, $produto['grades']);

            return $produto;
        }, $produtos);

        $resultado = [
            'mais_pags' => false,
            'produtos' => $produtos,
        ];

        $pageBinding['id_fornecedor'] = $idFornecedor;

        $totalPags = DB::selectOneColumn(
            "SELECT
                COUNT(produtos.id)
            FROM produtos
            WHERE produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                AND produtos.permitido_reposicao = 1
                AND produtos.id_fornecedor = :id_fornecedor
                $where",
            $pageBinding
        );

        $totalPags = ceil($totalPags / $itensPorPagina);
        $resultado['mais_pags'] = $totalPags - $pagina > 0;

        return $resultado;
    }

    public static function buscaDevolucoesAguardandoEntrada(int $idProduto, ?string $nomeTamanho): array
    {
        $condicao = '';
        $binds[':id_produto'] = $idProduto;
        if ($nomeTamanho) {
            $condicao = ' AND produtos_aguarda_entrada_estoque.nome_tamanho = :nome_tamanho';
            $binds[':nome_tamanho'] = $nomeTamanho;
        }

        $informacoes = DB::select(
            "SELECT
            produtos_aguarda_entrada_estoque.id,
            produtos_aguarda_entrada_estoque.nome_tamanho,
            produtos_aguarda_entrada_estoque.tipo_entrada,
            DATE_FORMAT(produtos_aguarda_entrada_estoque.data_hora, '%d/%m/%Y') data_hora,
            (SELECT
                usuarios.nome
            FROM usuarios
            WHERE produtos_aguarda_entrada_estoque.usuario = usuarios.id
            LIMIT 1) usuario
            FROM produtos_aguarda_entrada_estoque
            WHERE produtos_aguarda_entrada_estoque.id_produto = :id_produto
                AND produtos_aguarda_entrada_estoque.tipo_entrada = 'TR'
                AND produtos_aguarda_entrada_estoque.em_estoque = 'F' $condicao;",
            $binds
        );

        return $informacoes;
    }

    public static function logsMovimentacoesLocalizacoes(int $idProduto, ?string $nomeTamanho): array
    {
        $origem = app(Origem::class);
        $bindings = [':id_produto' => $idProduto];
        $where = ' AND DATE(log_estoque_movimentacao.data) = CURDATE()';

        if ($origem->ehAplicativoInterno()) {
            $where = ' AND DATE(log_estoque_movimentacao.data) = CURDATE() - INTERVAL 10 DAY';
        }

        if ($nomeTamanho) {
            $bindings[':nome_tamanho'] = $nomeTamanho;
            $where .= ' AND log_estoque_movimentacao.nome_tamanho = :nome_tamanho ';
        }

        $logs = DB::selectOne(
            "SELECT
                CONCAT(
                    '[',
                        (
                            SELECT GROUP_CONCAT(
                                JSON_OBJECT(
                                'new', log_produtos_localizacao.new_localizacao,
                                'old', log_produtos_localizacao.old_localizacao,
                                'qtd', log_produtos_localizacao.qtd_entrada,
                                'usuario', (
                                                SELECT
                                                    usuarios.nome
                                                FROM usuarios
                                                WHERE usuarios.id = log_produtos_localizacao.usuario
                                                LIMIT 1
                                            ),
                                'data', DATE_FORMAT(log_produtos_localizacao.data_hora, '%d/%m/%Y'),
                                'data_order', log_produtos_localizacao.data_hora
                                )
                            )
                            FROM log_produtos_localizacao
                            WHERE log_produtos_localizacao.id_produto = produtos.id
                            GROUP BY log_produtos_localizacao.id_produto
                            ORDER BY log_produtos_localizacao.data_hora DESC
                        ),
                    ']'
                ) AS `json_historico_localizacoes`,
                CONCAT(
                    '[',
                        (
                            SELECT GROUP_CONCAT(
                                JSON_OBJECT(
                                'descricao', log_estoque_movimentacao.descricao,
                                'tamanho', log_estoque_movimentacao.nome_tamanho,
                                'data_hora', DATE_FORMAT(log_estoque_movimentacao.data, '%d/%m/%Y %H:%i:%s'),
                                'tipo_movimentacao', log_estoque_movimentacao.tipo_movimentacao
                                )
                            )
                            FROM log_estoque_movimentacao
                            WHERE log_estoque_movimentacao.id_produto = produtos.id
                                $where
                            ORDER BY log_estoque_movimentacao.data DESC
                        ),
                    ']'
                ) AS `json_historico_movimentacoes`
            FROM produtos
            WHERE produtos.id = :id_produto",
            $bindings
        );

        return $logs;
    }
}

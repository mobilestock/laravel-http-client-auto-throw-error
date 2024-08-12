<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use MobileStock\helper\ConversorArray;
use MobileStock\service\ConfiguracaoService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @issue https://github.com/mobilestock/backend/issues/488
 * @issue https://github.com/mobilestock/backend/issues/489
 * @property int $id
 * @property string $descricao
 * @property int $id_fornecedor
 * @property bool $bloqueado
 * @property int $id_linha
 * @property string $data_entrada
 * @property bool $promocao
 * @property int $grade
 * @property string $forma
 * @property string $nome_comercial
 * @property float $preco_promocao
 * @property float $valor_custo_produto
 * @property int $id_usuario
 * @property int $tipo_grade
 * @property string $sexo
 * @property string $cores
 * @property bool $fora_de_linha
 * @property string $embalagem
 * @property string $outras_informacoes
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 * @property bool $em_liquidacao
 * @property string $data_primeira_entrada
 * @property string $localizacao
 */
class Produto extends Model
{
    protected $fillable = [
        'descricao',
        'id_fornecedor',
        'bloqueado',
        'id_linha',
        'data_entrada',
        'promocao',
        'outras_informacoes',
        'forma',
        'embalagem',
        'nome_comercial',
        'preco_promocao',
        'valor_custo_produto',
        'id_usuario',
        'tipo_grade',
        'sexo',
        'cores',
        'fora_de_linha',
        'permitido_reposicao',
        'eh_moda',
        'em_liquidacao',
        'localizacao',
    ];
    protected $casts = [
        'eh_moda' => 'boolean',
        'promocao' => 'boolean',
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
    public const PRECO_CUSTO_MINIMO = 0.5;

    protected static function boot()
    {
        parent::boot();

        self::updating(function (self $model) {
            if ($model->valor_custo_produto > $model->getOriginal('valor_custo_produto') && $model->em_liquidacao) {
                throw new UnprocessableEntityHttpException(
                    'Não é permitido aumentar o preco de custo do produto caso esteja em liquidação'
                );
            }

            if (!$model->isDirty('fora_de_linha') || $model->fora_de_linha) {
                return;
            }

            $ehExterno = DB::selectOneColumn(
                "SELECT EXISTS(
                SELECT 1
                FROM estoque_grade
                WHERE estoque_grade.id_responsavel <> 1
                    AND estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = :id_produto
            ) `existe_estoque_externo`;",
                [':id_produto' => $model->id]
            );

            if (!$ehExterno) {
                return;
            }
            $linhasAfetadas = DB::update(
                "UPDATE estoque_grade SET
                estoque_grade.estoque = 0,
                estoque_grade.tipo_movimentacao = 'X',
                estoque_grade.descricao = 'Estoque zerado porque o produto foi colocado como fora de linha'
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;",
                [':id_produto' => $model->id]
            );

            if ($linhasAfetadas < 1) {
                throw new Exception('Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.');
            }

            //            $query = "INSERT INTO log_produtos_localizacao
            //                        (id_produto, old_localizacao, new_localizacao, usuario)
            //                    VALUE
            //                        (:id_produto, :antiga_localizacao, :nova_localizacao, :usuario)";
            //            $stmt = $conexao->prepare($query);
            //            $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
            //            $stmt->bindValue(':antiga_localizacao', $antigaLocalizacao, PDO::PARAM_INT);
            //            $stmt->bindValue(':nova_localizacao', $localizacao, PDO::PARAM_INT);
            //            $stmt->bindValue(':usuario', $idUsuario, PDO::PARAM_INT);
            //            $stmt->execute();
        });

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
                produtos.descricao,
                produtos.id_fornecedor,
                produtos.bloqueado AS `bool_bloqueado`,
                produtos.id_linha,
                produtos.outras_informacoes,
                produtos.forma,
                produtos.embalagem,
                produtos.nome_comercial,
                produtos.valor_custo_produto,
                produtos.tipo_grade,
                produtos.sexo,
                produtos.cores,
                produtos.fora_de_linha AS `bool_fora_de_linha`,
                produtos.permitido_reposicao AS `bool_permitido_reposicao`,
                produtos.eh_moda,
                produtos.em_liquidacao,
                produtos.data_primeira_entrada,
                produtos.localizacao
            FROM produtos
            WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }

    public static function buscaEstoqueFulfillmentParado(): array
    {
        $configuracoes = ConfiguracaoService::buscaFatoresEstoqueParado();
        $qtdDiasParado = $configuracoes['qtd_maxima_dias'];

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
                ) AS `foto_produto`,
                produtos.nome_comercial,
                SUM(estoque_grade.estoque) AS `quantidade_estoque`,
                DATE_FORMAT(_logistica_item.data, '%d/%m/%Y %H:%i') AS `data_ultima_venda`,
                DATE_FORMAT(_log_estoque_movimentacao.data, '%d/%m/%Y %H:%i') AS `data_ultima_entrada`,
                colaboradores.telefone,
                produtos.valor_custo_produto AS `preco_custo`,
                produtos.promocao AS `em_promocao`,
                produtos.em_liquidacao OR
                    DATE(
                        GREATEST(
                            COALESCE(_logistica_item.data, 0),
                            _log_estoque_movimentacao.data
                        )
                    ) <= CURRENT_DATE() - INTERVAL :dias_baixar_preco DAY AS `deve_baixar_preco`,
                produtos.em_liquidacao
            FROM estoque_grade
            INNER JOIN produtos ON produtos.id_fornecedor NOT IN (12, 6984)
                AND produtos.id = estoque_grade.id_produto
                AND produtos.valor_custo_produto > :preco_custo_minimo
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
                AND (
                    produtos.em_liquidacao OR
                        DATE(
                            GREATEST(
                                COALESCE(_logistica_item.data, 0),
                                _log_estoque_movimentacao.data
                            )
                        ) <= CURRENT_DATE() - INTERVAL :dias_parado DAY
                )
            GROUP BY estoque_grade.id_produto
            HAVING `foto_produto` IS NOT NULL;",
            [
                'dias_parado' => $qtdDiasParado,
                'dias_baixar_preco' => $qtdDiasParado + $configuracoes['dias_carencia'],
                'preco_custo_minimo' => self::PRECO_CUSTO_MINIMO,
            ]
        );

        return $produtos;
    }

    public static function desativaPromocaoMantemValores(int $idProduto): void
    {
        $produto = self::buscarProdutoPorId($idProduto);
        $valorCustoProduto = $produto->valor_custo_produto;
        $produto->preco_promocao = 0;
        $produto->save();

        $produto->refresh();

        $produto->valor_custo_produto = $valorCustoProduto;
        $produto->save();
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
     * @issue https://github.com/mobilestock/backend/issues/438
     */
    public static function buscaCadastrados(string $pesquisa, int $pagina): array
    {
        $where = '';
        $pageBinding = [];

        if (!Gate::allows('ADMIN')) {
            $where = 'AND produtos.id_fornecedor = :id_fornecedor ';
            $bindings[':id_fornecedor'] = Auth::user()->id_colaborador;
            $pageBinding['id_fornecedor'] = Auth::user()->id_colaborador;
        }

        if (!empty($pesquisa)) {
            $where .= "AND LOWER(CONCAT_WS(
                        ' - ',
                        produtos.id,
                        produtos.nome_comercial,
                        produtos.descricao,
                        colaboradores.razao_social,
                        colaboradores.telefone
                    )) LIKE LOWER(:pesquisa)";

            $bindings[':pesquisa'] = "%$pesquisa%";
            $pageBinding[':pesquisa'] = "%$pesquisa%";
        }

        $itensPorPagina = 30;
        $offset = ($pagina - 1) * $itensPorPagina;

        $bindings[':itens_por_pag'] = $itensPorPagina;
        $bindings[':offset'] = $offset;

        $produtos = DB::select(
            "SELECT
                produtos.id_fornecedor,
                CONCAT(colaboradores.id, '-', colaboradores.razao_social) AS `fornecedor`,
                CONCAT(produtos.descricao, ' ', produtos.cores) AS `descricao`,
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
                COALESCE(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                        ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                        LIMIT 1
                    ),
                    '{$_ENV['URL_MOBILE']}/images/img-placeholder.png'
                ) AS `foto`
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            WHERE produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                AND produtos.permitido_reposicao = 1
                $where
            GROUP BY produtos.id
            ORDER BY produtos.id DESC
            LIMIT :itens_por_pag OFFSET :offset",
            $bindings
        );

        if (empty($produtos)) {
            return ['produtos' => [], 'possui_mais_paginas' => false];
        }

        $produtos = array_map(function ($produto): array {
            $produto['grades'] = array_map(function ($grade): array {
                $grade['total'] = $grade['estoque'] - $grade['reservado'];

                return $grade;
            }, $produto['grades']);

            return $produto;
        }, $produtos);

        $resultado = [
            'possui_mais_paginas' => false,
            'produtos' => $produtos,
        ];

        $contagemProdutos = DB::selectOneColumn(
            "SELECT
                COUNT(produtos.id)
            FROM produtos
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            WHERE produtos.bloqueado = 0
                AND produtos.fora_de_linha = 0
                AND produtos.permitido_reposicao = 1
                $where",
            $pageBinding
        );

        $contagemProdutos = ceil($contagemProdutos / $itensPorPagina);
        $resultado['possui_mais_paginas'] = $contagemProdutos - $pagina > 0;

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

    public static function buscarLocalizacao(array $produtosIds): ?string
    {
        [$idsSql, $binds] = ConversorArray::criaBindValues($produtosIds);
        $localizacoes = DB::selectColumns(
            "SELECT produtos.localizacao
            FROM produtos
            WHERE produtos.id IN ($idsSql)",
            $binds
        );

        if (count($localizacoes) !== 1) {
            throw new Exception('Erro ao buscar localização dos produtos');
        }

        return current($localizacoes);
    }
}

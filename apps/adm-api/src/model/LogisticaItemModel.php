<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\ReputacaoFornecedoresService;
use MobileStock\service\Separacao\separacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue https://github.com/mobilestock/backend/issues/487
 * https://github.com/mobilestock/backend/issues/131
 * @property string $uuid_produto
 * @property string $sku
 * @property int $id_usuario
 * @property string $situacao
 * @property int $id_produto
 * @property int $id_cliente
 * @property int $id_transacao
 * @property int $id_colaborador_tipo_frete
 * @property string $nome_tamanho
 */
class LogisticaItemModel extends Model
{
    public const REGEX_ETIQUETA_UUID_PRODUTO_CLIENTE = "/^[0-9]+_[0-9A-z]+\.[0-9]+$/";
    public const REGEX_ETIQUETA_PRODUTO_SKU_LEGADO = "/^SKU_\d+_\d+$/";
    public const REGEX_ETIQUETA_PRODUTO_SKU = '/^SKU\d+$/';
    public const REGEX_ETIQUETA_PRODUTO_COD_BARRAS = "/^\d+$/";
    public const SITUACAO_FINAL_PROCESSO_LOGISTICA = 3;

    protected $table = 'logistica_item';
    protected $primaryKey = 'uuid_produto';
    protected $keyType = 'string';
    protected $fillable = ['situacao', 'id_usuario', 'sku'];

    public static function converteSituacao(string $situacao): string
    {
        $situacoes = [
            'PE' => 'Pendente',
            'SE' => 'Separado',
            'CO' => 'Conferido',
            'RE' => 'Rejeitado',
            'DE' => 'Devolução',
            'DF' => 'Defeito',
            'ES' => 'Estorno',
        ];

        if (array_key_exists($situacao, $situacoes)) {
            return $situacoes[$situacao];
        } else {
            throw new Exception('Situacao invalido');
        }
    }

    /**
     * @return array<self|string>
     */
    public static function buscaInformacoesLogisticaItem(string $uuidProduto): array
    {
        $logisticaItem = self::fromQuery(
            "SELECT
                logistica_item.id_produto,
                logistica_item.situacao,
                logistica_item.uuid_produto,
                logistica_item.sku,
                logistica_item.nome_tamanho,
                produtos_grade.cod_barras
            FROM logistica_item
            INNER JOIN produtos_grade ON produtos_grade.id_produto = logistica_item.id_produto
                AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
            WHERE logistica_item.uuid_produto = :uuid_produto",
            ['uuid_produto' => $uuidProduto]
        )->first();
        if (empty($logisticaItem)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }
        return [$logisticaItem, $logisticaItem->cod_barras];
    }

    public function liberarLogistica(string $origem): void
    {
        $condicaoProdutoPago = '';
        $colaboradorTipoFrete = $this->id_colaborador_tipo_frete;

        if ($origem === Origem::ML) {
            $condicaoProdutoPago = 'AND pedido_item.id_transacao = :idTransacao';
            $colaboradorTipoFrete = TransacaoFinanceiraItemProdutoService::buscaFreteTransacao(
                DB::getPdo(),
                $this->id_transacao
            );
        }

        $produtos = DB::select(
            "SELECT
                    (
                        SELECT
                            SUM(transacao_financeiras_produtos_itens.preco)
                        FROM transacao_financeiras_produtos_itens
                        WHERE
                            transacao_financeiras_produtos_itens.id_transacao = pedido_item.id_transacao
                            AND transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
                    ) preco,
                    :idUsuario id_usuario,
                    :idCliente id_cliente,
                    pedido_item.id_produto,
                    pedido_item.nome_tamanho,
                    pedido_item.uuid uuid_produto,
                    pedido_item.id_transacao,
                    pedido_item.id_responsavel_estoque,
                    :colaboradorTipoFrete id_colaborador_tipo_frete,
                    pedido_item.observacao
                FROM pedido_item
                WHERE
                    pedido_item.id_cliente = :idCliente
                    AND pedido_item.situacao = 'DI'
                    $condicaoProdutoPago
                    ;",
            [
                ':idCliente' => $this->id_cliente,
                ':idUsuario' => Auth::id(),
                ':colaboradorTipoFrete' => $colaboradorTipoFrete,
            ] + ($origem === Origem::ML ? [':idTransacao' => $this->id_transacao] : [])
        );

        if (empty($produtos)) {
            throw new RuntimeException('Falha ao identificar os produtos para inserir na logistica.');
        }

        $logisticaItem = DB::table('logistica_item');
        $pedidoItem = DB::table('pedido_item');

        $uuids = array_column($produtos, 'uuid_produto');

        /**
         * logistica_item.preco
         * logistica_item.id_cliente
         * logistica_item.id_usuario
         * logistica_item.id_produto
         * logistica_item.nome_tamanho
         * logistica_item.uuid_produto
         * logistica_item.id_transacao
         * logistica_item.id_responsavel_estoque
         * logistica_item.id_colaborador_tipo_frete
         * logistica_item.observacao
         */
        $stmt = DB::getPdo()->prepare(
            $logisticaItem->grammar->compileInsert($logisticaItem, $produtos) .
                ';' .
                $pedidoItem->grammar->compileDelete($pedidoItem->whereIn('pedido_item.uuid', $uuids))
        );
        $stmt->execute(
            array_merge($logisticaItem->cleanBindings(Arr::flatten($produtos, 1)), $pedidoItem->cleanBindings($uuids))
        );

        $linhasAtualizadas = 0;
        do {
            $linhasAtualizadas += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if (count($produtos) + count($uuids) !== $linhasAtualizadas) {
            throw new RuntimeException('Falha ao inserir os produtos na logistica.');
        }

        separacaoService::alertarSepararProdutoExterno($this->id_transacao);

        $job = new GerenciarAcompanhamento($uuids);
        dispatch($job->afterCommit());
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public static function buscaInformacoesProdutoPraAtualizarPrevisao(string $uuidProduto): array
    {
        $idTipoFreteTransportadora = TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA;
        $auxiliarBuscarPedidos = TransportadoresRaio::retornaSqlAuxiliarPrevisaoMobileEntregas();

        $informacao = DB::selectOne(
            "SELECT
                logistica_item.id_transacao,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.id_responsavel_estoque,
                $auxiliarBuscarPedidos
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            LEFT JOIN transportadores_raios ON transportadores_raios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
            INNER JOIN municipios ON municipios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
            WHERE logistica_item.uuid_produto = :uuid_produto;",
            ['uuid_produto' => $uuidProduto, 'id_tipo_frete_transportadora' => $idTipoFreteTransportadora]
        );
        if (empty($informacao)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $informacao;
    }

    public static function buscaProdutosCancelamento(): array
    {
        $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores(['dias_mensurar_cancelamento']);
        $uuids = DB::selectColumns(
            "SELECT
                logistica_item.uuid_produto
             FROM logistica_item
             WHERE logistica_item.situacao < :situacao
               AND DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) > :dias;",
            [
                ':situacao' => self::SITUACAO_FINAL_PROCESSO_LOGISTICA,
                ':dias' => $fatores['dias_mensurar_cancelamento'],
            ]
        );

        return $uuids;
    }

    public static function buscaListaProdutosCancelados(): array
    {
        $produtosCancelados = DB::selectColumns(
            "SELECT logistica_item_data_alteracao.uuid_produto
            FROM logistica_item_data_alteracao
            WHERE logistica_item_data_alteracao.situacao_nova = 'RE'
                AND DATE(logistica_item_data_alteracao.data_criacao) >= CURRENT_DATE() - INTERVAL 1 MONTH"
        );
        if (empty($produtosCancelados)) {
            return [];
        }

        [$bind, $valores] = ConversorArray::criaBindValues($produtosCancelados, 'uuid_produto');
        $sqlCriterioAfetarReputacao = ReputacaoFornecedoresService::sqlCriterioCancelamentoAfetarReputacao(
            'fornecedor_colaboradores.id'
        );
        $produtos = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id_produto,
                transacao_financeiras_produtos_itens.nome_tamanho AS `tamanho`,
                transacao_financeiras_produtos_itens.uuid_produto,
                transacao_financeiras_produtos_itens.id_transacao,
                fornecedor_colaboradores.razao_social AS `nome_fornecedor`,
                reputacao_fornecedores.reputacao,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = transacao_financeiras.pagador
                    LIMIT 1
                ) AS `nome_cliente`,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i') AS `data_compra`,
                DATE_FORMAT(logistica_item_data_alteracao.data_criacao, '%d/%m/%Y %H:%i') AS `data_cancelamento`,
                $sqlCriterioAfetarReputacao AS `porque_afetou_reputacao`
            FROM logistica_item_data_alteracao
            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.uuid_produto = logistica_item_data_alteracao.uuid_produto
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            INNER JOIN colaboradores AS `fornecedor_colaboradores` ON fornecedor_colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
            WHERE logistica_item_data_alteracao.situacao_nova = 'RE'
                AND logistica_item_data_alteracao.uuid_produto IN ($bind)
            GROUP BY transacao_financeiras_produtos_itens.uuid_produto
            HAVING porque_afetou_reputacao IS NOT NULL
            ORDER BY logistica_item_data_alteracao.data_criacao DESC;",
            $valores
        );

        return $produtos;
    }

    public static function buscaProdutosParaAdicionarNoAcompanhamento(
        int $idDestinatario,
        int $idTipoFrete,
        int $idCidade,
        ?int $idRaio = null
    ): array {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $parametros = [
            ':id_tipo_frete' => $idTipoFrete,
        ];

        $inner = '';

        if (!in_array($idTipoFrete, explode(',', $idTipoFreteEntregaCliente))) {
            $where = " AND logistica_item.id_colaborador_tipo_frete = :id_colaborador_tipo_frete
            AND IF(
                tipo_frete.tipo_ponto = 'PM',
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') = :id_raio,
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade') = :id_cidade
            ) ";
            $inner = " INNER JOIN transacao_financeiras_metadados ON
            transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON' ";

            $parametros[':id_colaborador_tipo_frete'] = $idDestinatario;
            $parametros[':id_raio'] = $idRaio;
            $parametros[':id_cidade'] = $idCidade;
        } else {
            $where = 'AND logistica_item.id_cliente = :id_cliente';
            $parametros[':id_cliente'] = $idDestinatario;
        }

        $sql = "SELECT
                logistica_item.uuid_produto
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            $inner
            LEFT JOIN entregas ON entregas.id = logistica_item.id_entrega
            WHERE
                tipo_frete.id = :id_tipo_frete
                AND IF(logistica_item.id_entrega IS NOT NULL, entregas.situacao = 'AB', TRUE)
                $where";

        $resultado = DB::selectColumns($sql, $parametros);
        return $resultado;
    }

    public static function buscaAcompanhamentoPendentePorUuidProduto(array $listaDeUuid): array
    {
        [$itemsSql, $bind] = ConversorArray::criaBindValues($listaDeUuid);
        $idColaboradorEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;
        $sql = "SELECT
                    IF(logistica_item.id_colaborador_tipo_frete IN ($idColaboradorEntregaCliente),
                        logistica_item.id_cliente,
                        logistica_item.id_colaborador_tipo_frete
                    ) id_destinatario,
                    IF(tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id_colaborador IN ($idColaboradorEntregaCliente),
                        JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                        colaboradores_enderecos.id_cidade
                    ) id_cidade,
                    JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') id_raio,
                    tipo_frete.id id_tipo_frete,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(
                            DISTINCT
                            JSON_OBJECT(
                                'uuid_produto', logistica_item.uuid_produto,
                                'esta_no_acompanhamento', acompanhamento_item_temp.id_acompanhamento IS NOT NULL
                            )
                        ),
                        ']'
                    ) uuids_produtos_json,
                    (
                        SELECT acompanhamento_temp.id
                        FROM acompanhamento_temp
                        WHERE
                            acompanhamento_temp.id_destinatario =
                                IF(logistica_item.id_colaborador_tipo_frete IN ($idColaboradorEntregaCliente),
                                    logistica_item.id_cliente,
                                    logistica_item.id_colaborador_tipo_frete
                                )
                            AND acompanhamento_temp.id_tipo_frete = tipo_frete.id
                            AND acompanhamento_temp.id_cidade = IF(
                                tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id_colaborador IN ($idColaboradorEntregaCliente),
                                JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                                colaboradores_enderecos.id_cidade
                            )
                            AND IF(
								tipo_frete.tipo_ponto = 'PM',
								JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio') = acompanhamento_temp.id_raio,
                                TRUE
							)
                        LIMIT 1
                    ) id_acompanhamento
                FROM logistica_item
                INNER JOIN transacao_financeiras_metadados ON
                    transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    AND transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = IF(
                        tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id_colaborador IN ($idColaboradorEntregaCliente),
                        logistica_item.id_cliente,
                        logistica_item.id_colaborador_tipo_frete
                    ) AND
                    colaboradores_enderecos.eh_endereco_padrao = 1
                LEFT JOIN acompanhamento_item_temp ON acompanhamento_item_temp.uuid_produto = logistica_item.uuid_produto
                WHERE
                    logistica_item.uuid_produto IN ($itemsSql)
                    GROUP BY
                        id_destinatario,
                        tipo_frete.id,
                        IF(
                            tipo_frete.tipo_ponto = 'PM',
                            JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio'),
                            TRUE
                        );";
        $resultado = DB::select($sql, $bind);

        return $resultado;
    }

    public static function buscaProdutosComConferenciaAtrasada(): array
    {
        $produtosAtrasados = DB::select(
            "SELECT
                colaboradores.telefone,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto_produto,
                DATEDIFF_DIAS_UTEIS(CURDATE(), DATE(logistica_item.data_criacao)) = 2 `esta_atrasado`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
            WHERE logistica_item.id_responsavel_estoque <> 1
                AND logistica_item.situacao < :situacao_logistica
            HAVING esta_atrasado;",
            ['situacao_logistica' => self::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        );

        return $produtosAtrasados;
    }

    public static function buscaUltimosExternosVendidos(int $pagina, string $data): array
    {
        $limite = '';
        $condicao = '';
        $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores(['dias_mensurar_cancelamento']);
        $binds = [
            ':dias_para_cancelar' => $fatores['dias_mensurar_cancelamento'],
            ':situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA,
        ];

        if (!empty($data)) {
            $condicao = ' AND DATE(logistica_item.data_criacao) = DATE(:data_buscada) ';
            $binds[':data_buscada'] = $data;
        } else {
            $itensPorPag = 150;
            $offset = $itensPorPag * ($pagina - 1);
            $limite = 'LIMIT :itens_por_pag OFFSET :offset';
            $binds[':itens_por_pag'] = $itensPorPag;
            $binds[':offset'] = $offset;
        }

        $produtos = DB::select(
            "SELECT
                logistica_item.id,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y às %H:%i') data_liberacao,
                DATE_FORMAT(
                    DATEADD_DIAS_UTEIS(:dias_para_cancelar, logistica_item.data_criacao),
                    '%d/%m/%Y'
                ) data_validade,
                logistica_item.preco,
                logistica_item.situacao,
                JSON_OBJECT(
                    'nome', colaboradores.razao_social,
                    'id_colaborador', colaboradores.id,
                    'telefone', colaboradores.telefone
                ) json_cliente,
                (
                    SELECT JSON_OBJECT(
                        'nome', colaboradores.razao_social,
                        'id_colaborador', colaboradores.id,
                        'reputacao_atual', COALESCE(reputacao_fornecedores.reputacao, 'NOVATO'),
                        'telefone', colaboradores.telefone
                    )
                    FROM colaboradores
                    LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
                    WHERE colaboradores.id = logistica_item.id_responsavel_estoque
                    GROUP BY colaboradores.id
                ) json_fornecedor,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto_produto
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            WHERE logistica_item.id_responsavel_estoque > 1
                AND logistica_item.situacao < :situacao
                $condicao
            GROUP BY logistica_item.uuid_produto
            ORDER BY JSON_EXTRACT(json_fornecedor, '$.nome') ASC, logistica_item.data_criacao ASC, logistica_item.situacao ASC
            $limite;",
            $binds
        );

        $linkQrCode = env('URL_GERADOR_QRCODE');
        $produtos = array_map(function (array $produto) use ($linkQrCode): array {
            // Informações do produto
            $produto['situacao'] = self::converteSituacao($produto['situacao']);

            // Informações do cliente
            $nome = trim(mb_substr(Str::toUtf8($produto['cliente']['nome']), 0, 18));
            $idColaborador = $produto['cliente']['id_colaborador'];
            $produto['cliente']['nome'] = "($idColaborador) $nome";
            unset($produto['cliente']['id_colaborador']);
            $telefoneCliente = $produto['cliente']['telefone'];
            $produto['cliente']['telefone'] = "{$linkQrCode}https://api.whatsapp.com/send/?phone=55$telefoneCliente";

            // Informações do seller
            $nome = trim(mb_substr(Str::toUtf8($produto['fornecedor']['nome']), 0, 18));
            $idColaborador = $produto['fornecedor']['id_colaborador'];
            $produto['fornecedor']['nome'] = "($idColaborador) $nome";
            unset($produto['fornecedor']['id_colaborador']);
            $telefoneSeller = $produto['fornecedor']['telefone'];
            $produto['fornecedor']['telefone'] = "{$linkQrCode}https://api.whatsapp.com/send/?phone=55$telefoneSeller";

            return $produto;
        }, $produtos);

        $qtdProdutos = DB::selectOneColumn(
            "SELECT COUNT(logistica_item.uuid_produto) qtd_produtos
            FROM logistica_item
            WHERE logistica_item.id_responsavel_estoque > 1
                AND logistica_item.situacao < :situacao
                $condicao;",
            Arr::only($binds, [':situacao', ':data_buscada'])
        );

        $resultado = [
            'produtos' => $produtos,
            'qtd_produtos' => $qtdProdutos,
        ];

        return $resultado;
    }

    public static function buscaProdutosResponsavelTransacoes(int $idResponsavelEstoque, array $idsTransacoes): array
    {
        $where = '';
        if (!empty($idsTransacoes)) {
            [$bind, $valores] = ConversorArray::criaBindValues($idsTransacoes, 'id_transacao');
            $where .= " AND logistica_item.id_transacao IN ($bind) ";
        }
        $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores(['dias_mensurar_cancelamento']);
        $valores[':dias_mensurar_cancelamento'] = $fatores['dias_mensurar_cancelamento'] + 1;
        $valores[':situacao_logistica'] = self::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $valores[':id_responsavel_estoque'] = $idResponsavelEstoque;

        $produtos = DB::select(
            "SELECT
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y às %H:%i') AS `data_compra`,
                DATE_FORMAT(DATEADD_DIAS_UTEIS(:dias_mensurar_cancelamento, logistica_item.data_criacao), '%d/%m/%Y') AS `data_correcao`,
                produtos.nome_comercial,
                colaboradores.razao_social,
                colaboradores.telefone,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`
            FROM logistica_item
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            WHERE logistica_item.situacao < :situacao_logistica
                AND logistica_item.id_responsavel_estoque = :id_responsavel_estoque
                $where
            GROUP BY logistica_item.uuid_produto;",
            $valores
        );

        $linkQrCode = env('URL_GERADOR_QRCODE');
        $produtos = array_map(function (array $produto) use ($linkQrCode): array {
            $produto['qr_code'] = "{$linkQrCode}https://api.whatsapp.com/send/?phone=55{$produto['telefone']}";
            unset($produto['telefone']);

            return $produto;
        }, $produtos);

        return $produtos;
    }

    public static function consultaQuantidadeParaSeparar(): int
    {
        $quantidade = DB::selectOneColumn(
            "SELECT COUNT(logistica_item.uuid_produto) quantidade
            FROM logistica_item
            WHERE logistica_item.id_responsavel_estoque = :id_responsavel_estoque
                AND logistica_item.situacao = 'PE';",
            [':id_responsavel_estoque' => Auth::user()->id_colaborador]
        );

        return $quantidade;
    }

    public static function buscarColetasPendentes(?string $pesquisa): array
    {
        [$produtosFreteSql, $produtosFreteBinds] = ConversorArray::criaBindValues(
            Produto::IDS_PRODUTOS_FRETE,
            'ids_produto_frete'
        );

        $where = '';

        if ($pesquisa) {
            $where = "AND CONCAT_WS(
                        ' ',
                        colaboradores.id,
                        colaboradores.razao_social,
                        logistica_item.id_produto,
                        logistica_item.id_transacao,
                        logistica_item.uuid_produto,
                        transacao_financeiras_produtos_itens.id
                    ) LIKE :pesquisa";

            $produtosFreteBinds[':pesquisa'] = "%$pesquisa%";
        }

        $query = "SELECT
                    transacao_financeiras_produtos_itens.id AS `id_frete`,
                    logistica_item.id_transacao,
                    logistica_item.uuid_produto,
                    colaboradores.id AS `id_colaborador`,
                    colaboradores.razao_social,
                    DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) AS `dias_na_separacao`,
                    (
                        SELECT produtos.nome_comercial
                        FROM produtos
                        WHERE produtos.id = logistica_item.id_produto
                    ) AS `nome_produto_frete`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            INNER JOIN transacao_financeiras_produtos_itens ON
                transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
            WHERE
                logistica_item.id_produto IN ($produtosFreteSql)
                AND logistica_item.situacao = 'PE'
                AND EXISTS (
					SELECT 1
					FROM transacao_financeiras_metadados
					WHERE transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
					AND transacao_financeiras_metadados.chave = 'ENDERECO_COLETA_JSON'
				)
                $where
            ORDER BY logistica_item.id_transacao ASC";

        $coletas = DB::select($query, $produtosFreteBinds);

        return $coletas;
    }

    public static function listarProdutosLogisticasLimpar(): \Generator
    {
        $situacaoFinalLogistica = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $prazoRetencao = ConfiguracaoService::buscarPrazoRetencaoSku();

        $produtos = DB::cursor(
            "SELECT
                        logistica_item.sku,
                        MAX(DATE(entregas_faturamento_item.data_entrega)) <= CURDATE() - INTERVAL :prazo_retencao_entregue YEAR AS `esta_expirado`
                    FROM logistica_item
                         INNER JOIN produtos_logistica ON produtos_logistica.sku = logistica_item.sku
                         INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                        AND entregas_faturamento_item.situacao = 'EN'
                    WHERE logistica_item.sku IS NOT NULL
                        AND logistica_item.situacao = :situacao_final_logistica
                    GROUP BY logistica_item.sku

                    UNION

                    SELECT
                        logistica_item.sku,
                        TRUE AS `esta_expirado`
                    FROM logistica_item
                         INNER JOIN produtos_logistica ON produtos_logistica.sku = logistica_item.sku
                    WHERE logistica_item.sku IS NOT NULL
                      AND logistica_item.situacao = 'DF'

                    UNION

                    SELECT
                        produtos_logistica.sku,
                        TRUE AS `esta_expirado`
                    FROM produtos_logistica
                    WHERE produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
                      AND produtos_logistica.data_atualizacao <= CURDATE() - INTERVAL :prazo_retencao_aguarda_entrada DAY;",
            [
                ':prazo_retencao_entregue' => $prazoRetencao['prazo_retencao_entregue'],
                ':prazo_retencao_aguarda_entrada' => $prazoRetencao['prazo_retencao_aguarda_entrada'],
                ':situacao_final_logistica' => $situacaoFinalLogistica,
            ]
        );

        return $produtos;
    }
}

<?php

namespace MobileStock\model;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Separacao\separacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * https://github.com/mobilestock/backend/issues/131
 * @property string $uuid_produto
 * @property int $id_usuario
 * @property string $situacao
 * @property int $id_produto
 * @property int $id_cliente
 * @property int $id_transacao
 * @property int $id_colaborador_tipo_frete
 */
class LogisticaItemModel extends Model
{
    public const REGEX_ETIQUETA_PRODUTO = "/^[0-9]+_[0-9A-z]+\.[0-9]+$/";
    public const SITUACAO_FINAL_PROCESSO_LOGISTICA = 3;

    protected $table = 'logistica_item';
    protected $fillable = ['situacao', 'id_usuario'];

    public static function buscaInformacoesLogisticaItem(string $uuidProduto): self
    {
        $logisticaItem = self::fromQuery(
            "SELECT
                logistica_item.id_produto,
                logistica_item.situacao,
                logistica_item.uuid_produto
            FROM logistica_item
            WHERE logistica_item.uuid_produto = :uuid_produto;",
            ['uuid_produto' => $uuidProduto]
        )->first();
        if (empty($logisticaItem)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $logisticaItem;
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

    public static function buscaInformacoesProdutoPraAtualizarPrevisao(string $uuidProduto): array
    {
        $idTipoFreteTransportadora = TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA;

        $informacao = DB::selectOne(
            "SELECT
                logistica_item.id_transacao,
                logistica_item.situacao,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.id_responsavel_estoque,
                IF (
                    tipo_frete.id = :id_tipo_frete_transportadora,
                    municipios.id_colaborador_frete_expresso,
                    tipo_frete.id_colaborador_ponto_coleta
                ) AS `id_colaborador_ponto_coleta`,
                IF(
                    tipo_frete.id = :id_tipo_frete_transportadora,
                    JSON_OBJECT(
                        'dias_entregar_cidade', municipios.dias_entrega,
                        'dias_margem_erro', 0
                    ),
                    JSON_OBJECT(
                        'dias_entregar_cliente', transportadores_raios.dias_entregar_cliente,
                        'dias_margem_erro', transportadores_raios.dias_margem_erro
                    )
                ) AS `json_dias_processo_entrega`
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            LEFT JOIN transportadores_raios ON transportadores_raios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
            LEFT JOIN municipios ON municipios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
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
        $diasParaOCancelamento = ConfiguracaoService::buscaDiasDeCancelamentoAutomatico(DB::getPdo());
        $uuids = DB::selectColumns(
            "SELECT
                logistica_item.uuid_produto
             FROM logistica_item
             WHERE logistica_item.situacao < :situacao
               AND DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) > :dias;",
            [
                ':situacao' => self::SITUACAO_FINAL_PROCESSO_LOGISTICA,
                ':dias' => $diasParaOCancelamento,
            ]
        );

        return $uuids;
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

    public static function confereItens(array $produtos, int $idUsuario): void
    {
        foreach ($produtos as $uuidProduto) {
            $logisticaItem = new self();
            $logisticaItem->exists = true;
            $logisticaItem->setKeyName('uuid_produto');
            $logisticaItem->setKeyType('string');

            $logisticaItem->situacao = 'CO';
            $logisticaItem->uuid_produto = $uuidProduto;
            $logisticaItem->id_usuario = $idUsuario;

            $logisticaItem->update();
        }
    }
}

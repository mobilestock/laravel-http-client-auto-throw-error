<?php

namespace MobileStock\service;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\GeradorSql;
use MobileStock\model\Entrega\Entregas;
use MobileStock\model\EntregasEtiqueta;
use MobileStock\model\LogisticaItem;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\TipoFrete;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LogisticaItemService extends LogisticaItem
{
    public static function buscaProdutoLogisticaPorUuid(PDO $conexao, string $uuid): array
    {
        $sql = $conexao->prepare(
            "SELECT
                logistica_item.id_produto,
                logistica_item.preco,
                logistica_item.nome_tamanho,
                logistica_item.id_cliente,
                logistica_item.uuid_produto AS uuid,
                (SELECT
                     JSON_OBJECT(
                             'comissao_fornecedor', SUM(transacao_financeiras_produtos_itens.comissao_fornecedor),
                             'id_fornecedor', transacao_financeiras_produtos_itens.id_fornecedor
                         )
                 FROM transacao_financeiras_produtos_itens
                 WHERE transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
                   AND transacao_financeiras_produtos_itens.tipo_item = 'PR') AS dados_fornecedor,
                logistica_item.data_criacao AS data_compra,
                logistica_item.id_transacao,
                (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = logistica_item.id_produto
                        AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
                ) AS cod_barras,
                (
                    SELECT produtos.descricao
                    FROM produtos
                    WHERE produtos.id = logistica_item.id_produto
                ) AS referencia
            FROM logistica_item
            WHERE logistica_item.uuid_produto = :uuid"
        );
        $sql->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $sql->execute();
        $resultado = $sql->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            return [];
        }

        $resultado['dados_fornecedor'] = json_decode($resultado['dados_fornecedor'], true);
        $resultado = array_merge($resultado, $resultado['dados_fornecedor']);
        unset($resultado['dados_fornecedor']);

        return $resultado;
    }

    public static function consultaInfoProdutoTroca(string $uuidProduto, int $idCliente, bool $apenasEntregue): array
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $join = '';
        if ($apenasEntregue) {
            $where = ' AND entregas_faturamento_item.situacao = "EN" ';
        } else {
            $situacaoExpedicao = Entregas::SITUACAO_EXPEDICAO;
            $join = ' INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega ';
            $where = " AND entregas.situacao > $situacaoExpedicao ";
        }

        $stmt = "SELECT
                    logistica_item.uuid_produto AS uuid,
                    0 pedido_origem,
                    transacao_financeiras_produtos_itens.id_transacao transacao_origem,
                    logistica_item.id_produto,
                    logistica_item.nome_tamanho,
                    CASE
                        WHEN logistica_item.situacao > $situacao
                            THEN 'indisponivel'
                        WHEN EXISTS(SELECT 1
                                    FROM troca_pendente_agendamento
                                    WHERE logistica_item.uuid_produto = troca_pendente_agendamento.uuid)
                            THEN 'agendado'
                        WHEN EXISTS(SELECT 1
                                    FROM transacao_financeiras_produtos_trocas
                                    WHERE transacao_financeiras_produtos_trocas.uuid = logistica_item.uuid_produto)
                            THEN 'troca_usada_em_outro_pedido'
                        ELSE 'disponivel'
                    END AS situacao,
                    NOT DATE(entregas_faturamento_item.data_base_troca)
                        BETWEEN CURRENT_DATE - INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_normal
                                                        FROM configuracoes
                                                        LIMIT 1) DAY
                            AND CURRENT_DATE passou_prazo_troca_normal,
                    NOT DATE(entregas_faturamento_item.data_base_troca)
                        BETWEEN CURRENT_DATE - INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_defeito
                                                        FROM configuracoes
                                                        LIMIT 1) DAY
                            AND CURRENT_DATE passou_prazo_troca_defeito,
                    (
                        SELECT CONCAT(
                            '[',
                                GROUP_CONCAT(
                                    JSON_OBJECT(
                                        'valor_debito', comissoes_transacao_financeiras_produtos_itens.comissao_fornecedor,
                                        'preco_comissao', comissoes_transacao_financeiras_produtos_itens.preco,
                                        'id_colaborador', comissoes_transacao_financeiras_produtos_itens.id_fornecedor,
                                        'origem_lancamento', comissoes_transacao_financeiras_produtos_itens.sigla_estorno,
                                        'tipo_comissao', comissoes_transacao_financeiras_produtos_itens.tipo_item
                                    )
                                ),
                            ']'
                        )
                        FROM transacao_financeiras_produtos_itens comissoes_transacao_financeiras_produtos_itens
                        WHERE comissoes_transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras_produtos_itens.id_transacao
                            AND comissoes_transacao_financeiras_produtos_itens.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    ) json_debitos,
                    DATE(entregas_faturamento_item.data_entrega) data_atualizacao_entrega,
                    entregas_faturamento_item.origem,
                    entregas_faturamento_item.situacao situacao_entregas_faturamento_item,
                    (
                        SELECT 1
                        FROM tipo_frete
                        INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                        WHERE tipo_frete.id = entregas.id_tipo_frete
                        AND tipo_frete.id = 2
                    ) entrega_por_transportadora,
                    (
                        SELECT troca_fila_solicitacoes.id
                        FROM troca_fila_solicitacoes
                        WHERE troca_fila_solicitacoes.uuid_produto = logistica_item.uuid_produto
                        LIMIT 1
                    ) `id_troca_fila_solicitacao`
                FROM transacao_financeiras
                INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                INNER JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                $join
                WHERE logistica_item.uuid_produto = :uuidProduto
                    AND transacao_financeiras.pagador = :idCliente
                    $where
                GROUP BY logistica_item.uuid_produto;";

        $consulta = DB::selectOne($stmt, [
            ':uuidProduto' => $uuidProduto,
            ':idCliente' => $idCliente,
        ]);

        return $consulta;
    }

    public static function ehMeuLook(PDO $conexao, string $uuid): bool
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM pedido_item_meu_look
            WHERE pedido_item_meu_look.uuid = :uuid"
        );
        $sql->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $sql->execute();

        $ehMl = (bool) $sql->fetchColumn();
        return $ehMl;
    }

    public function atualiza(PDO $conexao): void
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->updatePorCampo(array_keys(Arr::only($this->extrair(), ['id', 'uuid_produto'])));

        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);

        if (!$stmt->rowCount()) {
            throw new InvalidArgumentException('Não foi possivel atualizar o item.');
        }
    }

    public static function listaLogisticaPendenteParaEnvio(string $identificador): array
    {
        $situacaoLogistica = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $sqlCaseLogisticaPendente = EntregasFaturamentoItemService::sqlCaseBuscarLogisticaPendente('json');

        if (preg_match(EntregasEtiqueta::REGEX_VOLUME, $identificador)) {
            $idEntrega = explode('_', $identificador)[1];

            $where = ' base_entregas.id = :id_entrega';
            $valores[':id_entrega'] = $idEntrega;
        } else {
            $where = ' base_entregas.uuid_entrega = :identificador';
            $valores[':identificador'] = $identificador;
        }

        $valores[':situacao_logistica'] = $situacaoLogistica;

        $sql = "SELECT
                base_entregas.id AS `id_entrega_pesquisada`,
                tipo_frete.id IN ($idTipoFreteEntregaCliente) AS `eh_entrega_cliente`,
                IF(
                    tipo_frete.id IN ($idTipoFreteEntregaCliente),
                    (
                        SELECT colaboradores.id
                        FROM colaboradores
                        WHERE colaboradores.id = base_entregas.id_cliente
                    ),
                    tipo_frete.id_colaborador
                ) id_remetente,
                IF(
                    tipo_frete.id IN ($idTipoFreteEntregaCliente),
                    (
                        SELECT colaboradores.razao_social
                        FROM colaboradores
                        WHERE colaboradores.id = base_entregas.id_cliente
                    ),
                    tipo_frete.nome
                ) AS `nome_remetente`,
                IF(
                    tipo_frete.id IN ($idTipoFreteEntregaCliente),
                    (
                        SELECT colaboradores.foto_perfil
                        FROM colaboradores
                        WHERE colaboradores.id = base_entregas.id_cliente
                    ),
                    tipo_frete.foto
                ) AS `foto_remetente`,
                CONCAT(
                    '[',
                    (
                        SELECT GROUP_CONCAT(
                            JSON_OBJECT(
                                'id_entrega', entregas.id,
                                'apelido_raio', (
                                                    SELECT
                                                        CONCAT('(', transportadores_raios.id, ') ', transportadores_raios.apelido)
                                                    FROM transportadores_raios
                                                    WHERE transportadores_raios.id = entregas.id_raio
                                                ),
                                'json_produtos', $sqlCaseLogisticaPendente
                            )
                        )
                        FROM entregas
                        WHERE
                            entregas.id_tipo_frete = base_entregas.id_tipo_frete
                            AND entregas.situacao IN ('AB', 'EX')
                            AND entregas.id_cliente = base_entregas.id_cliente
                    ),
                    ']'
                ) AS `json_detalhes_entregas`
            FROM entregas AS `base_entregas`
            INNER JOIN tipo_frete ON tipo_frete.id = base_entregas.id_tipo_frete
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = base_entregas.id
            WHERE $where
            GROUP BY base_entregas.id";
        $dados = DB::selectOne($sql, $valores);

        if (!empty($dados['detalhes_entregas'])) {
            $dados['detalhes_entregas'] = array_filter(
                $dados['detalhes_entregas'],
                fn($entrega) => !empty($entrega['produtos'])
            );

            $dados['detalhes_entregas'] = array_values($dados['detalhes_entregas']);
        }

        if (empty($dados['foto_remetente'])) {
            $dados['foto_remetente'] = $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg';
        }

        return $dados;
    }
    public static function listaProdutosPedido(bool $ehRetiradaCliente, string $identificador): array
    {
        $where = '';
        $situacaoLogistica = LogisticaItemService::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        if ($ehRetiradaCliente) {
            $where .= ' AND logistica_item.situacao < :situacao_logistica ';
        } else {
            $where .= ' AND logistica_item.situacao = :situacao_logistica ';
        }
        $transacoes = explode(',', $identificador);
        [$bind, $valores] = ConversorArray::criaBindValues($transacoes, 'id_transacao');

        $produtos = DB::select(
            "SELECT
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.situacao,
                logistica_item.uuid_produto,
                logistica_item.id_transacao,
                DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y %H:%i:%s') AS `data_atualizacao`,
                IF (logistica_item.id_responsavel_estoque = 1, 'Fulfillment', 'Externo') AS `responsavel_estoque`,
                COALESCE(produtos.localizacao, '-') AS `localizacao`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE NOT produtos_foto.tipo_foto = 'SM'
                        AND produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `produto_foto`,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) AS `nome_fornecedor`,
                IF (
                    logistica_item.situacao = 'PE',
                    '-',
                    (
                        SELECT usuarios.nome
                        FROM usuarios
                        WHERE usuarios.id = logistica_item.id_usuario
                    )
                ) AS `nome_usuario`
            FROM logistica_item
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            WHERE
                logistica_item.id_entrega IS NULL
                AND logistica_item.id_transacao IN ($bind)
                $where
            GROUP BY logistica_item.uuid_produto;",
            array_merge($valores, ['situacao_logistica' => $situacaoLogistica])
        );
        if (empty($produtos)) {
            return [];
        }
        $produtos = array_map(function (array $produto): array {
            $produto['situacao'] = LogisticaItem::converteSituacao($produto['situacao']);
            return $produto;
        }, $produtos);

        return $produtos;
    }

    public static function buscaItensForaDaEntregaParaImprimir(array $uuids, bool $ehColeta): array
    {
        $order = [];
        if (!count($uuids)) {
            throw new RuntimeException('Defina um item para a busca');
        }

        $paineisImpressao = ConfiguracaoService::buscaPaineisImpressao();
        [$bind, $valores] = ConversorArray::criaBindValues($uuids, 'uuid_produto');

        if (!$ehColeta) {
            $order = array_map(fn($painel) => "produtos.localizacao = $painel DESC", $paineisImpressao);
            $order = implode(',', $order);
        } else {
            $order = 'colaboradores.razao_social';
        }

        $sql = "SELECT
                    produtos.id id_produto,
                    produtos.descricao nome_produto,
                    colaboradores.id id_cliente,
                    EXISTS(
                        SELECT 1
                        FROM negociacoes_produto_log
                        WHERE negociacoes_produto_log.uuid_produto = logistica_item.uuid_produto
                            AND negociacoes_produto_log.situacao = 'ACEITA'
                    ) AS `eh_negociacao_aceita`,
                    transacao_financeiras_metadados.valor AS `json_endereco`,
                    IF (
                        tipo_frete.tipo_ponto = 'PP' OR JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio') IS NULL,
                        NULL,
                        (
                            SELECT transportadores_raios.apelido
                            FROM transportadores_raios
                            WHERE transportadores_raios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
                        )
                    ) AS `apelido_raio`,
                    produtos.cores,
                    colaboradores.razao_social nome_cliente,
                    tipo_frete.tipo_ponto = 'PM' AS `eh_ponto_movel`,
                    tipo_frete.categoria,
                    if (
						tipo_frete.categoria IN ('ML','PE'),
						JSON_OBJECT(
							'id_remetente', tipo_frete.id_colaborador,
                            'nome_remetente', tipo_frete.nome
                        ),
                        JSON_OBJECT(
							'id_remetente', colaboradores.id,
                            'nome_remetente', colaboradores.razao_social
                        )
                    ) AS `json_parametro_etiqueta`,
                    logistica_item.uuid_produto,
                    logistica_item.nome_tamanho,
                    logistica_item.id_transacao,
                    logistica_item.situacao,
                    (
                        SELECT
                            produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                            produtos_foto.id = logistica_item.id_produto
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto,
                    COALESCE(
                        (
                            SELECT
                                publicacoes_produtos.id
                            FROM publicacoes_produtos
                            WHERE
                                publicacoes_produtos.id_produto = logistica_item.id_produto
                            LIMIT 1
                        ),
                        0
                    ) id_publicacao,
                    (
                        SELECT
                            IF(transacao_financeiras.origem_transacao = 'MP', 'MS', 'ML')
                        FROM transacao_financeiras
                        WHERE transacao_financeiras.id = logistica_item.id_transacao
                    ) AS `origem`,
                    logistica_item.observacao AS `json_observacao`,
                    transacao_financeiras_produtos_itens.id IS NOT NULL AS `tem_coleta`
                FROM logistica_item
                INNER JOIN produtos ON logistica_item.id_produto = produtos.id
                INNER JOIN colaboradores ON logistica_item.id_cliente = colaboradores.id
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                LEFT JOIN transacao_financeiras_produtos_itens ON
                    transacao_financeiras_produtos_itens.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_produtos_itens.tipo_item = 'DIREITO_COLETA'
                WHERE logistica_item.uuid_produto IN ($bind)
                GROUP BY logistica_item.uuid_produto
                ORDER BY $order;";
        $dados = DB::select($sql, $valores);

        if (!$dados) {
            return [];
        }

        $dadosFormatados = array_map(function ($item) {
            $item = array_merge($item, $item['endereco']);
            $item['nome_cliente'] = Str::toUtf8($item['nome_cliente']);
            $item['parametro_etiqueta']['nome_remetente'] = Str::toUtf8($item['parametro_etiqueta']['nome_remetente']);
            if ($item['eh_negociacao_aceita']) {
                $item['nome_produto'] = "{$item['id_produto']} - SUBSTITUTO -TR-{$item['id_transacao']}";
            } else {
                $nomeProduto = Str::toUtf8($item['nome_produto']);
                $item['nome_produto'] = "{$item['id_produto']} - $nomeProduto {$item['cores']}";
                $item['nome_produto'] .= " -TR-{$item['id_transacao']}";
            }
            if ($item['tem_coleta']) {
                $item['nome_produto'] = '[COLETA] ' . $item['nome_produto'];
            }
            $item['nome_cliente'] = ConversorStrings::capitalize(
                $item['id_cliente'] . '-' . mb_substr($item['nome_cliente'], 0, 35)
            );
            $item['id_remetente'] = $item['parametro_etiqueta']['id_remetente'];
            $item['nome_remetente'] = trim(mb_substr($item['parametro_etiqueta']['nome_remetente'], 0, 25));

            return $item;
        }, $dados);

        return $dadosFormatados;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/282
     */
    public static function buscaItensNaoExpedidosPorTransportadora(): array
    {
        $idColaboradorTipoFrete = ConfiguracaoService::buscaIdColaboradorTipoFreteTransportadoraMeuLook();

        $resultado = DB::select(
            "SELECT logistica_item.id,
                logistica_item.id_colaborador_tipo_frete,
                COALESCE((
                    SELECT transacao_financeiras_produtos_itens.comissao_fornecedor
                    FROM transacao_financeiras_produtos_itens
                    WHERE transacao_financeiras_produtos_itens.id_transacao = logistica_item.id_transacao
                        AND transacao_financeiras_produtos_itens.tipo_item = 'FR'
                ), 0) valor_frete
            FROM logistica_item
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            LEFT JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
            WHERE logistica_item.id_cliente = :idCliente
                AND logistica_item.id_colaborador_tipo_frete = :idColaboradorTipoFrete
                AND (logistica_item.id_entrega IS NULL OR entregas.situacao = 'AB');",
            [':idCliente' => Auth::user()->id_colaborador, ':idColaboradorTipoFrete' => $idColaboradorTipoFrete]
        );

        return $resultado;
    }

    public static function buscaDadosParaAcompanhamentoPorUuid(string $uuidProduto): array
    {
        $resultado = DB::selectOne(
            "SELECT
                    logistica_item.id_cliente,
                    JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_cidade') as `id_cidade_json`,
                    tipo_frete.id AS `id_tipo_frete`,
                    (
                        SELECT 1
                        FROM entregas
                        WHERE
                            entregas.id_cliente = logistica_item.id_cliente
                            AND entregas.id_tipo_frete = tipo_frete.id
                            AND entregas.situacao IN ('AB','EX')
                    ) AS `possui_entrega`
                FROM logistica_item
                INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                INNER JOIN transacao_financeiras_metadados ON
                    transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                WHERE
                    logistica_item.uuid_produto = ?",
            [$uuidProduto]
        );

        if (!$resultado) {
            throw new NotFoundHttpException('Não foi possível encontrar os dados para pausar a expedição.');
        }

        return $resultado;
    }

    public static function existeLogisticaExternaPendenteParaAcompanhamento(
        int $idCliente,
        int $idTipoFrete,
        int $idCidade
    ): bool {
        $resultado = (bool) DB::selectOneColumn(
            "SELECT 1
                FROM logistica_item
                INNER JOIN transacao_financeiras_metadados ON
                    transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                WHERE
                    logistica_item.id_cliente = :id_cliente
                    AND logistica_item.id_responsavel_estoque > 1
                    AND logistica_item.situacao = 'PE'
                    AND tipo_frete.id = :id_tipo_frete
                    AND JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade') = :id_cidade",
            [
                ':id_cliente' => $idCliente,
                ':id_tipo_frete' => $idTipoFrete,
                ':id_cidade' => $idCidade,
            ]
        );

        return $resultado;
    }
}

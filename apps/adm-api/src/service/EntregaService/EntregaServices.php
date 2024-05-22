<?php

namespace MobileStock\service\EntregaService;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate as FacadesGate;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Globals;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaDadosEnvioExpedicao;
use MobileStock\model\Entrega;
use MobileStock\model\Entrega\Entregas;
use MobileStock\model\EntregasEtiqueta;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\model\LogisticaItem;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\TipoFrete;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasProdutosTrocasService;
use PDO;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntregaServices extends Entregas
{
    protected $id_colaborador;

    public static function validaEtiqueta(int $idEntrega, array $etiquetas): void
    {
        $volumes = DB::selectColumns(
            "SELECT
                CONCAT(entregas.id_cliente,'_',entregas.id,'_',entregas_etiquetas.volume)
            FROM entregas_etiquetas
            INNER JOIN entregas ON entregas.id = entregas_etiquetas.id_entrega
            WHERE entregas_etiquetas.id_entrega = :idEntrega;",
            [
                'idEntrega' => $idEntrega,
            ]
        );

        if (array_diff($etiquetas, $volumes)) {
            throw new NotFoundHttpException('Algumas etiquetas não pertencem a esta entrega');
        }
    }

    public static function buscaEntregasVolumesDoColaborador(int $idColaborador): array
    {
        $where = '';
        if (FacadesGate::allows('ENTREGADOR')) {
            $where = " AND entregas.situacao = 'PT'";
        }
        if (FacadesGate::allows('ADMIN')) {
            $where = " AND entregas.situacao IN ( 'AB', 'EX' )";
        }

        $sql = "SELECT
                    GROUP_CONCAT(DISTINCT entregas.id) lista_de_ids_entrega,
                    tipo_frete_colaboradores.razao_social nome_ponto,
                    tipo_frete.tipo_ponto,
                    CASE
                        WHEN tipo_frete.id = 2 THEN 'TRANSPORTADORA'
                        WHEN tipo_frete.id = 3 THEN 'VOU_BUSCAR'
                        ELSE tipo_frete.categoria
                    END categoria_ponto,
                    colaboradores.razao_social nome_cliente,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'identificador_volume_legado',CONCAT(
                                    entregas.uuid_entrega,
                                    '_',
                                    entregas_etiquetas.uuid_volume
                                ),
                                'identificador_volume', CONCAT(
                                    entregas.id_cliente,
                                    '_',
                                    entregas.id,
                                    '_',
                                    entregas_etiquetas.volume
                                ),
                                'id_volume',entregas_etiquetas.id,
                                'volume', entregas_etiquetas.volume,
                                'id_entrega', entregas_etiquetas.id_entrega
                            )
                        ),
                        ']'
                    ) json_etiquetas
                FROM entregas
                INNER JOIN entregas_etiquetas ON entregas_etiquetas.id_entrega = entregas.id
                INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
                INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                INNER JOIN colaboradores tipo_frete_colaboradores ON tipo_frete_colaboradores.id = tipo_frete.id_colaborador
                WHERE
                    entregas.id_cliente  = :idColaborador
                    $where
                GROUP BY entregas.id_cliente;";
        $dados = DB::selectOne($sql, [
            ':idColaborador' => $idColaborador,
        ]);
        if (!$dados) {
            throw new NotFoundHttpException('Não existe entregas para este colaborador.');
        }

        return $dados;
    }

    public static function buscaIdDeEntrega(int $idCliente, int $idTipoFrete, ?int $idRaio): int
    {
        $where = '';
        $binds = ['idTipoFrete' => $idTipoFrete, 'idCliente' => $idCliente];
        if (!empty($idRaio)) {
            $where = ' AND entregas.id_raio = :idRaio';
            $binds['idRaio'] = $idRaio;
        }

        $idDeEntrega =
            DB::selectOneColumn(
                "SELECT
                    entregas.id
                FROM entregas
                WHERE entregas.id_tipo_frete = :idTipoFrete
                    AND entregas.id_cliente = :idCliente
                    AND entregas.situacao = 'AB'
                    $where;",
                $binds
            ) ?:
            0;

        return $idDeEntrega;
    }

    public static function buscaListaDeEntregas(array $filtros): array
    {
        $order = '';
        $where = '';

        $binds['situacao_logistica'] = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $idTipoFrete = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $sqlCaseLogisticaPendente = EntregasFaturamentoItemService::sqlCaseBuscarLogisticaPendente('bool');

        if (!empty($filtros['pesquisa'])) {
            if (is_numeric($filtros['pesquisa'])) {
                $where = ' AND :pesquisa IN (entregas.id, colaboradores.id) ';
            } else {
                $where = ' AND colaboradores.razao_social REGEXP :pesquisa ';
            }

            $binds['pesquisa'] = $filtros['pesquisa'];
        }

        if ($filtros['situacao'] !== 'TD') {
            $where .= ' AND entregas.situacao = :situacao ';
            $binds['situacao'] = $filtros['situacao'];
        }

        if (in_array($filtros['situacao'], ['EX', 'PT', 'EN', 'TD'])) {
            $order = ' ORDER BY entregas.id DESC ';
        }

        $entregas = DB::select(
            "SELECT
                entregas.id AS `id_entrega`,
                entregas.uuid_entrega,
                DATE_FORMAT(entregas.data_criacao, '%d/%m/%Y às %k:%i') AS `data_criacao`,
                entregas.volumes,
                COUNT(DISTINCT entregas_faturamento_item.id) AS `qtd_produtos`,
                IF(
                    tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                    CONCAT(JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade'), ' (', JSON_VALUE(transacao_financeiras_metadados.valor, '$.uf') ,')'),
                    CONCAT(colaborador_municipios.nome, ' (', colaborador_municipios.uf, ')')
                ) AS `cidade`,
                JSON_OBJECT(
                    'id_colaborador', colaboradores.id,
                    'nome', TRIM(colaboradores.razao_social),
                    'id_cidade',IF(
                            tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                            JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                            colaborador_municipios.id
                    ),
                    'id_raio', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio')
                ) AS `json_destinatario`,
                tipo_frete.nome AS `transportador`,
                (CASE
                    WHEN entregas.situacao = 'AB' THEN 'Aberta'
                    WHEN entregas.situacao = 'EX' THEN 'Na expedição'
                    WHEN entregas.situacao = 'PT' THEN 'Indo p/ ponto'
                    WHEN entregas.situacao = 'EN' THEN 'Entregue'
                    ELSE entregas.situacao
                END) AS `situacao`,
                (
                    SELECT
                        COUNT(entregas_devolucoes_item.id)
                    FROM entregas_devolucoes_item
                    WHERE entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
                        AND entregas_devolucoes_item.situacao = 'PE'
                ) AS `devolucoes_pendentes`,
                tipo_frete.categoria,
                IF (
                    tipo_frete.id = 2 AND SUM(entregas_faturamento_item.origem = 'ML') > 0,
                    'ML',
                    tipo_frete.categoria
                ) AS `categoria_cor`,
                IF (
                    tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
                ) AS `tipo_entrega`,
                IF (entregas.situacao = 'AB', (
                        $sqlCaseLogisticaPendente),0) AS `tem_mais_produtos`,
                EXISTS(
                    SELECT 1
                    FROM colaboradores_suspeita_fraude
                    WHERE colaboradores_suspeita_fraude.situacao = 'PE'
                        AND colaboradores_suspeita_fraude.origem = 'DEVOLUCAO'
                        AND colaboradores_suspeita_fraude.id_colaborador = tipo_frete.id_colaborador
                ) AS `eh_fraude`,
                IF(entregas.id_raio IS NULL, '-',
                    (SELECT
                         COALESCE(CONCAT('(',entregas.id_raio,') ', transportadores_raios.apelido), entregas.id_raio)
                     FROM transportadores_raios
                     WHERE transportadores_raios.id = entregas.id_raio)
                ) AS `apelido_raio`,
                (
                    SELECT
                        JSON_OBJECT(
                            'situacao', acompanhamento_temp.situacao,
                            'id', acompanhamento_temp.id
                        )
                    FROM acompanhamento_temp
                    WHERE acompanhamento_temp.id_tipo_frete = tipo_frete.id
                        AND acompanhamento_temp.id_destinatario = colaboradores.id
                        AND acompanhamento_temp.id_cidade = IF(
                            tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                            JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                            colaborador_municipios.id
                        )
                        AND IF(acompanhamento_temp.id_raio IS NULL,
                            TRUE,
                            acompanhamento_temp.id_raio = JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.id_raio')
                        )
                ) AS `json_acompanhamento`,
                entregas.id_tipo_frete,
                entregas.id_tipo_frete IN ($idTipoFrete) AS `eh_retirada_cliente`,
                COALESCE(pontos_coleta.valor_custo_frete, 0) AS `valor_custo_frete`
            FROM entregas
            JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = entregas_faturamento_item.uuid_produto
            JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = IF (
                tipo_frete.id IN ($idTipoFrete),
                entregas_faturamento_item.id_cliente,
                tipo_frete.id_colaborador
            )
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = IF(
                    tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id IN ($idTipoFrete),
                    colaboradores.id,
                    tipo_frete.id_colaborador
                )
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador
            INNER JOIN transacao_financeiras_metadados ON
                transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            INNER JOIN municipios AS `colaborador_municipios` ON  colaborador_municipios.id = colaboradores_enderecos.id_cidade
            WHERE TRUE $where
            GROUP BY entregas.id
            $order
            LIMIT 250;",
            $binds
        );

        $entregas = array_map(function (array $entrega): array {
            $entrega = array_merge($entrega, $entrega['destinatario']);
            $seed = rand(7, 21);
            $bits = random_bytes($seed);
            $entrega['identificador'] = bin2hex($bits);
            if (in_array($entrega['tipo_entrega'], ['PP', 'ENVIO_TRANSPORTADORA'])) {
                $entrega['destino'] =
                    '(' . $entrega['destinatario']['id_colaborador'] . ') ' . trim($entrega['destinatario']['nome']);
            } else {
                $entrega['destino'] =
                    "({$entrega['destinatario']['id_colaborador']}) " . trim($entrega['destinatario']['nome']);
            }

            unset($entrega['nome'], $entrega['envio_anterior'], $entrega['uf']);

            return $entrega;
        }, $entregas);

        return $entregas;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/99
     */
    public static function buscarEntregaPorID(int $idEntrega): array
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $resultado = DB::selectOne(
            "SELECT
                entregas.id,
                entregas.volumes,
                tipo_frete.tipo_ponto,
                tipo_frete.nome AS `nome_ponto`,
                DATE_FORMAT(entregas.data_atualizacao, '%d/%m/%Y às %k:%i') AS `data_atualizacao`,
                (
                    SELECT JSON_OBJECT(
                        'data_expedicao', DATE_FORMAT(entregas_logs.data_criacao, '%d/%m/%Y às %k:%i'),
                        'usuario_expedicao', (
                            SELECT usuarios.nome
                            FROM usuarios
                            WHERE usuarios.id = entregas_logs.id_usuario
                        )
                    )
                    FROM entregas_logs
                    WHERE entregas_logs.id_entrega = entregas.id
                    AND entregas_logs.situacao_anterior IN ('AB','EX')
                    ORDER BY entregas_logs.data_criacao DESC
                    LIMIT 1
                ) json_detalhes_expedicao,
                CASE
                    WHEN tipo_frete.categoria = 'MS' THEN (
                        SELECT JSON_OBJECT(
                            'id_colaborador', colaboradores.id,
                            'nome', colaboradores.razao_social
                        )
                        FROM colaboradores
                        WHERE colaboradores.id = entregas.id_cliente
                    )
                    WHEN tipo_frete.categoria IN ('ML', 'PE') AND tipo_frete.tipo_ponto = 'PP' THEN (
                        SELECT JSON_OBJECT(
                            'id_colaborador', colaboradores.id,
                            'nome', colaboradores.razao_social
                        )
                        FROM colaboradores
                        WHERE colaboradores.id = tipo_frete.id_colaborador
                    )
                    WHEN tipo_frete.tipo_ponto = 'PM' THEN (
                        SELECT JSON_OBJECT(
                            'uf', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.uf'),
                            'cidade', JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.cidade')
                        )
                        FROM transacao_financeiras_metadados
                        WHERE transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                            AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                        LIMIT 1
                    )
                END AS `json_destino`,
                (
                    CASE
                        WHEN entregas.situacao = 'AB' THEN 'Aberto'
                        WHEN entregas.situacao = 'EX' THEN 'Na expedição'
                        WHEN entregas.situacao = 'PT' THEN 'Indo p/ ponto'
                        WHEN entregas.situacao = 'EN' THEN 'Entregue'
                        ELSE entregas.situacao
                    END
                ) AS `situacao`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT
                        (
                            SELECT JSON_OBJECT(
                                'id_transacao', logistica_item.id_transacao,
                                'preco', logistica_item.preco,
                                'id_cliente', logistica_item.id_cliente,
                                'id_produto', logistica_item.id_produto,
                                'nome_tamanho', logistica_item.nome_tamanho,
                                'uuid_produto', logistica_item.uuid_produto,
                                'ja_estornado', logistica_item.situacao > :situacao,
                                'razao_social', colaboradores.razao_social,
                                'data_transacao', DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %k:%i'),
                                'saldo_cliente', IF(
						  		    entregas_faturamento_item.situacao = 'EN',
                                     NULL,
                                     saldo_cliente(logistica_item.id_cliente)
                                ),
                                'metodo_pagamento', (
                                    CASE
                                        WHEN transacao_financeiras.metodo_pagamento = 'CR' THEN 'Crédito'
                                        WHEN transacao_financeiras.metodo_pagamento = 'DE' THEN 'Dinheiro'
                                        WHEN transacao_financeiras.metodo_pagamento = 'CA' THEN 'Cartão'
                                        WHEN transacao_financeiras.metodo_pagamento = 'BL' THEN 'Boleto'
                                        WHEN transacao_financeiras.metodo_pagamento = 'PX' THEN 'Pix'
                                        ELSE transacao_financeiras.metodo_pagamento
                                    END
                                ),
                                'situacao_entrega', entregas_faturamento_item.situacao,
                                'descricao', (
                                    SELECT produtos.descricao
                                    FROM produtos
                                    WHERE produtos.id = logistica_item.id_produto
                                ),
                                'historico_logistica', (
                                    SELECT
                                    JSON_OBJECT(
                                            'usuario', usuarios.nome,
                                            'data_adicionado', DATE_FORMAT(entregas_faturamento_item.data_criacao, '%d/%m/%Y às %k:%i')
                                        )
                                    FROM entregas_log_faturamento_item
                                    INNER JOIN usuarios ON usuarios.id = entregas_log_faturamento_item.id_usuario
                                    WHERE
                                        entregas_log_faturamento_item.id_entregas_fi = entregas_faturamento_item.id
                                        AND (entregas_log_faturamento_item.situacao_nova = 'PE'
                                        OR entregas_log_faturamento_item.situacao_anterior IS NULL)
                                    LIMIT 1
                                ),
                                'recebedor', (
									SELECT
										JSON_OBJECT(
											'nome_recebedor', entregas_faturamento_item.nome_recebedor
										)
                                ),
                                'origem', entregas_faturamento_item.origem
                            )
                            FROM transacao_financeiras
                            INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
                            WHERE transacao_financeiras.id = logistica_item.id_transacao
                        ) ORDER BY logistica_item.id_transacao ASC
                    ),
                    ']'
                ) AS `json_produtos`,
                CONCAT(
                    '[',
                    (
						SELECT GROUP_CONCAT(
                            DISTINCT CONCAT(
                                '\"',
                                entregas.id_cliente,
                                '_',
                                entregas.id,
                                '_',
                                entregas_etiquetas.volume,
                                '\"'
                            )
                        )
                        FROM entregas_etiquetas
                            WHERE entregas_etiquetas.id_entrega = entregas.id
                    ),
                    ']'
                ) json_etiquetas,
                (
                    SELECT 1
                    FROM troca_pendente_agendamento
                    WHERE logistica_item.id_cliente = troca_pendente_agendamento.id_cliente
                    AND entregas.id_tipo_frete IN (1, 2, 3)
                    LIMIT 1
                ) tem_devolucao_pendente,
                entregas.id_tipo_frete,
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                        DISTINCT logistica_item.id_transacao
                    ),
                    ']'
                ) AS `json_id_transacoes`
            FROM logistica_item
            INNER JOIN entregas ON entregas.id = logistica_item.id_entrega
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN entregas_faturamento_item
                ON entregas_faturamento_item.id_entrega = entregas.id
                AND entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
           WHERE entregas_faturamento_item.id_entrega = :idEntrega
            GROUP BY logistica_item.id_entrega;",
            [
                ':idEntrega' => $idEntrega,
                ':situacao' => $situacao,
            ]
        );

        if (empty($resultado)) {
            throw new NotFoundHttpException('Não foi possível encontrar a entrega');
        }

        $resultado = array_merge($resultado, $resultado['destino']);
        unset($resultado['destino']);

        if ($resultado['tipo_ponto'] === 'PP') {
            $resultado['destino'] = '(' . $resultado['id_colaborador'] . ') ' . trim($resultado['nome']);
            unset($resultado['id_colaborador'], $resultado['nome']);
        } else {
            $resultado['destino'] = trim($resultado['cidade']) . ' (' . $resultado['uf'] . ')';
            unset($resultado['uf'], $resultado['cidade']);
        }

        $resultado['valor_total'] = TipoFreteService::buscaValoresPorIdTransacao(
            $resultado['id_transacoes'],
            $resultado['id_tipo_frete']
        )['valor_pedido'];

        foreach ($resultado['produtos'] as &$produto) {
            $produto['origem'] = $produto['origem'] === 'ML' ? 'Meu Look' : 'Mobile Stock';
            $produto['historico_logistica'] = json_decode($produto['historico_logistica'], true);
        }

        unset($resultado['id_colaborador'], $resultado['nome']);

        usort($resultado['etiquetas'], function ($a, $b) {
            $volumeA = explode('_', $a);
            $volumeAInt = (int) end($volumeA);

            $volumeB = explode('_', $b);
            $volumeBInt = (int) end($volumeB);

            return $volumeAInt - $volumeBInt;
        });

        return $resultado;
    }

    /**
     * @issue Obsolescência programada: https://github.com/mobilestock/backend/issues/125
     * @param string $etiquetaExpedicao
     * @param string $acao 'IMPRIMIR' | 'VISUALIZAR'
     * @return array|string
     */
    public static function buscarDadosEtiquetaEnvio(string $etiquetaExpedicao, string $acao)
    {
        $where = '';
        $binds = [];

        switch (true) {
            case preg_match(EntregasEtiqueta::REGEX_VOLUME_LEGADO, $etiquetaExpedicao):
                $etiquetaExpedicao = explode('_', $etiquetaExpedicao);
                $uuidEntrega = $etiquetaExpedicao[0];
                $uuidVolume = $etiquetaExpedicao[1];
                $where = ' entregas.uuid_entrega = :uuid_entrega AND entregas_etiquetas.uuid_volume = :uuid_volume';
                $binds = [
                    ':uuid_entrega' => $uuidEntrega,
                    ':uuid_volume' => $uuidVolume,
                ];
                break;
            case preg_match(EntregasEtiqueta::REGEX_VOLUME, $etiquetaExpedicao):
                $etiquetaExpedicao = explode('_', $etiquetaExpedicao);
                $idEntrega = (int) $etiquetaExpedicao[1];
                $numeroVolume = (int) $etiquetaExpedicao[2];
                $where = ' entregas.id = :id_entrega AND entregas_etiquetas.volume = :numero_volume';
                $binds = [
                    ':id_entrega' => $idEntrega,
                    ':numero_volume' => $numeroVolume,
                ];
                break;
            default:
                throw new BadRequestHttpException('Etiqueta de envio inválida');
        }

        $sql = "SELECT
                    entregas.id AS `id_entrega`,
                    entregas_etiquetas.volume,
                    entregas.volumes AS `volume_total`,
                    transacao_financeiras_metadados.valor AS `json_endereco`,
                    CONCAT(colaboradores.id,' ', colaboradores.razao_social) AS `cliente`,
                    colaboradores.telefone
                FROM
                    entregas
                INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                INNER JOIN transacao_financeiras_metadados ON
                    transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                INNER JOIN entregas_etiquetas ON entregas_etiquetas.id_entrega = entregas.id
                WHERE $where";

        $resultado = DB::selectOne($sql, $binds);

        if (!$resultado) {
            throw new BadRequestHttpException('Não foi possível encontrar a entrega');
        }

        $resultado['telefone'] = Str::formatarTelefone($resultado['telefone']);

        $resultado = array_merge($resultado, $resultado['endereco']);
        unset($resultado['endereco']);

        switch ($acao) {
            case 'VISUALIZAR':
                return $resultado;
            case 'IMPRIMIR':
                $imagem = new ImagemEtiquetaDadosEnvioExpedicao(
                    $resultado['id_entrega'],
                    $resultado['cliente'],
                    $resultado['logradouro'],
                    $resultado['numero'],
                    $resultado['bairro'],
                    $resultado['cidade'],
                    $resultado['uf'],
                    $resultado['telefone'],
                    $resultado['volume'],
                    $resultado['volume_total']
                );
                return $imagem->criarZpl();
            default:
                throw new BadRequestHttpException('Ação na busca de etiqueta de envios inválida');
        }
    }

    public function buscaRaioMaisProximoDoEntregador(int $idEntrega, int $idTransportador): ?int
    {
        $raio = DB::selectOne(
            "SELECT
                transportadores_raios.id,
                distancia_geolocalizacao(
                    JSON_VALUE(transacao_financeiras_metadados.valor, '$.latitude'),
                    JSON_VALUE(transacao_financeiras_metadados.valor, '$.longitude'),
                    transportadores_raios.latitude,
                    transportadores_raios.longitude
                ) * 1000 AS `distancia`
            FROM entregas_faturamento_item
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
            INNER JOIN transportadores_raios ON transportadores_raios.id_cidade = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
                AND transportadores_raios.id_colaborador = :idTransportador
            WHERE entregas_faturamento_item.id_entrega = :idEntrega
            GROUP BY transportadores_raios.id
            ORDER BY distancia ASC
            LIMIT 1;",
            [':idEntrega' => $idEntrega, ':idTransportador' => $idTransportador]
        );
        if (empty($raio)) {
            return null;
        }

        return $raio['id'];
    }
    /**
     * Esta função possui teste unitário.
     * Quando o método realizar o flip do saldo do cliente ele irá commitar a transação e recrirá a mesma porque o
     * restante do processo pode falhar e o saldo do cliente tem que se manter atualizado.
     */
    public static function forcarEntregaDeProduto(string $uuidProduto): void
    {
        $idCliente = (int) current(explode('_', $uuidProduto));
        TransacaoFinanceirasProdutosTrocasService::converteDebitoPendenteParaNormalSeNecessario($idCliente);
        TransacaoFinanceirasProdutosTrocasService::sincronizaTrocaPendenteAgendamentoSeNecessario($idCliente);
        // @issue: https://github.com/mobilestock/backend/issues/277
        if (DB::getPdo()->inTransaction()) {
            DB::commit();
            DB::beginTransaction();
        }
        $sql = "SELECT
                entregas.id id_entrega,
                entregas.situacao situacao_entrega,
                entregas_faturamento_item.situacao situacao_produto
            FROM entregas
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            WHERE
                entregas_faturamento_item.uuid_produto = :uuidProduto
                AND (
                    entregas_faturamento_item.situacao <> 'EN'
                    OR entregas.situacao <> 'EN'
                );";
        $produto = DB::selectOne($sql, [':uuidProduto' => $uuidProduto]);

        if (!$produto) {
            throw new Exception('Não foi possível encontrar o produto.');
        }
        if ($produto['situacao_entrega'] !== 'EN') {
            $entrega = Entrega::fromQuery(
                "SELECT
                        entregas.id,
                        entregas.id_tipo_frete,
                        tipo_frete.tipo_ponto
                    FROM entregas
                    INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                    WHERE entregas.id = :idEntrega;",
                [':idEntrega' => $produto['id_entrega']]
            )->first();
            $entrega->situacao = 'EN';
            $entrega->save();
        }
        if ($produto['situacao_produto'] !== 'EN') {
            app(EntregasFaturamentoItem::class)->confirmaEntregaDeProdutos(
                [$uuidProduto],
                'FORCAR ENTREGA (' . Auth::user()->id . ')'
            );
        }
    }

    public static function letrasMaisUsadas(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                SUM(_letras.quantidade_por_cliente) AS `quantidade`,
                _letras.letra,
                CONCAT(
                    '[',
                        GROUP_CONCAT(JSON_OBJECT(
                            'nome', _letras.nome_cliente,
                            'quantidade', _letras.quantidade_por_cliente
                        )),
                    ']'
                ) AS `clientes`
            FROM (
                SELECT
                    COUNT(entregas_faturamento_item.id) AS `quantidade_por_cliente`,
                    CONCAT('( ',colaboradores.id,' ) - ',colaboradores.razao_social) AS `nome_cliente`,
                    UPPER(LEFT(REGEXP_REPLACE(colaboradores.razao_social,'[^A-z]/i',''),1)) AS `letra`
                FROM entregas
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
                WHERE DATE(entregas_faturamento_item.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                GROUP BY entregas.id_cliente
                ORDER BY quantidade_por_cliente DESC
            ) AS `_letras`
            GROUP BY _letras.letra
            ORDER BY quantidade DESC;"
        );
        $sql->execute();
        $retorno = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    public static function deletaLogEntregasFaturamentoItem(): void
    {
        $sql = "DELETE FROM entregas_log_faturamento_item
            WHERE entregas_log_faturamento_item.data_criacao < DATE_SUB(NOW(), INTERVAL 1 YEAR);";

        DB::delete($sql);
    }

    public static function deletaLogEntregas(): void
    {
        $sql = "DELETE FROM entregas_logs
            WHERE entregas_logs.data_criacao < DATE_SUB(NOW(), INTERVAL 1 YEAR);";

        DB::delete($sql);
    }
    public static function ConsultaEntregaCliente(int $idEntrega): array
    {
        $sql = "SELECT
                    entregas.id id_entrega,
                    entregas.id_cliente,
                    entregas.id_tipo_frete,
                    entregas.situacao,
                    colaboradores.tipo_embalagem,
                    colaboradores.razao_social nome_cliente,
                    entregas.volumes
                FROM entregas
                INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
                WHERE entregas.id = :idEntrega;";
        $dados = DB::selectOne($sql, [
            'idEntrega' => $idEntrega,
        ]);

        return $dados ?: [];
    }
    /**
     * Método utilizado para exibir a quantidade de entregas e produtos que estão disponíveis para
     * manipulação no app de entregas e o app interno
     */
    public static function consultaStatusDeEntrega(): array
    {
        $sql = "SELECT
                    COALESCE(
                        SUM(entregas.situacao = 'EN'
                        AND IF(tipo_frete.tipo_ponto = 'PM',
                            entregas_faturamento_item.situacao IN ('AR','PE'),
                            entregas_faturamento_item.situacao = 'AR'
                        ))
                        ,0
                    ) entrega,
                    COALESCE(
                        SUM((
                            (
                                tipo_frete.tipo_ponto = 'PM'
                                AND entregas.situacao = 'EN'
                            ) OR
                            (
                                tipo_frete.tipo_ponto = 'PP'
                                AND entregas.situacao IN ('PT','EN')
                            )
                        )
                        AND entregas_faturamento_item.situacao = 'PE')
                        ,0
                    ) pendente
                FROM entregas
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                WHERE
                    tipo_frete.id_colaborador = :idColaborador
                    AND entregas_faturamento_item.situacao IN ('AR', 'PE')
                    AND entregas.situacao in ('PT','EN');";
        $dados = DB::selectOne($sql, [
            'idColaborador' => Auth::user()->id_colaborador,
        ]);

        if (!$dados) {
            $dados = [
                'pendente' => 0,
                'entrega' => 0,
            ];
        }
        return $dados;
    }
    /**
     * @param int $idColaborador
     * @param string $tipoDeEtiqueta [ ETIQUETA_PRODUTO | ETIQUETA_CLIENTE | ETIQUETA_VOLUME ]
     */
    public static function consultaSituacaoParaEntregar(int $idColaborador, string $tipoDeEtiqueta): array
    {
        $listaEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $condicaoVolumes =
            FacadesGate::allows('ADMIN') ||
            (FacadesGate::allows('ENTREGADOR') && $tipoDeEtiqueta === 'ETIQUETA_VOLUME');
        $condicaoTrocasEProdutos =
            in_array($tipoDeEtiqueta, ['ETIQUETA_PRODUTO', 'ETIQUETA_CLIENTE']) &&
            FacadesGate::any(['ENTREGADOR', 'PONTO_RETIRADA']);

        $sql = "SELECT
                    colaboradores.id id_colaborador,
                    colaboradores.razao_social nome_colaborador,
                    colaboradores.foto_perfil,
                    saldo_cliente(colaboradores.id) float_saldo_cliente,
                    IF(:condicaoTrocasEProdutos,
                        (
                            SELECT
                                COALESCE(
                                    CONCAT(
                                        '[',
                                        GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'foto_produto',(
                                                    SELECT
                                                        produtos_foto.caminho
                                                    FROM produtos_foto
                                                    WHERE
                                                        produtos_foto.id = entregas_faturamento_item.id_produto
                                                        AND produtos_foto.tipo_foto <> 'SM'
                                                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                                    LIMIT 1
                                                ),
                                                'nome_produto',(
                                                    SELECT
                                                        IF(LENGTH(produtos.nome_comercial) >= 2,
                                                            produtos.descricao,
                                                            produtos.nome_comercial
                                                        )
                                                    FROM produtos
                                                    WHERE produtos.id = entregas_faturamento_item.id_produto
                                                    LIMIT 1
                                                ),
                                                'situacao',entregas_faturamento_item.situacao,
                                                'id_produto',entregas_faturamento_item.id_produto,
                                                'nome_tamanho',entregas_faturamento_item.nome_tamanho,
                                                'preco',logistica_item.preco,
                                                'uuid_produto',entregas_faturamento_item.uuid_produto
                                            )
                                        ),
                                        ']'
                                    ),
                                    '[]'
                                )
                            FROM transacao_financeiras_produtos_trocas
                            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_trocas.uuid
                            INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
                            WHERE
                                transacao_financeiras_produtos_trocas.id_cliente = colaboradores.id
                                AND transacao_financeiras_produtos_trocas.situacao = 'PE'
                        ),
                        '[]'
                    ) json_trocas_agendadas,
                    CONCAT(
                        '[',
                        COALESCE(
                            IF(:condicaoVolumes
                                ,(
                                    SELECT
                                        GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'id',entregas.id,
                                                'situacao',entregas.situacao,
                                                'eh_entrega_cliente', entregas.id_tipo_frete IN ( :listaEntregaCliente )
                                            )
                                        )
                                    FROM entregas
                                    WHERE
                                        IF(:souEntregador,
                                            entregas.situacao = 'PT',
                                            entregas.situacao IN ('AB', 'EX')
                                        )
                                        AND entregas.id_cliente = :idColaborador
                                ),NULL
                            ),''
                        ),
                        ']'
                    ) json_quantidade_volumes_pendentes,
                    IF(:condicaoTrocasEProdutos,
                        (
                            SELECT
                                COALESCE(
                                    CONCAT(
                                        '[',
                                        GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'foto_produto',(
                                                    SELECT
                                                        produtos_foto.caminho
                                                    FROM produtos_foto
                                                    WHERE
                                                        produtos_foto.id = entregas_faturamento_item.id_produto
                                                        AND produtos_foto.tipo_foto <> 'SM'
                                                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                                    LIMIT 1
                                                ),
                                                'nome_produto',(
                                                    SELECT
                                                        IF(LENGTH(produtos.nome_comercial) >= 2,
                                                            produtos.descricao,
                                                            produtos.nome_comercial
                                                        )
                                                    FROM produtos
                                                    WHERE produtos.id = entregas_faturamento_item.id_produto
                                                    LIMIT 1
                                                ),
                                                'tipo_ponto',tipo_frete.tipo_ponto,
                                                'situacao',entregas_faturamento_item.situacao,
                                                'id_produto',entregas_faturamento_item.id_produto,
                                                'nome_tamanho',entregas_faturamento_item.nome_tamanho,
                                                'uuid_produto',entregas_faturamento_item.uuid_produto
                                            )
                                        ),
                                        ']'
                                    ),
                                    '[]'
                                )
                            FROM entregas
                            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                            WHERE
                                entregas_faturamento_item.id_cliente = colaboradores.id
                                AND entregas.situacao = 'EN'
                                AND (
                                    tipo_frete.id_colaborador = :idColaboradorTipoFrete
                                    OR tipo_frete.id_colaborador_ponto_coleta = :idColaboradorTipoFrete
                                )
                                AND entregas_faturamento_item.situacao IN ('PE','AR')
                        ),
                        '[]'
                    ) json_entregas_itens_pendentes,
                    COALESCE(
                        (
                            SELECT
                                JSON_OBJECT(
                                    'id',transacao_financeiras.id,
                                    'transacao',transacao_financeiras.cod_transacao,
                                    'tipo',transacao_financeiras.emissor_transacao,
                                    'id_pagador',transacao_financeiras.pagador,
                                    'pix',transacao_financeiras.qrcode_pix,
                                    'valor',transacao_financeiras.valor_liquido
                                )
                            FROM transacao_financeiras
                            WHERE
                                transacao_financeiras.origem_transacao = 'ET'
                                AND transacao_financeiras.status = 'PE'
                                AND transacao_financeiras.pagador = colaboradores.id
                            LIMIT 1
                        ),
                        '[]'
                    ) json_transacoes_pendentes,
                    (
                        SELECT
                            tipo_frete.id NOT IN ( :listaEntregaCliente )
                        FROM tipo_frete
                        WHERE tipo_frete.id_colaborador = colaboradores.id
                    ) bool_colaborador_ponto
                FROM colaboradores
                WHERE
                    colaboradores.id = :idColaborador";
        $dados = DB::selectOne($sql, [
            ':idColaborador' => $idColaborador,
            ':idColaboradorTipoFrete' => Auth::user()->id_colaborador,
            ':condicaoVolumes' => $condicaoVolumes,
            ':souEntregador' => FacadesGate::allows('ENTREGADOR'),
            ':condicaoTrocasEProdutos' => $condicaoTrocasEProdutos,
            ':listaEntregaCliente' => $listaEntregaCliente,
        ]);

        if (!$dados) {
            return [];
        }
        if (
            !empty($dados['quantidade_volumes_pendentes']) &&
            $dados['saldo_cliente'] < 0 &&
            $dados['colaborador_ponto'] &&
            !array_filter($dados['quantidade_volumes_pendentes'], fn($item) => $item['eh_entrega_cliente'])
        ) {
            // valor zerado intencionalmente para nao exibir tela de cobrança quando o ponto estiver devendo mas tiver entrega pendente
            $dados['saldo_cliente'] = 0;
        }
        $dados['quantidade_volumes_pendentes'] = count($dados['quantidade_volumes_pendentes']);
        unset($dados['colaborador_ponto']);

        if (
            $dados['saldo_cliente'] >= 0 &&
            empty($dados['trocas_agendadas']) &&
            empty($dados['entregas_itens_pendentes']) &&
            !(bool) $dados['quantidade_volumes_pendentes']
        ) {
            return [];
        }

        return $dados;
    }

    public static function criaEntregaOuMesclaComEntregaExistente(
        int $idCliente,
        int $idTipoFrete,
        int $volumes,
        ?int $idRaio,
        array $produtos
    ): int {
        $dadosTipoFrete = TipoFreteService::buscaDadosPontoComIdColaborador($idCliente);

        $entregaDePontoMovel =
            !empty($dadosTipoFrete['id']) &&
            $dadosTipoFrete['tipo_ponto'] === 'PM' &&
            !in_array($idTipoFrete, explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE));

        if ($entregaDePontoMovel) {
            if (empty($idRaio)) {
                throw new BadRequestException('Cliente sem raio de entrega');
            }

            if (!empty($produtos)) {
                EntregasFaturamentoItemService::verificaQuantidadeRaiosPorProdutos($produtos);
            }
        }
        $idDeEntregaExistente = self::buscaIdDeEntrega($idCliente, $idTipoFrete, $idRaio);

        if (!$idDeEntregaExistente) {
            $entrega = new Entrega();
            $entrega->id_cliente = $idCliente;
            $entrega->id_tipo_frete = $idTipoFrete;
            if ($entregaDePontoMovel) {
                $entrega->id_raio = $idRaio;
            }
            $entrega->save();
            $idDeEntregaExistente = $entrega->id;
        }

        if (!empty($produtos)) {
            app(EntregasFaturamentoItemService::class)->cria($idDeEntregaExistente, $produtos);
        }
        app(self::class)->recalculaEtiquetas($idDeEntregaExistente, $volumes);

        return $idDeEntregaExistente;
    }
    /**
     * Caso seja necessário manipular a etiqueta de outras formas, o programador deverá criar um model com o eloquent
     * assim como é feito no model de Entrega.
     *
     */
    public function recalculaEtiquetas(int $idEntrega, int $volume): int
    {
        $idUsuario = Auth::user()->id;

        $query = "SELECT
                        entregas.situacao,
                        entregas.volumes
                    FROM entregas
                    WHERE entregas.id = :idEntrega";

        $dadosEntrega = DB::selectOne($query, ['idEntrega' => $idEntrega]);

        if (!in_array($dadosEntrega['situacao'], ['AB', 'EX'])) {
            throw new BadRequestHttpException('Não é possível alterar o volume de uma entrega que não está em aberto');
        }

        $sql = "SELECT
                entregas_etiquetas.id,
                entregas_etiquetas.volume
            FROM entregas_etiquetas
            WHERE entregas_etiquetas.id_entrega = :id_entrega";

        $listaDeEtiquetas = DB::select($sql, ['id_entrega' => $idEntrega]);

        $quantidadeDeItems = COUNT($listaDeEtiquetas);
        $etiquetasParaDeletar = [];
        if ($quantidadeDeItems > $volume) {
            foreach ($listaDeEtiquetas as $etiqueta) {
                if ($etiqueta['volume'] > $volume) {
                    $etiquetasParaDeletar[] = $etiqueta['id'];
                }
            }
            [$bind, $valores] = ConversorArray::criaBindValues($etiquetasParaDeletar, 'id_etiqueta');
            $rowCount = DB::delete(
                "DELETE
                FROM entregas_etiquetas
                WHERE entregas_etiquetas.id IN ($bind)",
                $valores
            );

            if ($rowCount !== $quantidadeDeItems - $volume) {
                throw new Exception('Não foi possível remover as etiquetas');
            }
        }

        $bindAdicionaItem = [
            'id_entrega' => $idEntrega,
            'id_usuario' => $idUsuario,
        ];
        $sqlAdicionaItems = "INSERT INTO entregas_etiquetas
                (
                    entregas_etiquetas.id_entrega,
                    entregas_etiquetas.volume,
                    entregas_etiquetas.id_usuario
                )
            VALUES";
        if ($quantidadeDeItems < $volume) {
            for ($indice = $quantidadeDeItems + 1; $indice <= $volume; $indice++) {
                $sqlAdicionaItems .= " (:id_entrega, :volume$indice, :id_usuario),";
                $bindAdicionaItem[":volume$indice"] = $indice;
            }
            $sqlAdicionaItems = mb_substr($sqlAdicionaItems, 0, -1);

            DB::insert($sqlAdicionaItems, $bindAdicionaItem);
        }

        if ($dadosEntrega['volumes'] !== $volume) {
            $entrega = new Entrega();
            $entrega->exists = true;
            $entrega->id = $idEntrega;
            $entrega->volumes = $volume;
            $entrega->update();
        }

        return $volume;
    }
    public static function ConsultaEtiquetas(int $idEntrega): array
    {
        $resposta = DB::select(
            "SELECT
            entregas.id id_entrega,
            colaboradores.id nome_cliente,
            entregas_etiquetas.volume,
            COALESCE(municipios.nome, colaboradores_enderecos.cidade,'') nome_cidade_entrega,
            COALESCE(municipios.uf, colaboradores_enderecos.uf,'') uf_cidade_entrega,
            CONCAT(
                entregas.id_cliente,
                '_',
                entregas.id,
                '_',
                entregas_etiquetas.volume
            ) qrcode_entrega,
            IF (tipo_frete.categoria = 'ML', tipo_frete.nome,colaboradores.razao_social) nome_remetente,
            transportadores_raios.apelido apelido_raio
        FROM
            entregas
            INNER JOIN entregas_etiquetas ON entregas_etiquetas.id_entrega = entregas.id
            LEFT JOIN transportadores_raios ON transportadores_raios.id = entregas.id_raio
            LEFT JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
            LEFT JOIN colaboradores_enderecos ON
                colaboradores.id = colaboradores_enderecos.id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao IS TRUE
            LEFT JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
        WHERE
            entregas.id = :idEntrega
            GROUP BY entregas_etiquetas.id;",
            [
                'idEntrega' => $idEntrega,
            ]
        );
        if (empty($resposta)) {
            return [];
        }
        $resposta = array_map(
            function ($etiqueta) {
                $etiqueta['cidade'] =
                    mb_substr(Str::toUtf8($etiqueta['nome_cidade_entrega']), 0, 20) .
                    ', ' .
                    $etiqueta['uf_cidade_entrega'];
                if (mb_strlen($etiqueta['nome_remetente']) > 25) {
                    $etiqueta['nome_remetente'] = trim(mb_substr($etiqueta['nome_remetente'], 0, 25));
                }

                unset($etiqueta['nome_cidade_entrega'], $etiqueta['uf_cidade_entrega']);

                return $etiqueta;
            },
            $resposta,
            array_keys($resposta)
        );

        return $resposta;
    }
    public static function exibeQrcodeEntregasProntas(): array
    {
        $idCliente = Auth::user()->id_colaborador;
        $idsTipoFreteEntrega = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                JSON_OBJECT(
                    'id_tipo_frete', tipo_frete.id,
                    'nome_ponto', tipo_frete.nome,
                    'endereco_ponto', tipo_frete.mensagem,
                    'telefone_ponto', colaboradores.telefone,
                    'foto_perfil', colaboradores.foto_perfil,
                    'horario_ponto', tipo_frete.horario_de_funcionamento,
                    'tipo_ponto',
                        CASE
                            WHEN tipo_frete.id = 3 THEN 'RETIRADA'
                            WHEN tipo_frete.id NOT IN ($idsTipoFreteEntrega) THEN 'PONTO_RETIRADA'
                            ELSE 'INDEFINIDO'
                        END
                ) AS `json_dados_ponto`
        FROM
            entregas
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
        WHERE
            entregas_faturamento_item.id_cliente = :id_cliente
            AND IF(
                entregas.id_tipo_frete = 3,
                entregas.situacao IN ('AB', 'EX')
                AND entregas_faturamento_item.situacao = 'PE',
                entregas.situacao = 'EN'
                AND entregas_faturamento_item.situacao = 'AR'
            )
            AND tipo_frete.tipo_ponto <> 'PM'
        GROUP BY entregas.id;";

        $dados = DB::select($query, [
            'id_cliente' => $idCliente,
        ]);

        if (empty($dados)) {
            return [];
        }

        $novoResultado['pontos'] = array_map(function ($item) use ($idsTipoFreteEntrega) {
            if (in_array($item['dados_ponto']['id_tipo_frete'], explode(',', $idsTipoFreteEntrega))) {
                unset($item['dados_ponto']['telefone_ponto']);
            }
            return $item['dados_ponto'];
        }, $dados);

        $novoResultado['qrcode'] = Globals::geraQRCODE(Entrega::formataEtiquetaCliente($idCliente));

        return $novoResultado;
    }
    public static function consultarDadosDaEntregaParaFaturaMobile(int $idEntrega): array
    {
        $resultado = DB::selectOne(
            "SELECT
                    entregas.id,
                    DATE_FORMAT(entregas.data_atualizacao,'%d/%m/%Y - %H:%i') AS `data_atualizacao`,
                    colaboradores.razao_social,
                    colaboradores.telefone,
                    colaboradores_enderecos.logradouro,
                    colaboradores_enderecos.numero,
                    colaboradores_enderecos.bairro,
                    colaboradores_enderecos.cidade,
                    colaboradores_enderecos.uf,
                    CONCAT('[',
                        GROUP_CONCAT(
                            JSON_OBJECT(
                                'id_produto', entregas_faturamento_item.id_produto,
                                'nome_produto', (
                                    SELECT CONCAT_WS(' ', produtos.nome_comercial, produtos.cores)
                                    FROM produtos
                                    WHERE produtos.id = entregas_faturamento_item.id_produto),
                                'nome_tamanho', entregas_faturamento_item.nome_tamanho,
                                'preco', (
                                    SELECT logistica_item.preco
                                    FROM logistica_item
                                    WHERE logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
                                ),
                                'foto', (
                                    SELECT produtos_foto.caminho
                                    FROM produtos_foto
                                    WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                                    ORDER BY produtos_foto.tipo_foto = 'SM' DESC
                                    LIMIT 1
                                )
                            ) ORDER BY entregas_faturamento_item.id_produto ASC
                        )
                    ,']') AS `json_produtos`
                FROM entregas
                INNER JOIN colaboradores ON colaboradores.id = entregas.id_cliente
                LEFT JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id
                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                WHERE entregas.id = :idEntrega",
            [
                'idEntrega' => $idEntrega,
            ]
        );

        usort($resultado['produtos'], function ($produtoA, $produtoB) {
            return $produtoA['id_produto'] <=> $produtoB['id_produto'];
        });

        return $resultado;
    }
    public static function buscaIdClienteDaEntrega(string $uuidEntrega): int
    {
        $idClienteEntrega = DB::selectOneColumn(
            'SELECT
                entregas.id_cliente
            FROM entregas
            WHERE entregas.uuid_entrega = :uuid_entrega;',
            [
                'uuid_entrega' => $uuidEntrega,
            ]
        );
        if (!$idClienteEntrega) {
            throw new NotFoundHttpException('Esta entrega não existe.');
        }
        return $idClienteEntrega;
    }

    public static function existePedidoRetireAquiEmAberto(): bool
    {
        $temPedido = DB::selectOneColumn(
            "SELECT EXISTS (
                SELECT 1
                FROM logistica_item
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                WHERE logistica_item.id_cliente = :id_cliente
                    AND logistica_item.id_colaborador_tipo_frete = 32254
                    AND (logistica_item.id_entrega IS NULL OR entregas_faturamento_item.situacao <> 'EN')
                ) AS `tem_pedido`",
            [
                ':id_cliente' => Auth::user()->id_colaborador,
            ]
        );

        return $temPedido;
    }
}

<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\model\LogisticaItem;
use MobileStock\model\TipoFrete;

class AcompanhamentoTempService
{
    public function buscaProdutosParaAdicionarNoAcompanhamento(
        int $idDestinatario,
        int $idTipoFrete,
        int $idCidade
    ): array {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $parametros = [
            ':id_tipo_frete' => $idTipoFrete,
        ];

        $where = '';
        $inner = '';

        if (!in_array($idTipoFrete, explode(',', $idTipoFreteEntregaCliente))) {
            $where = " AND logistica_item.id_colaborador_tipo_frete = :id_colaborador_tipo_frete
            AND IF(
                tipo_frete.tipo_ponto = 'PM',
                JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade') = :id_cidade,
                TRUE
            ) ";
            $inner = " INNER JOIN transacao_financeiras_metadados ON
            transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON' ";

            $parametros[':id_colaborador_tipo_frete'] = $idDestinatario;
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
                AND IF(logistica_item.id_entrega > 0, entregas.situacao = 'AB', TRUE)
                $where";

        $resultado = DB::selectColumns($sql, $parametros);
        return $resultado;
    }

    public function buscaProdutosParaAdicionarNoAcompanhamentoPorPontosColeta(array $pontosColeta): array
    {
        [$bind, $valores] = ConversorArray::criaBindValues($pontosColeta, 'id_colaborador_ponto_coleta');
        $produtos = DB::selectColumns(
            "SELECT logistica_item.uuid_produto
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            LEFT JOIN entregas ON entregas.id = logistica_item.id_entrega
            WHERE tipo_frete.id_colaborador_ponto_coleta IN ($bind)
                AND IF (
                    logistica_item.id_entrega > 0,
                    entregas.situacao = 'AB',
                    TRUE
                )
            GROUP BY logistica_item.uuid_produto;",
            $valores
        );

        return $produtos;
    }

    public function criaAcompanhamento(int $idDestinatario, int $idTipoFrete, int $idUsuario, int $idCidade): int
    {
        $query = "INSERT IGNORE INTO acompanhamento_temp (
                    acompanhamento_temp.id_cidade,
                    acompanhamento_temp.id_destinatario,
                    acompanhamento_temp.id_tipo_frete,
                    acompanhamento_temp.id_usuario
                ) VALUES (
                    :id_cidade,
                    :id_destinatario,
                    :id_tipo_frete,
                    :id_usuario
                )";

        DB::insert($query, [
            'id_cidade' => $idCidade,
            'id_destinatario' => $idDestinatario,
            'id_tipo_frete' => $idTipoFrete,
            'id_usuario' => $idUsuario,
        ]);
        $ultimoId = DB::getPdo()->lastInsertId();
        return $ultimoId;
    }

    public function removerAcompanhamentoDestino(int $idDestinatario, int $idTipoFrete, int $idCidade): void
    {
        $query = "DELETE FROM acompanhamento_temp
                    WHERE
                        acompanhamento_temp.id_destinatario = :id_destinatario
                        AND acompanhamento_temp.id_tipo_frete = :id_tipo_frete
                        AND acompanhamento_temp.id_cidade = :id_cidade";

        $rowCount = DB::delete($query, [
            'id_destinatario' => $idDestinatario,
            'id_tipo_frete' => $idTipoFrete,
            'id_cidade' => $idCidade,
        ]);

        if ($rowCount !== 1) {
            throw new Exception('Não foi possível remover o acompanhamento');
        }
    }

    public function removeAcompanhamentoSemItems(): void
    {
        $sql = "SELECT
                     acompanhamento_temp.id_destinatario,
                     acompanhamento_temp.id_tipo_frete,
                     acompanhamento_temp.id_cidade,
                    (
                        SELECT
                             COUNT(acompanhamento_item_temp.id)
                        FROM acompanhamento_item_temp
                        WHERE acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                    ) quantidade
                FROM acompanhamento_temp
                GROUP BY acompanhamento_temp.id
                HAVING quantidade = 0";
        $acompanhamentos = DB::select($sql);

        if (empty($acompanhamentos)) {
            return;
        }

        foreach ($acompanhamentos as $item) {
            $this->removerAcompanhamentoDestino($item['id_destinatario'], $item['id_tipo_frete'], $item['id_cidade']);
        }
    }

    public function buscaAcompanhamentoPendentePorUuidProduto(array $listaDeUuid): array
    {
        [$itemsSql, $bind] = ConversorArray::criaBindValues($listaDeUuid);
        $idColaboradorEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;
        $sql = "SELECT
                    IF(logistica_item.id_colaborador_tipo_frete IN ($idColaboradorEntregaCliente),
                        logistica_item.id_cliente,
                        logistica_item.id_colaborador_tipo_frete
                    ) id_destinatario,
                    IF(tipo_frete.tipo_ponto = 'PM' OR tipo_frete.id_colaborador IN ($idColaboradorEntregaCliente),
                        metadados_municipios.id,
                        colaboradores_enderecos.id_cidade
                    ) AS `id_cidade`,
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
                                metadados_municipios.id,
                                colaboradores_enderecos.id_cidade
                            )
                        LIMIT 1
                    ) AS `id_acompanhamento`
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
                INNER JOIN municipios metadados_municipios ON
                    metadados_municipios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
                LEFT JOIN acompanhamento_item_temp ON acompanhamento_item_temp.uuid_produto = logistica_item.uuid_produto
                WHERE
                    logistica_item.uuid_produto IN ($itemsSql)
                    GROUP BY
                        id_destinatario,
                        tipo_frete.id,
                        IF(
                            tipo_frete.tipo_ponto = 'PM',
                            metadados_municipios.id,
                            TRUE
                        );";
        $resultado = DB::select($sql, $bind);

        return $resultado;
    }

    public function adicionaItemAcompanhamento(array $listaDeUuidProduto, int $idAcompanhamento, int $idUsuario): void
    {
        DB::table('acompanhamento_item_temp')->insertOrIgnore(
            array_map(
                fn(string $uuidProduto) => [
                    'id_acompanhamento' => $idAcompanhamento,
                    'uuid_produto' => $uuidProduto,
                    'id_usuario' => $idUsuario,
                ],
                $listaDeUuidProduto
            )
        );
    }

    public function determinaNivelDoAcompanhamento(int $idAcompanhamento, string $acao): void
    {
        $situacaoConferencia = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $idColaboradorEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;

        $acompanhamento = DB::selectOneColumn(
            "SELECT
                    acompanhamento_temp.situacao
                FROM acompanhamento_temp
                WHERE acompanhamento_temp.id = ?",
            [$idAcompanhamento]
        );

        if ($acompanhamento === 'PAUSADO' && $acao !== 'DESPAUSAR_ACOMPANHAMENTO') {
            return;
        }

        $sql = "SELECT
                    acompanhamento_item_temp.id_acompanhamento,
                    GROUP_CONCAT(DISTINCT(
                        SELECT 1
                        FROM logistica_item
                        WHERE
                            logistica_item.situacao = 'PE'
                            AND logistica_item.id_responsavel_estoque = 1
                            AND logistica_item.id_colaborador_tipo_frete IN ($idColaboradorEntregaCliente)
                            AND logistica_item.uuid_produto = acompanhamento_item_temp.uuid_produto
                    )) as `pode_separar`,
                    GROUP_CONCAT(DISTINCT(
                        SELECT 1
                        FROM logistica_item
                        WHERE
                            logistica_item.situacao = :situacaoLogistica
                            AND logistica_item.uuid_produto = acompanhamento_item_temp.uuid_produto
                            AND logistica_item.id_entrega IS NULL
                            AND IF(
                                logistica_item.id_colaborador_tipo_frete IN ($idColaboradorEntregaCliente),
                                NOT EXISTS (
                                    SELECT 1
                                    FROM logistica_item AS `ignora_logistica_item`
                                    WHERE
                                        ignora_logistica_item.id_cliente = acompanhamento_temp.id_destinatario
                                        AND ignora_logistica_item.id_colaborador_tipo_frete = logistica_item.id_colaborador_tipo_frete
                                        AND ignora_logistica_item.situacao IN ('PE','SE')
                                        AND ignora_logistica_item.id_responsavel_estoque = 1
                                ),
                                TRUE
                            )
                    )) as `pode_adicionar_entrega`,
                    GROUP_CONCAT(DISTINCT(
                        SELECT 1
                        FROM entregas
                        INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                        WHERE
                            entregas.id IN (
                                SELECT
                                    logistica_item.id_entrega
                                FROM logistica_item
                                WHERE acompanhamento_item_temp.uuid_produto = logistica_item.uuid_produto
                            )
                            AND entregas.situacao = 'AB'
                            AND CASE
                                WHEN entregas.id_tipo_frete = 2 THEN
                                NOT EXISTS(
                                    SELECT  1
                                    FROM logistica_item
                                    WHERE
                                        logistica_item.id_colaborador_tipo_frete = tipo_frete.id_colaborador
                                        AND logistica_item.id_cliente = entregas.id_cliente
                                        AND logistica_item.id_entrega IS NULL
                                        AND IF(logistica_item.id_responsavel_estoque > 1,
                                            logistica_item.situacao = :situacaoLogistica,
                                            logistica_item.situacao <= :situacaoLogistica
                                        )
                                    LIMIT 1
                                )
                                WHEN entregas.id_tipo_frete = 3 THEN
                                NOT EXISTS(
                                    SELECT  1
                                    FROM logistica_item
                                    WHERE
                                        logistica_item.id_colaborador_tipo_frete = tipo_frete.id_colaborador
                                        AND logistica_item.id_cliente = entregas.id_cliente
                                        AND logistica_item.id_entrega IS NULL
                                    LIMIT 1
                                )
                                ELSE
                                NOT EXISTS(
                                    SELECT  1
                                    FROM logistica_item
                                    INNER JOIN transacao_financeiras_metadados ON
                                        transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                                    WHERE
                                        logistica_item.id_colaborador_tipo_frete = entregas.id_cliente
                                        AND logistica_item.id_entrega IS NULL
                                        AND logistica_item.situacao = :situacaoLogistica
                                        AND IF(tipo_frete.tipo_ponto = 'PM',
                                            entregas.id_cliente = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                                            TRUE
                                        )
                                    LIMIT 1
                                )
                            END
                    )) as `tem_entrega_em_aberto`
                FROM acompanhamento_temp
                INNER JOIN acompanhamento_item_temp ON acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                WHERE acompanhamento_item_temp.id_acompanhamento = :idAcompanhamento";
        $dados = DB::selectOne($sql, [
            'idAcompanhamento' => $idAcompanhamento,
            'situacaoLogistica' => $situacaoConferencia,
        ]);
        if (empty($dados)) {
            throw new Exception('Defina o id acompanhamento');
        }

        switch (true) {
            case $acao === 'PAUSAR_ACOMPANHAMENTO':
                $novaSituacao = 'PAUSADO';
                break;
            case $dados['pode_separar']:
                $novaSituacao = 'AGUARDANDO_SEPARAR';
                break;
            case $dados['pode_adicionar_entrega']:
                $novaSituacao = 'AGUARDANDO_ADICIONAR_ENTREGA';
                break;
            case $dados['tem_entrega_em_aberto']:
                $novaSituacao = 'ENTREGA_EM_ABERTO';
                break;
            default:
                $novaSituacao = 'PENDENTE';
        }
        if ($acompanhamento !== $novaSituacao) {
            $sql = 'UPDATE
                acompanhamento_temp
            SET
                acompanhamento_temp.situacao =:situacao
            WHERE acompanhamento_temp.id = :idAcompanhamento';
            $rowCount = DB::update($sql, [
                'idAcompanhamento' => $idAcompanhamento,
                'situacao' => $novaSituacao,
            ]);

            if ($rowCount === 0) {
                throw new Exception(
                    "Não foi possível atualizar a situação do acompanhamento $idAcompanhamento para $novaSituacao"
                );
            }
        }
    }

    public function listarAcompanhamentoDestino(): array
    {
        $query = "SELECT
                    SUM(acompanhamento_temp.situacao = 'AGUARDANDO_SEPARAR') AS `pendentes`,
                    SUM(acompanhamento_temp.situacao = 'AGUARDANDO_ADICIONAR_ENTREGA') AS `conferidos`,
                    SUM(acompanhamento_temp.situacao = 'ENTREGA_EM_ABERTO') AS `entregasAB`,
                    (
                        SELECT
                            COUNT(entregas_fechadas_temp.id_entrega) AS `entregasEX`
                        FROM entregas_fechadas_temp
                        INNER JOIN entregas ON entregas.id = entregas_fechadas_temp.id_entrega
                        WHERE entregas.situacao = 'EX'
                    ) AS `entregasEX`
                FROM acompanhamento_temp";

        $resultado = DB::selectOne($query);

        return [
            'pendentes' => (int) $resultado['pendentes'],
            'conferidos' => (int) $resultado['conferidos'],
            'entregasAB' => (int) $resultado['entregasAB'],
            'entregasEX' => (int) $resultado['entregasEX'],
        ];
    }

    public function listarAcompanhamentoParaSeparar(): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                        colaboradores.id AS `id_cliente`,
                        IF(tipo_frete.id IN ($idTipoFreteEntregaCliente), colaboradores.razao_social, tipo_frete.nome) AS `razao_social`,
                        colaboradores.foto_perfil,
                        COUNT(acompanhamento_item_temp.uuid_produto) AS `qtd_produtos`,
                        DATE_FORMAT(MAX(acompanhamento_item_temp.data_criacao), '%d/%m/%Y %H:%i:%s') AS `data_ultima_liberacao`,
                        GROUP_CONCAT(acompanhamento_item_temp.uuid_produto) AS `uuids`,
                        IF (
                            tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
                        ) AS `tipo_ponto`,
                        tipo_frete.id IN ($idTipoFreteEntregaCliente) AS `entrega_cliente`
                    FROM acompanhamento_temp
                    INNER JOIN acompanhamento_item_temp ON acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                    INNER JOIN colaboradores ON colaboradores.id = acompanhamento_temp.id_destinatario
                    INNER JOIN tipo_frete ON tipo_frete.id = acompanhamento_temp.id_tipo_frete
                    INNER JOIN logistica_item ON
                        logistica_item.uuid_produto = acompanhamento_item_temp.uuid_produto
                        AND logistica_item.id_responsavel_estoque = 1
                        AND logistica_item.situacao = 'PE'
                    WHERE acompanhamento_temp.situacao = 'AGUARDANDO_SEPARAR'
                    GROUP BY acompanhamento_temp.id";

        $resultado = DB::select($query);

        $resultado = array_map(function ($item) {
            $item['id'] = rand();
            $item['id_cliente'] = (int) $item['id_cliente'];
            $item['qtd_produtos'] = (int) $item['qtd_produtos'];
            $item['uuids'] = explode(',', $item['uuids']);
            $item['entrega_cliente'] = (bool) $item['entrega_cliente'];
            return $item;
        }, $resultado);

        return $resultado;
    }

    public function listarAcompanhamentoConferidos(): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                    colaboradores.id AS `id_cliente`,
                    IF(tipo_frete.id IN ($idTipoFreteEntregaCliente), colaboradores.razao_social, tipo_frete.nome) AS `razao_social`,
                    colaboradores.foto_perfil,
                    COUNT(acompanhamento_item_temp.uuid_produto) AS `qtd_produtos`,
                    DATE_FORMAT(MAX(logistica_item.data_atualizacao), '%d/%m/%Y %H:%i:%s') AS `data_ultima_conferencia`,
                    acompanhamento_item_temp.uuid_produto AS `ultimo_uuid`,
                    IF (
                        tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
                    ) AS `tipo_ponto`,
                    tipo_frete.id IN ($idTipoFreteEntregaCliente) AS `bool_entrega_cliente`,
                    (
                        SELECT CONCAT(municipios.nome, ' (', municipios.uf, ')')
                        FROM municipios
                        INNER JOIN colaboradores_enderecos ON
                            colaboradores_enderecos.id_colaborador = colaboradores.id
                            AND colaboradores_enderecos.eh_endereco_padrao = 1
                        WHERE
                            IF(
                                tipo_frete.id IN ($idTipoFreteEntregaCliente) OR tipo_frete.tipo_ponto = 'PM',
                                municipios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                                municipios.id = colaboradores_enderecos.id_cidade
                            )
                        LIMIT 1
                    ) AS `cidade`
                FROM acompanhamento_temp
                INNER JOIN acompanhamento_item_temp ON acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                INNER JOIN colaboradores ON colaboradores.id = acompanhamento_temp.id_destinatario
                INNER JOIN tipo_frete ON tipo_frete.id = acompanhamento_temp.id_tipo_frete
                INNER JOIN logistica_item ON
                    logistica_item.uuid_produto = acompanhamento_item_temp.uuid_produto
                    AND logistica_item.situacao = 'CO'
                    AND logistica_item.id_entrega IS NULL
                INNER JOIN transacao_financeiras_metadados ON
                    transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                WHERE acompanhamento_temp.situacao = 'AGUARDANDO_ADICIONAR_ENTREGA'
                GROUP BY acompanhamento_temp.id";

        $resultado = DB::select($query);

        return $resultado;
    }

    public function listarAcompanhamentoEntregasAbertas(): array
    {
        $idEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                        logistica_item.id_entrega AS `id_entrega`,
                        DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y %H:%i:%s') AS `data_criacao`,
                        @entrega_cliente := tipo_frete.id IN ($idEntregaCliente) AS `bool_entrega_cliente`,
                        CASE
                            WHEN @entrega_cliente = TRUE THEN (
                                SELECT JSON_OBJECT(
                                    'id_colaborador', colaboradores.id,
                                    'destino', colaboradores.razao_social,
                                    'cidade', CONCAT(
                                        JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade'),', ', JSON_VALUE(transacao_financeiras_metadados.valor, '$.uf')
                                        ),
                                    'tipo_embalagem', colaboradores.tipo_embalagem
                                )
                                FROM colaboradores
                                INNER JOIN transacao_financeiras_metadados ON
                                    transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
                                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                                INNER JOIN colaboradores_enderecos ON
                                    colaboradores_enderecos.id_colaborador = tipo_frete.id_colaborador
                                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                                WHERE colaboradores.id = acompanhamento_temp.id_destinatario
                                LIMIT 1
                            )
                            WHEN @entrega_cliente = FALSE AND tipo_frete.tipo_ponto = 'PP' THEN (
                                SELECT JSON_OBJECT(
                                    'id_colaborador', colaboradores.id,
                                    'destino', tipo_frete.nome,
                                    'cidade', CONCAT(colaboradores_enderecos.cidade,', ', colaboradores_enderecos.uf),
                                    'tipo_embalagem', colaboradores.tipo_embalagem
                                )
                                FROM colaboradores
                                INNER JOIN colaboradores_enderecos ON
                                    colaboradores_enderecos.id_colaborador = colaboradores.id
                                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                                WHERE colaboradores.id = tipo_frete.id_colaborador
                                LIMIT 1
                            )
                            WHEN @entrega_cliente = FALSE AND tipo_frete.tipo_ponto = 'PM' THEN (
                                SELECT JSON_OBJECT(
                                    'cidade', concat(municipios.nome, ', ', municipios.uf),
                                    'destino', tipo_frete.nome,
                                    'id_colaborador', tipo_frete.id_colaborador,
                                    'tipo_embalagem', colaboradores.tipo_embalagem
                                )
                                FROM municipios
                                INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador_ponto_coleta
                                WHERE municipios.id = acompanhamento_temp.id_cidade
                                LIMIT 1
                            )
                        END AS `destino_item_json`,
                        IF (
                            tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
                        ) AS `tipo_ponto`
                    FROM acompanhamento_temp
                    INNER JOIN acompanhamento_item_temp ON acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                    INNER JOIN tipo_frete ON tipo_frete.id = acompanhamento_temp.id_tipo_frete
                    INNER JOIN colaboradores ON colaboradores.id = acompanhamento_temp.id_destinatario
                    INNER JOIN logistica_item ON logistica_item.uuid_produto = acompanhamento_item_temp.uuid_produto
                    WHERE
                        acompanhamento_temp.situacao = 'ENTREGA_EM_ABERTO'
                        AND logistica_item.id_entrega IS NOT NULL
                    GROUP BY logistica_item.id_entrega
                    ORDER BY logistica_item.id_entrega ASC";

        $retorno = DB::select($query);

        foreach ($retorno as &$item) {
            $item = array_merge($item, $item['destino_item']);
            unset($item['destino_item']);
        }

        return $retorno ?: [];
    }

    public function buscarAcompanhamentoDestino(int $idDestinatario, int $idTipoFrete, int $idCidade): array
    {
        $resultado = DB::selectOne(
            "SELECT
                    acompanhamento_temp.id AS `id_acompanhamento`,
                    acompanhamento_temp.situacao
                FROM acompanhamento_temp
                WHERE
                    acompanhamento_temp.id_cidade = :id_cidade
                    AND acompanhamento_temp.id_destinatario = :id_destinatario
                    AND acompanhamento_temp.id_tipo_frete = :id_tipo_frete",
            [
                'id_cidade' => $idCidade,
                'id_destinatario' => $idDestinatario,
                'id_tipo_frete' => $idTipoFrete,
            ]
        );

        return $resultado ?: [];
    }
}

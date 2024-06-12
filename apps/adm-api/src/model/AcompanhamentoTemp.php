<?php
namespace MobileStock\model;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property int $id_destinatario
 * @property int $id_tipo_frete
 * @property int $id_cidade
 * @property ?int $id_raio
 * @property int $id_usuario
 * @property string $situacao
 */
class AcompanhamentoTemp extends Model
{
    protected $table = 'acompanhamento_temp';
    protected $fillable = ['id_destinatario', 'id_tipo_frete', 'id_cidade', 'id_raio', 'id_usuario'];

    public static function determinaNivelDoAcompanhamento(int $idAcompanhamento, string $acao): void
    {
        $situacaoConferencia = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $idColaboradorEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;

        $acompanhamento = self::buscarDadosAcompanhamentoPorId($idAcompanhamento);

        if ($acompanhamento->situacao === 'PAUSADO' && $acao !== 'DESPAUSAR_ACOMPANHAMENTO') {
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
            throw new NotFoundHttpException('Defina o id acompanhamento');
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
        if ($acompanhamento->situacao !== $novaSituacao) {
            $acompanhamento->situacao = $novaSituacao;
            $acompanhamento->update();
        }
    }

    public static function buscarDadosAcompanhamentoPorId(int $idAcompanhamento): self
    {
        $resultado = self::fromQuery(
            "SELECT
            acompanhamento_temp.id,
            acompanhamento_temp.situacao
        FROM acompanhamento_temp WHERE acompanhamento_temp.id = :id_acompanhamento",
            ['id_acompanhamento' => $idAcompanhamento]
        )->first();

        if (!$resultado) {
            throw new NotFoundHttpException('Acompanhamento não encontrado');
        }

        return $resultado;
    }

    public static function listarAcompanhamentoConferidos(): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                    colaboradores.id AS `id_cliente`,
                    IF(tipo_frete.id IN ($idTipoFreteEntregaCliente), colaboradores.razao_social, tipo_frete.nome) AS `razao_social`,
                    COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}/images/avatar-padrao-mobile.jpg') AS `foto_perfil`,
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
                    ) AS `cidade`,
                    (
                        SELECT
                            COALESCE(
                                CONCAT('(',transportadores_raios.id,') ', transportadores_raios.apelido),
                                CONCAT('ID ', transportadores_raios.id)
                            )
                        FROM transportadores_raios
                        WHERE
                            transportadores_raios.id = acompanhamento_temp.id_raio
                    ) AS `apelido_raio`
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

    public static function listarAcompanhamentoEntregasAbertas(): array
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
                        ) AS `tipo_ponto`,
                        (
                            SELECT
                                COALESCE(
                                    CONCAT('(',transportadores_raios.id,') ', transportadores_raios.apelido),
                                    CONCAT('ID ', transportadores_raios.id)
                                )
                            FROM transportadores_raios
                            WHERE
                                transportadores_raios.id = acompanhamento_temp.id_raio
                        ) AS `apelido_raio`
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

        return $retorno;
    }

    public static function buscarAcompanhamentoDestino(
        int $idDestinatario,
        int $idTipoFrete,
        int $idCidade,
        ?int $idRaio = null
    ): array {
        $resultado = DB::selectOne(
            "SELECT
                    acompanhamento_temp.id AS `id_acompanhamento`,
                    acompanhamento_temp.situacao
                FROM acompanhamento_temp
                WHERE
                    acompanhamento_temp.id_cidade = :id_cidade
                    AND acompanhamento_temp.id_destinatario = :id_destinatario
                    AND acompanhamento_temp.id_tipo_frete = :id_tipo_frete
                    AND IF(
                        :id_raio IS NOT NULL,
                        acompanhamento_temp.id_raio = :id_raio,
                        TRUE
                    )",
            [
                'id_cidade' => $idCidade,
                'id_destinatario' => $idDestinatario,
                'id_tipo_frete' => $idTipoFrete,
                'id_raio' => $idRaio,
            ]
        );

        return $resultado ?: [];
    }

    public static function removeAcompanhamentoSemItems(): void
    {
        $acompanhamentos = self::fromQuery(
            "SELECT acompanhamento_temp.id
                FROM acompanhamento_temp
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM acompanhamento_item_temp
                    WHERE acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                );"
        );

        foreach ($acompanhamentos as $acompanhamento) {
            $acompanhamento->delete();
        }
    }
}

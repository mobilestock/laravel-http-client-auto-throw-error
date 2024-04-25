<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\model\LogisticaItemModel;

class ReputacaoFornecedoresService
{
    const REPUTACAO_RUIM = 'RUIM';
    const REPUTACAO_REGULAR = 'REGULAR';
    const REPUTACAO_EXCELENTE = 'EXCELENTE';
    const REPUTACAO_MELHOR_FABRICANTE = 'MELHOR_FABRICANTE';

    public static function limparReputacoes(): void
    {
        DB::delete('DELETE FROM reputacao_fornecedores WHERE TRUE');
    }

    public static function gerarValorEQuantidadeVendas(): void
    {
        $diasVendas = ProdutosPontosMetadadosService::buscaValoresMetadados(DB::getPdo(), ['DIAS_VENDAS'])[
            'DIAS_VENDAS'
        ];
        $rowCount = DB::insert(
            "INSERT INTO reputacao_fornecedores (
                reputacao_fornecedores.id_colaborador,
                reputacao_fornecedores.valor_vendido,
                reputacao_fornecedores.vendas_totais
            )
            SELECT usuarios.id_colaborador,
                COALESCE(SUM(IF(logistica_item.situacao <= :situacao, transacao_financeiras_produtos_itens.comissao_fornecedor, 0)), 0),
                COUNT(transacao_financeiras_produtos_itens.id)
            FROM transacao_financeiras_produtos_itens
            INNER JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            INNER JOIN usuarios ON usuarios.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
                AND usuarios.permissao REGEXP '30'
            WHERE DATE(logistica_item.data_criacao) >= CURDATE() - INTERVAL :dias_vendas DAY
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
            GROUP BY usuarios.id_colaborador",
            ['situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA, 'dias_vendas' => $diasVendas]
        );
        if ($rowCount === 0) {
            throw new Exception('Erro em gerarValorEQuantidadeVendas()');
        }
    }

    public static function gerarVendasEntregues(): void
    {
        $rowCount = DB::update(
            "UPDATE reputacao_fornecedores
            INNER JOIN (
                SELECT
                    COUNT(entregas_faturamento_item.id) quantidade,
                    produtos.id_fornecedor
                FROM entregas_faturamento_item
                INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
                WHERE
                      entregas_faturamento_item.situacao = 'EN'
                GROUP BY produtos.id_fornecedor
            ) consulta
            SET reputacao_fornecedores.vendas_entregues = consulta.quantidade
            WHERE reputacao_fornecedores.id_colaborador = consulta.id_fornecedor;"
        );
        if ($rowCount === 0) {
            throw new Exception('Erro em gerarVendasEntregues()');
        }
    }

    public static function gerarMediaEnvio(): void
    {
        $diasMediasEnvio = ProdutosPontosMetadadosService::buscaValoresMetadados(DB::getPdo(), ['DIAS_MEDIAS_ENVIO'])[
            'DIAS_MEDIAS_ENVIO'
        ];
        $rowCount = DB::update(
            "UPDATE reputacao_fornecedores
            SET reputacao_fornecedores.media_envio = (
                SELECT COALESCE(CEIL(AVG(
                    (
                        SELECT DATEDIFF_DIAS_UTEIS(logistica_item_data_alteracao.data_criacao, logistica_item.data_criacao)
                        FROM logistica_item_data_alteracao
                        WHERE logistica_item_data_alteracao.uuid_produto = logistica_item.uuid_produto
                            AND logistica_item_data_alteracao.situacao_anterior = 'SE'
                            AND logistica_item_data_alteracao.situacao_nova = 'CO'
                    )
                )), 0)
                FROM logistica_item
                INNER JOIN produtos ON produtos.id = logistica_item.id_produto
                WHERE DATE(logistica_item.data_criacao) >= CURDATE() - INTERVAL :dias_media DAY
                    AND produtos.id_fornecedor = reputacao_fornecedores.id_colaborador
            )",
            ['dias_media' => $diasMediasEnvio]
        );
        if ($rowCount === 0) {
            throw new Exception('Erro em gerarMediaEnvio()');
        }
    }

    public static function gerarCancelamentos(): void
    {
        $metadados = ProdutosPontosMetadadosService::buscaValoresMetadados(DB::getPdo(), [
            'DIAS_CANCELAMENTO',
            'DIAS_VENDAS',
        ]);
        $sqlCriterioAfetarReputacao = self::sqlCriterioAfetarReputacao();
        $logisticasDeletadas = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id_fornecedor,
                SUM(IF(DATE(logistica_item_data_alteracao.data_criacao) >= CURDATE() - INTERVAL :dias_cancelamento DAY, 1, 0)) qtd_recente,
                COUNT(logistica_item_data_alteracao.id) qtd
            FROM logistica_item_data_alteracao
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = logistica_item_data_alteracao.uuid_produto
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
            WHERE logistica_item_data_alteracao.situacao_nova = 'RE'
                AND $sqlCriterioAfetarReputacao
                AND DATE(logistica_item_data_alteracao.data_criacao) >= CURDATE() - INTERVAL :dias_vendas DAY
            GROUP BY transacao_financeiras_produtos_itens.id_fornecedor",
            [
                'dias_cancelamento' => $metadados['DIAS_CANCELAMENTO'],
                'dias_vendas' => $metadados['DIAS_VENDAS'],
            ]
        );

        $itensDevolvidos = DB::select(
            "SELECT produtos.id_fornecedor,
                SUM(IF(DATE(entregas_devolucoes_item.data_criacao) >= CURDATE() - INTERVAL :dias_cancelamento DAY, 1, 0)) qtd_recente,
                COUNT(entregas_devolucoes_item.id) qtd
            FROM entregas_devolucoes_item
            INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = entregas_devolucoes_item.uuid_produto
            WHERE entregas_devolucoes_item.tipo = 'DE'
                AND DATE(entregas_devolucoes_item.data_criacao) >= CURDATE() - INTERVAL :dias_vendas DAY
            GROUP BY produtos.id_fornecedor",
            [
                'dias_cancelamento' => $metadados['DIAS_CANCELAMENTO'],
                'dias_vendas' => $metadados['DIAS_VENDAS'],
            ]
        );

        $bind = [];
        $casesDiasRecentes = '';
        $cases = '';
        foreach ($logisticasDeletadas as $item) {
            $chave_fornecedor = ":_chave_fornecedor_{$item['id_fornecedor']}";
            $bind[$chave_fornecedor] = $item['id_fornecedor'];

            $chave_cancelamento_recentes = ":_chave_cancelamento_recentes_{$item['id_fornecedor']}";
            $casesDiasRecentes .= " WHEN reputacao_fornecedores.id_colaborador = $chave_fornecedor THEN $chave_cancelamento_recentes";
            $bind[$chave_cancelamento_recentes] = (int) $item['qtd_recente'];

            $chave_cancelamento = ":_chave_cancelamento_{$item['id_fornecedor']}";
            $cases .= " WHEN reputacao_fornecedores.id_colaborador = $chave_fornecedor THEN $chave_cancelamento";
            $bind[$chave_cancelamento] = (int) $item['qtd'];
        }

        foreach ($itensDevolvidos as $item) {
            $chave_fornecedor = ":_chave_fornecedor_{$item['id_fornecedor']}";
            $bind[$chave_fornecedor] = $item['id_fornecedor'];

            $chave_cancelamento_recentes = ":_chave_cancelamento_recentes_{$item['id_fornecedor']}";
            if (isset($bind[$chave_cancelamento_recentes])) {
                $bind[$chave_cancelamento_recentes] += (int) $item['qtd_recente'];
            } else {
                $casesDiasRecentes .= " WHEN reputacao_fornecedores.id_colaborador = $chave_fornecedor THEN $chave_cancelamento_recentes";
                $bind[$chave_cancelamento_recentes] = (int) $item['qtd_recente'];
            }

            $chave_cancelamento = ":_chave_cancelamento_{$item['id_fornecedor']}";
            if (isset($bind[$chave_cancelamento])) {
                $bind[$chave_cancelamento] += (int) $item['qtd'];
            } else {
                $cases .= " WHEN reputacao_fornecedores.id_colaborador = $chave_fornecedor THEN $chave_cancelamento";
                $bind[$chave_cancelamento] = (int) $item['qtd'];
            }
        }

        $rowCount = DB::update(
            "UPDATE reputacao_fornecedores
            SET reputacao_fornecedores.vendas_canceladas_totais = (CASE $cases ELSE 0 END),
                reputacao_fornecedores.vendas_canceladas_recentes = (CASE $casesDiasRecentes ELSE 0 END)",
            $bind
        );
        if ($rowCount === 0) {
            throw new Exception('Erro em gerarCancelamentos()');
        }

        $rowCount = DB::update(
            "UPDATE reputacao_fornecedores
            SET reputacao_fornecedores.taxa_cancelamento = COALESCE(reputacao_fornecedores.vendas_canceladas_totais, 0) / COALESCE(reputacao_fornecedores.vendas_totais, 1) * 100"
        );
        if ($rowCount === 0) {
            throw new Exception('Erro em gerarCancelamentos() Taxa de cancelamento');
        }
    }

    public static function gerarReputacao(): void
    {
        $metadados = ProdutosPontosMetadadosService::buscaValoresMetadados(DB::getPdo(), [
            'VALOR_VENDIDO_MELHOR_FABRICANTE',
            'VALOR_VENDIDO_EXCELENTE',
            'VALOR_VENDIDO_REGULAR',
            'MEDIA_DIAS_ENVIO_MELHOR_FABRICANTE',
            'MEDIA_DIAS_ENVIO_EXCELENTE',
            'MEDIA_DIAS_ENVIO_REGULAR',
            'TAXA_CANCELAMENTO_MELHOR_FABRICANTE',
            'TAXA_CANCELAMENTO_EXCELENTE',
            'TAXA_CANCELAMENTO_REGULAR',
        ]);
        $rowCount = DB::update(
            "UPDATE reputacao_fornecedores
            SET reputacao_fornecedores.reputacao = CASE
                WHEN (reputacao_fornecedores.valor_vendido >= :valor_vendido_melhor_fabricante
                    AND reputacao_fornecedores.media_envio <= :media_dias_envio_melhor_fabricante
                    AND reputacao_fornecedores.taxa_cancelamento <= :taxa_cancelamento_melhor_fabricante
                ) THEN :reputacao_melhor_fabricante
                WHEN (reputacao_fornecedores.valor_vendido >= :valor_vendido_excelente
                    AND reputacao_fornecedores.media_envio <= :media_dias_envio_excelente
                    AND reputacao_fornecedores.taxa_cancelamento <= :taxa_cancelamento_excelente
                ) THEN :reputacao_excelente
                WHEN (reputacao_fornecedores.valor_vendido >= :valor_vendido_regular
                    AND reputacao_fornecedores.media_envio <= :media_dias_envio_regular
                    AND reputacao_fornecedores.taxa_cancelamento <= :taxa_cancelamento_regular
                ) THEN :reputacao_regular
                ELSE :reputacao_ruim
            END",
            [
                'valor_vendido_melhor_fabricante' => $metadados['VALOR_VENDIDO_MELHOR_FABRICANTE'],
                'media_dias_envio_melhor_fabricante' => $metadados['MEDIA_DIAS_ENVIO_MELHOR_FABRICANTE'],
                'taxa_cancelamento_melhor_fabricante' => $metadados['TAXA_CANCELAMENTO_MELHOR_FABRICANTE'],
                'valor_vendido_excelente' => $metadados['VALOR_VENDIDO_EXCELENTE'],
                'media_dias_envio_excelente' => $metadados['MEDIA_DIAS_ENVIO_EXCELENTE'],
                'taxa_cancelamento_excelente' => $metadados['TAXA_CANCELAMENTO_EXCELENTE'],
                'valor_vendido_regular' => $metadados['VALOR_VENDIDO_REGULAR'],
                'media_dias_envio_regular' => $metadados['MEDIA_DIAS_ENVIO_REGULAR'],
                'taxa_cancelamento_regular' => $metadados['TAXA_CANCELAMENTO_REGULAR'],
                'reputacao_melhor_fabricante' => self::REPUTACAO_MELHOR_FABRICANTE,
                'reputacao_excelente' => self::REPUTACAO_EXCELENTE,
                'reputacao_regular' => self::REPUTACAO_REGULAR,
                'reputacao_ruim' => self::REPUTACAO_RUIM,
            ]
        );
        if ($rowCount === 0) {
            throw new Exception('Erro em gerarReputacao()');
        }
    }

    public static function buscaFornecedoresFiltro(): array
    {
        $resultado = DB::select(
            "SELECT reputacao_fornecedores.id_colaborador `id`,
                colaboradores.razao_social `nome`
            FROM reputacao_fornecedores
            INNER JOIN colaboradores ON colaboradores.id = reputacao_fornecedores.id_colaborador
            WHERE reputacao_fornecedores.reputacao IN (
                '" .
                ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE .
                "',
                '" .
                ReputacaoFornecedoresService::REPUTACAO_EXCELENTE .
                "'
            )
            ORDER BY colaboradores.razao_social"
        );
        return $resultado;
    }

    public static function sqlCriterioAfetarReputacao(): string
    {
        return "(
            logistica_item_data_alteracao.id_usuario = 2
         OR transacao_financeiras_produtos_itens.id_responsavel_estoque = usuarios.id_colaborador
         OR EXISTS(
            SELECT 1
            FROM negociacoes_produto_log
            WHERE negociacoes_produto_log.uuid_produto = logistica_item_data_alteracao.uuid_produto
              AND negociacoes_produto_log.situacao = 'RECUSADA')
        )";
    }
}

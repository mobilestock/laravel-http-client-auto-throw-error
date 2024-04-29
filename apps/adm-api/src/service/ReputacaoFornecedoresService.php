<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\LogisticaItemModel;
use MobileStock\repository\UsuariosRepository;

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
        $diasVendas = ConfiguracaoService::buscaFatoresReputacaoFornecedores()['dias_vendas'];
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
        $diasMediasEnvio = ConfiguracaoService::buscaFatoresReputacaoFornecedores()['dias_medias_envio'];
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
        $metadados = ConfiguracaoService::buscaFatoresReputacaoFornecedores();
        $metadados = Arr::only($metadados, ['dias_cancelamento', 'dias_vendas']);
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
            $metadados
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
            $metadados
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
        $metadados = ConfiguracaoService::buscaFatoresReputacaoFornecedores();
        $metadados = array_merge(
            Arr::only($metadados, [
                'valor_vendido_melhor_fabricante',
                'valor_vendido_excelente',
                'valor_vendido_regular',
                'media_dias_envio_melhor_fabricante',
                'media_dias_envio_excelente',
                'media_dias_envio_regular',
                'taxa_cancelamento_melhor_fabricante',
                'taxa_cancelamento_excelente',
                'taxa_cancelamento_regular',
            ]),
            [
                'reputacao_melhor_fabricante' => self::REPUTACAO_MELHOR_FABRICANTE,
                'reputacao_excelente' => self::REPUTACAO_EXCELENTE,
                'reputacao_regular' => self::REPUTACAO_REGULAR,
                'reputacao_ruim' => self::REPUTACAO_RUIM,
            ]
        );

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
            $metadados
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
    public static function buscaDadosDoFornecedorPraDashboard(): ?array
    {
        $idColaborador = Auth::user()->id_colaborador;
        $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores();
        $informacoes = DB::selectOne(
            "SELECT
                reputacao_fornecedores.media_envio AS `dias_despacho`,
                reputacao_fornecedores.taxa_cancelamento,
                reputacao_fornecedores.reputacao,
                COALESCE(reputacao_fornecedores.valor_vendido, 0) AS `valor_vendido`
            FROM reputacao_fornecedores
            WHERE reputacao_fornecedores.id_colaborador = :id_fornecedor",
            ['id_fornecedor' => $idColaborador]
        );
        if (empty($informacoes)) {
            return null;
        }

        $informacoes[
            'dias_impulsionar'
        ] = (int) UsuariosRepository::buscaDiasFaltaParaDesbloquearBotaoAtualizadaDataEntradaProdutos(
            DB::getPdo(),
            $idColaborador
        )['dias'];

        $progressos = [
            'cancelamento' => 100,
            'despacho' => 100,
            'valor_vendas' => 0,
        ];
        if (!empty($informacoes['dias_despacho'])) {
            $progressos['despacho'] = min(
                100,
                ($fatores['media_dias_envio_melhor_fabricante'] / $informacoes['dias_despacho']) * 100
            );
        }
        if (!empty($informacoes['taxa_cancelamento'])) {
            $progressos['cancelamento'] = min(
                100,
                ($fatores['taxa_cancelamento_melhor_fabricante'] / $informacoes['taxa_cancelamento']) * 100
            );
        }
        if (!empty($informacoes['valor_vendido'])) {
            $progressos['valor_vendas'] = min(
                100,
                ($informacoes['valor_vendido'] / $fatores['valor_vendido_melhor_fabricante']) * 100
            );
        }
        $informacoes['objetivos'] = [
            'dias_despacho_concluido' => $progressos['despacho'] === 100,
            'taxa_cancelamento_concluido' => $progressos['cancelamento'] === 100,
            'valor_vendido_concluido' => $progressos['valor_vendas'] === 100,
        ];
        $informacoes['porcentagem_barra'] = round(array_sum($progressos) / count($progressos));

        return $informacoes;
    }
    public static function sqlCriterioAfetarReputacao(): string
    {
        $negociacaoRecusada = NegociacoesProdutoTempService::SITUACAO_RECUSADA;

        return "(
            logistica_item_data_alteracao.id_usuario = 2
         OR transacao_financeiras_produtos_itens.id_responsavel_estoque = usuarios.id_colaborador
         OR EXISTS(
            SELECT 1
            FROM negociacoes_produto_log
            WHERE negociacoes_produto_log.uuid_produto = logistica_item_data_alteracao.uuid_produto
              AND negociacoes_produto_log.situacao = '$negociacaoRecusada'
         )
        )";
    }
}

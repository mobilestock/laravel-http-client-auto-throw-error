<?php

namespace MobileStock\service\Ranking;

use Exception;
use MobileStock\model\LogisticaItem;
use MobileStock\service\ConfiguracaoService;
use PDO;

class RankingService
{
    // public static function buscaRankingPremiosPorChave(PDO $conexao, String $chave, int $quantidade = -1)
    // {
    //     $limite = $quantidade > 0 ? "LIMIT $quantidade" : '';
    //     $premios = $conexao->query(
    //         "SELECT
    //             ranking_premios.id,
    //             ranking_premios.posicao,
    //             ranking_premios.porcentagem,
    //             ranking.recontar_premios,
    //             ranking.nome
    //         FROM ranking_premios
    //         INNER JOIN ranking ON ranking.id = ranking_premios.id_ranking
    //         WHERE
    //             ranking.chave = '$chave' AND
    //             ranking_premios.ativo = 1
    //         $limite"
    //     )->fetchAll(PDO::FETCH_ASSOC);

    //     if (empty($premios)) return [];

    //     $premios = array_map(function($item) {
    //         $item['recontar_premios'] = (bool) $item['recontar_premios'];
    //         return $item;
    //     }, $premios);

    //     return $premios;
    // }

    public static function buscaRankingsAtivos(PDO $conexao)
    {
        $rankings = $conexao->query(
            "SELECT * FROM ranking WHERE ranking.ativo = 1"
        )->fetchAll(PDO::FETCH_ASSOC);

        return $rankings;
    }

    public static function buscaPremiacoesRanking(PDO $conexao, int $id_ranking)
    {
        $premiacoes = $conexao->query(
            "SELECT *
            FROM ranking_premios
            WHERE
                ranking_premios.id_ranking = $id_ranking AND
                ranking_premios.ativo = 1
            ORDER BY posicao"
        )->fetchAll(PDO::FETCH_ASSOC);

        return $premiacoes;
    }

    public static function montaFiltroPeriodo(PDO $conexao, Array $campos = [], String $periodo)
    {
        $horario = ConfiguracaoService::consultaHorarioFinalDiaRankingMeuLook($conexao);

        $queryComparacao = "";
        switch ($periodo) {
            case 'ontem':
                $queryComparacao .= "BETWEEN DATE_FORMAT('$horario' - INTERVAL 2 DAY, '%Y-%m-%d %H:%i:%s')
                    AND DATE_FORMAT('$horario' - INTERVAL 1 DAY, '%Y-%m-%d %H:%i:%s')";
                break;
            case 'hoje':
                $queryComparacao .= "BETWEEN DATE_FORMAT('$horario' - INTERVAL 1 DAY, '%Y-%m-%d %H:%i:%s')
                    AND DATE_FORMAT('$horario', '%Y-%m-%d %H:%i:%s')";
                break;
            case 'mes-atual':
                $queryComparacao .= "BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
                    AND DATE_FORMAT(LAST_DAY(NOW()), '%Y-%m-%d 23:59:59')";
                break;
            case 'mes-passado':
                $queryComparacao .= "BETWEEN DATE_FORMAT(DATE_FORMAT(NOW(), '%Y-%m-01') - INTERVAL 1 SECOND, '%Y-%m-01 00:00:00')
                    AND DATE_FORMAT(NOW(), '%Y-%m-01') - INTERVAL 1 SECOND";
                break;
            case 'geral': return '';
            default:
                throw new Exception("Período inválido!");
        }

        $filtro = "";
        foreach ($campos as $campo) $filtro .= " AND ($campo $queryComparacao)";

        return $filtro;
    }

    // public static function filtroExcluiUsuariosInternos()
    // {
    //     return " AND usuarios.permissao NOT REGEXP '50|51|52|53|54|55|56|57'";
    // }

    public static function ocorreuPremiacaoRecente(PDO $conexao, $pendente = false): bool
    {
        $tabela = $pendente ? 'lancamento_financeiro_pendente' : 'lancamento_financeiro';
        $intervalo = $pendente ? '27 DAY' : '23 HOUR';

        $premiacao = $conexao->query(
            "SELECT $tabela.id
            FROM $tabela
            WHERE
                $tabela.origem = 'MR' AND
                $tabela.data_emissao > CURDATE() - INTERVAL $intervalo
            LIMIT 1"
        )->fetchAll(PDO::FETCH_ASSOC);

        return !empty($premiacao);
    }

    public static function filtroSomenteColaboradoresOficiais()
    {
        return " AND usuarios.permissao REGEXP '12'";
    }

    public static function buscaRankingEmApuracao(PDO $conexao, $ranking)
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $stmt = $conexao->prepare(
            "SELECT
                DATE(_rankings.data) data,
                _rankings.nome,
                _rankings.origem,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'usuario_meulook', _rankings.usuario_meulook,
                        'valor_total', _rankings.valor,
                        'foto', _rankings.foto_perfil,
                        'id_lancamento_pendente', _rankings.id,
                        'premio', ROUND(IF(
                            _rankings.origem = 'RK',
                            _rankings.valor * (_rankings.porcentagem / 100),
                            _rankings.porcentagem
                        ), 2),
                        'porcentagem', IF(
                            _rankings.origem = 'RK',
                            _rankings.porcentagem,
                            NULL
                        )
                    ) ORDER BY _rankings.valor DESC),
                    ']'
                ) participantes
            FROM (
                SELECT
                    lancamento_financeiro_pendente.id,
                    lancamento_financeiro_pendente.valor porcentagem,
                    ranking.nome,
                    ranking.chave,
                    DATE(lancamento_financeiro_pendente.data_emissao - INTERVAL 1 DAY) data,
                    colaboradores.id id_colaborador,
                    colaboradores.usuario_meulook,
                    IF(
                        LENGTH(colaboradores.foto_perfil) > 0,
                        colaboradores.foto_perfil,
                        '" . $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg' . "'
                    ) foto_perfil,
                    SUM(IF(
                        logistica_item.situacao <= $situacao,
                        pedido_item_meu_look.preco,
                        0
                    )) valor,
                    lancamento_financeiro_pendente.origem
                FROM ranking_vencedores_itens
                INNER JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.id = ranking_vencedores_itens.id_lancamento_pendente
                INNER JOIN ranking ON ranking.chave = lancamento_financeiro_pendente.numero_documento
                INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro_pendente.id_colaborador
                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = ranking_vencedores_itens.uuid_produto
                INNER JOIN logistica_item ON logistica_item.uuid_produto = ranking_vencedores_itens.uuid_produto
                WHERE lancamento_financeiro_pendente.numero_documento = :ranking
                GROUP BY lancamento_financeiro_pendente.id
            ) _rankings
            GROUP BY _rankings.data
            ORDER BY _rankings.data DESC"
        );
        $stmt->execute([':ranking' => $ranking]);

        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($consulta)) return [];

        $consulta = array_map(function($item) {
            $item['participantes'] = json_decode($item['participantes'], true);
            return $item;
        }, $consulta);

        return $consulta;
    }

    // public static function buscaUltimoRankingConcluido(PDO $conexao, $ranking)
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y') data,
    //             ranking.nome,
    //             CONCAT(
    //                 '[',
    //                 GROUP_CONCAT(JSON_OBJECT(
    //                     'usuario_meulook', colaboradores.usuario_meulook,
    //                     'premio', lancamento_financeiro.valor,
    //                     'foto', IF(
    //                         LENGTH(colaboradores.foto_perfil) > 0,
    //                         colaboradores.foto_perfil,
    //                         '" . $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg' . "'
    //                     ),
    //                     'posicao', lancamento_financeiro.numero_movimento
    //                 ) ORDER BY lancamento_financeiro.numero_movimento ASC),
    //                 ']'
    //             ) participantes
    //         FROM lancamento_financeiro
    //         INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
    //         INNER JOIN ranking ON ranking.chave = lancamento_financeiro.numero_documento
    //         WHERE
    //             lancamento_financeiro.origem IN ('MR', 'RK') AND
    //             lancamento_financeiro.numero_documento = :ranking
    //         GROUP BY
    //             DATE(lancamento_financeiro.data_emissao),
    //             lancamento_financeiro.numero_documento
    //         ORDER BY DATE(lancamento_financeiro.data_emissao) DESC
    //         LIMIT 1"
    //     );
    //     $stmt->execute([':ranking' => $ranking]);

    //     $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     if (empty($consulta)) return [];

    //     $consulta = array_map(function($item) {
    //         $item['participantes'] = json_decode($item['participantes'], true);
    //         return $item;
    //     }, $consulta);

    //     return $consulta;
    // }

    // public static function vendasRanking(PDO $conexao, $idColaborador, $periodo, $situacao = null)
    // {
    //     $filtroPeriodo = self::montaFiltroPeriodo($conexao, ['logistica_item.data_criacao'], $periodo);
    //     $status = self::statusItem();

    //     $sql = "SELECT
    //         produtos.id id_produto,
    //         LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome_produto,
    //         pedido_item_meu_look.nome_tamanho tamanho,
    //         colaboradores.usuario_meulook usuario_meulook_cliente,
    //         ponto.usuario_meulook usuario_meulook_ponto,
    //         IF(
    //             LENGTH(colaboradores.foto_perfil) > 0,
    //             colaboradores.foto_perfil,
    //             '" . $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg' . "'
    //         ) foto_perfil_cliente,
    //         IF(
    //             LENGTH(ponto.foto_perfil) > 0,
    //             ponto.foto_perfil,
    //             '" . $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg' . "'
    //         ) foto_perfil_ponto,
    //         DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y %H:%i:%s') data_compra,
    //         pedido_item_meu_look.preco valor,
    //         (
    //             SELECT produtos_foto.caminho
    //             FROM produtos_foto
    //             WHERE produtos_foto.id = produtos.id
    //             ORDER BY produtos_foto.tipo_foto = 'MD' DESC
    //             LIMIT 1
    //         ) foto_produto
    //         $status
    //     FROM pedido_item_meu_look
    //     INNER JOIN colaboradores ponto ON ponto.id = pedido_item_meu_look.id_ponto
    //     INNER JOIN logistica_item ON logistica_item.uuid_produto = pedido_item_meu_look.uuid 
    //     INNER JOIN colaboradores ON colaboradores.id = pedido_item_meu_look.id_cliente
    //     INNER JOIN produtos ON produtos.id = pedido_item_meu_look.id_produto
    //     LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = pedido_item_meu_look.uuid
    //     WHERE
    //         pedido_item_meu_look.id_colaborador_compartilhador_link = :idColaborador
    //         $filtroPeriodo
    //     ORDER BY logistica_item.data_criacao DESC";

    //     $stmt = $conexao->prepare($sql);
    //     $stmt->execute([':idColaborador' => $idColaborador]);

    //     $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     $resposta = [
    //         'produtos' => [],
    //         'valor_total' => 0,
    //         'quantidade_total' => 0,
    //         'valor_devolvido' => 0,
    //         'quantidade_devolvida' => 0
    //     ];

    //     $arrayStatusFinalizado = ['Devolvido', 'Finalizado'];
    //     foreach ($consulta as $item) {
    //         if ($situacao !== 'pendente' && $situacao !== 'finalizado') {
    //             array_push($resposta['produtos'], $item);
    //             $resposta['valor_total'] += $item['valor'];
    //             $resposta['quantidade_total'] += 1;
    //         } else if ($situacao === 'pendente' && !in_array($item['status'], $arrayStatusFinalizado)) {
    //             array_push($resposta['produtos'], $item);
    //             $resposta['valor_total'] += $item['valor'];
    //             $resposta['quantidade_total'] += 1;
    //         } else if ($situacao === 'finalizado' && in_array($item['status'], $arrayStatusFinalizado)) {
    //             array_push($resposta['produtos'], $item);
    //             if ($item['status'] === 'Devolvido') {
    //                 $resposta['quantidade_devolvida'] += 1;
    //                 $resposta['valor_devolvido'] += $item['valor'];
    //             } else {
    //                 $resposta['quantidade_total'] += 1;
    //                 $resposta['valor_total'] += $item['valor'];
    //             }
    //         }
    //     }

    //     return $resposta;
    // }

    public static function buscaQuantidadesApuracao(PDO $conexao, string $ranking, int $mes): array
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $stmt = $conexao->prepare(
            "SELECT
                lancamentos.data,
                lancamentos.numero_documento ranking,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'id_lancamento_pendente', id_lancamento_pendente,
                        'usuario_meulook', lancamentos.usuario_meulook,
                        'foto', lancamentos.foto_perfil,
                        'qtd_em_transito', lancamentos.qtd_em_transito,
                        'qtd_ponto_entrega', lancamentos.qtd_ponto_entrega,
                        'qtd_esperando_dias', lancamentos.qtd_esperando_dias
                    ) ORDER BY lancamentos.valor DESC),
                    ']'
                ) participantes
            FROM (
                SELECT
                    lancamento_financeiro_pendente.id id_lancamento_pendente,
                    lancamento_financeiro_pendente.numero_documento,
                    DATE(lancamento_financeiro_pendente.data_emissao) data,
                    colaboradores.usuario_meulook,
                    COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg') foto_perfil,
                    SUM(IF(logistica_item.situacao <= $situacao,
                        pedido_item_meu_look.preco,
                        0
                    )) valor,
                    SUM(COALESCE(logistica_item.id_entrega IS NOT NULL
                                     AND EXISTS(SELECT 1
                                                FROM entregas
                                                WHERE entregas.id = logistica_item.id_entrega
                                                  AND entregas.situacao = 'EX'), 0)) qtd_em_transito,
                    SUM(COALESCE(entregas_faturamento_item.situacao = 'AR', 0)) qtd_ponto_entrega,
                    SUM(COALESCE(logistica_item.situacao = 'CO' AND entregas_faturamento_item.situacao = 'EN', 0)) qtd_esperando_dias
                FROM ranking_vencedores_itens
                INNER JOIN lancamento_financeiro_pendente ON
                    lancamento_financeiro_pendente.id = ranking_vencedores_itens.id_lancamento_pendente AND
                    lancamento_financeiro_pendente.numero_documento = :ranking
                INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro_pendente.id_colaborador
                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = ranking_vencedores_itens.uuid_produto
                INNER JOIN logistica_item ON logistica_item.uuid_produto = ranking_vencedores_itens.uuid_produto
                LEFT JOIN entregas_faturamento_item ON
                    entregas_faturamento_item.uuid_produto = ranking_vencedores_itens.uuid_produto AND
                    NOT (
                        entregas_faturamento_item.situacao = 'EN' AND
                        DATE(entregas_faturamento_item.data_entrega) + INTERVAL
                        (SELECT qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1) DAY <= CURDATE()
                    )
                GROUP BY lancamento_financeiro_pendente.id
            ) lancamentos
            WHERE MONTH(lancamentos.data - INTERVAL 1 SECOND) = :mes
            GROUP BY
                lancamentos.numero_documento,
                lancamentos.data
            ORDER BY lancamentos.data DESC
            LIMIT 1"
        );
        $stmt->execute([
            ':ranking' => $ranking,
            ':mes' => $mes
        ]);
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($consulta)) return [];
        $participantes = json_decode($consulta['participantes'], true);
        foreach($participantes as $key => $value) $participantes[$key]['posicao'] = $key + 1;
        $consulta['participantes'] = $participantes;
        return $consulta;
    }

    /**
     * Para utilizar essa função a query precisa ter:
     * * LEFT JOIN faturamento_item ON faturamento_item.uuid = pedido_item_meu_look.uuid
     * * INNER JOIN colaboradores ponto ON ponto.id = pedido_item_meu_look.id_ponto
     * * LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = pedido_item_meu_look.uuid
     */
    public static function statusItem(): string
    {
        $situacaoFinalProcesso = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;

        return ", CASE
            WHEN (logistica_item.situacao > $situacaoFinalProcesso) THEN 'Devolvido'
            WHEN (entregas_faturamento_item.situacao = 'EN') THEN 'Finalizado'
            WHEN (entregas_faturamento_item.situacao IN ('AR', 'PB')) THEN 'Ponto de Entrega'
            WHEN (logistica_item.situacao = 'CO') THEN 'Aguardando Envio'
            WHEN (logistica_item.situacao = 'SE') THEN 'Conferência'
            WHEN (logistica_item.situacao = 'PE') THEN 'Separação'
            WHEN (
                entregas_faturamento_item.situacao = 'EN' AND
                (
                    DATE(entregas_faturamento_item.data_entrega) + INTERVAL
                    (SELECT configuracoes.qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1)
                    DAY > CURDATE()
                )
            ) THEN 'Aguardando 8° Dia'
            WHEN (
                logistica_item.situacao = 'CO' AND
                logistica_item.id_entrega IS NOT NULL AND
                EXISTS(SELECT 1
                       FROM entregas
                       WHERE entregas.id = logistica_item.id_entrega
                         AND entregas.situacao = 'EX')
            ) THEN 'Em Trânsito'
            ELSE 'Pago'
        END status";
    }

    // public static function rankingInfluencersOficiais(PDO $conexao, $periodo = 'mes-atual', $quantidade = -1, $retornarListaItens = false)
    // {
    //     $filtroPeriodo = RankingService::montaFiltroPeriodo($conexao, ['logistica_item.data_criacao'], $periodo);

    //     $filtroColaboradoresOficiais = RankingService::filtroExcluiUsuariosInternos();
    //     $filtroColaboradoresOficiais .= RankingService::filtroSomenteColaboradoresOficiais();

    //     $limite = $quantidade <= 0 ? '' : "LIMIT $quantidade";

    //     $subQueryItens = $retornarListaItens
    //         ? ", GROUP_CONCAT(pedido_item_meu_look.uuid) lista_itens"
    //         : '';

    //     $situacaoFinalProcesso = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
    //     $query = "SELECT
    //         colaboradores.id,
    //         usuarios.id id_usuario,
    //         colaboradores.usuario_meulook,
    //         COALESCE(colaboradores.foto_perfil, '" . $_ENV['URL_MOBILE'] . "images/avatar-padrao-mobile.jpg') foto,
    //         SUM(logistica_item.situacao <= $situacaoFinalProcesso) vendas,
    //         SUM(
    //             (logistica_item.situacao <= $situacaoFinalProcesso) AND
    //             DATE(logistica_item.data_criacao) = DATE(NOW())
    //         ) vendas_dia,
    //         SUM(IF(
    //             logistica_item.situacao <= $situacaoFinalProcesso,
    //             pedido_item_meu_look.preco,
    //             0
    //         )) valor_total,
    //         SUM(IF(
    //             (logistica_item.situacao <= $situacaoFinalProcesso) AND DATE(logistica_item.data_criacao) = DATE(NOW()),
    //             pedido_item_meu_look.preco,
    //             0
    //         )) valor_total_dia
    //         $subQueryItens
    //     FROM pedido_item_meu_look
    //     INNER JOIN colaboradores ON colaboradores.id = pedido_item_meu_look.id_colaborador_compartilhador_link
    //     INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
    //     INNER JOIN logistica_item ON logistica_item.uuid_produto = pedido_item_meu_look.uuid
    //     WHERE
    //         pedido_item_meu_look.situacao = 'PA' AND
    //         colaboradores.usuario_meulook IS NOT NULL
    //         $filtroColaboradoresOficiais
    //         $filtroPeriodo
    //     GROUP BY colaboradores.id
    //     ORDER BY
    //         valor_total DESC,
    //         vendas
    //     $limite";

    //     $influencers = $conexao->query($query)->fetchAll(PDO::FETCH_ASSOC);

    //     if (empty($influencers)) return [];

    //     return array_map(function($influencer) use ($retornarListaItens) {
    //         $influencer['id'] = (int) $influencer['id'];
    //         $influencer['id_usuario'] = (int) $influencer['id_usuario'];
    //         $influencer['vendas'] = (int) $influencer['vendas'];
    //         $influencer['vendas_dia'] = (int) $influencer['vendas_dia'];
    //         $influencer['valor_total'] = (float) $influencer['valor_total'];
    //         $influencer['valor_total_dia'] = (float) $influencer['valor_total_dia'];
    //         if ($retornarListaItens) $influencer['lista_itens'] = explode(',', $influencer['lista_itens']);
    //         return $influencer;
    //     }, $influencers);
    // }

    // public static function buscaColaboradoresCompartilhadoresLinkMeuLook(PDO $conexao)
    // {
    //     $filtroExcluiUsuariosInternos = RankingService::filtroExcluiUsuariosInternos();

    //     $colaboradores = $conexao->query(
    //         "SELECT
    //             colaboradores.id,
    //             CONCAT(
    //                 colaboradores.razao_social,
    //                 ' (',
    //                 COALESCE(
    //                     colaboradores.usuario_meulook,
    //                     usuarios.nome,
    //                     ' - '
    //                 ),
    //                 ')'
    //             ) nome,
    //             colaboradores.telefone,
    //             usuarios.id id_usuario,
    //             CASE
    //                 WHEN usuarios.permissao REGEXP '12' THEN 'ATIVO'
    //                 WHEN influencers_oficiais_links.situacao = 'RE' THEN 'DESATIVADO'
    //                 WHEN influencers_oficiais_links.situacao = 'CR' THEN 'LINK_ENVIADO'
    //                 ELSE 'ENVIAR_LINK'
    //             END 'situacao',
    //             SUM(IF(
    //                 (
    //                     transacao_financeiras.status = 'PA' AND
    //                     transacao_financeiras.data_atualizacao > NOW() - INTERVAL 30 DAY
    //                 ),
    //                 pedido_item_meu_look.preco,
    //                 0
    //             )) valor_ultimo_mes,
    //             SUM(IF(transacao_financeiras.status = 'PA', pedido_item_meu_look.preco, 0)) valor
    //         FROM colaboradores
    //         INNER JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
    //         LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.id_colaborador_compartilhador_link = colaboradores.id
    //         LEFT JOIN transacao_financeiras ON transacao_financeiras.id = pedido_item_meu_look.id_transacao
    //         LEFT JOIN influencers_oficiais_links ON influencers_oficiais_links.id_usuario = usuarios.id
    //         WHERE 1=1 $filtroExcluiUsuariosInternos
    //         GROUP BY colaboradores.id
    //         ORDER BY
    //             valor_ultimo_mes DESC,
    //             valor DESC"
    //     )->fetchAll(PDO::FETCH_ASSOC);

    //     if (empty($colaboradores)) return [];

    //     $colaboradores = array_map(function($item) {
    //         $item['telefone'] = ConversorStrings::formataTelefone($item['telefone']);
    //         return $item;
    //     }, $colaboradores);

    //     return $colaboradores;
    // }

    // public static function alterarSituacaoInfluencerOficial(PDO $conexao, int $idUsuario)
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             usuarios.id,
    //             COALESCE(colaboradores.telefone, usuarios.telefone) telefone,
    //             CASE
    //                 WHEN (usuarios.permissao REGEXP '12') THEN 'DESATIVADO'
    //                 WHEN (
    //                     influencers_oficiais_links.id IS NULL OR influencers_oficiais_links.situacao = 'CR'
    //                 ) THEN 'LINK_ENVIADO'
    //                 WHEN influencers_oficiais_links.situacao = 'RE' THEN 'ATIVO'
    //                 ELSE 'SITUACAO_INVALIDA'
    //             END proxima_situacao
    //         FROM usuarios
    //         INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
    //         LEFT JOIN influencers_oficiais_links ON influencers_oficiais_links.id_usuario = usuarios.id
    //         WHERE usuarios.id = :idUsuario"
    //     );
    //     $stmt->execute([':idUsuario' => $idUsuario]);
    //     $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (empty($colaborador)) throw new Exception('Usuário inválido');

    //     switch ($colaborador['proxima_situacao']) {
    //         case 'DESATIVADO':
    //             InfluencersOficiaisLinksService::toggleSituacaoUsuario($conexao, $idUsuario);
    //             ColaboradoresRepository::removePermissaoUsuario($conexao, $idUsuario, [12]);
    //             RegrasAutenticacao::armazenaTokenUsuario($idUsuario, null, $conexao);
    //             break;

    //         case 'LINK_ENVIADO':
    //             $randomHash = bin2hex('influencer_' . $colaborador['id'] . '_oficial');
    //             $model = new InfluencersOficiaisLinks();
    //             $geradorSQL = new GeradorSql($model->hidratar(['id_usuario' => $idUsuario, 'hash' => $randomHash]));
    //             $conexao->prepare($geradorSQL->insert())->execute($geradorSQL->bind);
    //             $mensagem = "Você foi selecionado para se tornar um dos Influencers Oficiais do MeuLook!\n" .
    //                 "Para prosseguir acesse o link: " . $_ENV['URL_MEULOOK'] . "completar-dados/{$randomHash}";
    //             $messageService = new MessageService();
    //             $messageService->sendMessageWhatsApp($colaborador['telefone'], $mensagem);
    //             break;

    //         case 'ATIVO':
    //             InfluencersOficiaisLinksService::toggleSituacaoUsuario($conexao, $idUsuario);
    //             ColaboradoresRepository::adicionaPermissaoUsuario($conexao, $idUsuario, [12]);
    //             RegrasAutenticacao::armazenaTokenUsuario($idUsuario, null, $conexao);
    //             break;

    //         case 'SITUACAO_INVALIDA':
    //             throw new Exception('Situação não prevista na alteração');

    //         default:
    //             break;
    //     }

    //     return $colaborador['proxima_situacao'];
    // }

}

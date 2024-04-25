<?php

namespace MobileStock\service\Ranking;

use Error;
use Exception;
use MobileStock\model\Ranking\RankingVencedoresItens;
use PDO;

class RankingVencedoresItensService extends RankingVencedoresItens
{
    public function adiciona(PDO $conexao)
    {
        $sql = '';
        $camposTabela = [];
        $dadosTabela = [];
        $dados = [];
        foreach ($this as $key => $value) {
            if (!$value || in_array($key,['id'])) continue;
            array_push($camposTabela,$key);
            array_push($dadosTabela ,":{$key}");
            $dados[$key] = $value;
        }
        $sql = "INSERT INTO ranking_vencedores_itens (".implode(',',$camposTabela).") VALUES (".implode(',',$dadosTabela).")";
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);
        $this->id = $conexao->lastInsertId();
        return $this;
    }

    public function atualiza(PDO $conexao)
    {
        $dados = [];
        $sql = "UPDATE ranking_vencedores_itens SET ";

        foreach ($this as $key => $valor) {
            if ((!$valor && !is_null($valor)) || in_array($key,['id', 'data_criacao'])) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            if(is_null($valor)){
                $valor = "NULL";
            }
            array_push($dados, $key . " = " . $valor);
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser atualizada');
        }

        $sql .= " " . implode(',', $dados) . " WHERE ranking_vencedores_itens.id = '" . $this->id. "'";

        return $conexao->exec($sql);
    }

    public function busca(PDO $conexao)
    {
        if(!$this->id) throw new Exception("Erro ao buscar comentário", 1);

        $sql = "SELECT *
                FROM ranking_vencedores_itens
                WHERE id = {$this->id}";

        $dados = $conexao->query($sql)->fetch(PDO::FETCH_ASSOC);
        if($dados && count($dados) !== 0):
            $this->id = $dados['id'];
            $this->uuid_produto = $dados['uuid_produto'];
            $this->id_lancamento_pendente = $dados['id_lancamento_pendente'];
            $this->data_criacao = $dados['data_criacao'];
        endif;
    }

    public function remove(PDO $conexao)
    {
        return $conexao->exec("DELETE FROM ranking_vencedores_itens WHERE id = {$this->id}");
    }

//    public static function buscaLancamentosPendentes(PDO $conexao)
//    {
//        $consulta = $conexao->query(
//            "SELECT
//                _rankings.data,
//                _rankings.chave_ranking,
//                (
//                    SUM(_rankings.valor_total) -
//                    SUM(_rankings.valor_entregue) -
//                    SUM(_rankings.valor_trocado) -
//                    SUM(_rankings.valor_corrigido)
//                ) = 0 pagar,
//                CONCAT(
//                    '[',
//                    GROUP_CONCAT(JSON_OBJECT(
//                        'id_lancamento_pendente', _rankings.id_lancamento_pendente,
//                        'id_colaborador', _rankings.id_colaborador,
//                        'id_usuario', _rankings.id_usuario,
//                        'data_emissao', _rankings.data,
//                        'valor', (_rankings.valor_total - _rankings.valor_trocado),
//                        'posicao', _rankings.posicao,
//                        'premio', (_rankings.premio * (1 - (_rankings.valor_trocado / (_rankings.valor_total - _rankings.valor_corrigido))))
//                    ) ORDER BY (_rankings.valor_total - _rankings.valor_trocado) DESC),
//                    ']'
//                ) lancamentos
//            FROM (
//                SELECT
//                    lancamento_financeiro_pendente.id id_lancamento_pendente,
//                    lancamento_financeiro_pendente.id_colaborador,
//                    lancamento_financeiro_pendente.id_usuario,
//                    lancamento_financeiro_pendente.numero_documento chave_ranking,
//                    lancamento_financeiro_pendente.numero_movimento posicao,
//                    lancamento_financeiro_pendente.valor premio,
//                    DATE(lancamento_financeiro_pendente.data_emissao - INTERVAL 1 DAY) data,
//                    SUM(pedido_item_meu_look.preco) valor_total,
//                    SUM(IF(faturamento_item.situacao = 19, pedido_item_meu_look.preco, 0)) valor_corrigido,
//                    SUM(IF(
//                        (
//                            faturamento_item.situacao = 6 AND
//                            entregas_faturamento_item.situacao = 'EN' AND
//                            DATE(entregas_faturamento_item.data_atualizacao) + INTERVAL (
//                                SELECT configuracoes.qtd_dias_disponiveis_troca_normal
//                                FROM configuracoes
//                                LIMIT 1
//                            ) DAY <= CURDATE()
//                        ),
//                        pedido_item_meu_look.preco,
//                        0
//                    )) valor_entregue,
//                    SUM(IF(
//                        faturamento_item.situacao = 12,
//                        pedido_item_meu_look.preco,
//                        0
//                    )) valor_trocado
//                FROM ranking_vencedores_itens
//                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = ranking_vencedores_itens.uuid_produto
//                INNER JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.id = ranking_vencedores_itens.id_lancamento_pendente
//                LEFT JOIN faturamento_item ON faturamento_item.uuid = ranking_vencedores_itens.uuid_produto
//                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = ranking_vencedores_itens.uuid
//                GROUP BY lancamento_financeiro_pendente.id
//            ) _rankings
//            GROUP BY
//                _rankings.data,
//                _rankings.chave_ranking"
//        )->fetchAll(PDO::FETCH_ASSOC);
//
//        if (sizeof($consulta) === 0) return [];
//
//        return array_map(function($item) {
//            $item['pagar'] = (bool) $item['pagar'];
//            $item['lancamentos'] = json_decode($item['lancamentos'], true);
//            return $item;
//        }, $consulta);
//    }

    public static function removeItensLancamentoPendente(PDO $conexao, $idLancamentoPendente)
    {
        return $conexao->exec(
            "DELETE FROM ranking_vencedores_itens
            WHERE ranking_vencedores_itens.id_lancamento_pendente = $idLancamentoPendente
        ");
    }

    public static function atualizaItensLancamentoPendente(PDO $conexao, $idLancamentoPendente, $idLancamentoReal)
    {
        return $conexao->exec(
            "UPDATE ranking_vencedores_itens
            SET ranking_vencedores_itens.id_lancamento_pendente = NULL,
                ranking_vencedores_itens.id_lancamento = $idLancamentoReal
            WHERE ranking_vencedores_itens.id_lancamento_pendente = $idLancamentoPendente"
        );
    }

//    public static function buscaListaPremiacoes(PDO $conexao)
//    {
//        // A data foi subtraída um dia porque o processo de premiação roda Meia Noite, e consequentemente salva
//        // as vendas como se fossem do próximo dia deixando confundível olhar as datas.
//        $consulta = $conexao->query(
//            "SELECT
//                DATE_FORMAT(ranking_vencedores_itens.data_criacao - INTERVAL 1 DAY, '%d-%m-%Y') data_emissao,
//                CONCAT(
//                    COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-influencers-oficiais', '0'), lancamento_financeiro.id, NULL)),
//                    '/',
//                    (
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro_pendente.numero_documento = 'top-influencers-oficiais', '0'), lancamento_financeiro_pendente.id, NULL)) +
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-influencers-oficiais', '0'), lancamento_financeiro.id, NULL))
//                    )
//                ) 'top_influencers_oficiais',
//                CONCAT(
//                    COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-influencers', '0'), lancamento_financeiro.id, NULL)),
//                    '/',
//                    (
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro_pendente.numero_documento = 'top-influencers', '0'), lancamento_financeiro_pendente.id, NULL)) +
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-influencers', '0'), lancamento_financeiro.id, NULL))
//                    )
//                ) 'top_influencers',
//                CONCAT(
//                    COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-influencers-link', '0'), lancamento_financeiro.id, NULL)),
//                    '/',
//                    (
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro_pendente.numero_documento = 'top-influencers-link', '0'), lancamento_financeiro_pendente.id, NULL)) +
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-influencers-link', '0'), lancamento_financeiro.id, NULL))
//                    )
//                ) 'top_influencers_link',
//                CONCAT(
//                    COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-publicacoes', '0'), lancamento_financeiro.id, NULL)),
//                    '/',
//                    (
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro_pendente.numero_documento = 'top-publicacoes', '0'), lancamento_financeiro_pendente.id, NULL)) +
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-publicacoes', '0'), lancamento_financeiro.id, NULL))
//                    )
//                ) 'top_publicacoes',
//                CONCAT(
//                    COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-pontos-entrega', '0'), lancamento_financeiro.id, NULL)),
//                    '/',
//                    (
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro_pendente.numero_documento = 'top-pontos-entrega', '0'), lancamento_financeiro_pendente.id, NULL)) +
//                        COUNT(DISTINCT IF(COALESCE(lancamento_financeiro.numero_documento = 'top-pontos-entrega', '0'), lancamento_financeiro.id, NULL))
//                    )
//                ) 'top_pontos_entrega'
//            FROM ranking_vencedores_itens
//            LEFT JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.id = ranking_vencedores_itens.id_lancamento_pendente
//            LEFT JOIN lancamento_financeiro ON lancamento_financeiro.id = ranking_vencedores_itens.id_lancamento
//            GROUP BY DATE(ranking_vencedores_itens.data_criacao)"
//        )->fetchAll(PDO::FETCH_ASSOC);
//
//        if (empty($consulta)) return [];
//
//        $itensIncompletos = [];
//        foreach($consulta as $item) {
//            $topInfluencers = explode('/', $item['top_influencers']);
//            $topInfluencersLink = explode('/', $item['top_influencers_link']);
//            $topPublicacoes = explode('/', $item['top_publicacoes']);
//            $topPontosEntrega = explode('/', $item['top_pontos_entrega']);
//            $topInfluencersOficiais = explode('/', $item['top_influencers_oficiais']);
//
//            if (
//                $topInfluencers[0] != $topInfluencers[1] ||
//                $topInfluencersLink[0] != $topInfluencersLink[1] ||
//                $topPublicacoes[0] != $topPublicacoes[1] ||
//                $topPontosEntrega[0] != $topPontosEntrega[1] ||
//                $topInfluencersOficiais[0] != $topInfluencersOficiais[1]
//            ) array_push($itensIncompletos, $item);
//        }
//
//        return $itensIncompletos;
//    }

//    public static function buscarListaPremiacoesFiltradas(PDO $conexao, $data, $ranking)
//    {
//        // A data foi subtraída um dia no where porque o processo de premiação roda Meia Noite,
//        // e consequentemente salva as vendas como se fossem do próximo dia deixando confundível
//        // olhar as datas.
//        $consulta = $conexao->prepare(
//            "SELECT
//                lancamento_financeiro_pendente.id,
//                lancamento_financeiro_pendente.numero_movimento posicao,
//                CONCAT(colaboradores.razao_social, ' (', colaboradores.usuario_meulook, ')') razao_social,
//                COUNT(ranking_vencedores_itens.id) qtd_total,
//                SUM(COALESCE(faturamento_item.situacao = 19, 0)) qtd_corrigida,
//                SUM(COALESCE(faturamento_item.situacao = 12, 0)) qtd_devolvida,
//                SUM(COALESCE(entregas_faturamento_item.situacao IN ('AR', 'PB'), 0)) qtd_aguardando_retirada,
//                SUM(COALESCE(entregas_faturamento_item.situacao = 'EN', 0)) qtd_entregue,
//                SUM(IF(
//                    faturamento_item.situacao IS NULL OR faturamento_item.situacao = 6,
//                    piml_total.preco,
//                    0
//                )) valor_final,
//                SUM(piml_total.preco) valor_vendido,
//                SUM(IF(faturamento_item.situacao = 19, piml_total.preco, 0)) valor_corrigido,
//                SUM(IF(faturamento_item.situacao = 12, piml_total.preco, 0)) valor_devolvido,
//                lancamento_financeiro_pendente.valor premio_total,
//                GROUP_CONCAT(IF(
//                    entregas_faturamento_item.situacao = 'EN',
//                    entregas_faturamento_item.data_atualizacao,
//                    NULL
//                ) ORDER BY entregas_faturamento_item.data_atualizacao LIMIT 1) data_ultima_entrega,
//                IF(
//                    COUNT(entregas_faturamento_item.situacao) > 0,
//                    SUM(
//                        COALESCE(
//                            entregas_faturamento_item.situacao = 'EN' AND
//                            (
//                                DATE(entregas_faturamento_item.data_atualizacao) +
//                                INTERVAL (SELECT qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1) DAY >
//                                CURDATE()
//                            ),
//                            0
//                        )
//                    ),
//                0
//            ) esperando_dias_troca
//            FROM ranking_vencedores_itens
//            # Obrigatório
//            INNER JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.id = ranking_vencedores_itens.id_lancamento_pendente
//            # Nome do vencedor do ranking
//            INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro_pendente.id_colaborador
//            # Preço pago nos produtos
//            INNER JOIN pedido_item_meu_look piml_total ON piml_total.uuid = ranking_vencedores_itens.uuid_produto
//            # Produtos corrigidos, devolvidos
//            LEFT JOIN faturamento_item ON faturamento_item.uuid = piml_total.uuid
//            # Produtos entregues ao consumidor final
//            LEFT JOIN entregas_faturamento_item ON
//            entregas_faturamento_item.uuid_produto = ranking_vencedores_itens.uuid_produto AND
//            faturamento_item.situacao = 6
//            WHERE DATE(lancamento_financeiro_pendente.data_emissao - INTERVAL 1 DAY) = :data_emissao AND
//            lancamento_financeiro_pendente.numero_documento = :ranking
//            GROUP BY ranking_vencedores_itens.id_lancamento_pendente
//            ORDER BY valor_final DESC"
//        );
//        $consulta->execute([
//            ':data_emissao' => $data,
//            ':ranking' => $ranking
//        ]);
//
//        return $consulta->fetchAll(PDO::FETCH_ASSOC);
//    }

    public static function buscarVendasLancamentoPendente(PDO $conexao, $idLancamentoPendente, $situacao = null)
    {
        $status = RankingService::statusItem();
        $stmt = $conexao->prepare(
            "SELECT
                colaboradores.usuario_meulook usuario_meulook_cliente,
                ponto.usuario_meulook usuario_meulook_ponto,
                colaboradores.razao_social,
                IF(
                    LENGTH(colaboradores.foto_perfil) > 0,
                    colaboradores.foto_perfil,
                    '" . $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg' . "'
                ) foto_perfil_cliente,
                IF(
                    LENGTH(ponto.foto_perfil) > 0,
                    ponto.foto_perfil,
                    '" . $_ENV['URL_MOBILE'] . 'images/avatar-padrao-mobile.jpg' . "'
                ) foto_perfil_ponto,
                produtos.id id_produto,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome_produto,
                pedido_item_meu_look.nome_tamanho tamanho,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto_produto,
                COALESCE(colaboradores.telefone, colaboradores.telefone2) telefone,
                SUM(pedido_item_meu_look.preco) valor,
                COALESCE(
                    (
                        SELECT colaboradores.razao_social
                        FROM pedido_item_meu_look
                        INNER JOIN colaboradores ON colaboradores.id = pedido_item_meu_look.id_ponto
                        WHERE pedido_item_meu_look.uuid = ranking_vencedores_itens.uuid_produto
                    ),
                    'Não foi definido'
                ) ponto,
                IF(
                    entregas_faturamento_item.situacao = 'EN',
                    entregas_faturamento_item.data_entrega,
                    ' - '
                ) data_entrega,
                logistica_item.data_criacao data_compra
                $status
            FROM ranking_vencedores_itens
            INNER JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.id = ranking_vencedores_itens.id_lancamento_pendente
            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = ranking_vencedores_itens.uuid_produto
            INNER JOIN logistica_item ON logistica_item.uuid_produto = ranking_vencedores_itens.uuid_produto
            LEFT JOIN colaboradores ponto ON ponto.id = pedido_item_meu_look.id_ponto
            INNER JOIN colaboradores ON colaboradores.id = pedido_item_meu_look.id_cliente
            INNER JOIN produtos ON produtos.id = pedido_item_meu_look.id_produto
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = ranking_vencedores_itens.uuid_produto
            WHERE lancamento_financeiro_pendente.id = :lancamentoId
              AND pedido_item_meu_look.situacao = 'PA'
            GROUP BY
                pedido_item_meu_look.id_cliente,
                pedido_item_meu_look.uuid
            ORDER BY logistica_item.data_criacao DESC"
        );
        $stmt->bindValue(':lancamentoId', $idLancamentoPendente, PDO::PARAM_INT);
        $stmt->execute();

        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resposta = [
            'produtos' => [],
            'valor_total' => 0,
            'quantidade_total' => 0,
            'valor_devolvido' => 0,
            'quantidade_devolvida' => 0
        ];

        $arrayStatusFinalizado = ['Devolvido', 'Finalizado'];
        foreach ($consulta as $item) {
            if ($situacao !== 'pendente' && $situacao !== 'finalizado') {
                array_push($resposta['produtos'], $item);
                $resposta['valor_total'] += $item['valor'];
                $resposta['quantidade_total'] += 1;
            } else if ($situacao === 'pendente' && !in_array($item['status'], $arrayStatusFinalizado)) {
                array_push($resposta['produtos'], $item);
                $resposta['valor_total'] += $item['valor'];
                $resposta['quantidade_total'] += 1;
            } else if ($situacao === 'finalizado' && in_array($item['status'], $arrayStatusFinalizado)) {
                array_push($resposta['produtos'], $item);
                if ($item['status'] === 'Devolvido') {
                    $resposta['quantidade_devolvida'] += 1;
                    $resposta['valor_devolvido'] += $item['valor'];
                } else {
                    $resposta['quantidade_total'] += 1;
                    $resposta['valor_total'] += $item['valor'];
                }
            }
        }

        return $resposta;
    }

//    public static function listarPremiosAplicados(PDO $conexao)
//    {
//        $stmt = $conexao->prepare(
//            "SELECT
//                lancamento_financeiro.id,
//                colaboradores.id id_colaborador,
//                CONCAT(colaboradores.razao_social, ' (', colaboradores.usuario_meulook, ')') nome,
//                colaboradores.telefone,
//                lancamento_financeiro.numero_documento chave_ranking,
//                lancamento_financeiro.numero_movimento posicao,
//                DATE_FORMAT(lancamento_financeiro.data_emissao, '%d-%m-%Y %H:%i:%s') data,
//                SUM(faturamento_item.situacao = 6) qtd_itens,
//                SUM(IF(faturamento_item.situacao = 6, pedido_item_meu_look.preco, 0)) valor_vendido,
//                lancamento_financeiro.valor premio
//            FROM ranking_vencedores_itens
//            INNER JOIN lancamento_financeiro ON lancamento_financeiro.id = ranking_vencedores_itens.id_lancamento
//            INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
//            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = ranking_vencedores_itens.uuid_produto
//            INNER JOIN faturamento_item ON faturamento_item.uuid = ranking_vencedores_itens.uuid_produto
//            GROUP BY lancamento_financeiro.id
//            ORDER BY lancamento_financeiro.data_emissao DESC
//            LIMIT 300"
//        );
//        $stmt->execute();
//        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
//
//        return $consulta;
//    }
}
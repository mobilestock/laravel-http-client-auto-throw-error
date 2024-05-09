<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\model\LogisticaItemModel;
use PDO;

class CatalogoFixoService
{
    const TIPO_MODA_GERAL = 'MODA_GERAL';
    const TIPO_MODA_20 = 'MODA_20';
    const TIPO_MODA_40 = 'MODA_40';
    const TIPO_MODA_60 = 'MODA_60';
    const TIPO_MODA_80 = 'MODA_80';
    const TIPO_MODA_100 = 'MODA_100';
    const TIPO_MELHOR_FABRICANTE = 'MELHOR_FABRICANTE';
    const TIPO_PROMOCAO_TEMPORARIA = 'PROMOCAO_TEMPORARIA';
    const TIPO_VENDA_RECENTE = 'VENDA_RECENTE';
    const TIPO_MELHORES_PRODUTOS = 'MELHOR_PONTUACAO';

    public static function removeItensInvalidos(PDO $conexao): void
    {
        $stmt = $conexao->prepare(
            "SELECT GROUP_CONCAT(catalogo_fixo.id) ids
            FROM catalogo_fixo
            INNER JOIN publicacoes ON publicacoes.id = catalogo_fixo.id_publicacao
            INNER JOIN produtos ON produtos.id = catalogo_fixo.id_produto
            WHERE publicacoes.situacao = 'RE'
                OR produtos.bloqueado = 1
                OR ( # SEM ESTOQUE E FORA DE LINHA
                    SELECT SUM(estoque_grade.estoque) = 0
                    FROM estoque_grade
                    WHERE estoque_grade.id_produto = catalogo_fixo.id_produto
                        AND produtos.fora_de_linha = 1
                )
                OR NOT EXISTS ( # SEM FOTO
                    SELECT 1
                    FROM produtos_foto
                    WHERE produtos_foto.id = catalogo_fixo.id_produto
                )
                OR catalogo_fixo.tipo IN ('" .
                self::TIPO_VENDA_RECENTE .
                "', '" .
                self::TIPO_MELHORES_PRODUTOS .
                "') # VENDA RECENTE E MELHORES PRODUTOS
                OR ( # PROMOÇÃO TEMPORÁRIA EXPIRADA
                    catalogo_fixo.tipo = '" .
                self::TIPO_PROMOCAO_TEMPORARIA .
                "'
                    AND NOW() >= catalogo_fixo.expira_em + INTERVAL COALESCE(
                        (SELECT qtd_dias_repostar_promocao_temporaria FROM configuracoes LIMIT 1),
                        3
                    ) DAY
                );"
        );
        $stmt->execute();
        $ids = $stmt->fetchColumn();

        if (empty($ids)) {
            return;
        }

        [$itens, $bind] = ConversorArray::criaBindValues(explode(',', $ids));
        $stmt = $conexao->prepare("DELETE FROM catalogo_fixo WHERE catalogo_fixo.id IN ($itens)");
        $stmt->execute($bind);
    }

    public static function geraVendidosRecentemente(): void
    {
        $produtos = DB::select(
            "SELECT
                publicacoes_produtos.id_publicacao,
                '" .
                self::TIPO_VENDA_RECENTE .
                "' tipo,
                NOW() expira_em,
                publicacoes_produtos.id id_publicacao_produto,
                `mais_vendidos_logistica_item`.id_produto,
                produtos.id_fornecedor,
                LOWER(produtos.nome_comercial) nome_produto,
                produtos.valor_venda_ml,
                IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0) valor_venda_ml_historico,
                produtos.valor_venda_ms,
                IF(produtos.promocao > 0, produtos.valor_venda_ms_historico, 0) valor_venda_ms_historico,
                SUM(estoque_grade.id_responsavel = 1) > 0 possui_fulfillment,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = `mais_vendidos_logistica_item`.id_produto
                        AND NOT produtos_foto.tipo_foto = 'SM'
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`,
                produtos.quantidade_vendida,
                `mais_vendidos_logistica_item`.`vendas` vendas_recentes,
                COALESCE(produtos_pontos.total, 0) pontuacao
            FROM (
                SELECT
                    COUNT(logistica_item.id_produto) AS `vendas`,
                    logistica_item.id_produto
                FROM logistica_item
                WHERE logistica_item.data_criacao >= DATE_SUB(NOW(), INTERVAL 5 HOUR)
                GROUP BY logistica_item.id_produto
            ) AS `mais_vendidos_logistica_item`
            JOIN produtos ON produtos.id = `mais_vendidos_logistica_item`.id_produto
            JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
                AND reputacao_fornecedores.reputacao IN ('" .
                ReputacaoFornecedoresService::REPUTACAO_EXCELENTE .
                "','" .
                ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE .
                "')
            LEFT JOIN produtos_pontos ON produtos_pontos.id_produto = `mais_vendidos_logistica_item`.`id_produto`
            JOIN estoque_grade ON estoque_grade.id_produto = `mais_vendidos_logistica_item`.id_produto
                AND estoque_grade.estoque > 0
            JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = `mais_vendidos_logistica_item`.`id_produto`
                AND publicacoes_produtos.situacao = 'CR'
            GROUP BY estoque_grade.id_produto
            HAVING `foto_produto` IS NOT NULL
            ORDER BY `mais_vendidos_logistica_item`.`vendas` DESC"
        );

        DB::table('catalogo_fixo')->insert($produtos);
    }

    public static function geraMelhoresProdutos(PDO $conexao): void
    {
        $conexao->query(
            "INSERT INTO catalogo_fixo (
                catalogo_fixo.id_publicacao,
                catalogo_fixo.tipo,
                catalogo_fixo.expira_em,
                catalogo_fixo.id_publicacao_produto,
                catalogo_fixo.id_produto,
                catalogo_fixo.id_fornecedor,
                catalogo_fixo.nome_produto,
                catalogo_fixo.valor_venda_ml,
                catalogo_fixo.valor_venda_ml_historico,
                catalogo_fixo.valor_venda_ms,
                catalogo_fixo.valor_venda_ms_historico,
                catalogo_fixo.possui_fulfillment,
                catalogo_fixo.foto_produto,
                catalogo_fixo.quantidade_vendida,
                catalogo_fixo.pontuacao )
            SELECT
                publicacoes_produtos.id_publicacao,
                '" .
                self::TIPO_MELHORES_PRODUTOS .
                "',
                NOW(),
                publicacoes_produtos.id,
                `melhores_produtos_pontos`.id_produto,
                produtos.id_fornecedor,
                LOWER(produtos.nome_comercial),
                produtos.valor_venda_ml,
                IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0),
                produtos.valor_venda_ms,
                IF(produtos.promocao > 0, produtos.valor_venda_ms_historico, 0),
                SUM(IF(estoque_grade.id_responsavel = 1, 1, 0)) > 0,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = `melhores_produtos_pontos`.id_produto
                        AND NOT produtos_foto.tipo_foto = 'SM'
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `produto_foto`,
                produtos.quantidade_vendida,
                COALESCE(`melhores_produtos_pontos`.`total`, 0)
            FROM (
                SELECT
                    produtos_pontos.total,
                    produtos_pontos.id_produto
                FROM produtos_pontos
                ORDER BY produtos_pontos.total DESC
                LIMIT 4000
            ) AS `melhores_produtos_pontos`
            JOIN produtos ON produtos.id = `melhores_produtos_pontos`.id_produto
            JOIN estoque_grade ON estoque_grade.id_produto = `melhores_produtos_pontos`.id_produto
                AND estoque_grade.estoque > 0
            JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = `melhores_produtos_pontos`.`id_produto`
                AND publicacoes_produtos.situacao = 'CR'
            GROUP BY estoque_grade.id_produto
            HAVING `produto_foto` IS NOT NULL
            ORDER BY `melhores_produtos_pontos`.`total` DESC"
        );
    }

    public static function atualizaInformacoesProdutosCatalogoFixo(PDO $conexao): void
    {
        $conexao->query(
            "UPDATE catalogo_fixo
            INNER JOIN produtos ON produtos.id = catalogo_fixo.id_produto
            SET catalogo_fixo.nome_produto = LOWER(IF(
                    LENGTH(produtos.nome_comercial) > 0,
                    produtos.nome_comercial,
                    produtos.descricao
                )),
                catalogo_fixo.valor_venda_ml = produtos.valor_venda_ml,
                catalogo_fixo.valor_venda_ml_historico = produtos.valor_venda_ml_historico,
                catalogo_fixo.valor_venda_ms = produtos.valor_venda_ms,
                catalogo_fixo.valor_venda_ms_historico = produtos.valor_venda_ms_historico,
                catalogo_fixo.foto_produto = (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = catalogo_fixo.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                )
            WHERE catalogo_fixo.tipo <> '" .
                self::TIPO_MELHOR_FABRICANTE .
                "'"
        );
    }

    public static function geraCatalogoModaComPorcentagem(string $tipo, ?int $porcentagem = 50): void
    {
        $restoDaPorcentagem = 100 - $porcentagem;
        $produtos = DB::select(
            "
            (
                SELECT
                    publicacoes_produtos.id_publicacao,
                    NOW() AS `expira_em`,
                    publicacoes_produtos.id AS `id_publicacao_produto`,
                    produtos.id AS `id_produto`,
                    produtos.id_fornecedor,
                    LOWER(produtos.nome_comercial) AS `nome_produto`,
                    produtos.valor_venda_ml,
                    produtos.valor_venda_ml_historico,
                    produtos.valor_venda_ms,
                    produtos.valor_venda_ms_historico,
                    EXISTS(
                      SELECT 1
                      FROM estoque_grade
                      WHERE estoque_grade.id_produto = produtos.id
                        AND estoque_grade.estoque > 0
                        AND estoque_grade.id_responsavel = 1
                    ) AS `possui_fulfillment`,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) AS `foto_produto`,
                    COUNT(DISTINCT(logistica_item.id_cliente)) AS `diferentes_clientes`,
                    COUNT(logistica_item.id_produto) AS `quantidade_vendida`,
                    produtos_pontos.total AS `pontos`,
                    (
                        SELECT SUM(estoque_grade.estoque)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = logistica_item.id_produto
                    ) AS `estoque_atual`
                FROM
                    produtos
                LEFT JOIN logistica_item ON logistica_item.id_produto = produtos.id
                    AND logistica_item.situacao <= :situacao_logistica
                    AND logistica_item.data_criacao >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                LEFT JOIN produtos_pontos ON produtos_pontos.id_produto = produtos.id
                LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
                WHERE produtos.eh_moda = 1
                GROUP BY
                    produtos.id
                HAVING
                 `estoque_atual` > 5
                ORDER BY
                    `diferentes_clientes` DESC,
                    `quantidade_vendida` DESC,
                    `pontos` DESC
                LIMIT :procentagem
            )
            UNION ALL
            (
                SELECT
                    publicacoes_produtos.id_publicacao,
                    NOW() AS `expira_em`,
                    publicacoes_produtos.id AS `id_publicacao_produto`,
                    produtos.id AS `id_produto`,
                    produtos.id_fornecedor,
                    LOWER(produtos.nome_comercial) AS `nome_produto`,
                    produtos.valor_venda_ml,
                    produtos.valor_venda_ml_historico,
                    produtos.valor_venda_ms,
                    produtos.valor_venda_ms_historico,
                    EXISTS(
                      SELECT 1
                      FROM estoque_grade
                      WHERE estoque_grade.id_produto = produtos.id
                        AND estoque_grade.estoque > 0
                        AND estoque_grade.id_responsavel = 1
                    ) AS `possui_fulfillment`,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                            AND produtos_foto.tipo_foto <> 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) AS `foto_produto`,
                    COUNT(DISTINCT(logistica_item.id_cliente)) AS `diferentes_clientes`,
                    COUNT(logistica_item.id_produto) AS `quantidade_vendida`,
                    produtos_pontos.total AS `pontos`,
                    (
                        SELECT SUM(estoque_grade.estoque)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = logistica_item.id_produto
                    ) AS `estoque_atual`
                FROM
                    produtos
                LEFT JOIN logistica_item ON logistica_item.id_produto = produtos.id
                    AND logistica_item.situacao <= :situacao_logistica
                    AND logistica_item.data_criacao >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                LEFT JOIN produtos_pontos ON produtos_pontos.id_produto = produtos.id
                LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
                WHERE produtos.eh_moda = 0
                GROUP BY
                    produtos.id
                HAVING
                 `estoque_atual` > 5
                ORDER BY
                    `diferentes_clientes` DESC,
                    `quantidade_vendida` DESC,
                    `pontos` DESC
                LIMIT :resto_da_porcentagem
            )
        ",
            [
                'procentagem' => $porcentagem,
                'resto_da_porcentagem' => $restoDaPorcentagem,
                'situacao_logistica' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA,
            ]
        );

        foreach ($produtos as $produto) {
            DB::insert(
                "INSERT INTO catalogo_fixo (
                    catalogo_fixo.id_publicacao,
                    catalogo_fixo.tipo,
                    catalogo_fixo.expira_em,
                    catalogo_fixo.id_publicacao_produto,
                    catalogo_fixo.id_produto,
                    catalogo_fixo.id_fornecedor,
                    catalogo_fixo.nome_produto,
                    catalogo_fixo.valor_venda_ml,
                    catalogo_fixo.valor_venda_ml_historico,
                    catalogo_fixo.valor_venda_ms,
                    catalogo_fixo.valor_venda_ms_historico,
                    catalogo_fixo.possui_fulfillment,
                    catalogo_fixo.foto_produto,
                    catalogo_fixo.quantidade_vendida,
                    catalogo_fixo.pontuacao
                ) VALUES (
                    :id_publicacao,
                    :tipo,
                    :expira_em,
                    :id_publicacao_produto,
                    :id_produto,
                    :id_fornecedor,
                    :nome_produto,
                    :valor_venda_ml,
                    :valor_venda_ml_historico,
                    :valor_venda_ms,
                    :valor_venda_ms_historico,
                    :possui_fulfillment,
                    :foto_produto,
                    :quantidade_vendida,
                    :pontos
                )",
                [
                    'id_publicacao' => $produto['id_publicacao'],
                    'tipo' => $tipo,
                    'expira_em' => $produto['expira_em'],
                    'id_publicacao_produto' => $produto['id_publicacao_produto'],
                    'id_produto' => $produto['id_produto'],
                    'id_fornecedor' => $produto['id_fornecedor'],
                    'nome_produto' => $produto['nome_produto'],
                    'valor_venda_ml' => $produto['valor_venda_ml'],
                    'valor_venda_ml_historico' => $produto['valor_venda_ml_historico'],
                    'valor_venda_ms' => $produto['valor_venda_ms'],
                    'valor_venda_ms_historico' => $produto['valor_venda_ms_historico'],
                    'possui_fulfillment' => $produto['possui_fulfillment'],
                    'foto_produto' => $produto['foto_produto'],
                    'quantidade_vendida' => $produto['quantidade_vendida'],
                    'pontos' => $produto['pontos'],
                ]
            );
        }
    }

    public static function geraCatalogoModaPorcentagemFixa(): void
    {
        for ($porcentagem = 20; $porcentagem < 100; $porcentagem += 20) {
            $tag = 'MODA_' . $porcentagem;
            self::geraCatalogoModaComPorcentagem($tag, $porcentagem);
        }
    }
}

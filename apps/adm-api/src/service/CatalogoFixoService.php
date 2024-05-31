<?php

namespace MobileStock\service;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use PDO;

class CatalogoFixoService
{
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
                publicacoes_produtos.id id_publicacao_produto,
                mais_vendidos_logistica_item.id_produto,
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
                    WHERE produtos_foto.id = mais_vendidos_logistica_item.id_produto
                        AND NOT produtos_foto.tipo_foto = 'SM'
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`,
                produtos.quantidade_vendida,
                mais_vendidos_logistica_item.vendas vendas_recentes,
                COALESCE(produtos_pontuacoes.total, 0) pontuacao
            FROM (
                SELECT
                    COUNT(logistica_item.id_produto) AS `vendas`,
                    logistica_item.id_produto
                FROM logistica_item
                WHERE logistica_item.data_criacao >= DATE_SUB(NOW(), INTERVAL 5 HOUR)
                GROUP BY logistica_item.id_produto
            ) AS `mais_vendidos_logistica_item`
            JOIN produtos ON produtos.id = mais_vendidos_logistica_item.id_produto
            JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
                AND reputacao_fornecedores.reputacao IN (:reputacao_excelente, :reputacao_melhor_fabricante)
            LEFT JOIN produtos_pontuacoes ON produtos_pontuacoes.id_produto = mais_vendidos_logistica_item.id_produto
            JOIN estoque_grade ON estoque_grade.id_produto = mais_vendidos_logistica_item.id_produto
                AND estoque_grade.estoque > 0
            JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = mais_vendidos_logistica_item.id_produto
                AND publicacoes_produtos.situacao = 'CR'
            GROUP BY estoque_grade.id_produto
            HAVING `foto_produto` IS NOT NULL
            ORDER BY mais_vendidos_logistica_item.vendas DESC;",
            [
                ':reputacao_excelente' => ReputacaoFornecedoresService::REPUTACAO_EXCELENTE,
                ':reputacao_melhor_fabricante' => ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE,
            ]
        );
        $dataExpiracao = (new Carbon('NOW'))->format('Y-m-d H:i:s');
        $produtos = array_map(function (array $produto) use ($dataExpiracao): array {
            $produto['tipo'] = self::TIPO_VENDA_RECENTE;
            $produto['expira_em'] = $dataExpiracao;

            return $produto;
        }, $produtos);

        /**
         * catalogo_fixo.id_publicacao
         * catalogo_fixo.tipo
         * catalogo_fixo.expira_em
         * catalogo_fixo.id_publicacao_produto
         * catalogo_fixo.id_produto
         * catalogo_fixo.id_fornecedor
         * catalogo_fixo.nome_produto
         * catalogo_fixo.valor_venda_ml
         * catalogo_fixo.valor_venda_ml_historico
         * catalogo_fixo.valor_venda_ms
         * catalogo_fixo.valor_venda_ms_historico
         * catalogo_fixo.possui_fulfillment
         * catalogo_fixo.foto_produto
         * catalogo_fixo.quantidade_vendida
         * catalogo_fixo.pontuacao
         */
        DB::table('catalogo_fixo')->insert($produtos);
    }

    public static function geraMelhoresProdutos(): void
    {
        $produtos = DB::select(
            "SELECT
                publicacoes_produtos.id_publicacao,
                publicacoes_produtos.id AS `id_publicacao_produto`,
                melhores_produtos_pontuacoes.id_produto,
                produtos.id_fornecedor,
                LOWER(produtos.nome_comercial) AS `nome_produto`,
                produtos.valor_venda_ml,
                IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0) AS `valor_venda_ml_historico`,
                produtos.valor_venda_ms,
                IF(produtos.promocao > 0, produtos.valor_venda_ms_historico, 0) AS `valor_venda_ms_historico`,
                SUM(estoque_grade.id_responsavel = 1) > 0 AS `possui_fulfillment`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = melhores_produtos_pontuacoes.id_produto
                        AND NOT produtos_foto.tipo_foto = 'SM'
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`,
                produtos.quantidade_vendida,
                COALESCE(melhores_produtos_pontuacoes.total, 0) AS `pontuacao`
            FROM (
                SELECT
                    produtos_pontuacoes.total,
                    produtos_pontuacoes.id_produto
                FROM produtos_pontuacoes
                ORDER BY produtos_pontuacoes.total DESC
                LIMIT 4000
            ) AS `melhores_produtos_pontuacoes`
            JOIN produtos ON produtos.id = melhores_produtos_pontuacoes.id_produto
            JOIN estoque_grade ON estoque_grade.id_produto = melhores_produtos_pontuacoes.id_produto
                AND estoque_grade.estoque > 0
            JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = melhores_produtos_pontuacoes.id_produto
                AND publicacoes_produtos.situacao = 'CR'
            GROUP BY estoque_grade.id_produto
            HAVING foto_produto IS NOT NULL
            ORDER BY melhores_produtos_pontuacoes.total DESC;"
        );
        $dataExpiracao = (new Carbon('NOW'))->format('Y-m-d H:i:s');
        $produtos = array_map(function (array $produto) use ($dataExpiracao): array {
            $produto['tipo'] = self::TIPO_MELHORES_PRODUTOS;
            $produto['expira_em'] = $dataExpiracao;

            return $produto;
        }, $produtos);

        /**
         * catalogo_fixo.id_publicacao
         * catalogo_fixo.tipo
         * catalogo_fixo.expira_em
         * catalogo_fixo.id_publicacao_produto
         * catalogo_fixo.id_produto
         * catalogo_fixo.id_fornecedor
         * catalogo_fixo.nome_produto
         * catalogo_fixo.valor_venda_ml
         * catalogo_fixo.valor_venda_ml_historico
         * catalogo_fixo.valor_venda_ms
         * catalogo_fixo.valor_venda_ms_historico
         * catalogo_fixo.possui_fulfillment
         * catalogo_fixo.foto_produto
         * catalogo_fixo.quantidade_vendida
         * catalogo_fixo.pontuacao
         */
        DB::table('catalogo_fixo')->insert($produtos);
    }

    // public static function geraMelhoresFabricantes(PDO $conexao): void
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT GROUP_CONCAT(catalogo_fixo_meulook.id_produto) ids
    //         FROM catalogo_fixo_meulook
    //         WHERE catalogo_fixo_meulook.tipo = '" . self::TIPO_MELHOR_FABRICANTE . "'"
    //     );
    //     $stmt->execute();
    //     $listaIds = $stmt->fetchColumn() ?: '';

    //     $whereListaIds = $listaIds ? " AND produtos.id NOT IN ($listaIds)" : '';
    //     $stmt = $conexao->prepare(
    //         "SELECT produtos_acessos.id_produto,
    //             COUNT(1) `qtd_acessos`
    //         FROM produtos_acessos
    //         INNER JOIN produtos ON produtos.id = produtos_acessos.id_produto
    //             AND produtos.bloqueado = 0
    //         INNER JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = produtos.id_fornecedor
    //             AND reputacao_fornecedores.reputacao = '" . self::TIPO_MELHOR_FABRICANTE . "'
    //         WHERE produtos_acessos.origem = 'ML'
    //             AND produtos_acessos.data >= NOW() - INTERVAL 1 HOUR
    //             $whereListaIds
    //         GROUP BY produtos_acessos.id_produto
    //         ORDER BY `qtd_acessos` DESC"
    //     );
    //     $stmt->execute();
    //     $produtosClicados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     $cases = [];
    //     $orders = [];
    //     foreach ($produtosClicados as $produtoClicado) {
    //         $cases[] = "WHEN produtos.id = {$produtoClicado['id_produto']} THEN {$produtoClicado['qtd_acessos']}";
    //         $orders[] = "produtos.id = {$produtoClicado['id_produto']} DESC";
    //     }

    //     if ($cases) $cases = "CASE " . implode(' ', $cases) . " ELSE 0 END";
    //     else $cases = "0";

    //     if ($orders) $orders = "ORDER BY " . implode(', ', $orders) . ", RAND()";
    //     else $orders = "ORDER BY RAND()";

    //     $conexao->query(
    //         "INSERT INTO catalogo_fixo_meulook (
    //             catalogo_fixo_meulook.id_publicacao,
    //             catalogo_fixo_meulook.tipo,
    //             catalogo_fixo_meulook.expira_em,
    //             catalogo_fixo_meulook.id_publicacao_produto,
    //             catalogo_fixo_meulook.id_produto,
    //             catalogo_fixo_meulook.nome_produto,
    //             catalogo_fixo_meulook.valor_venda_ml,
    //             catalogo_fixo_meulook.valor_venda_ml_historico,
    //             catalogo_fixo_meulook.valor_venda_ms,
    //             catalogo_fixo_meulook.valor_venda_ms_historico,
    //             catalogo_fixo_meulook.possui_fullfillment,
    //             catalogo_fixo_meulook.foto_produto,
    //             catalogo_fixo_meulook.quantidade_acessos,
    //             catalogo_fixo_meulook.id_fornecedor
    //         )
    //         SELECT publicacoes.id id_publicacao,
    //             '" . self::TIPO_MELHOR_FABRICANTE . "' tipo,
    //             NOW() + INTERVAL 50 MINUTE expira_em,
    //             publicacoes_produtos.id id_publicacao_produto,
    //             produtos.id id_produto,
    //             LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome_produto,
    //             produtos.valor_venda_ml,
    //             IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0) valor_venda_ml_historico,
    //             produtos.valor_venda_ms,
    //             IF(produtos.promocao > 0, produtos.valor_venda_ms_historico, 0) valor_venda_ms_historico,
    //             SUM(IF(estoque_grade.id_responsavel = 1, 1, 0)) > 0 possui_fullfillment,
    //             COALESCE((
    //                 SELECT produtos_foto.caminho
    //                 FROM produtos_foto
    //                 WHERE produtos_foto.id = produtos.id
    //                 ORDER BY produtos_foto.tipo_foto = 'MD' DESC
    //                 LIMIT 1
    //             ), '') foto_produto,
    //             $cases quantidade_acessos,
    //             produtos.id_fornecedor
    //         FROM reputacao_fornecedores
    //         INNER JOIN produtos ON produtos.id_fornecedor = reputacao_fornecedores.id_colaborador
    //             AND produtos.bloqueado = 0
    //             AND produtos.data_primeira_entrada IS NOT NULL
    //         INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
    //             AND publicacoes_produtos.situacao = 'CR'
    //         INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
    //             AND publicacoes.situacao = 'CR'
    //             AND publicacoes.tipo_publicacao = 'AU'
    //         INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
    //             AND estoque_grade.estoque > 0
    //         WHERE reputacao_fornecedores.reputacao = '" . self::TIPO_MELHOR_FABRICANTE . "'
    //             $whereListaIds
    //         GROUP BY produtos.id
    //         $orders
    //         LIMIT 100;"
    //     );
    // }

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

    // public static function atualizaQuantidadesVendidas(PDO $conexao): void
    // {
    //     $conexao->query(
    //         "UPDATE catalogo_fixo_meulook
    //         SET catalogo_fixo_meulook.quantidade_vendida = (
    //             SELECT COUNT(pedido_item_meu_look.id)
    //             FROM pedido_item_meu_look
    //             WHERE pedido_item_meu_look.id_produto = catalogo_fixo_meulook.id_produto
    //                 AND pedido_item_meu_look.situacao = 'PA'
    //         )"
    //     );
    // }
}

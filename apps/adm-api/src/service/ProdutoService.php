<?php

namespace MobileStock\service;

use Exception;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\Colaborador;
use MobileStock\model\LogisticaItem;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\repository\ColaboradoresRepository;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../../vendor/autoload.php';

class ProdutoService
{
    public static function buscaDetalhesProduto(int $idProduto): array
    {
        $consulta = DB::selectOne(
            "SELECT
                produtos.localizacao,
                produtos.id,
                produtos.nome_comercial,
                produtos.cores AS `cor`,
                (
                    SELECT
                        produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`,
                produtos.descricao,
                (
                    SELECT
                        CONCAT(colaboradores.id, ' - ', colaboradores.razao_social)
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ) AS `nome_fornecedor`,
                CONCAT(
                    '[',
                        (
                            SELECT DISTINCT GROUP_CONCAT(
                                JSON_OBJECT(
                                    'nome_tamanho', produtos_grade.nome_tamanho,
                                    'qtd', COALESCE(
                                        (
                                            SELECT SUM(estoque_grade.estoque)
                                            FROM estoque_grade
                                            WHERE estoque_grade.id_produto = produtos_grade.id_produto
                                            AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                                        ), 0),
                                    'vendido', COALESCE(
                                        (
                                            SELECT SUM(estoque_grade.vendido)
                                            FROM estoque_grade
                                            WHERE estoque_grade.id_produto = produtos_grade.id_produto
                                            AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                                        ), 0),
                                    'cod_barras', produtos_grade.cod_barras
                                )
                            )
                            FROM produtos_grade
                            WHERE produtos_grade.id_produto = produtos.id
                            ORDER BY produtos_grade.sequencia ASC
                        ),
                    ']'
                ) AS `json_estoque`
            FROM produtos
            WHERE produtos.id = :id_produto
            GROUP BY produtos.id",
            [':id_produto' => $idProduto]
        );

        return $consulta;
    }

    public static function buscaTransacoesProduto(int $idProduto, ?string $nomeTamanho): array
    {
        $condicaoTransacao = '';
        $binds[':id_produto'] = $idProduto;
        if ($nomeTamanho) {
            $condicaoTransacao = ' AND transacao_financeiras_produtos_itens.nome_tamanho = :nome_tamanho';
            $binds[':nome_tamanho'] = $nomeTamanho;
        }

        $resultado = DB::select(
            "SELECT
                transacao_financeiras.id,
                GROUP_CONCAT(transacao_financeiras_produtos_itens.nome_tamanho) nome_tamanho,
                (
                    SELECT
                        CONCAT(colaboradores.id, ' - ', colaboradores.razao_social)
                    FROM colaboradores
                    WHERE colaboradores.id = transacao_financeiras.pagador
                ) nome_cliente,
                transacao_financeiras.status = 'PA' esta_pago,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y') data_hora
            FROM transacao_financeiras
            INNER JOIN transacao_financeiras_produtos_itens
                ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
            WHERE transacao_financeiras_produtos_itens.id_produto = :id_produto $condicaoTransacao
            GROUP BY transacao_financeiras.id
            ORDER BY transacao_financeiras.id DESC",
            $binds
        );

        return $resultado;
    }

    public static function buscaTrocasDoProduto(int $idProduto, ?string $nomeTamanho): array
    {
        $condicaoItem = '';
        $condicaoAgendamento = '';
        $binds[':id_produto'] = $idProduto;
        if ($nomeTamanho) {
            $condicaoItem = 'AND troca_pendente_item.nome_tamanho = :nome_tamanho';
            $condicaoAgendamento = 'AND troca_pendente_agendamento.nome_tamanho = :nome_tamanho';
            $binds[':nome_tamanho'] = $nomeTamanho;
        }

        $trocas = DB::select(
            "SELECT
                1 esta_confirmada,
                troca_pendente_item.nome_tamanho,
                troca_pendente_item.uuid,
                (
                    SELECT
                        CONCAT(colaboradores.id, ' - ', colaboradores.razao_social)
                    FROM colaboradores
                    WHERE colaboradores.id = troca_pendente_item.id_cliente
                ) AS `nome_cliente`,
                (
                    SELECT
                        logistica_item.preco
                    FROM logistica_item
                    WHERE logistica_item.uuid_produto = troca_pendente_item.uuid
                 ) - troca_pendente_item.preco AS `taxa`,
                (
                    SELECT
                        logistica_item.preco
                    FROM logistica_item
                    WHERE logistica_item.uuid_produto = troca_pendente_item.uuid
                ) AS `preco`,
                DATE_FORMAT(troca_pendente_item.data_hora, '%d/%m/%Y') AS `data`
            FROM troca_pendente_item
            WHERE troca_pendente_item.id_produto = :id_produto $condicaoItem

            UNION ALL

            SELECT
                0 esta_confirmada,
                troca_pendente_agendamento.nome_tamanho,
                troca_pendente_agendamento.uuid,
                (
                    SELECT
                        CONCAT(colaboradores.id, ' - ', colaboradores.razao_social)
                    FROM colaboradores
                    WHERE colaboradores.id = troca_pendente_agendamento.id_cliente
                ) AS `cliente`,
                troca_pendente_agendamento.taxa,
                troca_pendente_agendamento.preco,
                DATE_FORMAT(troca_pendente_agendamento.data_hora, '%d/%m/%Y') AS `data`
            FROM troca_pendente_agendamento
            WHERE troca_pendente_agendamento.id_produto = :id_produto $condicaoAgendamento;",
            $binds
        );

        return $trocas;
    }

    // public function buscaEstoqueProdutosPorFornecedor(PDO $conexao, int $id)
    // {
    //     $query = "SELECT eg.id_produto, SUM(eg.estoque)estoque,
    //     (SELECT p.valor_custo_produto FROM produtos p WHERE p.id=eg.id_produto)custo, SUM(eg.vendido)vendido FROM estoque_grade eg
    //     INNER JOIN produtos p ON p.id = eg.id_produto WHERE p.id_fornecedor={$id} GROUP BY eg.id_produto ORDER BY eg.id_produto";
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll(PDO::FETCH_ASSOC);
    // }
    //
    //    public static function buscaTodos()
    //    {
    //        $sql = "SELECT * FROM produtos";
    //        $conexao = Conexao::criarConexao();
    //        $result = $conexao->prepare($sql);
    //        $result->execute();
    //        return $result->fetchAll(PDO::FETCH_ASSOC);
    //    }
    //

    //
    //    public static function buscaProdutoPorId() {
    //        $sql = "SELECT id, descricao FROM produtos";
    //        $conexao = Conexao::criarConexao();
    //        $result = $conexao->prepare($sql);
    //        $result->execute();
    //        $lista = $result->fetchAll(PDO::FETCH_ASSOC);
    //        return $lista;
    //    }
    //

    //
    //    public static function buscaTodosFornecedor($id_fornecedor)
    //    {
    //        $sql = "SELECT produtos.id,produtos.descricao FROM produtos WHERE produtos.id_fornecedor = {$id_fornecedor}";
    //        $conexao = Conexao::criarConexao();
    //        $result = $conexao->prepare($sql);
    //        $result->execute();
    //        return $result->fetchAll(PDO::FETCH_ASSOC);
    //    }
    //

    // public static function buscaProdutosDefasados(PDO $conn, int $dias = 180)
    // {
    //     $sql = " SELECT  produtos.id,
    //                     produtos.descricao,
    //                     (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = produtos.id AND produtos_foto.sequencia = 1)foto,
    //                     (
    //                         SELECT colaboradores.razao_social
    //                             FROM colaboradores
    //                                 WHERE colaboradores.id = produtos.id_fornecedor
    //                     ) fornecedor,
    //                    produtos.data_ultima_venda,
    //                    DATE_FORMAT(data_ultima_venda, '%d/%m/%Y')data_formatada
    //                     FROM produtos
    //                     WHERE DATEDIFF(DATE(NOW()), DATE(data_ultima_venda)) >= $dias
    // 									  AND  NOT EXISTS (SELECT 1 FROM estoque_grade WHERE estoque_grade.id_produto = produtos.id AND estoque_grade.estoque > 0 )
    //                                       AND produtos.bloqueado = 0
    //                              GROUP BY produtos.id
    // 									ORDER BY data_ultima_venda DESC";
    //     $stmt = $conn->prepare($sql);
    //     $stmt->execute();
    //     $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $lista;
    // }
    //
    //    public static function blockProdutos(PDO $conn, array $lista_produtos)
    //    {
    //        $start = 1;
    //        $sql = "";
    //        foreach ($lista_produtos as $key => $item) :
    //            $start == 1 ? $start = 0 : $sql .= ",";
    //            $sql .= "'$item'";
    //        endforeach;
    //        $query = "UPDATE produtos SET produtos.bloqueado = 1 where produtos.id IN ({$sql}) ";
    //        $stmt = $conn->prepare($query);
    //        return $stmt->execute();
    //    }
    //

    //
    //    public static function deletPhotoBanco(PDO $conn, int $id)
    //    {
    //        $query = "DELETE FROM produtos_foto WHERE produtos_foto.id = {$id}";
    //        $stmt = $conn->prepare($query);
    //        return $stmt->execute();
    //    }
    //

    //
    //    public static function buscaPhotoBanco(PDO $conn, int $id)
    //    {
    //        $query = "SELECT caminho,nome_foto FROM produtos_foto WHERE produtos_foto.id = {$id}";
    //        $stmt = $conn->prepare($query);
    //        $stmt->execute();
    //        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $lista;
    //    }
    //

    public static function salvaSugestaoDeProduto(PDO $con, string $foto, int $idCliente)
    {
        $query = "INSERT INTO produtos_sugestao (foto_produto, id_cliente, data_criacao) VALUES('{$foto}',{$idCliente},NOW());";
        $stmt = $con->prepare($query);
        return $stmt->execute();
    }

    public static function buscaProdutosParaTroca(): array
    {
        $origem = app(Origem::class);
        $auxiliares = ConfiguracaoService::buscaAuxiliaresTroca($origem);

        [$produtosFreteSql, $binds] = ConversorArray::criaBindValues(
            ProdutoModel::IDS_PRODUTOS_FRETE,
            'id_produto_frete'
        );

        $binds = array_merge($binds, [
            ':id_cliente' => Auth::user()->id_colaborador,
            ':dias_defeito' => $auxiliares['dias_defeito'],
            ':situacao_logistica' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA,
        ]);

        $lista = DB::select(
            "SELECT
        entregas.id AS id_pedido,
        DATE_FORMAT(entregas.data_entrega, '%d/%m/%Y') AS data_pedido,
        COUNT(logistica_item.id) AS pares,
        CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
                'id_produto',     logistica_item.id_produto,
                'descricao',      (SELECT produtos.descricao FROM produtos WHERE produtos.id = logistica_item.id_produto),
                'nome_comercial', (SELECT produtos.nome_comercial
                                    FROM produtos
                                    WHERE produtos.id = logistica_item.id_produto),
                'nome_tamanho',   logistica_item.nome_tamanho,
                'preco',          transacao_financeiras_produtos_itens.preco,
                'preco_logistica', logistica_item.preco,
                'valor_estorno', (
                                    SELECT SUM(transacao_financeiras_produtos_itens.preco)
                                    FROM transacao_financeiras_produtos_itens
                                    WHERE transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
                                    AND transacao_financeiras_produtos_itens.sigla_estorno IS NOT NULL
                                ),
                'situacao',       logistica_item.situacao,
                'foto',           COALESCE((SELECT produtos_foto.caminho
                                            FROM produtos_foto
                                            WHERE produtos_foto.id = logistica_item.id_produto
                                            ORDER BY produtos_foto.tipo_foto = 'MD' ASC
                                            LIMIT 1), ''),
                'uuid',           logistica_item.uuid_produto,
                'data_retirada', DATE_FORMAT(entregas.data_entrega, '%d/%m/%Y %H:%i'),
                'situacao_troca', CASE
                    WHEN entregas_devolucoes_item.id IS NOT NULL
                        THEN 'ITEM_TROCADO'
                    WHEN troca_fila_solicitacoes.situacao = 'CANCELADO_PELO_CLIENTE'
                        THEN 'CLIENTE_DESISTIU'
                    WHEN troca_fila_solicitacoes.situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO'
                        THEN 'PASSOU_PRAZO'
                    ELSE
                        'TROCA_DISPONIVEL'
                    END,
                'descricao_defeito', COALESCE(troca_fila_solicitacoes.descricao_defeito, ''),
                'foto1', troca_fila_solicitacoes.foto1,
                'foto2', troca_fila_solicitacoes.foto2,
                'foto3', troca_fila_solicitacoes.foto3,
                'data_base_troca', entregas_faturamento_item.data_base_troca,
                'limite_noventa_dias', entregas_faturamento_item.data_base_troca >= DATE_SUB(NOW(), INTERVAL :dias_defeito DAY),
                'data_limite_tarifa', DATE_FORMAT(DATE_ADD(entregas_faturamento_item.data_base_troca, INTERVAL :dias_defeito DAY), '%d/%m/%Y %H:%i'),
                'data_limite_troca', DATE_FORMAT(DATE_ADD(entregas_faturamento_item.data_base_troca, INTERVAL 1 YEAR), '%d/%m/%Y %H:%i'),
                'qtd_dias_do_ano' , DAYOFYEAR(CONCAT(YEAR(entregas_faturamento_item.data_base_troca),'-12-31'))
            )),']') json_produtos
            FROM entregas
            INNER JOIN logistica_item ON logistica_item.id_entrega = entregas.id AND logistica_item.id_entrega <> 40261
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
	            AND transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = logistica_item.uuid_produto
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = entregas_faturamento_item.uuid_produto
            WHERE logistica_item.situacao >= :situacao_logistica
              AND logistica_item.id_produto NOT IN ($produtosFreteSql)
              AND logistica_item.id_cliente = :id_cliente
              AND entregas_faturamento_item.situacao = 'EN'
              AND entregas.data_atualizacao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
              AND NOT EXISTS(SELECT 1
                             FROM pedido_item_meu_look
                             WHERE pedido_item_meu_look.uuid = logistica_item.uuid_produto)
              AND NOT EXISTS(
                    SELECT 1
                    FROM troca_pendente_agendamento
                    WHERE troca_pendente_agendamento.uuid = logistica_item.uuid_produto
              )
              AND IF (
                troca_fila_solicitacoes.id,
                troca_fila_solicitacoes.situacao IN ('CANCELADO_PELO_CLIENTE','PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO'),
                TRUE
              )
            GROUP BY entregas.id
            ORDER BY entregas.id DESC;",
            $binds
        );

        $lista = array_filter($lista, fn(array $pedido): bool => !empty($pedido['produtos']));
        $consulta = array_map(function (array $pedido) use ($auxiliares, $origem): array {
            $pedido['produtos'] = array_map(function (array $produto) use ($auxiliares, $origem): array {
                $produto['situacao_solicitacao'] = TrocaFilaSolicitacoesService::retornaTextoSituacaoTroca(
                    $produto['data_base_troca'],
                    $produto['situacao_troca'],
                    0,
                    $origem,
                    $auxiliares
                );

                return $produto;
            }, $pedido['produtos']);

            return $pedido;
        }, $lista);

        return $consulta;
    }

    public static function buscaTrocasAgendadas(): array
    {
        $auxiliares = ConfiguracaoService::buscaAuxiliaresTroca(Origem::MS);
        $produtos = DB::select(
            "
            SELECT
                produtos.nome_comercial,
                troca_fila_solicitacoes.id id_solicitacao,
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.uuid_produto uuid,
                troca_pendente_agendamento.id defeito,
                entregas_faturamento_item.nome_tamanho,
                DATE_FORMAT(entregas_faturamento_item.data_entrega, '%d/%m/%Y') data_retirada,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                    AND produtos_foto.tipo_foto <> 'SM'
                    LIMIT 1
                ) foto,
                troca_fila_solicitacoes.situacao situacao_troca,
                entregas_faturamento_item.data_base_troca,
                troca_fila_solicitacoes.data_atualizacao data_atualizacao_solicitacao,
                CASE
                    WHEN troca_fila_solicitacoes.situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO' THEN 'RETORNO_PRODUTO_EXPIRADO'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADA_NA_DISPUTA' THEN 'DISPUTA_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'EM_DISPUTA' THEN 'DISPUTA'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADO' THEN 'SELLER_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'SOLICITACAO_PENDENTE' THEN 'TROCA_PENDENTE'
                    WHEN troca_fila_solicitacoes.situacao = 'REPROVADA_POR_FOTO' THEN 'FOTO_REPROVOU'
                    WHEN troca_fila_solicitacoes.situacao = 'PENDENTE_FOTO' THEN 'PENDENTE_FOTO'
                    ELSE 'Troca aprovada'
                END situacao_solicitacao,
                troca_fila_solicitacoes.motivo_reprovacao_seller motivo_reprovacao,
                (
                    SELECT correios_atendimento.numeroColeta
                    FROM correios_atendimento
                    WHERE correios_atendimento.id_cliente = entregas_faturamento_item.id_cliente
                    AND correios_atendimento.status = 'A'
                    AND correios_atendimento.prazo > NOW()
                    ORDER BY correios_atendimento.data_verificacao DESC
                    LIMIT 1
                ) AS numero_coleta,
                troca_fila_solicitacoes.situacao = 'APROVADO' AND logistica_item.situacao = :situacao AS em_processo_troca
            FROM entregas_faturamento_item
            INNER JOIN produtos ON produtos.id = entregas_faturamento_item.id_produto
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = entregas_faturamento_item.uuid_produto
            LEFT JOIN troca_pendente_agendamento ON troca_pendente_agendamento.uuid = entregas_faturamento_item.uuid_produto
            INNER JOIN logistica_item ON logistica_item.uuid_produto = entregas_faturamento_item.uuid_produto
            WHERE entregas_faturamento_item.id_cliente = :id_cliente
            AND (troca_fila_solicitacoes.id IS NOT NULL OR troca_pendente_agendamento.id IS NOT NULL)
            AND entregas_faturamento_item.origem <> 'ML'
            AND IF (troca_fila_solicitacoes.id,
                troca_fila_solicitacoes.situacao IN (
                    'EM_DISPUTA', 'SOLICITACAO_PENDENTE', 'REPROVADO', 'REPROVADA_NA_DISPUTA', 'REPROVADA_POR_FOTO', 'PENDENTE_FOTO', 'APROVADO'
                    ),
                TRUE
                )
            GROUP BY entregas_faturamento_item.uuid_produto
            ORDER BY situacao_solicitacao = 'SELLER_REPROVOU' DESC,
                    situacao_solicitacao = 'DISPUTA' DESC,
                    situacao_solicitacao = 'TROCA_PENDENTE' DESC,
                    situacao_solicitacao = 'FOTO_REPROVOU' DESC,
                    situacao_solicitacao = 'PENDENTE_FOTO' DESC,
                    situacao_solicitacao = 'Troca aprovada' DESC,
                    entregas_faturamento_item.data_entrega DESC
        ",
            [
                'id_cliente' => Auth::user()->id_colaborador,
                'situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA,
            ]
        );

        $consulta = array_map(function ($item) use ($auxiliares): array {
            $dataBaseTroca = $item['data_base_troca'];
            $dataAtualizacaoSolicitacao = $item['data_atualizacao_solicitacao'];
            $item['situacao_solicitacao'] = TrocaFilaSolicitacoesService::retornaTextoSituacaoTroca(
                $item['situacao_solicitacao'],
                $dataBaseTroca,
                $dataAtualizacaoSolicitacao ?: 0,
                'MS',
                $auxiliares
            );
            return $item;
        }, $produtos);

        return $consulta ?: [];
    }

    // public static function buscaDefeitos(PDO $conexao, int $idCliente)
    // {
    //     $query =
    //         "SELECT
    //         troca_pendente_item.id_produto,
    //         produtos.nome_comercial,
    //         troca_pendente_item.tamanho,
    //         troca_pendente_item.preco,
    //         troca_pendente_item.descricao_defeito,
    //         produtos_foto.caminho AS foto_produto,
    //         colaboradores.razao_social,
    //         DATE_FORMAT(troca_pendente_item.data_hora, '%d/%m/%Y') AS 'data',
    //         IF(entregas_devolucoes_item.situacao IN ('CO', 'RE'),0,1) verifica_devolucao
    //     FROM
    //         troca_pendente_item
    //         JOIN produtos ON produtos.id = troca_pendente_item.id_produto
    //         JOIN produtos_foto on produtos_foto.id = troca_pendente_item.id_produto
    //         JOIN colaboradores ON colaboradores.id = troca_pendente_item.id_cliente
    //         LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = troca_pendente_item.uuid AND entregas_devolucoes_item.situacao_envio = 'NO'
    //     WHERE
    //         produtos.id_fornecedor = $idCliente AND
    //         troca_pendente_item.defeito = 1 AND
    //         troca_pendente_item.descricao_defeito IS NOT NULL AND
    //         troca_pendente_item.data_hora BETWEEN (SELECT DATE_SUB(NOW(), INTERVAL 31 DAY)) AND NOW()
    //     GROUP BY descricao_defeito
    //     HAVING verifica_devolucao = 0
    //     ORDER BY data";
    //     $prepare = $conexao->prepare($query);
    //     $prepare->execute();
    //     $resultado = $prepare->fetchAll(PDO::FETCH_ASSOC);
    //     return $resultado;
    // }

    public static function consultaProdutosCompradosParametros(
        PDO $conexao,
        int $idUsuario,
        int $idCliente,
        array $parametros,
        int $pagina
    ): array {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $query = "SELECT
            DISTINCT logistica_item.id_transacao id_faturamento,
            CONCAT(
            '[',
            GROUP_CONCAT(DISTINCT JSON_OBJECT(
                'uuid', logistica_item.uuid_produto,
                'id_produto', logistica_item.id_produto,
                'id_faturamento', logistica_item.id_transacao,
                'nome_tamanho', logistica_item.nome_tamanho,
                'data_hora', logistica_item.data_criacao,
                'preco', logistica_item.preco,
                'premio', 0,
                'descricao', produtos.descricao,
                'passou_prazo', NOT (
                                            SELECT
                                                entregas_faturamento_item.data_base_troca
                                            FROM entregas_faturamento_item
                                            WHERE entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                                            LIMIT 1
                                        ) BETWEEN CURDATE() - INTERVAL 1 YEAR AND CURDATE(),
                'fotoProduto', (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ),
                'fornecedor', (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                ),
                'cod_barras', (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = logistica_item.id_produto
                        AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
                ),
                'linha', (
                    SELECT linha.nome
                    FROM linha
                    WHERE linha.id = produtos.id_linha
                )
            ))
            , ']'
            )produtos
        FROM logistica_item
        INNER JOIN produtos ON produtos.id = logistica_item.id_produto
        WHERE logistica_item.id_cliente = :id_cliente
            AND logistica_item.situacao >= $situacao
            AND logistica_item.id_responsavel_estoque = 1
            AND NOT EXISTS(
                SELECT 1
                FROM troca_pendente_agendamento
                WHERE troca_pendente_agendamento.uuid = logistica_item.uuid_produto
            ) AND NOT EXISTS(
                SELECT 1
                FROM pedido_item_meu_look
                WHERE pedido_item_meu_look.uuid = logistica_item.uuid_produto
            )";

        $bindValues = [':id_cliente' => (int) $idCliente];

        if (!empty($parametros['id_fornecedor'])) {
            Validador::validar($parametros, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $query .= ' AND produtos.id_fornecedor = :id_fornecedor';
            $bindValues[':id_fornecedor'] = (int) $parametros['id_fornecedor'];
        }
        if (!empty($parametros['id_faturamento'])) {
            Validador::validar($parametros, [
                'id_faturamento' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $query .= ' AND logistica_item.id_transacao = :id_faturamento';
            $bindValues[':id_faturamento'] = (int) $parametros['id_faturamento'];
        }
        if (!empty($parametros['id_categorias'])) {
            Validador::validar($parametros, [
                'id_categorias' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            $categorias = (string) implode(',', $parametros['id_categorias']);
            $query .= " AND EXISTS(
                SELECT 1
                FROM produtos_categorias
                WHERE produtos_categorias.id_produto = logistica_item.id_produto
                    AND produtos_categorias.id_categoria IN ($categorias)
            )";
        }
        if (!empty($parametros['id_linha'])) {
            Validador::validar($parametros, [
                'id_linha' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $query .= ' AND produtos.id_linha = :id_linha';
            $bindValues[':id_linha'] = (int) $parametros['id_linha'];
        }
        if (!empty($parametros['nome_tamanho'])) {
            Validador::validar($parametros, [
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);

            $query .= ' AND logistica_item.nome_tamanho = :nome_tamanho';
            $bindValues[':nome_tamanho'] = (string) $parametros['nome_tamanho'];
        }
        if (!empty($parametros['descricao'])) {
            Validador::validar($parametros, [
                'descricao' => [Validador::OBRIGATORIO],
            ]);

            $query .= " AND LOWER(CONCAT_WS(
                    ' - ',
                    logistica_item.id_produto,
                    produtos.descricao
                )) REGEXP LOWER(:descricao) ";
            $bindValues[':descricao'] = (string) $parametros['descricao'];
        }
        if (!empty($parametros['cod_barras'])) {
            Validador::validar($parametros, [
                'cod_barras' => [Validador::OBRIGATORIO],
            ]);

            $query .= " AND EXISTS(
                SELECT 1
                FROM produtos_grade
                WHERE produtos_grade.id_produto = logistica_item.id_produto
                    AND produtos_grade.nome_tamanho = logistica_item.nome_tamanho
                    AND produtos_grade.cod_barras = :cod_barras
            )";
            $bindValues[':cod_barras'] = (string) $parametros['cod_barras'];
        }
        $query .= ' GROUP BY logistica_item.id_transacao ORDER BY logistica_item.id_transacao DESC';
        if ($pagina !== 0) {
            $offset = ($pagina - 1) * 3;
            $query .= " LIMIT 3 OFFSET $offset;";
        }

        $stmt = $conexao->prepare($query);
        $stmt->execute($bindValues);
        $faturamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /** @var Colaborador $colaborador */
        $colaborador = ColaboradoresRepository::busca([
            'id' => "(SELECT usuarios.id_colaborador FROM usuarios WHERE usuarios.id = $idUsuario)",
        ]);

        foreach ($faturamentos as $key => $faturamento) {
            $faturamento = (array) json_decode($faturamento['produtos'], true);

            foreach ($faturamento as $index => $produto) {
                try {
                    $troca = new TrocaPendenteItem(
                        $idCliente,
                        $produto['id_produto'],
                        $produto['nome_tamanho'],
                        $colaborador->getId(),
                        $produto['preco'],
                        $produto['uuid'],
                        '',
                        $produto['data_hora']
                    );
                    $produto['taxa'] = (float) round($troca->calculaTaxa());
                } catch (\InvalidArgumentException $exception) {
                    $produto['disponivel'] = 0;
                }

                $produto['mensagem_indisponivel'] = '';
                if ($produto['premio']) {
                    $produto['mensagem_indisponivel'] = 'Esse produto é prêmio';
                } elseif ($produto['passou_prazo']) {
                    $produto['mensagem_indisponivel'] = 'Esse produto já passou do prazo de 365 dias';
                }

                $produto['preco'] = (float) $produto['preco'];
                $produto['data_hora'] = (string) (new \DateTime($produto['data_hora']))->format('d/m/y H:i:s');
                $produto['correto'] = true;
                $produto['agendada'] = false;
                $produto['defeito'] = false;

                $faturamento[$index] = $produto;
            }

            $faturamentos[$key] = $faturamento;
        }

        return $faturamentos ?? [];
    }
    public static function buscaProdutosDefeituosos(int $idFornecedor): array
    {
        $produtos = DB::select(
            "SELECT
                    entregas_devolucoes_item.id_produto,
                    entregas_devolucoes_item.uuid_produto,
                    entregas_devolucoes_item.nome_tamanho,
                    DATE_FORMAT(entregas_devolucoes_item.data_atualizacao, '%d/%m/%Y')data,
                    CONCAT(COALESCE(produtos.nome_comercial, produtos.descricao), ' ', COALESCE(produtos.cores, ''))nome_comercial,
                    (
                        SELECT colaboradores.razao_social
                        FROM colaboradores
                        WHERE colaboradores.id = entregas_devolucoes_item.id_cliente
                    )razao_social,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = entregas_devolucoes_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ) foto_produto,
                    (
                        SELECT troca_pendente_item.descricao_defeito
                        FROM troca_pendente_item
                        WHERE troca_pendente_item.uuid = entregas_devolucoes_item.uuid_produto
                    ) descricao_defeito
                FROM entregas_devolucoes_item
                INNER JOIN produtos ON produtos.id = entregas_devolucoes_item.id_produto
                WHERE
                    DATE(entregas_devolucoes_item.data_atualizacao) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND entregas_devolucoes_item.situacao IN ('CO','RE')
                    AND entregas_devolucoes_item.tipo = 'DE'
                    AND produtos.id_fornecedor = :id_fornecedor
                    GROUP BY entregas_devolucoes_item.uuid_produto
                    ORDER BY entregas_devolucoes_item.data_atualizacao DESC",
            [
                ':id_fornecedor' => $idFornecedor,
            ]
        );

        return $produtos;
    }
    public static function listaLinhas(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                linha.id,
                linha.nome
            FROM linha
            ORDER BY linha.nome;"
        );
        $sql->execute();
        $linhas = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $linhas;
    }
    public static function listaTiposGrade(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos_tipos_grades.id,
                produtos_tipos_grades.nome,
                produtos_tipos_grades.grade_json
            FROM produtos_tipos_grades;"
        );
        $sql->execute();
        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);

        $grades = array_map(function ($grade) {
            if (!is_null($grade['grade_json'])) {
                $grade['grade_json'] = json_decode($grade['grade_json'], true);
            }

            return (array) $grade;
        }, $grades);

        return $grades;
    }
    public static function listaCategorias(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                categorias.id,
                categorias.nome,
                categorias.id_categoria_pai
            FROM categorias
            ORDER BY categorias.id_categoria_pai ASC, categorias.nome;"
        );
        $sql->execute();
        $listaCategorias = $sql->fetchAll(PDO::FETCH_ASSOC);
        $categorias = [];
        $tipos = [];

        foreach ($listaCategorias as $categoria) {
            if (is_null($categoria['id_categoria_pai'])) {
                //Define Categorias
                $categorias[] = $categoria;
            } else {
                //Define Tipos
                $tipos[] = $categoria;
            }
        }
        $listaCategorias = ['categorias' => (array) $categorias, 'tipos' => (array) $tipos];

        return $listaCategorias;
    }
    public static function listaCores(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                tags_tipos.id_tag,
                tags_tipos.tipo,
                (
                    SELECT tags.nome
                    FROM tags
                    WHERE tags.id = tags_tipos.id_tag
                )nome
            FROM tags_tipos
            WHERE tags_tipos.tipo = 'CO'
            ORDER BY tags_tipos.ordem DESC;"
        );
        $sql->execute();
        $cores = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $cores;
    }

    //    public static function buscaEstoqueGradeFornecedor(\PDO $conexao, int $idFornecedor): array
    //    {
    //        $sql = $conexao->prepare(
    //            "SELECT
    //                estoque_grade.id_produto,
    //                estoque_grade.nome_tamanho,
    //                estoque_grade.estoque
    //            FROM estoque_grade
    //            WHERE estoque_grade.id_responsavel = :id_fornecedor;"
    //        );
    //        $sql->bindValue(":id_fornecedor", $idFornecedor, PDO::PARAM_INT);
    //        $sql->execute();
    //        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);
    //
    //        return $grades;
    //    }
    public static function buscaListaPontuacoes(string $pesquisa, int $pagina, bool $listarTodos): array
    {
        $binds['porPagina'] = $porPagina = 100;
        $binds['offset'] = $porPagina * ($pagina - 1);
        $binds['idCliente'] = Auth::user()->id_colaborador;
        $binds['ehInterno'] = Gate::allows('ADMIN');

        $where = '';
        if (!empty($pesquisa)) {
            $where .= " AND (
                CONCAT_WS(
                    ' ',
                    produtos.nome_comercial,
                    produtos.descricao,
                    colaboradores.razao_social,
                    colaboradores.usuario_meulook
                ) LIKE :pesquisa
                OR produtos.id = :pesquisa_id
            ) ";
            $binds['pesquisa'] = "%$pesquisa%";
            $binds['pesquisa_id'] = $pesquisa;
        }

        if (!$listarTodos) {
            $where .= ' AND colaboradores.id = :idCliente';
        }

        $consulta = DB::select(
            "SELECT
                produtos_pontuacoes.id_produto,
                LOWER(IF(LENGTH(produtos.nome_comercial) > 0, produtos.nome_comercial, produtos.descricao)) nome,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_avaliacoes, 0) pontuacao_avaliacoes,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_seller, 0) pontuacao_seller,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_fullfillment, 0) pontuacao_fullfillment,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.quantidade_vendas, 0) quantidade_vendas,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_devolucao_normal, 0) pontuacao_devolucao_normal,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_devolucao_defeito, 0) pontuacao_devolucao_defeito,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_cancelamento, 0) pontuacao_cancelamento,
                IF(:ehInterno OR colaboradores.id = :idCliente, produtos_pontuacoes.pontuacao_atraso_separacao, 0) pontuacao_atraso_separacao,
                produtos_pontuacoes.total,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos_pontuacoes.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto,
                colaboradores.razao_social razao_social_seller,
                colaboradores.usuario_meulook usuario_meulook_seller,
                colaboradores.id = :idCliente eh_meu_produto
            FROM produtos_pontuacoes
            INNER JOIN produtos ON produtos.id = produtos_pontuacoes.id_produto
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            WHERE produtos.bloqueado = 0
                AND (produtos.fora_de_linha = 0
                    OR (produtos.fora_de_linha = 1
                        AND EXISTS(
                            SELECT 1
                            FROM estoque_grade
                            WHERE estoque_grade.id_produto = produtos.id
                                AND estoque_grade.estoque > 0
                            LIMIT 1
                        )
                    )
                ) $where
            GROUP BY produtos_pontuacoes.id_produto
            ORDER BY produtos_pontuacoes.total DESC
            LIMIT :porPagina OFFSET :offset;",
            $binds
        );

        $consulta = array_map(function ($item) {
            $item['link_produto'] = "{$_ENV['URL_MEULOOK']}produto/{$item['id_produto']}";
            $item['link_seller'] = "{$_ENV['URL_MEULOOK']}{$item['usuario_meulook_seller']}";
            return $item;
        }, $consulta);

        return $consulta;
    }

    /**
     * @param int[] $idProdutos
     */
    public static function buscarProdutosParaCatalogoPdf(PDO $conexao, array $idProdutos): array
    {
        [$idsLista, $bindId] = ConversorArray::criaBindValues($idProdutos, 'id_produto');

        $query = "SELECT
                    produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id IN ($idsLista)
                    AND produtos_foto.tipo_foto <> 'SM'
                GROUP BY produtos_foto.id
                ORDER BY produtos_foto.tipo_foto = 'MD' DESC";

        $stmt = $conexao->prepare($query);

        foreach ($bindId as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $produtos ?: [];
    }
    public static function buscaFotoDoProduto(PDO $conexao, int $idProduto): string
    {
        $sql = $conexao->prepare(
            "SELECT produtos_foto.caminho
            FROM produtos_foto
            WHERE produtos_foto.tipo_foto <> 'SM'
                AND produtos_foto.id = :id_produto
            GROUP BY produtos_foto.id
            ORDER BY produtos_foto.tipo_foto = 'MD' DESC;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $fotoProduto = $sql->fetchColumn();
        if (empty($fotoProduto)) {
            throw new Exception('Não foi possível encontrar a foto do produto');
        }

        return $fotoProduto;
    }

    // public static function geraCatalogoMobileStock(PDO $conexao): void
    // {

    //     $query = "TRUNCATE TABLE produtos_ordem_catalogo";
    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();

    //     $query = "INSERT INTO produtos_ordem_catalogo (id_produto)
    //                 SELECT produtos.id
    //                 FROM produtos
    //                     INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
    //                 WHERE produtos.data_primeira_entrada IS NOT NULL
    //                     AND estoque_grade.id_responsavel = 1
    //                 GROUP BY produtos.id
    //                 ORDER BY RAND()";

    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();

    // }
    public static function insereAvisoSeller(int $idProduto, int $idFornecedor, string $nomeTamanho): void
    {
        DB::insert(
            "INSERT INTO alerta_responsavel_estoque_cancelamento (
                alerta_responsavel_estoque_cancelamento.id_produto,
                alerta_responsavel_estoque_cancelamento.nome_tamanho,
                alerta_responsavel_estoque_cancelamento.id_responsavel_estoque
            ) VALUES (
                :id_produto,
                :nome_tamanho,
                :id_responsavel_estoque
            );",
            [
                ':id_produto' => $idProduto,
                ':nome_tamanho' => $nomeTamanho,
                ':id_responsavel_estoque' => $idFornecedor,
            ]
        );
    }
    public static function removeAvisoSeller(PDO $conexao, int $idFornecedor, int $idAlerta): void
    {
        $sql = $conexao->prepare(
            "DELETE FROM alerta_responsavel_estoque_cancelamento
            WHERE alerta_responsavel_estoque_cancelamento.id_responsavel_estoque = :id_responsavel_estoque
                AND alerta_responsavel_estoque_cancelamento.id = :id_alerta;"
        );
        $sql->bindValue(':id_responsavel_estoque', $idFornecedor, PDO::PARAM_INT);
        $sql->bindValue(':id_alerta', $idAlerta, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() < 1) {
            throw new Exception('Não foi possível remover alerta, entre em contato com a equipe de T.I.');
        }
    }
    public static function buscaListaProdutosCanceladosSeller(PDO $conexao, int $idFornecedor): array
    {
        $sql = $conexao->prepare(
            "SELECT
                alerta_responsavel_estoque_cancelamento.id,
                alerta_responsavel_estoque_cancelamento.id_produto,
                alerta_responsavel_estoque_cancelamento.nome_tamanho,
                DATE_FORMAT(alerta_responsavel_estoque_cancelamento.data_criacao, '%d/%m/%Y') AS data_cancelamento,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = alerta_responsavel_estoque_cancelamento.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto_produto
            FROM alerta_responsavel_estoque_cancelamento
            WHERE alerta_responsavel_estoque_cancelamento.id_responsavel_estoque = :id_responsavel_estoque;"
        );
        $sql->bindValue(':id_responsavel_estoque', $idFornecedor, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        $produtos = array_map(function (array $produto): array {
            $produto['id'] = (int) $produto['id'];
            $produto['id_produto'] = (int) $produto['id_produto'];

            return $produto;
        }, $produtos);

        return $produtos ?: [];
    }
    public static function listaDeProdutosMaisVendidos(PDO $conexao, int $pagina, string $dataIncial): array
    {
        $where = '';
        $itensPorPag = 150;
        $offset = $itensPorPag * ($pagina - 1);
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        if ($dataIncial !== '') {
            $where = ' AND DATE(logistica_item.data_criacao) >= DATE(:data_inicial) ';
        }

        $sql = $conexao->prepare(
            "SELECT
                estoque_grade.id_produto,
                estoque_grade.id_responsavel,
                colaboradores.razao_social,
                colaboradores.telefone,
                COALESCE(reputacao_fornecedores.reputacao, 'NOVATO') AS reputacao,
                (
                    SELECT COALESCE(produtos.nome_comercial, produtos.descricao)
                    FROM produtos
                    WHERE produtos.id = logistica_item.id_produto
                ) AS nome_produto,
                EXISTS(
                    SELECT 1
                    FROM produtos
                    WHERE produtos.id = estoque_grade.id_produto
                        AND produtos.permitido_reposicao = 1
                ) AS possui_permissao,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = estoque_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS foto_produto,
                COUNT(logistica_item.uuid_produto) AS qtd_vendas
            FROM estoque_grade
            INNER JOIN logistica_item ON logistica_item.id_produto = estoque_grade.id_produto
                AND logistica_item.id_responsavel_estoque = estoque_grade.id_responsavel
                AND logistica_item.nome_tamanho = estoque_grade.nome_tamanho
                AND logistica_item.situacao <= :situacao
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores.id
            WHERE estoque_grade.id_responsavel > 1
                $where
            GROUP BY estoque_grade.id_produto
            ORDER BY qtd_vendas DESC, possui_permissao ASC
            LIMIT :itens_por_pag OFFSET :offset;"
        );
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        $sql->bindValue(':itens_por_pag', $itensPorPag, PDO::PARAM_INT);
        $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
        if ($dataIncial !== '') {
            $sql->bindValue(':data_inicial', $dataIncial, PDO::PARAM_STR);
        }
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

        $produtos = array_map(function (array $produto): array {
            $idColaborador = (int) $produto['id_responsavel'];
            $razaoSocial = trim($produto['razao_social']);
            $produto['razao_social'] = "($idColaborador) $razaoSocial";
            $produto['id_responsavel'] = $idColaborador;

            $produto['telefone'] = (int) preg_replace('/[^0-9]+/i', '', $produto['telefone']);
            $produto['qr_code'] = Globals::geraQRCODE('https://api.whatsapp.com/send/?phone=55' . $produto['telefone']);
            $produto['telefone'] = ConversorStrings::formataTelefone($produto['telefone']);

            $produto['nome_produto'] = trim($produto['nome_produto']);
            $produto['possui_permissao'] = (bool) $produto['possui_permissao'];
            $produto['id_produto'] = (int) $produto['id_produto'];
            $produto['qtd_vendas'] = (int) $produto['qtd_vendas'];
            unset($produto['id_responsavel']);

            return $produto;
        }, $produtos);

        $sql = $conexao->prepare(
            "SELECT COUNT(DISTINCT estoque_grade.id_produto) qtd_produtos
            FROM estoque_grade
            INNER JOIN logistica_item ON logistica_item.id_produto = estoque_grade.id_produto
                AND logistica_item.id_responsavel_estoque = estoque_grade.id_responsavel
                AND logistica_item.nome_tamanho = estoque_grade.nome_tamanho
                AND logistica_item.situacao <= :situacao
            WHERE estoque_grade.id_responsavel > 1
                $where;"
        );
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        if ($dataIncial !== '') {
            $sql->bindValue(':data_inicial', $dataIncial, PDO::PARAM_STR);
        }
        $sql->execute();
        $total = (int) $sql->fetchColumn();

        $resultado = [
            'produtos' => $produtos,
            'total' => $total,
            'mais_pags' => ceil($total / $itensPorPag) - $pagina > 0,
        ];

        return $resultado ?: [];
    }
    public static function listaDeProdutosSemEntrega(PDO $conexao): array
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $sql = $conexao->prepare(
            "SELECT
                logistica_item.id_cliente,
                colaboradores.razao_social,
                colaboradores.tipo_embalagem,
                (
                    SELECT tipo_frete.nome
                    FROM tipo_frete
                    WHERE tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                    LIMIT 1
                ) AS `tipo_frete`,
                SUM(logistica_item.situacao < :situacao) AS `qtd_pendente`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
            WHERE logistica_item.id_colaborador_tipo_frete IN (32254, 32257)
                AND logistica_item.id_entrega IS NULL
            GROUP BY logistica_item.id_cliente
            HAVING NOT qtd_pendente
            ORDER BY colaboradores.razao_social ASC;"
        );
        $sql->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        $produtos = array_map(function (array $produto): array {
            $produto['id_cliente'] = (int) $produto['id_cliente'];
            $produto['tipo_embalagem'] = Colaborador::converteTipoEmbalagem($produto['tipo_embalagem']);
            unset($produto['qtd_pendente']);

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function dadosMensagemPagamentoAprovado(int $idTransacao): array
    {
        [$produtosFreteSql, $binds] = ConversorArray::criaBindValues(ProdutoModel::IDS_PRODUTOS_FRETE);

        $binds[':id_transacao'] = $idTransacao;

        $retorno = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id_transacao,
                transacao_financeiras_produtos_itens.id_produto,
                transacao_financeiras_produtos_itens.nome_tamanho,
                DATE_FORMAT(transacao_financeiras_produtos_itens.data_criacao,'%d/%m/%Y') AS `data_pagamento`,
                colaboradores.telefone,
                (
                    SELECT colaboradores.telefone
                    FROM colaboradores
                    WHERE colaboradores.id = transacao_financeiras_metadados.valor
                ) AS `telefone_entregador`,
                transacao_financeiras_produtos_itens.uuid_produto,
                produtos_transacao_financeiras_metadados.valor AS `json_produtos_metadados`,
                IF(transacao_financeiras_metadados.valor = '32257',
                    'Transportadora',
                    (
                        SELECT tipo_frete.tipo_ponto
                        FROM tipo_frete
                        WHERE tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
                    )
                ) AS `metodo_de_envio`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`,
                (
                    SELECT produtos.nome_comercial
                    FROM produtos
                    WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto
                ) AS `nome_comercial`,
                IF(
                    (
                        SELECT 1
                        FROM tipo_frete
                        JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
                        WHERE tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
                            AND tipo_frete.tipo_ponto = 'PP'
                            AND tipo_frete.id_colaborador <> 32257
                    ),
                    (
                        SELECT
                            JSON_OBJECT(
                                'endereco', colaboradores_enderecos.logradouro,
                                'numero', colaboradores_enderecos.numero,
                                'bairro', colaboradores_enderecos.bairro,
                                'cidade', colaboradores_enderecos.cidade,
                                'uf', colaboradores_enderecos.uf
                            )
                        FROM colaboradores_enderecos
                        WHERE colaboradores_enderecos.id_colaborador = transacao_financeiras_metadados.valor
                            AND colaboradores_enderecos.eh_endereco_padrao = 1
                        LIMIT 1
                    ), (
                        SELECT
                            transacao_financeiras_metadados.valor
                        FROM transacao_financeiras_metadados
                        WHERE
                            transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                            AND transacao_financeiras_metadados.id_transacao = transacao_financeiras_produtos_itens.id_transacao
                    )
                ) json_endereco
            FROM transacao_financeiras_produtos_itens
            JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = transacao_financeiras_produtos_itens.id_transacao
            JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
            JOIN transacao_financeiras_metadados AS `produtos_transacao_financeiras_metadados` ON produtos_transacao_financeiras_metadados.chave = 'PRODUTOS_JSON'
                AND produtos_transacao_financeiras_metadados.id_transacao = transacao_financeiras_produtos_itens.id_transacao
            WHERE transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
            AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
            AND transacao_financeiras_produtos_itens.id_transacao = :id_transacao
            AND transacao_financeiras_produtos_itens.id_produto NOT IN ($produtosFreteSql)
            GROUP BY transacao_financeiras_produtos_itens.uuid_produto;",
            $binds
        );

        $respostaTratada = array_map(function (array $item): array {
            if (isset($item['endereco']['logradouro'])) {
                $item['endereco']['endereco'] = $item['endereco']['logradouro'];
                unset($item['endereco']['logradouro']);
            }
            $item['produtos_metadados'] = array_filter(
                $item['produtos_metadados'],
                fn(array $produto): bool => $produto['uuid_produto'] === $item['uuid_produto']
            );
            if ($item['produtos_metadados'] = reset($item['produtos_metadados'])) {
                $item['previsao_entrega'] = $item['produtos_metadados']['previsao'] ?? null;
            } else {
                $item['previsao_entrega'] = null;
            }
            unset($item['produtos_metadados']);
            $item['telefone_entregador'] = Str::formatarTelefone($item['telefone_entregador']);
            return $item;
        }, $retorno);

        return $respostaTratada;
    }

    public static function buscaProdutosAtualizarOpensearch(string $timestamp, int $size, int $offset): array
    {
        [$produtosFreteSql, $binds] = ConversorArray::criaBindValues(ProdutoModel::IDS_PRODUTOS_FRETE);

        $binds[':size'] = $size;
        $binds[':offset'] = $offset;

        $where = '';
        if ($timestamp) {
            $where = "AND (
                produtos.data_qualquer_alteracao > DATE_FORMAT(:timestamp, '%Y-%m-%d %H:%i:%s')
                    AND produtos.data_qualquer_alteracao < NOW()
                )";
            $binds[':timestamp'] = $timestamp;
        }

        $retorno = DB::select(
            "SELECT `_produtos`.`id_produto`,
                `_produtos`.`descricao`,
                `_produtos`.`nome_produto`,
                `_produtos`.`valor_venda_ml`,
                `_produtos`.`valor_venda_ms`,
                `_produtos`.`sexo_produto`,
                COALESCE(`_produtos`.`cor_produto`, '') `cor_produto`,
                `_produtos`.`id_fornecedor`,
                COALESCE(linha.nome, '') `linha_produto`,
                COALESCE(`_produtos`.`grade_produto`, '') `grade_produto`,
                COALESCE(`_produtos`.`grade_fulfillment`, '') `grade_fulfillment`,
                COALESCE(GROUP_CONCAT(DISTINCT categorias.nome), '') `categoria_produto`,
                colaboradores.razao_social `nome_fornecedor`,
                colaboradores.usuario_meulook `usuario_fornecedor`,
                reputacao_fornecedores.reputacao `reputacao_fornecedor`,
                produtos_pontuacoes.total_normalizado `pontuacao_produto`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 5
                        AND avaliacao_produtos.origem = 'ML'
                ) `5_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 4
                        AND avaliacao_produtos.origem = 'ML'
                ) `4_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 3
                        AND avaliacao_produtos.origem = 'ML'
                ) `3_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 2
                        AND avaliacao_produtos.origem = 'ML'
                ) `2_estrelas`,
                (
                    SELECT COUNT(avaliacao_produtos.id)
                    FROM avaliacao_produtos
                    WHERE avaliacao_produtos.id_produto = `_produtos`.`id_produto`
                        AND avaliacao_produtos.qualidade = 1
                        AND avaliacao_produtos.origem = 'ML'
                ) `1_estrelas`
            FROM (
                SELECT produtos.id `id_produto`,
                    produtos.nome_comercial `nome_produto`,
                    produtos.descricao,
                    produtos.valor_venda_ml,
                    produtos.valor_venda_ms,
                    produtos.sexo `sexo_produto`,
                    produtos.cores `cor_produto`,
                    produtos.id_fornecedor,
                    produtos.id_linha,
                    REGEXP_REPLACE(GROUP_CONCAT(
                        DISTINCT IF(estoque_grade.estoque > 0, estoque_grade.nome_tamanho, NULL)
                        ORDER BY estoque_grade.sequencia
                        SEPARATOR ' '
                    ), ' +', ' ') `grade_produto`,
                    REGEXP_REPLACE(GROUP_CONCAT(
                        DISTINCT IF(estoque_grade.estoque > 0 AND estoque_grade.id_responsavel = 1, estoque_grade.nome_tamanho, NULL)
                        ORDER BY estoque_grade.sequencia
                        SEPARATOR ' '
                    ), ' +', ' ') `grade_fulfillment`
                FROM produtos
                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
                WHERE produtos.bloqueado = 0
                    AND TRUE IN (
                        produtos.fora_de_linha = 0,
                        produtos.fora_de_linha = 1 AND estoque_grade.estoque > 0
                    )
                    AND produtos.id NOT IN ($produtosFreteSql)
                    $where
                GROUP BY produtos.id
                LIMIT :size OFFSET :offset
            ) _produtos
            INNER JOIN colaboradores ON colaboradores.id = `_produtos`.`id_fornecedor`
            LEFT JOIN linha ON linha.id = `_produtos`.`id_linha`
            LEFT JOIN produtos_categorias ON produtos_categorias.id_produto = `_produtos`.`id_produto`
            LEFT JOIN categorias ON categorias.id = produtos_categorias.id_categoria
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = `_produtos`.`id_fornecedor`
            LEFT JOIN produtos_pontuacoes ON produtos_pontuacoes.id_produto = `_produtos`.`id_produto`
            GROUP BY `_produtos`.`id_produto`",
            $binds
        );

        if (empty($retorno)) {
            return [];
        }

        $categorias = DB::selectOneColumn(
            "SELECT LOWER(
                REGEXP_REPLACE(GROUP_CONCAT(DISTINCT categorias.nome), '[, ]+', '|')
            )
            FROM categorias"
        );
        $categorias = ConversorStrings::tratarTermoOpensearch($categorias);
        $categorias = explode(' ', $categorias);
        foreach ($categorias as $index => $categoria) {
            if (str_ends_with($categoria, 's')) {
                $categorias[] = '\b' . mb_substr($categoria, 0, -1) . '\b';
            }
            $categorias[$index] = "\b$categoria\b";
        }
        $categorias = array_unique($categorias);
        $categorias = implode('|', $categorias);

        $retorno = array_map(function ($item) use ($categorias) {
            $sexoItem = '';
            switch (mb_strtolower($item['sexo_produto'])) {
                case 'fe':
                    $sexoItem = 'feminino';
                    break;
                case 'ma':
                    $sexoItem = 'masculino';
                    break;
                default:
                    $sexoItem = 'feminino masculino';
                    break;
            }

            $fornecedor = ConversorStrings::tratarTermoOpensearch(
                "{$item['nome_fornecedor']} {$item['usuario_fornecedor']}"
            );
            $fornecedor = preg_replace("/$categorias/", '', $fornecedor);

            $item['tem_estoque'] = (bool) $item['grade_produto'];
            $item['tem_estoque_fulfillment'] = (bool) $item['grade_fulfillment'];

            $item['concatenado'] = implode(' ', [
                $item['id_produto'],
                $item['descricao'],
                $item['nome_produto'],
                $item['grade_produto'],
                $item['linha_produto'],
                $sexoItem,
                $item['cor_produto'],
                $item['categoria_produto'],
                $fornecedor,
            ]);
            $item['concatenado'] = explode(' ', $item['concatenado']);
            $item['concatenado'] = array_unique($item['concatenado']);
            $item['concatenado'] = implode(' ', $item['concatenado']);
            $item['concatenado'] = ConversorStrings::tratarTermoOpensearch($item['concatenado']);

            unset($item['descricao'], $item['nome_produto'], $item['nome_fornecedor'], $item['usuario_fornecedor']);

            return $item;
        }, $retorno);

        return $retorno;
    }

    public static function buscaPrecoEResponsavelProduto(int $idProduto, string $tamanho): array
    {
        $dados = DB::selectOne(
            "SELECT
                produtos.valor_venda_ml preco,
                estoque_grade.id_responsavel
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            WHERE produtos.id = :id_produto
                AND estoque_grade.nome_tamanho = :tamanho
            GROUP BY produtos.id
            ORDER BY estoque_grade.id_responsavel ASC;",
            ['id_produto' => $idProduto, 'tamanho' => $tamanho]
        );
        if (empty($dados)) {
            throw new RuntimeException('Não foi possível encontrar as informações do produto');
        }

        return $dados;
    }
    public static function buscaInformacoesProduto(PDO $conexao, int $idProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos.id AS `id_produto`,
                produtos.descricao,
                produtos.id_fornecedor,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`,
                _estoque_grade.possui_estoque_fulfillment,
                _estoque_grade.possui_estoque_externo
            FROM produtos
            INNER JOIN (
                SELECT
                    estoque_grade.id_produto,
                    SUM(DISTINCT estoque_grade.id_responsavel = 1) AS `possui_estoque_fulfillment`,
                    SUM(DISTINCT estoque_grade.id_responsavel > 1) AS `possui_estoque_externo`
                FROM estoque_grade
                WHERE estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = :id_produto
                GROUP BY estoque_grade.id_produto
            ) AS `_estoque_grade` ON _estoque_grade.id_produto = produtos.id
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $produto = $sql->fetch(PDO::FETCH_ASSOC);
        if (empty($produto)) {
            throw new NotFoundHttpException('Não foi possível encontrar as informações do produto');
        }

        $produto['id_produto'] = (int) $produto['id_produto'];
        $produto['id_fornecedor'] = (int) $produto['id_fornecedor'];
        $produto['possui_estoque_fulfillment'] = (bool) $produto['possui_estoque_fulfillment'];
        $produto['possui_estoque_externo'] = (bool) $produto['possui_estoque_externo'];

        return $produto;
    }
    public static function buscaProdutosFornecedorParaNegociar(
        PDO $conexao,
        int $idFornecedor,
        string $pesquisa,
        int $pagina
    ): array {
        $where = '';
        $itensPorPagina = 50;
        $offset = $itensPorPagina * ($pagina - 1);
        if (!empty($pesquisa)) {
            $where = " AND LOWER(CONCAT_WS(
                ' - ',
                produtos.id,
                produtos.nome_comercial,
                GROUP_CONCAT(estoque_grade.nome_tamanho)
            )) REGEXP LOWER(:pesquisa) ";
        }

        $sql = $conexao->prepare(
            "SELECT
                estoque_grade.id_produto,
                produtos.nome_comercial,
                produtos.forma,
                REPLACE(produtos.cores, '/_/', ' ') AS `cores`,
                produtos.valor_custo_produto AS `preco`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`,
                GROUP_CONCAT(estoque_grade.nome_tamanho ORDER BY estoque_grade.sequencia ASC) AS `grades`
            FROM produtos
            INNER JOIN estoque_grade ON estoque_grade.estoque > 0
                AND estoque_grade.id_responsavel > 1
                AND estoque_grade.id_produto = produtos.id
            WHERE produtos.id_fornecedor = :id_fornecedor
	            AND NOT produtos.bloqueado
                AND NOT produtos.fora_de_linha
            GROUP BY produtos.id
            HAVING COALESCE(foto, '') <> '' $where
            ORDER BY produtos.id DESC
            LIMIT :itens_por_pag OFFSET :offset;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->bindValue(':itens_por_pag', $itensPorPagina, PDO::PARAM_INT);
        $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
        if (!empty($pesquisa)) {
            $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        $produtos = array_map(function (array $produto): array {
            $produto['id_produto'] = (int) $produto['id_produto'];
            $produto['preco'] = (float) $produto['preco'];
            $produto['grades'] = explode(',', $produto['grades']);

            return $produto;
        }, $produtos);

        return $produtos;
    }
    public static function informacoesDoProdutoNegociado(PDO $conexao, string $uuidProduto): array
    {
        $sql = $conexao->prepare(
            "SELECT
                logistica_item.id_cliente,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.id_transacao,
                logistica_item.id_responsavel_estoque,
                logistica_item.uuid_produto,
                logistica_item.preco,
                produtos.nome_comercial,
                produtos.forma,
                REPLACE(produtos.cores, '/_/', ' ') AS `cores`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto`
            FROM logistica_item
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            WHERE logistica_item.uuid_produto = :uuid_produto;"
        );
        $sql->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $sql->execute();
        $produto = $sql->fetch(PDO::FETCH_ASSOC);
        if (empty($produto)) {
            throw new NotFoundHttpException('Não foi possível encontrar as informações do produto');
        }
        $produto['id_cliente'] = (int) $produto['id_cliente'];
        $produto['id_produto'] = (int) $produto['id_produto'];
        $produto['id_transacao'] = (int) $produto['id_transacao'];
        $produto['id_responsavel_estoque'] = (int) $produto['id_responsavel_estoque'];
        $produto['preco'] = (float) $produto['preco'];

        return $produto;
    }
    public static function desativaPromocaoMantemValores(PDO $conexao, int $idProduto, int $idUsuario): void
    {
        $sql = $conexao->prepare(
            "SELECT produtos.valor_custo_produto
            FROM produtos
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $valorCustoProduto = (float) $sql->fetchColumn();

        $sql = $conexao->prepare(
            "UPDATE produtos
            SET produtos.preco_promocao = 0,
                produtos.usuario = :id_usuario
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível desativar a promoção');
        }

        $sql = $conexao->prepare(
            "UPDATE produtos
            SET produtos.valor_custo_produto = :valor_custo_produto
            WHERE produtos.id = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':valor_custo_produto', $valorCustoProduto, PDO::PARAM_STR);
        $sql->execute();
        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível atualizar o custo do produto');
        }
    }

    public static function buscaLocalizacaoComEstoqueLiberado(): Generator
    {
        $produtos = DB::cursor(
            "SELECT
                estoque_grade.id_produto,
                produtos.localizacao,
                SUM(estoque_grade.estoque) AS soma_estoque,
                SUM(estoque_grade.vendido) AS soma_vendido,
                produtos_aguarda_entrada_estoque.id IS NOT NULL AS tem_aguardando_entrada
            FROM estoque_grade
            INNER JOIN produtos ON produtos.id = estoque_grade.id_produto
            LEFT JOIN produtos_aguarda_entrada_estoque
            ON produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.id_produto = produtos.id
                AND produtos_aguarda_entrada_estoque.localizacao IS NOT NULL
            WHERE produtos.localizacao IS NOT NULL
            AND estoque_grade.id_responsavel = 1
            GROUP BY produtos.id
            HAVING soma_estoque = 0 AND soma_vendido = 0;"
        );

        return $produtos;
    }
}

<?php

namespace MobileStock\repository;

use InvalidArgumentException;
use Illuminate\Support\Facades\DB as FacadesDB;
use MobileStock\helper\DB;
use MobileStock\helper\Validador;
use PDO;

class EstoqueRepository
{
    // public static function listaMelhoresEstoquistas(string $agrupar = 'month', string $inicio = '2019-01-01', string $final = 'CURRENT_DATE'): array
    // {
    //     if (!$inicio) {
    //         $inicio = '2019-01-01';
    //     }
    //     if (!$final) {
    //         $final = 'CURRENT_DATE';
    //     }

    //     $sql = 'SELECT log_produtos_localizacao.usuario usuario_id,
    //     sum(log_produtos_localizacao.qtd_entrada) quantidadePares,
    //     (SELECT usuarios.nome from usuarios where usuarios.id = log_produtos_localizacao.usuario) usuario,
    //     log_produtos_localizacao.data_hora,
    //     ' . $agrupar . '(log_produtos_localizacao.data_hora) ' . $agrupar . ',
    //     count(*) quantidadePedidos
    //  FROM log_produtos_localizacao
    //  WHERE log_produtos_localizacao.usuario IS NOT NULL AND
    //  log_produtos_localizacao.qtd_entrada <> 0 AND
    //  date(log_produtos_localizacao.data_hora) BETWEEN ' . "'$inicio'" . " AND " .
    //         (function () use ($final) {
    //             return $final === 'CURRENT_DATE' ? $final : "'$final'";
    //         })()
    //         . '
    //  group by log_produtos_localizacao.usuario,
    //  ' . $agrupar . '(log_produtos_localizacao.data_hora)
    //  ORDER BY log_produtos_localizacao.data_hora ASC,
    //  log_produtos_localizacao.usuario ASC;';
    //     return Conexao::criarConexao()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    // }

    // public static function listaMelhoresEstoquistasSemFiltro()
    // {
    //     return DB::select('SELECT log_produtos_localizacao.usuario usuario_id,
    //     sum(log_produtos_localizacao.qtd_entrada) quantidadePares,
    //     (SELECT usuarios.nome from usuarios where usuarios.id = log_produtos_localizacao.usuario) usuario,
    //     log_produtos_localizacao.data_hora,
    //     (log_produtos_localizacao.data_hora),
    //     count(*) quantidadePedidos
    //     FROM log_produtos_localizacao
    //     WHERE log_produtos_localizacao.usuario IS NOT NULL AND
    //     log_produtos_localizacao.qtd_entrada <> 0
    //     GROUP BY log_produtos_localizacao.usuario');
    // }

    // public static function listaLogsEntrada($page = 1, $params = []): array
    // {
    //     $perPage = 200;
    //     $offset = ($page - 1) * $perPage;
    //     $query = 'SELECT log_produtos_localizacao.old_localizacao,
    //          log_produtos_localizacao.new_localizacao,
    //          log_produtos_localizacao.data_hora,
    //          log_produtos_localizacao.qtd_entrada,
    //          (SELECT produtos.descricao FROM produtos where produtos.id = log_produtos_localizacao.id_produto) descricao,
    //          usuarios.nome usuario
    //          FROM log_produtos_localizacao
    //          LEFT OUTER JOIN usuarios ON (usuarios.id = log_produtos_localizacao.usuario)
    //          WHERE 1 = 1 ';

    //     if (!empty($params)) {
    //         foreach ($params as $key => $param) {
    //             $query .= " AND $key like '%$param%' ";
    //         }
    //     }
    //     $query .= 'ORDER BY log_produtos_localizacao.data_hora DESC';

    //     if (empty($params)) {
    //         $query .= ' LIMIT ' . "$perPage" . ' OFFSET ' . "$offset";
    //     }

    //     return Conexao::criarConexao()
    //         ->query($query)
    //         ->fetchAll(PDO::FETCH_ASSOC);
    // }

    // public static function buscaInformacoesEstoquista(int $estoquistaId, string $agrupar = 'month'): array
    // {
    //     return DB::select('SELECT log_produtos_localizacao.id_produto,
    //     log_produtos_localizacao.old_localizacao,
    //     log_produtos_localizacao.new_localizacao,
    //     log_produtos_localizacao.data_hora,
    //     log_produtos_localizacao.usuario estoquistaId,
    //     sum(log_produtos_localizacao.qtd_entrada) qtd_entrada,
    //     count(*) qtd_pedidos,
    //     usuarios.nome nome_usuario,
    //     (SELECT colaboradores.data_cadastro FROM colaboradores WHERE colaboradores.id = usuarios.id_colaborador) data_cadastro,
    //     ' . $agrupar . '(log_produtos_localizacao.data_hora) ' . $agrupar . '
    //     FROM log_produtos_localizacao
    //     INNER JOIN usuarios ON (usuarios.id = log_produtos_localizacao.usuario)
    //     WHERE usuario = :usuarioId
    //     group by ' . $agrupar . '(log_produtos_localizacao.data_hora)
    //     ORDER BY log_produtos_localizacao.data_hora ASC', [
    //         ':usuarioId' => $estoquistaId,
    //     ]);
    // }

    public static function buscaQtdTotalParesGuardadosPorEstoquista(int $estoquistaId)
    {
        return DB::select(
            'SELECT coalesce(SUM(log_produtos_localizacao.qtd_entrada),0) FROM log_produtos_localizacao
        WHERE usuario = :estoquistaId AND date(log_produtos_localizacao.data_hora) = CURRENT_DATE',
            [
                ':estoquistaId' => $estoquistaId,
            ],
            null,
            'fetchColumn',
            0
        );
    }

    /**
     * @param string $id_produto
     * @param string $numeracoes
     * @param int $quantidadeAguardandoEntrada
     */
    public static function testaMovimentacaoEstoqueOcorreu(
        string $id_produto,
        string $numeracoes,
        int $quantidadeAguardandoEntrada
    ): void {
        $quantidadeAguardandoEntradaAtualizada = (int) DB::select(
            "SELECT count(*) estoque FROM produtos_aguarda_entrada_estoque WHERE id_produto = $id_produto AND em_estoque = 'F'",
            [],
            null,
            'fetch'
        )['estoque'];

        $quantidadeRemovida = $numeracoes === '' ? 0 : (int) count(explode(',', $numeracoes));
        if ($quantidadeAguardandoEntrada - $quantidadeRemovida !== $quantidadeAguardandoEntradaAtualizada) {
            throw new InvalidArgumentException('Erro ao movimentar estoque');
        }
    }

    // public static function apagaEstoqueAguardandoEntradaOrigemCompra(array $grade, int $idProduto, int $idCompra, int $idUsuario, string $codBarras, PDO $conexao)
    // {
    //     $sql = '';
    //     foreach ($grade as $item) {
    //         $sql .= "DELETE FROM produtos_aguarda_entrada_estoque
    //         WHERE produtos_aguarda_entrada_estoque.id_produto = $idProduto
    //         AND produtos_aguarda_entrada_estoque.identificao = $idCompra
    //         AND produtos_aguarda_entrada_estoque.tamanho = {$item['tamanho']} LIMIT {$item['qtd']};";
    //     }

    //     $sql .= "INSERT INTO movimentacao_estoque (usuario, tipo, data, origem) VALUES ($idUsuario, 'S', now(), 'Caixa $codBarras voltou')";
    //     DB::exec($sql, [], $conexao);}
    public static function buscaCodBarrasAnaliseParFaltando(
        \PDO $conexao,
        int $idProduto,
        string $nomeTamanho,
        int $qtdProdutos
    ): string {
        $sql = $conexao->prepare(
            "SELECT COALESCE(
                (
                    SELECT CONCAT(
                        (
                            SELECT produtos_grade.cod_barras
                            FROM produtos_grade
                            WHERE produtos_grade.id_produto = :id_produto
                            AND produtos_grade.nome_tamanho = :nome_tamanho
                        ), IF(compras.lote = 'NA', '', CONCAT('_',compras.lote))
                    )
                    FROM compras_itens_grade
                    INNER JOIN compras ON compras.id = compras_itens_grade.id_compra
                    WHERE compras_itens_grade.id_produto = :id_produto
                    AND compras_itens_grade.id_produto = :nome_tamanho
                    GROUP BY compras_itens_grade.id_compra, compras_itens_grade.nome_tamanho
                    HAVING ((
                        SELECT SUM(produtos_aguarda_entrada_estoque.qtd)
                        FROM produtos_aguarda_entrada_estoque
                        WHERE produtos_aguarda_entrada_estoque.id_produto = :id_produto
                        AND produtos_aguarda_entrada_estoque.nome_tamanho = :nome_tamanho
                    ) - (
                        SELECT SUM(estoque_grade.vendido)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = :id_produto
                        AND estoque_grade.nome_tamanho = :nome_tamanho
                        AND estoque_grade.id_responsavel = 1
                    ) - :qtd_produtos) > 0
                    ORDER BY compras.lote ASC
                    LIMIT 1
                ), (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = :id_produto
                    AND produtos_grade.nome_tamanho = :nome_tamanho
                )
            ) cod_barras"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(':qtd_produtos', $qtdProdutos, PDO::PARAM_INT);
        $sql->execute();
        $codBarras = $sql->fetch(PDO::FETCH_ASSOC)['cod_barras'];

        return $codBarras;
    }

    /**
     *  @issue https://github.com/mobilestock/backend/issues/401
     */
    public static function insereGrade(array $grades, int $idProduto, int $idFornecedor): void
    {
        foreach ($grades as $grade) {
            Validador::validar($grade, [
                'sequencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            $sequencia = (int) $grade['sequencia'];
            $nomeTamanho = $grade['nome_tamanho'];

            $existeCompra = FacadesDB::selectOneColumn(
                "SELECT EXISTS(
                    SELECT 1
                    FROM compras_itens_grade
                    WHERE compras_itens_grade.id_produto = :id_produto
                        AND compras_itens_grade.nome_tamanho = :nome_tamanho
                ) AS `existe_reposicao`;",
                [':id_produto' => $idProduto, ':nome_tamanho' => $nomeTamanho]
            );

            if ($existeCompra) {
                continue;
            }

            FacadesDB::delete(
                "DELETE FROM produtos_grade
                WHERE produtos_grade.id_produto = :id_produto
                    AND produtos_grade.nome_tamanho = :nome_tamanho;",
                [':id_produto' => $idProduto, ':nome_tamanho' => $nomeTamanho]
            );

            FacadesDB::insert(
                "INSERT INTO produtos_grade(
                    produtos_grade.id_produto,
                    produtos_grade.sequencia,
                    produtos_grade.nome_tamanho,
                    produtos_grade.cod_barras
                ) VALUES (
                    :id_produto,
                    :sequencia,
                    :nome_tamanho,
                    CONCAT(:id_fornecedor, :id_produto, :sequencia)
                );",
                [
                    ':id_produto' => $idProduto,
                    ':sequencia' => $sequencia,
                    ':nome_tamanho' => $nomeTamanho,
                    ':id_fornecedor' => $idFornecedor,
                ]
            );
        }
    }
}

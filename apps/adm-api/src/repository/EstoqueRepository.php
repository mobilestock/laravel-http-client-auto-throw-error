<?php

namespace MobileStock\repository;

use Exception;
use Illuminate\Support\Facades\DB as FacadesDB;
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

    public static function foraDeLinhaZeraEstoque(PDO $conexao, int $idProduto): void
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM estoque_grade
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $ehExterno = (bool) $sql->fetchColumn();

        if (!$ehExterno) {
            return;
        }
        $query = $conexao->prepare(
            "UPDATE estoque_grade SET
                estoque_grade.estoque = 0,
                estoque_grade.tipo_movimentacao = 'X',
                estoque_grade.descricao = 'Estoque zerado porque o produto foi colocado como fora de linha'
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;"
        );
        $query->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() < 1) {
            throw new Exception('Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.');
        }
    }

    public static function insereGrade(array $grades, int $idProduto, int $idFornecedor)
    {
        foreach ($grades as $grade) {
            Validador::validar($grade, [
                'sequencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            ]);

            $sequencia = (int) $grade['sequencia'];
            $nomeTamanho = (string) $grade['nome_tamanho'];

            $sqlExistis = "SELECT 1
                FROM produtos_grade
                WHERE id_produto = :id_produto
                AND nome_tamanho = :nome_tamanho;";
            $bindsExistis = [
                ':id_produto' => $idProduto,
                ':nome_tamanho' => $nomeTamanho,
            ];
            $existeReposicao = FacadesDB::select($sqlExistis, $bindsExistis);

            if ($existeReposicao) {
                continue;
            }

            $sqlDelete = "DELETE FROM produtos_grade
                WHERE id_produto = :id_produto
                AND nome_tamanho = :nome_tamanho";
            $bindsDelete = [
                ':id_produto' => $idProduto,
                ':nome_tamanho' => $nomeTamanho,
            ];
            FacadesDB::delete($sqlDelete, $bindsDelete);

            $sqlInsert = "INSERT INTO produtos_grade (id_produto, sequencia, nome_tamanho, cod_barras)
                VALUES (:id_produto, :sequencia, :nome_tamanho, :cod_barras);
            ";
            $bindsInsert = [
                ':id_produto' => $idProduto,
                ':sequencia' => $sequencia,
                ':nome_tamanho' => $nomeTamanho,
                ':cod_barras' => $idFornecedor . $idProduto . $sequencia,
            ];
            FacadesDB::insert($sqlInsert, $bindsInsert);
        }
    }
}

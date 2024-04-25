<?php

namespace MobileStock\service\Compras;

use Error;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ComprasService
{
    const SITUACAO_ABERTO = 1;
    const SITUACAO_ENTREGUE = 2;
    const SITUACAO_CANCELADO = 3;
    const SITUACAO_PARCIALMENTE_ENTREGUE = 14;
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    // public static function consultaCompras(PDO $conexo, int $idColaborador, string $produto)
    // {
    //     $sql = "SELECT compras.id, compras.lote,
    //             DATE_FORMAT(compras.data_previsao,'%d/%m/%Y') data_previsao,
    //             compras.situacao,
    //             SUM(compras_itens.caixas) total_caixas,
    //             SUM(compras_itens.quantidade_total) total_pares,
    //             SUM(compras_itens.valor_total) total_valor,
    //             SUM(produtos.valor_custo_produto) total_valor_atualizado
    //         FROM compras
    //             INNER JOIN compras_itens ON compras_itens.id_compra = compras.id
    //             INNER JOIN produtos ON produtos.id = compras_itens.id_produto
    //         WHERE compras.id_fornecedor = :idColaborador
    //             AND lower(produtos.descricao) like lower('%{$produto}%')
    //             AND compras.lote IS NOT NULL
    //             GROUP BY compras.lote";
    //     $consulta = $conexo->prepare($sql);
    //     $consulta->bindParam(':idColaborador', $idColaborador, PDO::PARAM_INT);
    //     $consulta->execute();
    //     $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //     return $resposta;
    // }

    // public static function consultaComprasItens(PDO $conexao, string $lote)
    // {
    //     $sql = "SELECT compras.id compra,
    //                 produtos.id id_produto,
    //                 produtos.descricao,
    //                 compras_itens.preco_unit custo,
    //                 COALESCE(MAX(produtos_foto.caminho),'') foto,
    //                 SUM(compras_itens.valor_total) total_valor,
    //                 SUM(compras_itens.quantidade_total) pares
    //             FROM compras
    //                 INNER JOIN compras_itens ON compras_itens.id_compra = compras.id
    //                 INNER JOIN produtos ON produtos.id = compras_itens.id_produto
    //                 LEFT JOIN produtos_foto ON produtos_foto.id = produtos.id AND produtos_foto.sequencia = 1
    //             WHERE compras.lote = :lote
    //             group by produtos.id ORDER BY produtos.id;";
    //     $consulta = $conexao->prepare($sql);
    //     $consulta->bindParam(':lote', $lote, PDO::PARAM_STR);
    //     $consulta->execute();
    //     $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //     foreach ($resposta as $key => $r) {
    //         $query = "SELECT cig.tamanho, (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.id_produto = cig.id_produto AND estoque_grade.tamanho = cig.tamanho LIMIT 1) nome_tamanho,SUM(cig.quantidade) pares FROM compras_itens_grade cig
    //         INNER JOIN compras c ON c.id=cig.id_compra
    //         WHERE cig.id_produto = :idProduto AND c.lote = :lote
    //         GROUP BY cig.id_produto, cig.tamanho ORDER BY cig.tamanho;";
    //         $consulta = $conexao->prepare($query);
    //         $consulta->bindParam(':lote', $lote, PDO::PARAM_STR);
    //         $consulta->bindParam(':idProduto', $r['id_produto'], PDO::PARAM_STR);
    //         $consulta->execute();
    //         $grade = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //         $resposta[$key]['grade'] = $grade;
    //     }
    //     return $resposta;
    // }

    // public static function consultaComprasValores(PDO $conexo, string $lote)
    // {
    //     $sql = "SELECT
    //             compras.id compra,
    //             SUM(compras_itens.valor_total) valor_entrada,
    //             SUM(compras_itens.quantidade_total) pares_entrada,
    //             (SELECT SUM(fi.comissao_fornecedor) FROM faturamento_item fi
    //             INNER JOIN faturamento f ON f.id=fi.id_faturamento
    //             WHERE fi.lote = :lote AND fi.situacao=6) valor_vendido,
    //             (SELECT COUNT(fi.id_produto) valor_vendido FROM faturamento_item fi
    //             INNER JOIN faturamento f ON f.id=fi.id_faturamento
    //             WHERE fi.lote = :lote AND fi.situacao=6) pares_vendidos
    //         FROM compras
    //             INNER JOIN compras_itens ON compras_itens.id_compra = compras.id
    //             INNER JOIN produtos ON produtos.id = compras_itens.id_produto
    //         WHERE compras.lote = :lote;";
    //     $consulta = $conexo->prepare($sql);
    //     $consulta->bindParam(':lote', $lote, PDO::PARAM_STR);
    //     $consulta->execute();
    //     $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
    //     $resultado['estoque_restante'] = $resultado['pares_entrada'] - $resultado['pares_vendidos'];
    //     return $resultado;
    // }

    // public static function consultaComprasItensEmEstoque(PDO $conexao, int $lote)
    // {
    //     $sql = "SELECT
    //                 compras.id compra,
    //                 produtos.id id_produto,
    //                 produtos.descricao,
    //                 COALESCE(MAX(produtos_foto.caminho),'') foto,
    //                 SUM(compras_itens.valor_total) valor
    //             FROM compras
    //                 INNER JOIN compras_itens ON compras_itens.id_compra = compras.id
    //                 INNER JOIN produtos ON produtos.id = compras_itens.id_produto
    //                 LEFT JOIN produtos_foto ON produtos_foto.id = produtos.id AND produtos_foto.sequencia = 1
    //             WHERE compras.lote = :lote
    //             group by produtos.id ORDER BY produtos.id DESC;";
    //     $consulta = $conexao->prepare($sql);
    //     $consulta->bindParam(':lote', $lote, PDO::PARAM_INT);
    //     $consulta->execute();
    //     $resultado = $consulta->fetchAll(PDO::FETCH_ASSOC);

    //     foreach ($resultado as $key => $r) {
    //         $consulta = $conexao->prepare(
    //             "SELECT
    //                 compras_itens_grade.tamanho,
    //                 SUM(compras_itens_grade.quantidade) pares,
    //                 (
    //                     SELECT estoque_grade.nome_tamanho
    //                     FROM estoque_grade
    //                     WHERE estoque_grade.id_produto = compras_itens_grade.id_produto
    //                     AND estoque_grade.tamanho = compras_itens_grade.tamanho
    //                     LIMIT 1
    //                 )nome_tamanho
    //             FROM compras_itens_grade
    //             WHERE EXISTS(
    //                 SELECT 1
    //                 FROM compras
    //                 WHERE compras.lote = :lote
    //                 AND compras.id = compras_itens_grade.id_compra
    //             ) AND compras_itens_grade.id_produto = :idProduto
    //             GROUP BY compras_itens_grade.id_produto, compras_itens_grade.tamanho
    //             ORDER BY compras_itens_grade.tamanho ASC;"
    //         );
    //         $consulta->bindParam(':idProduto', $r['id_produto'], PDO::PARAM_INT);
    //         $consulta->bindParam(':lote', $lote, PDO::PARAM_INT);
    //         $consulta->execute();
    //         $grade = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //         $resultado[$key]['grade'] = $grade;
    //     }

    //     foreach ($resultado as $key => $r) {
    //         $sql = "SELECT eg.tamanho, COALESCE(COUNT(fi.id_produto),0) pares FROM estoque_grade eg
    //         LEFT OUTER JOIN faturamento_item fi ON eg.id_produto = fi.id_produto AND eg.tamanho = fi.tamanho
    //         WHERE fi.lote = :lote AND fi.id_produto = :idProduto AND fi.situacao=6 AND eg.id_responsavel = 1
    //         GROUP BY eg.tamanho ORDER BY eg.tamanho ASC;";
    //         $consulta = $conexao->prepare($sql);
    //         $consulta->bindParam(':lote', $lote, PDO::PARAM_INT);
    //         $consulta->bindParam(':idProduto', $r['id_produto'], PDO::PARAM_INT);
    //         $consulta->execute();
    //         $resultado[$key]['vendidos'] = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //     }

    //     return $resultado;
    // }

    // public static function consultaComprasDeProduto(int $id_produto, bool $buscarTodas)
    // {
    //     $condicoes = $buscarTodas === true ? '1=1' : "
    //     compras_itens_caixas.situacao = 2
    //     AND compras.id IN (SELECT produtos_aguarda_entrada_estoque.identificao FROM produtos_aguarda_entrada_estoque WHERE produtos_aguarda_entrada_estoque.id_produto = compras_itens_caixas.id_produto AND produtos_aguarda_entrada_estoque.em_estoque = 'F')";

    //     return array_map(function ($item) {
    //         $item['caixas'] = array_map(function ($caixa) {
    //             $caixa['voltar'] = false;
    //             return $caixa;
    //         }, json_decode($item['caixas'], true));
    //         $item['grade'] = json_decode($item['grade'], true);

    //         return $item;
    //     }, DB::select("SELECT
    //         compras.id,
    //         DATE_FORMAT(compras.data_emissao, '%d/%m/%Y') data_emissao,
    //         compras.situacao,
    //         CONCAT('[', GROUP_CONCAT(DISTINCT JSON_OBJECT(
    //             'cod_barras', compras_itens_caixas.codigo_barras,
    //             'qtd', compras_itens_caixas.quantidade,
    //             'usario_deu_baixa', (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = compras_itens_caixas.usuario),
    //             'data_baixa', DATE_FORMAT(compras_itens_caixas.data_baixa, '%d/%m/%Y %H:%i:%s'),
    //             'id_compra', compras_itens_caixas.id_compra
    //         )), ']') caixas,
    //         CONCAT('[', GROUP_CONCAT(DISTINCT JSON_OBJECT(
    // 				'tamanho', compras_itens_grade.tamanho,
    // 				'nome_tamanho', (SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.tamanho = compras_itens_grade.tamanho AND estoque_grade.id_produto = compras_itens_grade.id_produto LIMIT 1),
    // 				'quantidade', compras_itens_grade.quantidade
    // 		)), ']') grade
    //     FROM compras
    //     INNER JOIN compras_itens_caixas ON compras_itens_caixas.id_compra = compras.id
    //     INNER JOIN compras_itens_grade ON compras_itens_grade.id_compra = compras.id AND compras_itens_grade.id_produto = compras_itens_caixas.id_produto
    //     WHERE compras_itens_caixas.id_produto = :id_produto AND $condicoes
    //     GROUP BY compras.id
    //     /** HAVING (SELECT SUM(produtos_aguarda_entrada_estoque.qtd) FROM produtos_aguarda_entrada_estoque WHERE produtos_aguarda_entrada_estoque.id_produto = :id_produto AND produtos_aguarda_entrada_estoque.em_estoque = 'F') - SUM(compras_itens_caixas.quantidade) >= 0 */
    //     ORDER BY compras.id DESC, compras_itens_caixas.id_sequencia, compras_itens_caixas.volume DESC;", [
    //         ':id_produto' => $id_produto
    //     ]));
    // }

    // public static function voltaCompraCaixa(string $codigo, int $usuarioEfetuaVolta, \PDO $conexao)
    // {
    //     DB::exec(
    //         "UPDATE compras_itens_caixas
    //                 SET situacao = 1,
    //                 usuario = :usuario,
    //                 data_baixa = null,
    //                 id_lancamento = 0,
    //                 numero_mov = 0
    //             WHERE codigo_barras= :codigo;",
    //         [
    //             ':usuario' => $usuarioEfetuaVolta,
    //             ':codigo' => $codigo
    //         ],
    //         $conexao
    //     );
    // }

    // public static function devolveCompraCaixa(\PDO $conexao, int $idUsuario, array $compras): void
    // {
    //     $sql = "";

    //     foreach ($compras as $compra) {
    //         Validador::validar($compra, [
    //             "codigo_barras" => [Validador::OBRIGATORIO]
    //         ]);

    //         $codigoBarras = (string) $compra["codigo_barras"];

    //         $sql .= "UPDATE compras_itens_caixas SET
    //             compras_itens_caixas.situacao = 1,
    //             compras_itens_caixas.usuario = $idUsuario,
    //             compras_itens_caixas.data_baixa = NULL,
    //             compras_itens_caixas.id_lancamento = 0,
    //             compras_itens_caixas.numero_mov = 0
    //         WHERE compras_itens_caixas.codigo_barras = $codigoBarras;";
    //     }

    //     $sql = $conexao->prepare($sql);
    //     $sql->execute();
    // }

    // public static function consultaComprasPorCaixas(array $codigos)
    // {
    //     $codigos = implode(",", array_map(function (array $codigo): string {
    //         return "'$codigo'";
    //     }, $codigos));

    //     return array_map(function ($item) {
    //         $item['grade'] = json_decode($item['grade'], true);
    //         return $item;
    //     }, DB::select("SELECT
    //         compras_itens_caixas.id_produto,
    //         compras_itens_caixas.id_compra,
    //         compras_itens_caixas.codigo_barras,
    //         compras_itens_caixas.quantidade,
    //         CONCAT('[', GROUP_CONCAT(DISTINCT JSON_OBJECT(
    // 				'tamanho', compras_itens_grade.tamanho,
    // 				'qtd', compras_itens_grade.quantidade
    // 		)), ']') grade
    //     FROM compras
    //     INNER JOIN compras_itens_caixas ON compras_itens_caixas.id_compra = compras.id
    //     INNER JOIN compras_itens_grade ON compras_itens_grade.id_compra = compras.id
    //     where compras_itens_caixas.codigo_barras IN ($codigos)
    //     GROUP BY compras_itens_caixas.codigo_barras"));
    // }

    public function consultaDadosCodBarras(string $codBarras): array
    {
        $stmt = $this->conexao->prepare(
            "SELECT
                compras_itens_caixas.id_compra,
                compras_itens_caixas.id_sequencia,
                compras_itens_caixas.volume,
                compras_itens_caixas.situacao id_situacao,
                compras_itens_caixas.quantidade pares,
                compras_itens_caixas.id_fornecedor,
                compras_itens_caixas.id_produto,
                        EXISTS(
                            SELECT 1
                            FROM estoque_grade
                            WHERE estoque_grade.id_produto = compras_itens_grade.id_produto
                                AND estoque_grade.nome_tamanho = compras_itens_grade.nome_tamanho
                            AND estoque_grade.id_responsavel = 1
                        )consignado,
                CONCAT(compras_itens_caixas.id_compra, ' - ', compras_itens_caixas.id_sequencia, ' - ', compras_itens_caixas.volume)cod_compra,
                compras_itens_grade.nome_tamanho,
                compras_itens_grade.quantidade,
                        compras_itens.preco_unit,
                        compras_itens.valor_total,
                (
                    SELECT produtos.descricao
                    FROM produtos
                    WHERE produtos.id = compras_itens_caixas.id_produto
                )produto,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = compras_itens_caixas.id_fornecedor
                )fornecedor
            FROM compras_itens_caixas
            INNER JOIN compras_itens_grade ON compras_itens_grade.id_compra = compras_itens_caixas.id_compra
                AND compras_itens_grade.id_produto = compras_itens_caixas.id_produto
                AND compras_itens_grade.id_sequencia = compras_itens_caixas.id_sequencia
                AND compras_itens_grade.quantidade > 0
            INNER JOIN compras_itens ON compras_itens.id_compra = compras_itens_caixas.id_compra
                AND compras_itens.id_produto = compras_itens_caixas.id_produto
                AND compras_itens.sequencia = compras_itens_caixas.id_sequencia
            WHERE compras_itens_caixas.situacao <> 2
                AND compras_itens_caixas.codigo_barras = ? ;"
        );

        $stmt->execute([$codBarras]);

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado ?? [];
    }

    public function baixaCaixa(string $codBarras, int $idUsuarioLogado, int $id_movimentacao): string
    {
        return "UPDATE compras_itens_caixas
                SET situacao=2,
                usuario={$idUsuarioLogado},
                data_baixa=NOW(),
                id_lancamento=0,
                numero_mov={$id_movimentacao}
        WHERE codigo_barras='$codBarras';";
    }

    public function atualizaSituacaoDaCompra(int $idCompra): void
    {
        $sql = "WITH Q1 AS
            (
                SELECT  id_produto,id_sequencia,
                SUM( CASE WHEN situacao = 2 THEN 1 ELSE 0 END) entregues,
                SUM( CASE WHEN situacao = 1 THEN 1 ELSE 0 END) abertos,
                count(id_compra) quantidade_total
                from compras_itens_caixas where id_compra = {$idCompra}
                group by id_sequencia
            )
            SELECT *, CASE WHEN entregues = 0  THEN 1
                            ELSE CASE WHEN entregues = quantidade_total THEN 2 ELSE 14 END
                        END situacao from Q1;";
        $resultado = $this->conexao->query($sql);

        $result = $resultado->fetchAll();
        $situacao_compra = 2;
        $sql = '';
        foreach ($result as $key => $caixa_compra) {
            if ($caixa_compra['situacao'] != 2) {
                $situacao_compra = 14;
            }
            $sql .= "UPDATE compras_itens SET id_situacao = {$caixa_compra['situacao']} WHERE sequencia = {$caixa_compra['id_sequencia']} AND id_produto = {$caixa_compra['id_produto']} AND id_compra = {$idCompra};";
        }
        $sql .= "UPDATE compras SET situacao = {$situacao_compra} where id = {$idCompra} ;";
        $this->conexao->exec($sql);
    }

    public function salvaHistoricoEntradaCaixa(string $codigoBarras, int $fornecedor, int $status)
    {
        $this->conexao->exec(
            "INSERT INTO compras_entrada_historico
            (codigo_barras,fornecedor,sequencia,status,data)
            VALUES ('{$codigoBarras}' ,'{$fornecedor}', 1 , $status, now());"
        );
    }

    public static function buscaReposicaoMaisAntiga(PDO $conexao, int $idProduto)
    {
        $sql = "SELECT DATE_FORMAT(c.data_previsao,'%d-%m-%Y') data_previsao FROM compras_itens ci
        INNER JOIN compras c ON c.id = ci.id_compra
        INNER JOIN compras_itens_grade cig ON cig.id_produto = {$idProduto}
        AND cig.id_compra = ci.id_compra
        AND cig.id_sequencia = ci.sequencia
        AND ci.id_situacao < 3 ORDER BY c.data_previsao DESC LIMIT 1;";
        return $conexao->prepare($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscaCodigoBarrasCompra(PDO $conexao, int $idCompra)
    {
        $query = $conexao->prepare(
            "SELECT
                compras_itens_caixas.volume,
                compras_itens_caixas.id_sequencia,
                compras_itens_caixas.id_produto,
                compras_itens_caixas.codigo_barras,
                compras_itens_caixas.quantidade,
                compras_itens_caixas.situacao,
                CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) desc_produto,
                COALESCE(
                    IF(EXISTS(
                        SELECT 1
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos.id
                            AND estoque_grade.id_responsavel <> 1
                    ), (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                            AND NOT produtos_foto.tipo_foto = 'SM'
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                        LIMIT 1
                    ), (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = produtos.id
                            AND produtos_foto.tipo_foto = 'MD'
                        LIMIT 1
                    )), ''
                )caminho,
                IF(produtos.tipo_grade = 4, 13, 0) tamanhoParaFoto,
                (
                    SELECT GROUP_CONCAT(DISTINCT produtos_grade.nome_tamanho)
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = compras_itens_caixas.id_produto
                    ORDER BY produtos_grade.sequencia ASC
                ) grade_autocomplete
            FROM compras_itens_caixas
            INNER JOIN produtos ON produtos.id = compras_itens_caixas.id_produto
            WHERE compras_itens_caixas.id_compra = :id_compra
            ORDER BY compras_itens_caixas.id_compra, compras_itens_caixas.id_sequencia, compras_itens_caixas.volume"
        );
        $query->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $query->execute();
        $resultado = $query->fetchAll(PDO::FETCH_ASSOC);

        $etiquetas = array_map(function (array $caixa) {
            $caixa['grade_autocomplete'] = explode(',', $caixa['grade_autocomplete']);
            return $caixa;
        }, $resultado);

        return $etiquetas;
    }
    public static function updateCompra(PDO $conexao, int $idCompra, int $idFornecedor, string $dataPrevisao)
    {
        $sql = $conexao->prepare(
            "UPDATE compras SET
                compras.id_fornecedor = :id_fornecedor,
                compras.data_emissao = NOW(),
                compras.data_previsao = :data_previsao
            WHERE compras.id = :id_compra;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->bindValue(':data_previsao', $dataPrevisao, PDO::PARAM_STR);
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        return $sql->execute();
    }
    public static function excluirItemCompra(int $idCompra, array $produto): void
    {
        $sql = DB::getPdo()->prepare(
            "SELECT COUNT(1) AS `qtd_grades`
            FROM compras_itens_grade
            WHERE compras_itens_grade.id_compra = :id_compra
                AND compras_itens_grade.id_produto = :id_produto
                AND compras_itens_grade.id_sequencia = :sequencia;

            SELECT COUNT(1) AS `qtd_caixas`
            FROM compras_itens_caixas
            WHERE compras_itens_caixas.id_compra = :id_compra
                AND compras_itens_caixas.id_produto = :id_produto
                AND compras_itens_caixas.id_sequencia = :sequencia;

            DELETE FROM compras_itens
            WHERE compras_itens.id_compra = :id_compra
                AND compras_itens.id_produto = :id_produto
                AND compras_itens.sequencia = :sequencia;

            DELETE FROM compras_itens_grade
            WHERE compras_itens_grade.id_compra = :id_compra
                AND compras_itens_grade.id_produto = :id_produto
                AND compras_itens_grade.id_sequencia = :sequencia;

            DELETE FROM compras_itens_caixas
            WHERE compras_itens_caixas.id_compra = :id_compra
                AND compras_itens_caixas.id_produto = :id_produto
                AND compras_itens_caixas.id_sequencia = :sequencia;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->bindValue(':id_produto', $produto['id'], PDO::PARAM_INT);
        $sql->bindValue(':sequencia', $produto['sequencia'], PDO::PARAM_INT);
        $sql->execute();
        $qtdGrade = (int) $sql->fetchColumn();
        $sql->nextRowset();
        $qtdCaixas = (int) $sql->fetchColumn();
        $sql->nextRowset();
        if ($sql->rowCount() !== 1) {
            throw new RuntimeException('Erro ao excluir item da compra (1)');
        }

        $sql->nextRowset();
        if ($sql->rowCount() !== $qtdGrade) {
            throw new RuntimeException('Erro ao excluir item da compra (2)');
        }

        $sql->nextRowset();
        if ($sql->rowCount() !== $qtdCaixas) {
            throw new RuntimeException('Erro ao excluir item da compra (3)');
        }
    }
    public static function buscaUltimaCompra(PDO $conexao)
    {
        $sql = $conexao->prepare('SELECT MAX(compras.id) id_compras FROM compras');
        $sql->execute();
        $consulta = $sql->fetch(PDO::FETCH_ASSOC);

        return $consulta['id_compras'];
    }
    public static function insereNovaCompra(PDO $conexao, array $compra)
    {
        $sql = $conexao->prepare(
            "INSERT INTO compras (
                compras.id,
                compras.id_fornecedor,
                compras.data_emissao,
                compras.data_previsao,
                compras.situacao,
                compras.lote
            ) VALUES (
                :id_compra,
                :id_fornecedor,
                NOW(),
                :data_previsao,
                1,
                :lote
            )"
        );
        $sql->bindValue(':id_compra', $compra['id_compra'], PDO::PARAM_INT);
        $sql->bindValue(':id_fornecedor', $compra['id_fornecedor'], PDO::PARAM_INT);
        $sql->bindValue(':data_previsao', $compra['data_previsao'], PDO::PARAM_STR);
        $sql->bindValue(':lote', $compra['id_compra'], PDO::PARAM_INT);
        return $sql->execute();
    }
    public static function buscaUltimaSequenciaCompra(PDO $conexao, int $idCompra): int
    {
        $sql = $conexao->prepare(
            "SELECT COALESCE(MAX(compras_itens.sequencia), 0) sequencia
            FROM compras_itens
            WHERE compras_itens.id_compra = :id_compra"
        );
        $sql->bindValue('id_compra', $idCompra, PDO::PARAM_INT);
        $sql->execute();
        $sequencia = $sql->fetch(PDO::FETCH_ASSOC)['sequencia'];

        return $sequencia;
    }
    public static function buscaGradeProduto(PDO $conexao, int $idProduto)
    {
        $sql = $conexao->prepare(
            "SELECT
                produtos_grade.nome_tamanho,
                COALESCE(estoque_grade.estoque, 0) estoque
            FROM produtos_grade
            LEFT JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                AND estoque_grade.id_responsavel = 1
                AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
            WHERE produtos_grade.id_produto = :id_produto;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $grades;
    }
    public static function insereCompraProdutosGrade(
        PDO $conexao,
        int $idCompra,
        array $grades,
        int $caixas,
        int $sequencia
    ) {
        $sql = '';
        $bind = [];
        foreach ($grades as $index => $grade) {
            if (is_int($grade['quantidade'])) {
                $bindTemp = [
                    ":id_compra_$index" => $idCompra,
                    ":id_produto_$index" => $grade['produto'],
                    ":sequencia_$index" => $sequencia,
                    ":nome_tamanho_$index" => $grade['nome_tamanho'],
                    ":quantidade_$index" => $grade['quantidade'],
                    ":total_$index" => $grade['quantidade'] * $caixas,
                ];
                $bind = array_merge($bind, $bindTemp);

                $sql .= "INSERT INTO compras_itens_grade (
                    compras_itens_grade.id_compra,
                    compras_itens_grade.id_produto,
                    compras_itens_grade.id_sequencia,
                    compras_itens_grade.nome_tamanho,
                    compras_itens_grade.quantidade,
                    compras_itens_grade.quantidade_total
                ) VALUES (
                    :id_compra_$index,
                    :id_produto_$index,
                    :sequencia_$index,
                    :nome_tamanho_$index,
                    :quantidade_$index,
                    :total_$index
                );";
            }
        }
        return $conexao->prepare($sql)->execute($bind);
    }
    public static function insereCompraProdutos(
        PDO $conexao,
        int $idProduto,
        int $sequencia,
        int $idCompra,
        float $precoUnit,
        int $caixas,
        int $idSituacao,
        int $quantidadeTotal = 0,
        float $valorTotal = 0
    ) {
        $sql = $conexao->prepare(
            "INSERT INTO compras_itens (
                compras_itens.caixas,
                compras_itens.id_compra,
                compras_itens.id_produto,
                compras_itens.id_situacao,
                compras_itens.preco_unit,
                compras_itens.quantidade_total,
                compras_itens.sequencia,
                compras_itens.valor_total
            ) VALUES (
                :caixas,
                :id_compra,
                :id_produto,
                :id_situacao,
                :preco_unit,
                :quantidade_total,
                :sequencia,
                :valor_total
            )"
        );
        $sql->bindValue(':caixas', $caixas, PDO::PARAM_INT);
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':id_situacao', $idSituacao, PDO::PARAM_INT);
        $sql->bindValue(':preco_unit', $precoUnit);
        $sql->bindValue(':quantidade_total', $quantidadeTotal, PDO::PARAM_INT);
        $sql->bindValue(':sequencia', $sequencia, PDO::PARAM_INT);
        $sql->bindValue(':valor_total', $valorTotal);

        return $sql->execute();
    }
    public static function insereCompraCodigoBarras(
        PDO $conexao,
        int $idFornecedor,
        int $idProduto,
        int $idCompra,
        int $sequencia,
        int $caixas
    ) {
        $query = '';
        for ($i = 1; $i <= $caixas; $i++) {
            $sql = $conexao->prepare(
                "SELECT SUM(compras_itens_grade.quantidade) quantidade
                FROM compras_itens_grade
                WHERE compras_itens_grade.id_compra = :id_compra AND compras_itens_grade.id_sequencia = :sequencia;"
            );
            $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
            $sql->bindValue(':sequencia', $sequencia, PDO::PARAM_INT);
            $sql->execute();
            $quantidade = $sql->fetch(PDO::FETCH_ASSOC)['quantidade'];
            $codBarFornecedor = str_pad($idFornecedor, 5, 0, STR_PAD_LEFT);
            $codBarCompra = str_pad($idCompra, 7, 0, STR_PAD_LEFT);
            $codBarProduto = str_pad($idProduto, 6, 0, STR_PAD_LEFT);
            $codBarSequencia = str_pad($sequencia, 3, 0, STR_PAD_LEFT);
            $codBarVolumes = str_pad($i, 3, 0, STR_PAD_LEFT);
            $codigoBarras = "{$codBarFornecedor}{$codBarCompra}{$codBarProduto}{$codBarSequencia}{$codBarVolumes}";

            $query .= "INSERT INTO compras_itens_caixas(
                    compras_itens_caixas.id_fornecedor,
                    compras_itens_caixas.id_compra,
                    compras_itens_caixas.id_produto,
                    compras_itens_caixas.id_sequencia,
                    compras_itens_caixas.volume,
                    compras_itens_caixas.situacao,
                    compras_itens_caixas.quantidade,
                    compras_itens_caixas.codigo_barras
                ) VALUES (
                    $idFornecedor,
                    $idCompra,
                    $idProduto,
                    $sequencia,
                    $i,
                    1,
                    $quantidade,
                    $codigoBarras
                );";
        }
        $insert = $conexao->prepare($query);

        return $insert->execute();
    }
    public static function buscaDemandaProdutosFornecedor(
        PDO $conexao,
        int $idFornecedor,
        string $pesquisa = '',
        bool $aplicarLimit = false
    ): array {
        if (!$idFornecedor) {
            throw new NotFoundHttpException('Selecione um fornecedor');
        }

        $pesquisaSQL = '';
        $limit = $aplicarLimit ? 'LIMIT 100' : '';

        if ($pesquisa) {
            if (is_numeric($pesquisa)) {
                $pesquisaSQL = 'AND produtos.id = :pesquisa';
            } else {
                $pesquisaSQL = "AND (
                    produtos.descricao regexp :pesquisa
                    OR produtos.nome_comercial regexp :pesquisa
                    OR produtos.cores regexp :pesquisa
                    )";
            }
        }

        $sql = $conexao->prepare(
            "WITH Q1 AS (
                SELECT
                    produtos.nome_comercial,
                    produtos.cores,
                    produtos.id_fornecedor,
                    produtos.id,
                    produtos.descricao,
                    produtos.tipo_grade,
                    CAST(produtos.valor_custo_produto AS DECIMAL(10,2)) valor_custo_produto,
                        CASE
                            WHEN COALESCE(produtos.descricao, '') = '' THEN 1
                            WHEN COALESCE(produtos.nome_comercial, '') = '' THEN 1
                            WHEN COALESCE(produtos.id_linha, '') = '' THEN 1
                            WHEN COALESCE(produtos.grade_min, '') = '' THEN 1
                            WHEN COALESCE(produtos.grade_max, '') = '' THEN 1
                            WHEN COALESCE(produtos.valor_custo_produto, '') = '' THEN 1
                            WHEN NOT EXISTS(
                                SELECT 1
                                FROM produtos_categorias
                                INNER JOIN categorias ON categorias.id = produtos_categorias.id_categoria
                                    AND categorias.id_categoria_pai IS NULL
                                WHERE produtos_categorias.id_produto = produtos.id
                            ) THEN 1
                            WHEN NOT EXISTS(
                                SELECT 1
                                FROM produtos_categorias
                                INNER JOIN categorias ON categorias.id = produtos_categorias.id_categoria
                                    AND categorias.id_categoria_pai IS NOT NULL
                                WHERE produtos_categorias.id_produto = produtos.id
                            ) THEN 1
                            ELSE 0
                        END incompleto
                FROM produtos
                WHERE produtos.bloqueado = 0
                    AND produtos.fora_de_linha = 0
                    AND produtos.permitido_reposicao = 1
                    AND produtos.id_fornecedor = :id_fornecedor
                    $pesquisaSQL
                GROUP BY produtos.id
                $limit
            )
            SELECT
                Q1.nome_comercial,
                Q1.cores,
                Q1.id_fornecedor,
                Q1.id,
                Q1.descricao,
                Q1.valor_custo_produto,
                Q1.tipo_grade,
                Q1.incompleto,
                produtos_grade.nome_tamanho,
                COALESCE(estoque_grade.estoque, 0) estoque,
                COALESCE(estoque_grade.id_responsavel = 1, 0) consignado,
                COALESCE((estoque_grade.estoque - COALESCE(COUNT(pedido_item.id_produto), 0)),0) total,
                COUNT(pedido_item.id_produto) reservados,
                COALESCE((
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ), '') caminho
            FROM Q1
            INNER JOIN produtos_grade ON produtos_grade.id_produto = Q1.id
            LEFT JOIN estoque_grade ON estoque_grade.id_produto = produtos_grade.id_produto
                AND estoque_grade.nome_tamanho = produtos_grade.nome_tamanho
                AND estoque_grade.id_responsavel = 1
            LEFT JOIN pedido_item ON pedido_item.id_produto = produtos_grade.id_produto
                AND pedido_item.nome_tamanho = produtos_grade.nome_tamanho
            GROUP BY produtos_grade.id_produto, produtos_grade.nome_tamanho
            ORDER BY produtos_grade.sequencia ASC, total ASC;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        if ($pesquisa) {
            $sql->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $sql->execute();
        $lista = $sql->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($lista)) {
            $resultado = [];
            $previsao = self::buscaPrevisaoProdutosFornecedor($conexao, $idFornecedor);
            foreach ($lista as $key => $produto) {
                foreach ($previsao as $id => $prev) {
                    if ($id === (int) $produto['id']) {
                        if (!isset($produto['previsao'])) {
                            $produto['previsao'] = 0;
                        }
                        $produto['total'] += $prev[$produto['nome_tamanho']] ?? 0;
                        $produto['previsao'] += $prev[$produto['nome_tamanho']] ?? 0;
                    }
                }
                $resultado[$produto['id']]['id'] = $produto['id'];
                $resultado[$produto['id']]['cores'] = $produto['cores'];
                $resultado[$produto['id']]['nome_comercial'] = $produto['nome_comercial'];
                $resultado[$produto['id']]['caminho'] = $produto['caminho'];
                $resultado[$produto['id']]['consignado'] = $produto['consignado'];
                $resultado[$produto['id']]['descricao'] = $produto['descricao'];
                $resultado[$produto['id']]['incompleto'] = $produto['incompleto'];
                $resultado[$produto['id']]['valor_custo_produto'] = $produto['valor_custo_produto'];
                $resultado[$produto['id']]['estoqueTotal'] =
                    ($resultado[$produto['id']]['estoqueTotal'] ?? 0) + $produto['estoque'];
                $resultado[$produto['id']]['reservadosTotal'] =
                    ($resultado[$produto['id']]['reservadosTotal'] ?? 0) + $produto['reservados'];
                $resultado[$produto['id']]['saldoTotal'] =
                    ($resultado[$produto['id']]['saldoTotal'] ?? 0) + $produto['total'];
                if (!isset($resultado[$produto['id']]['children'])) {
                    $resultado[$produto['id']]['children'] = [];
                }
                $resultado[$produto['id']]['children'][] = $produto;

                if ($produto['tipo_grade'] == 'RO') {
                    $resultado[$produto['id']]['nome_tamanho'] = $produto['nome_tamanho'];
                }
            }

            usort($resultado, function ($a, $b) {
                return $a['saldoTotal'] > $b['saldoTotal'];
            });
            return $resultado;
        }
        return [];
    }
    public static function formataListaProdutosCompra(PDO $conexao, int $idFornecedor, int $idCompra): array
    {
        $listaDemandaProdutos = self::buscaDemandaProdutosFornecedor($conexao, $idFornecedor);
        $listaProdutosCompra = self::buscaGradeTodosProdutosDaCompra($conexao, $idCompra);
        $listaProdutosAdicionados = [];

        foreach ($listaDemandaProdutos as $produtoRequisitado) {
            foreach ($listaProdutosCompra as $produtoComprado) {
                if ($produtoRequisitado['id'] == $produtoComprado['id']) {
                    $produtoRequisitado['quantidadeTotal'] = (int) $produtoComprado['quantidadeTotal'];
                    $produtoRequisitado['valorTotal'] = (float) $produtoComprado['valorTotal'];
                    $produtoRequisitado['inputsGrade'] = (array) $produtoComprado['inputsGrade'];
                    $produtoRequisitado['situacao'] = (array) $produtoComprado['situacao'];
                    $produtoRequisitado['sequencia'] = (int) $produtoComprado['sequencia'];
                    $listaProdutosAdicionados[] = $produtoRequisitado;
                }
            }
        }

        return [
            'demanda' => (array) $listaDemandaProdutos ?? [],
            'adicionados' => (array) $listaProdutosAdicionados ?? [],
        ];
    }
    public static function buscaPrevisaoProdutosFornecedor(PDO $conexao, int $idFornecedor)
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_caixas.id_produto,
                SUM(compras_itens_grade.quantidade) previsao,
                compras_itens_grade.nome_tamanho
            FROM compras_itens_caixas
            INNER join compras_itens_grade ON compras_itens_grade.id_compra = compras_itens_caixas.id_compra AND compras_itens_grade.id_sequencia = compras_itens_caixas.id_sequencia
            WHERE compras_itens_caixas.id_fornecedor = :id_fornecedor AND compras_itens_caixas.situacao = 1
            GROUP BY compras_itens_caixas.id_compra, compras_itens_caixas.id_sequencia, compras_itens_grade.nome_tamanho;"
        );
        $sql->bindValue(':id_fornecedor', $idFornecedor, PDO::PARAM_INT);
        $sql->execute();
        $lista = $sql->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        if (!empty($lista)) {
            foreach ($lista as $item) {
                $resultado[$item['id_produto']][$item['nome_tamanho']] = (int) $item['previsao'];
            }
        }

        return $resultado;
    }
    public static function buscaGradeTodosProdutosDaCompra(PDO $conexao, int $idCompra)
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_grade.id_produto,
                compras_itens_grade.id_sequencia sequencia,
                compras_itens_grade.nome_tamanho,
                compras_itens_grade.quantidade,
                compras_itens_grade.quantidade_total,
                compras_itens.caixas,
                compras_itens.quantidade_total quantidadeTotal,
                compras_itens.valor_total valorTotal,
                situacao.nome,
                compras_itens.id_situacao situacao,
                (
                    SELECT produtos.tipo_grade
                    FROM produtos
                    WHERE produtos.id = compras_itens_grade.id_produto
                ) tipo_grade
            FROM compras_itens_grade
            INNER JOIN produtos_grade ON produtos_grade.id_produto = compras_itens_grade.id_produto
                AND produtos_grade.nome_tamanho = compras_itens_grade.nome_tamanho
            LEFT JOIN compras_itens ON compras_itens.id_compra = compras_itens_grade.id_compra AND
                    compras_itens.id_produto = compras_itens_grade.id_produto AND
                    compras_itens.sequencia = compras_itens_grade.id_sequencia
            LEFT JOIN situacao ON situacao.id = compras_itens.id_situacao
            WHERE compras_itens_grade.id_compra = :id_compra
            GROUP BY compras_itens_grade.id_produto,compras_itens_grade.id_sequencia,compras_itens_grade.nome_tamanho
            ORDER BY produtos_grade.id_produto ASC, produtos_grade.sequencia ASC;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::FETCH_ASSOC);
        $sql->execute();
        $grades = $sql->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];

        if (!empty($grades)) {
            foreach ($grades as $grade) {
                $index = "{$grade['id_produto']}-{$grade['sequencia']}";
                $resultado[$index]['id'] = $grade['id_produto'];
                $resultado[$index]['sequencia'] = $grade['sequencia'];
                $resultado[$index]['quantidadeTotal'] = $grade['quantidadeTotal'];
                $resultado[$index]['valorTotal'] = $grade['valorTotal'];
                $resultado[$index]['situacao']['situacao'] = $grade['situacao'];
                $resultado[$index]['situacao']['nome'] = $grade['nome'];
                $resultado[$index]['inputsGrade']['caixas'] = $grade['caixas'];
                if (!isset($resultado[$index]['inputsGrade']['novaGrade'])) {
                    $resultado[$index]['inputsGrade']['novaGrade'] = [];
                }
                array_push($resultado[$index]['inputsGrade']['novaGrade'], [
                    'nome_tamanho' => (string) $grade['nome_tamanho'],
                    'quantidade' => (int) $grade['quantidade'],
                ]);
            }
        }
        return $resultado;
    }
    public static function verificaSeTemExterno(PDO $conexao, int $idProduto, string $nomeTamanho): bool
    {
        $sql = $conexao->prepare(
            "SELECT EXISTS(
                SELECT 1
                FROM estoque_grade
                WHERE estoque_grade.id_produto = :id_produto
                    AND estoque_grade.nome_tamanho = :nome_tamanho
                    AND COALESCE(estoque_grade.id_responsavel, 1) <> 1
            ) existe_externo;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->execute();
        $verificacao = (bool) $sql->fetch(PDO::FETCH_ASSOC)['existe_externo'];

        return $verificacao;
    }
    public static function verificaSePermitido(PDO $conexao, int $idProduto): bool
    {
        $sql = $conexao->prepare(
            "SELECT NOT EXISTS(
                SELECT 1
                FROM produtos
                WHERE produtos.id = :id_produto
                    AND produtos.permitido_reposicao = 1
            ) permitido_repor;"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->execute();
        $verificacao = (bool) $sql->fetch(PDO::FETCH_ASSOC)['permitido_repor'];

        if ($verificacao) {
            throw new Error('Esse produto não tem permissão para repor no Mobile Stock');
        }

        return $verificacao;
    }
    public static function buscaCompraProdutoGrade(PDO $conexao, int $idCompra, int $sequencia)
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_grade.quantidade,
                compras_itens_grade.nome_tamanho
            FROM compras_itens_grade
            WHERE compras_itens_grade.id_compra = :id_compra
                AND compras_itens_grade.id_sequencia = :sequencia
            GROUP BY compras_itens_grade.nome_tamanho;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->bindValue(':sequencia', $sequencia, PDO::PARAM_INT);
        $sql->execute();
        $compras = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $compras;
    }
    public static function buscaInformacoesDaCompra(int $idCompra): array
    {
        $consulta = DB::selectOne(
            "SELECT
                compras.id_fornecedor,
                compras.situacao,
                compras.edicao_fornecedor,
                DATE_FORMAT(compras.data_previsao, '%Y-%m-%d') data_previsao
            FROM compras
            WHERE compras.id = :id_compra;",
            ['id_compra' => $idCompra]
        );

        return $consulta;
    }
    public static function verificaResponsavelPorBarCodeCompra(PDO $conexao, string $codigoBarras)
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_caixas.id_compra,
                produtos.permitido_reposicao,
                estoque_grade.id_responsavel,
                EXISTS(
                    SELECT 1
                    FROM compras
                    WHERE compras.id = compras_itens_caixas.id_compra
                        AND compras.edicao_fornecedor = 1
                ) concluiu_reposicao
            FROM produtos
            INNER JOIN compras_itens_caixas ON compras_itens_caixas.id_produto = produtos.id
                AND compras_itens_caixas.codigo_barras = :codigo_barras
            LEFT JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
            ORDER BY estoque_grade.id_responsavel = 1 DESC
            LIMIT 1;"
        );
        $sql->bindValue(':codigo_barras', $codigoBarras, PDO::PARAM_STR);
        $sql->execute();
        $resultado = $sql->fetch(PDO::FETCH_ASSOC);
        if (empty($resultado)) {
            throw new Exception('Compra não encontrada');
        }

        $resultado['concluiu_reposicao'] = (bool) $resultado['concluiu_reposicao'];

        return $resultado;
    }
    public static function concluiReposicao(PDO $conexao, int $idCompra)
    {
        $sql = $conexao->prepare(
            "UPDATE compras
            SET compras.edicao_fornecedor = 1
            WHERE compras.id = :id_compra;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() < 1) {
            throw new Error('Nenhuma compra foi atualizada');
        }
    }
    public static function buscaCompraProdutoGradeRelatorio(PDO $conexao, int $idCompra, int $sequencia)
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_grade.quantidade_total,
                compras_itens_grade.nome_tamanho
            FROM compras_itens_grade
            WHERE compras_itens_grade.id_compra = :id_compra
                AND compras_itens_grade.id_sequencia = :sequencia
            GROUP BY compras_itens_grade.nome_tamanho;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->bindValue(':sequencia', $sequencia, PDO::PARAM_INT);
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }
    public static function dadosEtiquetaColetiva(PDO $conexao, int $idCompra): array
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_caixas.codigo_barras,
                DATE_FORMAT(compras.data_previsao, '%d-%m-%Y') data_previsao,
                CONCAT(compras.id,' - ',compras_itens_caixas.id_sequencia,' - ',compras_itens_caixas.volume) cod_compra,
                CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) referencia,
                CONCAT(
                '[',
                (
                    SELECT GROUP_CONCAT(JSON_OBJECT(
                    'codigo_barras', compras_itens_caixas.codigo_barras,
                    'nome_tamanho', compras_itens_grade.nome_tamanho,
                    'quantidade', compras_itens_grade.quantidade
                    ))
                    FROM compras_itens_grade
                    WHERE compras_itens_grade.id_compra = compras_itens_caixas.id_compra
                    AND compras_itens_grade.id_sequencia = compras_itens_caixas.id_sequencia
                    AND compras_itens_grade.quantidade > 0
                )
                ,']'
                )grade
            FROM compras_itens_caixas
            INNER JOIN produtos ON produtos.id = compras_itens_caixas.id_produto
            INNER JOIN compras ON compras.id = compras_itens_caixas.id_compra
            WHERE compras_itens_caixas.id_compra = :id_compra;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->execute();
        $etiquetas = $sql->fetchAll(PDO::FETCH_ASSOC);

        $etiquetas = array_map(function ($etiqueta) {
            $etiqueta['grade'] = json_decode($etiqueta['grade'], true);
            return $etiqueta;
        }, $etiquetas);

        return $etiquetas;
    }
    public static function buscaEtiquetasUnitarias(PDO $conexao, int $idCompra): array
    {
        $sql = $conexao->prepare(
            "SELECT
                SUM(compras_itens_grade.quantidade_total)quantidade,
                CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) desc_produto,
                produtos.id,
                COALESCE(produtos.localizacao, '')localizacao,
                compras_itens_grade.nome_tamanho,
                (
                    SELECT produtos_grade.cod_barras
                    FROM produtos_grade
                    WHERE produtos_grade.id_produto = compras_itens_grade.id_produto
                        AND produtos_grade.nome_tamanho = compras_itens_grade.nome_tamanho
                )cod_barras
            FROM compras_itens_grade
            INNER JOIN produtos ON produtos.id = compras_itens_grade.id_produto
            WHERE compras_itens_grade.id_compra = :id_compra
                AND compras_itens_grade.id_produto = produtos.id
                AND compras_itens_grade.quantidade > 0
            GROUP BY compras_itens_grade.id_produto, compras_itens_grade.nome_tamanho
            ORDER BY compras_itens_grade.id_produto, compras_itens_grade.nome_tamanho;"
        );
        $sql->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
        $sql->execute();
        $linhas = $sql->fetchAll(PDO::FETCH_ASSOC);
        $etiquetas = [];
        foreach ($linhas as $linha) {
            for ($i = 0; $i < $linha['quantidade']; $i++) {
                $etiqueta['referencia'] = (string) $linha['id'] . ' - ' . $linha['desc_produto'];
                $etiqueta['tamanho'] = (string) $linha['nome_tamanho'];
                $etiqueta['cod_barras'] = (string) $linha['cod_barras'];
                $etiqueta['localizacao'] = (string) $linha['localizacao'];
                $etiqueta['referencia'] = (string) ConversorStrings::removeAcentos($etiqueta['referencia']);
                $etiquetas[] = $etiqueta;
            }
        }

        return $etiquetas;
    }
    public static function infosPorCodBarras(PDO $conexao, string $codBarras)
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_itens_caixas.volume,
                compras_itens_caixas.codigo_barras,
                compras_itens_caixas.quantidade pares,
                compras_itens_caixas.id_produto,
                CONCAT(compras_itens_caixas.id_compra, ' - ', compras_itens_caixas.id_sequencia, ' - ', compras_itens_caixas.volume)cod_compra,
                produtos.id_fornecedor,
                produtos.descricao produto,
                compras_itens.preco_unit,
                compras_itens_grade.nome_tamanho tamanho,
                compras_itens_grade.quantidade,
                0 tamanhoFoto,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = produtos.id_fornecedor
                )fornecedor,
                (
                    SELECT COALESCE(MAX(produtos_foto.caminho), '')
                    FROM produtos_foto
                    WHERE produtos_foto.id = compras_itens_caixas.id_produto
                )foto
            FROM compras_itens_caixas
            INNER JOIN produtos ON produtos.id = compras_itens_caixas.id_produto
            INNER JOIN compras_itens ON compras_itens.id_compra = compras_itens_caixas.id_compra AND compras_itens.sequencia = compras_itens_caixas.id_sequencia
            INNER JOIN compras_itens_grade ON compras_itens_grade.id_compra = compras_itens_caixas.id_compra AND compras_itens_grade.id_sequencia = compras_itens_caixas.id_sequencia
            WHERE compras_itens_grade.quantidade > 0
                AND compras_itens_caixas.situacao <> 2
                AND compras_itens_caixas.codigo_barras = :codigo_barras"
        );
        $sql->bindValue(':codigo_barras', $codBarras);
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }
    public static function consultaListaCompras(PDO $conexao, array $filtros): array
    {
        $where = '';
        if ($filtros['itens'] < 0) {
            $itens = (int) PHP_INT_MAX;
            $offset = (int) 0;
        } else {
            $itens = (int) $filtros['itens'];
            $offset = (int) ($filtros['pagina'] - 1) * $itens;
        }
        if ($filtros['id_compra']) {
            Validador::validar($filtros, [
                'id_compra' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND compras.id =  :id_compra';
        }
        if ($filtros['id_fornecedor']) {
            Validador::validar($filtros, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND compras.id_fornecedor = :id_fornecedor';
        }
        if ($filtros['referencia']) {
            Validador::validar($filtros, [
                'referencia' => [Validador::OBRIGATORIO],
            ]);

            $where .= " AND EXISTS(
                SELECT 1
                FROM produtos
                WHERE produtos.id = compras_itens.id_produto
                    AND (produtos.descricao REGEXP :referencia OR produtos.id REGEXP :referencia)
            )";
        }
        if ($filtros['nome_tamanho']) {
            Validador::validar($filtros, [
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);

            $where .= " AND EXISTS(
                SELECT 1
                FROM compras_itens_grade
                WHERE compras_itens_grade.id_compra = compras.id
                    AND compras_itens_grade.id_produto = compras_itens.id_produto
                    AND compras_itens_grade.nome_tamanho = :nome_tamanho
            )";
        }
        if ($filtros['situacao']) {
            Validador::validar($filtros, [
                'situacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $where .= ' AND compras.situacao = :situacao';
        }
        if ($filtros['data_inicial_emissao'] && $filtros['data_fim_emissao']) {
            Validador::validar($filtros, [
                'data_fim_emissao' => [Validador::OBRIGATORIO],
                'data_inicial_emissao' => [Validador::OBRIGATORIO],
            ]);

            $where .=
                " AND compras.data_emissao BETWEEN DATE_FORMAT(:data_emissao_inicial, '%Y-%m-%d %H:%i:%s') AND CONCAT(:data_emissao_final, ' 23:59:59')";
        }
        if ($filtros['data_inicial_previsao'] && $filtros['data_fim_previsao']) {
            Validador::validar($filtros, [
                'data_inicial_previsao' => [Validador::OBRIGATORIO],
                'data_fim_previsao' => [Validador::OBRIGATORIO],
            ]);

            $where .=
                " AND compras.data_previsao BETWEEN DATE_FORMAT(:data_previsao_inicial, '%Y-%m-%d %H:%i:%s') AND CONCAT(:data_previsao_final, ' 23:59:59')";
        }
        $sql = $conexao->prepare(
            "SELECT
                compras.id,
                compras.data_emissao,
                compras.data_previsao,
                compras.situacao,
                GROUP_CONCAT(DISTINCT compras_itens.id_produto)id_produto,
                SUM(compras_itens.valor_total)valor_total,
                (
                    SELECT situacao.nome
                    FROM situacao
                    WHERE situacao.id = compras.situacao
                )situacao_nome,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = compras.id_fornecedor
                )fornecedor
            FROM compras
            INNER JOIN compras_itens ON compras_itens.id_compra = compras.id
            WHERE 1 = 1 $where
            GROUP BY compras.id
            ORDER BY compras.id DESC
            LIMIT $itens OFFSET $offset;"
        );
        if ($filtros['id_compra']) {
            $sql->bindValue(':id_compra', $filtros['id_compra'], PDO::PARAM_INT);
        }
        if ($filtros['id_fornecedor']) {
            $sql->bindValue(':id_fornecedor', $filtros['id_fornecedor'], PDO::PARAM_INT);
        }
        if ($filtros['referencia']) {
            $sql->bindValue(':referencia', $filtros['referencia'], PDO::PARAM_STR);
        }
        if ($filtros['nome_tamanho']) {
            $sql->bindValue(':nome_tamanho', $filtros['nome_tamanho'], PDO::PARAM_STR);
        }
        if ($filtros['situacao']) {
            $sql->bindValue(':situacao', $filtros['situacao'], PDO::PARAM_INT);
        }
        if ($filtros['data_inicial_emissao'] && $filtros['data_fim_emissao']) {
            $sql->bindValue(':data_emissao_inicial', $filtros['data_inicial_emissao'], PDO::PARAM_STR);
            $sql->bindValue(':data_emissao_final', $filtros['data_fim_emissao'], PDO::PARAM_STR);
        }
        if ($filtros['data_inicial_previsao'] && $filtros['data_fim_previsao']) {
            $sql->bindValue(':data_previsao_inicial', $filtros['data_inicial_previsao'], PDO::PARAM_STR);
            $sql->bindValue(':data_previsao_final', $filtros['data_fim_previsao'], PDO::PARAM_STR);
        }
        $sql->execute();
        $compras = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $compras ?? [];
    }
    public static function infosImprimirHistoricoCompras(PDO $conexao, array $listaCodigos): array
    {
        $codigos = implode(
            ',',
            array_map(function (array $item): string {
                Validador::validar($item, [
                    'codigo_barras' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]);

                return $item['codigo_barras'];
            }, $listaCodigos)
        );

        $sql = $conexao->prepare(
            "SELECT
                compras_itens_caixas.volume,
                compras_itens_caixas.quantidade pares,
                compras_itens_caixas.id_fornecedor,
                compras_itens_caixas.id_produto,
                colaboradores.razao_social fornecedor,
                produtos.descricao produto,
                compras_itens_grade.quantidade,
                CONCAT(compras_itens_caixas.id_compra, ' - ', compras_itens_caixas.id_sequencia, ' - ', compras_itens_caixas.volume)cod_compra
            FROM compras_itens_caixas
            INNER JOIN colaboradores ON colaboradores.id = compras_itens_caixas.id_fornecedor
            INNER JOIN produtos ON produtos.id = compras_itens_caixas.id_produto
            INNER JOIN compras_itens ON compras_itens.id_compra = compras_itens_caixas.id_compra
                AND compras_itens.sequencia = compras_itens_caixas.id_sequencia
            INNER JOIN compras_itens_grade ON compras_itens_grade.id_compra = compras_itens_caixas.id_compra
                AND compras_itens_grade.id_sequencia = compras_itens_caixas.id_sequencia
                AND compras_itens_grade.quantidade > 0
            WHERE compras_itens_caixas.codigo_barras IN ($codigos);"
        );
        $sql->execute();
        $informacoes = $sql->fetchAll(PDO::FETCH_ASSOC);

        return $informacoes ?: [];
    }
    public static function relatorioUltimasCompras(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                compras_entrada_historico.sequencia,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'codigo_barras', compras_entrada_historico.codigo_barras,
                        'data', DATE_FORMAT(compras_entrada_historico.data, '%d/%m/%Y %H:%i'),
                        'fornecedor', COALESCE(compras_entrada_historico.fornecedor, '')
                    )),
                    ']'
                ) entradas
            FROM compras_entrada_historico
            GROUP BY compras_entrada_historico.sequencia
            ORDER BY compras_entrada_historico.sequencia DESC
            LIMIT 10;"
        );
        $sql->execute();
        $relatorios = $sql->fetchAll(PDO::FETCH_ASSOC);

        foreach ($relatorios as $index => $relatorio) {
            $relatorio['sequencia'] = (int) $relatorio['sequencia'];
            $relatorio['entradas'] = (array) json_decode($relatorio['entradas'], true);

            unset($relatorios[$index]);
            $relatorios[$relatorio['sequencia']] = $relatorio['entradas'];
        }

        return $relatorios;
    }
    public static function buscaDetalhesComprasItens(int $idCompra): array
    {
        $comprasItens = DB::select(
            "SELECT
                compras_itens.sequencia,
                compras_itens.id_situacao,
                compras_itens.caixas,
                SUM(compras_itens_caixas.situacao = 2) AS `caixas_entregues`
            FROM compras_itens
            INNER JOIN compras_itens_caixas ON compras_itens_caixas.id_compra = compras_itens.id_compra
                AND compras_itens_caixas.id_sequencia = compras_itens.sequencia
            WHERE compras_itens.id_compra = :id_compra
            GROUP BY compras_itens.id_compra, compras_itens.sequencia
            ORDER BY compras_itens.sequencia ASC;",
            ['id_compra' => $idCompra]
        );
        if (empty($comprasItens)) {
            throw new NotFoundHttpException('Compra não encontrada');
        }

        return $comprasItens;
    }
    public static function atualizaSituacaoCompraItem(int $idCompra, int $sequencia, int $idSituacao): void
    {
        $linhasAlteradas = DB::update(
            "UPDATE compras_itens
            SET compras_itens.id_situacao = :id_situacao
            WHERE compras_itens.id_compra = :id_compra
                AND compras_itens.sequencia = :sequencia;",
            [
                'id_situacao' => $idSituacao,
                'id_compra' => $idCompra,
                'sequencia' => $sequencia,
            ]
        );

        if ($linhasAlteradas !== 1) {
            throw new InvalidArgumentException(
                'Não foi possível atualizar a situação dos itens da reposição corretamente'
            );
        }
    }
    public static function atualizaSituacaoCompra(int $idCompra, int $idSituacao): void
    {
        $linhasAlteradas = DB::update(
            "UPDATE compras
            SET compras.situacao = :id_situacao
            WHERE compras.id = :id_compra;",
            ['id_situacao' => $idSituacao, 'id_compra' => $idCompra]
        );

        if ($linhasAlteradas !== 1) {
            throw new InvalidArgumentException('Não foi possível atualizar a situação da reposição corretamente');
        }
    }
    public static function geraTravaConcluirReposicao(int $idCompra): void
    {
        DB::selectOneColumn("SELECT GET_LOCK('CONCLUIR_REPOSICAO_:id_compra', 99999)", ['id_compra' => $idCompra]);
    }
}

<?php
namespace MobileStock\service;

use MobileStock\helper\GeradorSql;
use MobileStock\model\AvaliacaoProdutos;

class AvaliacaoProdutosService {
    public static function salva(\PDO $conexao, AvaliacaoProdutos $modelo): bool
    {
        $gerador = new GeradorSql($modelo);
        $stmt = $conexao->prepare($gerador->insert());
        return $stmt->execute($gerador->bind);
    }

    public static function existeAvaliacaoProdutoComFaturamento(\PDO $conexao, int $idProduto, int $idFaturamento): bool
    {
        $stmt = $conexao->prepare(
            "SELECT 1 existe
            FROM avaliacao_produtos
            WHERE
                avaliacao_produtos.id_produto = :idProduto AND
                avaliacao_produtos.id_faturamento = :idFaturamento"
        );
        $stmt->bindValue(':idProduto', $idProduto, \PDO::PARAM_INT);
        $stmt->bindValue(':idFaturamento', $idFaturamento, \PDO::PARAM_INT);
        $stmt->execute();
        $retorno = $stmt->fetch(\PDO::FETCH_ASSOC);
        return !empty($retorno);
    }

    public static function buscaAvaliacoesPendentesColaborador(\PDO $conexao, int $idColaborador): array
    {
        $qtdDias = ConfiguracaoService::consultaDatasDeTroca($conexao)[0]['qtd_dias_disponiveis_troca_normal'];

        $stmt = $conexao->prepare(
            "SELECT
                avaliacao_produtos.id,
                produtos.id id_produto,
                LOWER(IF(LENGTH(produtos.nome_comercial > 0), produtos.nome_comercial, produtos.descricao)) nome_produto,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) foto_produto
            FROM avaliacao_produtos
            INNER JOIN produtos ON produtos.id = avaliacao_produtos.id_produto
            WHERE avaliacao_produtos.origem = 'ML'
                AND avaliacao_produtos.id_cliente = :idCliente
                AND avaliacao_produtos.data_avaliacao IS NULL
                AND avaliacao_produtos.qualidade = 0
                AND (
                    DATE(avaliacao_produtos.data_criacao) + INTERVAL {$qtdDias} DAY <= CURDATE()
                )"
        );
        $stmt->bindValue(':idCliente', $idColaborador, \PDO::PARAM_INT);
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $avaliacoes;
    }

    public static function adiarAvaliacaoPendente(\PDO $conexao, int $idColaborador, int $idAvaliacao): bool
    {
        $stmt = $conexao->prepare(
            "UPDATE avaliacao_produtos
            SET avaliacao_produtos.data_avaliacao = CURRENT_TIMESTAMP()
            WHERE avaliacao_produtos.id_cliente = :idCliente
                AND avaliacao_produtos.id = :idAvaliacao
                AND avaliacao_produtos.data_avaliacao IS NULL"
        );
        $stmt->execute([
            ':idCliente' => $idColaborador,
            ':idAvaliacao' => $idAvaliacao
        ]);
        if ($stmt->rowCount() == 0) throw new \PDOException("Não foi possível adiar avaliação");
        return true;
    }

    public static function avaliarProduto(\PDO $conexao, int $idCliente, int $idAvaliacao, int $nota, string $comentario): bool
    {
        $stmt = $conexao->prepare(
            "SELECT 1 avaliacao
            FROM avaliacao_produtos
            WHERE
                avaliacao_produtos.id = :idAvaliacao AND
                avaliacao_produtos.id_cliente = :idCliente
            LIMIT 1"
        );
        $stmt->bindValue(':idAvaliacao', $idAvaliacao, \PDO::PARAM_INT);
        $stmt->bindValue(':idCliente', $idCliente, \PDO::PARAM_INT);
        $stmt->execute();
        $consulta = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($consulta['avaliacao'])) throw new \PDOException("Avaliação não encontrada");

        $stmt = $conexao->prepare(
            "UPDATE avaliacao_produtos
            SET
                avaliacao_produtos.qualidade = :nota,
                avaliacao_produtos.comentario = :comentario,
                avaliacao_produtos.data_avaliacao = CURRENT_TIMESTAMP()
            WHERE
                avaliacao_produtos.origem = 'ML' AND
                avaliacao_produtos.id = :id_avaliacao"
        );
        $stmt->execute([
            ':nota' => $nota,
            ':comentario' => $comentario,
            ':id_avaliacao' => $idAvaliacao
        ]);

        if ($stmt->rowCount() === 0) throw new \PDOException("Nenhum dado atualizado");

        return true;
    }

    public static function buscaAvaliacoesProduto(\PDO $conexao, int $idProduto): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                avaliacao_produtos.id,
                avaliacao_produtos.id_cliente,
                COALESCE(
                    colaboradores.usuario_meulook,
                    colaboradores.razao_social
                ) nome,
                COALESCE(
                    colaboradores.foto_perfil,
                    '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'
                ) foto_perfil,
                avaliacao_produtos.id_produto,
                avaliacao_produtos.qualidade nota,
                IF(LENGTH(avaliacao_produtos.comentario) > 0, avaliacao_produtos.comentario, NULL) comentario,
                DATE_FORMAT(avaliacao_produtos.data_avaliacao, '%d/%m/%Y') data
            FROM avaliacao_produtos
            INNER JOIN colaboradores ON colaboradores.id = avaliacao_produtos.id_cliente
            WHERE
                avaliacao_produtos.id_produto = :idProduto AND
                avaliacao_produtos.origem = 'ML' AND
                avaliacao_produtos.data_avaliacao IS NOT NULL AND
                avaliacao_produtos.qualidade > 0
            ORDER BY avaliacao_produtos.id DESC"
        );
        $stmt->bindValue(':idProduto', $idProduto, \PDO::PARAM_INT);
        $stmt->execute();
        $consulta = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $consulta;
    }

    public static function deletaAvaliacao(\PDO $conexao, int $idCliente, int $idAvaliacao): bool
    {
        $stmt = $conexao->prepare(
            "DELETE FROM avaliacao_produtos
            WHERE
                avaliacao_produtos.id = :idAvaliacao AND
                avaliacao_produtos.id_cliente = :idCliente"
        );
        $stmt->execute([
            ':idAvaliacao' => $idAvaliacao,
            ':idCliente' => $idCliente
        ]);
        if ($stmt->rowCount() === 0) throw new \PDOException("Nenhum dado deletado");
        return true;
    }

}
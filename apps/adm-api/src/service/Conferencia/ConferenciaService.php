<?php

namespace MobileStock\service\Conferencia;

use MobileStock\model\LogisticaItem;
use MobileStock\service\MessageService;
use PDO;

class ConferenciaService
{
    public int $idFaturamento;
    public int $idUsuario;

    //    public static function buscaConferenciaFaturamento(int $idFaturamento)
    //    {
    //        // quando criar um endpoint para a função modificar a $conexao desta função
    //        $conexao = Conexao::criarConexao();
    //        $sql = $conexao->prepare(
    //            "SELECT
    //                faturamento.id,
    //                faturamento.tipo_frete id_tipo_frete,
    //                faturamento.data_conferencia,
    //                faturamento.pares,
    //                faturamento.situacao,
    //                faturamento.entregue,
    //                faturamento.observacao,
    //                faturamento.observacao2,
    //                COALESCE(faturamento.id_responsavel_estoque, 1) id_responsavel_estoque,
    //                cliente.id id_cliente,
    //                cliente.razao_social cliente,
    //                COALESCE(freteiro.nome,'') freteiro,
    //                COALESCE(vendedor.nome, '') nome_vendedor,
    //                transportadora.razao_social nome_transportadora,
    //                conferidor.nome conferidor,
    //                tipo_frete.tipo_embalagem,
    //                tipo_frete.nome tipo_frete,
    //                (
    //                    SELECT COUNT(faturamento_item.id_faturamento)
    //                    FROM faturamento_item
    //                    WHERE faturamento_item.situacao = 6 AND faturamento_item.id_faturamento = faturamento.id and conferido = 1
    //                ) conferidos,
    //                CASE
    //                    WHEN(faturamento.tipo_frete = 1) THEN faturamento.freteiro
    //                    WHEN(faturamento.tipo_frete = 2) THEN faturamento.transportadora
    //                    WHEN(faturamento.tipo_frete = 3) THEN 0
    //                    WHEN(faturamento.tipo_frete = 4) THEN 12
    //                    WHEN(faturamento.tipo_frete = 5) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
    //                    WHEN(faturamento.tipo_frete = 6) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
    //                    WHEN(faturamento.tipo_frete = 7) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
    //                    WHEN(faturamento.tipo_frete = 8) THEN ( SELECT tipo_frete.id_colaborador FROM tipo_frete WHERE tipo_frete.id = faturamento.tipo_frete)
    //                END transporte
    //            FROM faturamento
    //            INNER JOIN colaboradores cliente ON cliente.id = faturamento.id_cliente
    //            LEFT OUTER JOIN usuarios vendedor ON vendedor.id = faturamento.vendedor
    //            LEFT OUTER JOIN usuarios conferidor ON conferidor.id = faturamento.id_conferidor
    //            LEFT OUTER JOIN colaboradores transportadora ON transportadora.id = faturamento.transportadora
    //            LEFT OUTER JOIN freteiro freteiro ON faturamento.freteiro=freteiro.id
    //            LEFT OUTER JOIN tipo_frete ON faturamento.tipo_frete=tipo_frete.id
    //            WHERE faturamento.id = :id_faturamento"
    //        );
    //        $sql->bindValue(":id_faturamento", $idFaturamento, PDO::PARAM_INT);
    //        $sql->execute();
    //        $resultado = $sql->fetch(PDO::FETCH_ASSOC);
    //
    //        return $resultado;
    //    }
    //    public static function buscaUsuarioConferencia(\PDO $conexao, string $senha): array
    //    {
    //        $senha = md5($senha);
    //
    //        $sql = $conexao->prepare(
    //            "SELECT usuarios.id, usuarios.nome, usuarios.permissao, usuarios.senha
    //            FROM usuarios
    //            WHERE usuarios.permissao REGEXP '55|57'
    //            AND usuarios.senha = ?"
    //        );
    //        $sql->execute([$senha]);
    //        $resultado = $sql->fetch(PDO::FETCH_ASSOC);
    //
    //        return $resultado;
    //    }

    public static function enviaMensagemParaSellerComSeparacaoAtrasada(PDO $conexao): void
    {
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $sql = $conexao->prepare(
            "SELECT
                colaboradores.telefone,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                produtos.nome_comercial,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto_produto,
                DATE(logistica_item.data_criacao) = DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS `atrasado`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            WHERE logistica_item.id_responsavel_estoque <> 1
                AND logistica_item.situacao < $situacao
            HAVING atrasado;"
        );
        $sql->execute();
        $produtosAtrasados = $sql->fetchAll(PDO::FETCH_ASSOC);
        $msgService = app(MessageService::class);

        foreach ($produtosAtrasados as $produto) {
            $mensagem = "Produto *{$produto['id_produto']}* - *{$produto['nome_tamanho']}* atrasado!";
            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= 'Caso não seja separado, sua venda será cancelada no próximo dia útil. ';
            $mensagem .= 'Caso não tenha o produto em estoque, você pode sugerir a substituição por outro produto ';
            $mensagem .= 'semelhante ao cliente.';
            $msgService->sendImageWhatsApp($produto['telefone'], $produto['foto_produto'], $mensagem);
        }
    }
}

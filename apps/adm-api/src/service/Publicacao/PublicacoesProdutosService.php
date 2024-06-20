<?php

namespace MobileStock\service\Publicacao;

use MobileStock\helper\GeradorSql;
use MobileStock\model\Publicacao\PublicacaoProduto;
use PDO;

class PublicacoesProdutosService extends PublicacaoProduto
{
    public function salva(\PDO $conexao)
    {
        $gerador = new GeradorSql($this);
        $sql = $this->id ? $gerador->update() : $gerador->insert();

        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        $this->id = $this->id ?? $conexao->lastInsertId();
        return $conexao->lastInsertId();
    }

    // public static function buscaInfoProdutosCarrinho(\PDO $conexao, array $arrayIds, array $arrayTamanho): array
    // {
    //     $bind = array();
    //     $bindTamanho = array();
    //     $bindId = array();

    //     foreach ($arrayTamanho as $key => $tamanho) {
    //         $bindTamanho[":tamanho_$key"] = $tamanho;
    //     }
    //     foreach ($arrayIds as $key => $id) {
    //         $bindId[":id_$key"] = $id;
    //     }

    //     $arrayTamanho = implode(",", array_keys($bindTamanho));
    //     $arrayIds = implode(",", array_keys($bindId));
    //     $bind = array_merge($bindTamanho, $bindId);

    //     $sql = $conexao->prepare(
    //         "SELECT
    //             publicacoes_produtos.id_produto,
    //             publicacoes_produtos.id,
    //             produtos.valor_venda_ml preco,
    //             (SELECT
    //                 CONCAT(
    //                     '[',
    //                     GROUP_CONCAT(
    //                         JSON_OBJECT(
    //                             'nome_tamanho', estoque_grade.nome_tamanho,
    //                             'id_responsavel', estoque_grade.id_responsavel
    //                         ) ORDER BY estoque_grade.id_responsavel ASC
    //                     ),
    //                     ']'
    //                 )
    //                 FROM estoque_grade
    //                 WHERE
    //                     estoque_grade.id_produto = produtos.id
    //                     AND estoque_grade.nome_tamanho IN ($arrayTamanho)
    //             ) tamanhos
    //         FROM produtos
    //         INNER JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos.id
    //         WHERE produtos.id IN ($arrayIds)
    //         GROUP BY publicacoes_produtos.id"
    //     );
    //     $sql->execute($bind);
    //     $consulta = $sql->fetchAll(PDO::FETCH_ASSOC);

    //     $consultaFormatada = array_map(function ($item){
    //         $item['tamanhos'] = json_decode($item['tamanhos'],true);
    //         return $item;
    //     },$consulta);

    //     return array_reduce($consultaFormatada, function(array $total, array $produto) {
    //         $total[$produto['id_produto']] = $produto;

    //         return $total;
    //     }, []);
    // }

    // public static function produtosEstaDisponivelParaPublicacao(\PDO $conexao, string $uuid): string
    // {
    //     if ($uuid === '') {
    //         return '';
    //     }

    //     $stmt = $conexao->prepare(
    //     "SELECT CASE
    //                 WHEN logistica_item.situacao IN ('DE', 'DF') THEN 'produto_trocado'
    //                 WHEN logistica_item.situacao <> 'CO' THEN 'Indisponivel'
    //                 WHEN EXISTS(SELECT 1
    //                             FROM troca_pendente_agendamento
    //                             WHERE troca_pendente_agendamento.uuid = logistica_item.uuid_produto) THEN 'produto_trocado'
    //                 ELSE ''
    //             END situacao
    //             FROM logistica_item
    //             WHERE logistica_item.uuid_produto = ?
    //         "
    //     );
    //     $stmt->execute([$uuid]);

    //     return $stmt->fetchColumn();
    // }
}

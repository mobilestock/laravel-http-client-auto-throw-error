<?php

namespace MobileStock\service\ConsumidorFinal;

use Exception;
use PDO;

class ConsumidorFinalService
{
    public static function salvaConsumidorFinal(PDO $conexao, string $observacao, string $uuidProduto): void
    {
        $stmt = $conexao->prepare(
            "UPDATE pedido_item
            SET pedido_item.observacao = :observacao
            WHERE pedido_item.uuid = :uuid_produto"
        );
        $stmt->bindValue(':observacao', $observacao, PDO::PARAM_STR);
        $stmt->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Erro ao salvar consumidor final');
        }
    }
}

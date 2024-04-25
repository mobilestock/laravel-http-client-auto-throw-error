<?php

namespace MobileStock\service\Fila;

use Illuminate\Support\Facades\DB;
use PDO;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FilaRespostasService
{
    protected PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function responde(string $idFila, array $resposta): void
    {
        $stmt = $this->conexao->prepare(
            "INSERT INTO fila_respostas (
                fila_respostas.id_fila,
                fila_respostas.resposta
            ) VALUES (
                :id_fila,
                :resposta
            )"
        );

        $stmt->bindValue(':id_fila', $idFila, PDO::PARAM_STR);
        $stmt->bindValue(':resposta', json_encode($resposta), PDO::PARAM_STR);

        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new \DomainException('Não foi possivel criar o item da fila de pagamento.');
        }
    }

    public function consulta(string $idFila): ?array
    {
        $stmt = $this->conexao->prepare(
            "SELECT fila_respostas.resposta
             FROM fila_respostas
             WHERE fila_respostas.id_fila = :id_fila"
        );

        $stmt->bindValue(':id_fila', $idFila, PDO::PARAM_STR);
        $stmt->execute();

        $resposta = $stmt->fetchColumn();

        if (!$resposta) {
            return null;
        }

        $resposta = json_decode($resposta, true);

        if (!empty($resposta['error']['code'])) {
            throw new HttpException($resposta['error']['code'], $resposta['message']);
        }

        return $resposta;
    }

    public function removeRespostas(): void
    {
        $sql = "DELETE
            FROM fila_respostas
            WHERE DATE(fila_respostas.data_criacao) <= CURRENT_DATE() - INTERVAL 1 DAY";

        DB::delete($sql);
    }
}

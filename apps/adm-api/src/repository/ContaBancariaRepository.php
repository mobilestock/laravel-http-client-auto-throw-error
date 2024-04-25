<?php

namespace MobileStock\repository;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\database\Conexao;
use MobileStock\model\ModelInterface;
use MobileStock\model\ContaBancaria;
use PDO;

class ContaBancariaRepository implements RepositoryInterface
{
    public static function busca($params)
    {
        $query =
            'select *, (SELECT bancos.nome FROM bancos where bancos.cod_banco = conta_bancaria_colaboradores.id_banco) banco from conta_bancaria_colaboradores WHERE 1 = 1';
        foreach ($params as $key => $param) {
            $query .= " AND $key = '$param' ";
        }
        $listaObj = [];
        $listaContasBancarias = Conexao::criarConexao()
            ->query($query)
            ->fetchAll(\PDO::FETCH_ASSOC);

        if (sizeof($listaContasBancarias) === 0) {
            throw new \DomainException('Não foi encontrado nenhum resultado da busca');
        }
        foreach ($listaContasBancarias as $contaBancaria) {
            $listaObj[] = ContaBancaria::hidratar($contaBancaria);
        }
        return $listaObj;
    }
    public static function salva(ModelInterface $model): void
    {
        // TODO: Implement salvar() method.
    }

    /**
     * @param ModelInterface $model
     * @param PDO $conn
     */
    public static function deleta(ModelInterface $model): void
    {
        $stmt = Conexao::criarConexao()->prepare('DELETE FROM conta_bancaria_colaboradores where token_zoop = ?');
        $stmt->bindValue(1, $model->getTokenZoop(), \PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function atualiza(ModelInterface $model, $params = []): ModelInterface
    {
        // TODO: Implement atualizar() method.
    }

    public static function bloqueiaContaIugu(PDO $conexao, string $iuguTokenLive)
    {
        $conexao->exec(
            "UPDATE conta_bancaria_colaboradores SET conta_bancaria_colaboradores.pagamento_bloqueado = 'T' WHERE conta_bancaria_colaboradores.iugu_token_live = '$iuguTokenLive'"
        );
    }

    /**
     * @return false|array
     */
    public static function dadosSubConta(PDO $conexao, string $subconta)
    {
        $sql = $conexao->prepare(
            "SELECT
                conta_bancaria_colaboradores.iugu_token_live,
                conta_bancaria_colaboradores.nome_titular,
                EXISTS(
                    SELECT 1
                    FROM colaboradores_prioridade_pagamento
                    WHERE colaboradores_prioridade_pagamento.id_transferencia = '0'
                    AND colaboradores_prioridade_pagamento.id_conta_bancaria = conta_bancaria_colaboradores.id
                ) AS `tem_transferencia_em_aberto`
            FROM conta_bancaria_colaboradores
            WHERE conta_bancaria_colaboradores.id_iugu = :id_subconta"
        );
        $sql->bindValue(':id_subconta', $subconta, PDO::PARAM_STR);
        $sql->execute();
        $retornoDadosSubconta = $sql->fetch(PDO::FETCH_ASSOC);
        return $retornoDadosSubconta;
    }

    public static function dadosContasBancarias(): array
    {
        $dados = DB::select(
            "SELECT conta_bancaria_colaboradores.id,
                conta_bancaria_colaboradores.nome_titular,
                conta_bancaria_colaboradores.cpf_titular,
                conta_bancaria_colaboradores.id_banco,
                conta_bancaria_colaboradores.agencia,
                conta_bancaria_colaboradores.conta,
                (
                    SELECT bancos.nome
                    FROM bancos
                    WHERE bancos.cod_banco = conta_bancaria_colaboradores.id_banco
                ) AS `banco`
            FROM conta_bancaria_colaboradores;"
        );
        return $dados;
    }

    public static function alteraDadosBancarios(
        int $idConta,
        int $codBanco,
        string $agencia,
        string $conta,
        string $nome
    ): void {
        $linhasAfetadas = DB::update(
            "UPDATE conta_bancaria_colaboradores
            SET conta_bancaria_colaboradores.id_banco = :cod_banco,
                conta_bancaria_colaboradores.agencia = :agencia,
                conta_bancaria_colaboradores.conta = :conta,
                conta_bancaria_colaboradores.nome_titular = :nome
            WHERE conta_bancaria_colaboradores.id = :id_conta
        ",
            [
                'id_conta' => $idConta,
                'cod_banco' => $codBanco,
                'agencia' => $agencia,
                'conta' => $conta,
                'nome' => $nome,
            ]
        );

        if ($linhasAfetadas !== 1) {
            throw new Exception('Não foi possível modificar os Dados Bancarios');
        }
    }
}

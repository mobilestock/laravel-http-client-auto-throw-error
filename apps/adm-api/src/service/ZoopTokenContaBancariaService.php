<?php

namespace MobileStock\service;

use MobileStock\helper\DB;
use MobileStock\model\ContaBancaria;
use PDO;
use Throwable;

//use Throwable;

class ZoopTokenContaBancariaService
{
    public static function criaContaBancariaPay(ContaBancaria $contaBancaria, object $dadosIugu, PDO $conn, int $phone)
    {
        $prioridade = 'P';
        $contaBancaria->setPrioridade($prioridade);
        $taxpayerId = str_replace('-', '', str_replace('.', '', str_replace('/', '', $contaBancaria->getTaxpayerId())));

        DB::exec(
            'INSERT INTO conta_bancaria_colaboradores(
                id_cliente,
                conta,
                agencia,
                id_banco,
                cpf_titular,
                nome_titular,
                prioridade,
                tipo,
                iugu_token_live,
                id_iugu,
                iugu_token_user,
                iugu_token_teste,
                phone
            ) values
            (
                :id_colaborador,
                :conta,
                :agencia,
                :id_banco,
                :cpf_titular,
                :nome_titular,
                :prioridade,
                :tipo,
                :iugu_token_live,
                :id_iugu,
                :iugu_token_user,
                :iugu_token_teste,
                :phone
            )',
            [
                ':id_colaborador' => $contaBancaria->getColaborador()->getId(),
                ':conta' => $contaBancaria->getAccountNumber(),
                ':agencia' => $contaBancaria->getRoutingNumber(),
                ':id_banco' => $contaBancaria->getBankCode(),
                ':cpf_titular' => $taxpayerId,
                ':nome_titular' => $contaBancaria->getHolderName(),
                ':prioridade' => $prioridade,
                ':tipo' => $contaBancaria->getType(),
                ':iugu_token_live' => $dadosIugu->live_api_token,
                ':id_iugu' => $dadosIugu->account_id,
                ':iugu_token_user' => $dadosIugu->user_token,
                ':iugu_token_teste' => $dadosIugu->test_api_token,
                ':phone' => $phone,
            ],
            $conn
        );

        return $conn->lastInsertId();
    }

    public static function verificaExisteContaBancariaJaCadastrada(
        ContaBancaria $contaBancaria,
        PDO $conexao,
        ?string $cpf = null
    ) {
        $sqlAdd = '';
        $bind = [
            ':conta' => $contaBancaria->getAccountNumber(),
            ':agencia' => $contaBancaria->getRoutingNumber(),
            ':bancoId' => $contaBancaria->getBankCode(),
        ];
        if ($cpf) {
            $sqlAdd = 'AND cpf_titular = :cpf';
            $bind[':cpf'] = $cpf;
        }

        $result = DB::select(
            "SELECT * FROM conta_bancaria_colaboradores
            WHERE conta = :conta
            AND agencia = :agencia
            AND id_banco = :bancoId
            $sqlAdd ORDER BY id DESC LIMIT 1",
            $bind,
            $conexao
        );

        if (!empty($result)) {
            return $result[0]['id'];
        } else {
            return false;
        }
    }

    public static function deletaBancoDados(PDO $conexao, int $id_cliente)
    {
        $sql = "DELETE FROM conta_bancaria_colaboradores WHERE id_cliente = $id_cliente";
        $stmt = $conexao->prepare($sql);
        return $stmt->execute();
    }
    // public static function deletaConta(PDO $conexao, int $id){
    //     $sql= "DELETE FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = $id";
    //     $stmt = $conexao->prepare($sql);
    //     return $stmt->execute();

    // }

    public static function buscaIDBanco(string $nome, PDO $conexao)
    {
        $sql = "SELECT cod_banco FROM bancos WHERE  bancos.nome LIKE UPPER('%$nome%') ORDER BY id ASC LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        return $lista['cod_banco'] != null ? $lista['cod_banco'] : 0;
    }
    public static function buscaConta(ContaBancaria $contaBancaria, PDO $conexao)
    {
        $result = DB::select(
            'SELECT * FROM conta_bancaria_colaboradores
            WHERE conta = :conta
            AND agencia = :agencia
            AND id_banco = :bancoId',
            [
                ':conta' => $contaBancaria->getAccountNumber(),
                ':agencia' => $contaBancaria->getRoutingNumber(),
                ':bancoId' => $contaBancaria->getBankCode(),
            ],
            $conexao
        );
        if (!empty($result)) {
            return $result['id'];
        }
    }
}

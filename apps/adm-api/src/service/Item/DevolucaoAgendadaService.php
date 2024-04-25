<?php

namespace MobileStock\service\Item;

use MobileStock\model\TaxaDevolucao;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use PDO;

class DevolucaoAgendadaService
{
    public static function salvaProdutoTrocaAgendada(PDO $conexao, string $uuid, int $idCliente): void
    {
        $produto = EntregasDevolucoesItemServices::buscarProdutoSemAgendamento(
            $conexao,
            $uuid
        );

        ['preco' => $precoCliente] = TransacaoFinanceiraItemProdutoService::buscaComissoesProduto($conexao, $uuid);
        $taxa = new TaxaDevolucao($precoCliente, $produto["data_base_troca"]);

        $sql = "INSERT IGNORE INTO troca_pendente_agendamento (
            troca_pendente_agendamento.id_produto,
            troca_pendente_agendamento.id_cliente,
            troca_pendente_agendamento.nome_tamanho,
            troca_pendente_agendamento.preco,
            troca_pendente_agendamento.taxa,
            troca_pendente_agendamento.uuid,
            troca_pendente_agendamento.data_hora,
            troca_pendente_agendamento.data_vencimento
        ) VALUES (
            :id_produto,
            :id_cliente,
            :nome_tamanho,
            :preco,
            :taxa,
            :uuid,
            :data_hora,
            '0000-00-00'
        );";
        $sql = $conexao->prepare($sql);
        $sql->bindValue(':id_produto', $produto["id_produto"], PDO::PARAM_INT);
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $produto["nome_tamanho"], PDO::PARAM_STR);
        $sql->bindValue(':preco', $precoCliente, PDO::PARAM_STR);
        $sql->bindValue(':taxa', $taxa->getTaxa(), PDO::PARAM_STR);
        $sql->bindValue(':uuid', $produto["uuid_produto"], PDO::PARAM_STR);
        $sql->bindValue(':data_hora', $produto["data_base_troca"], PDO::PARAM_STR);
        $sql->execute();
    }

    // public static function removeProdutoTrocaAgendada(PDO $con, string $uuid, int $idCliente)
    // {
    //     $sql = "DELETE FROM troca_pendente_agendamento WHERE uuid = '{$uuid}' AND id_cliente={$idCliente};";
    //     $stm = $con->prepare($sql);
    //     $stm->execute();
    // }
}

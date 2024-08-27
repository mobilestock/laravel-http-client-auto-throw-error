<?php

namespace MobileStock\service\Item;

use Illuminate\Support\Facades\DB;
use MobileStock\model\TaxaDevolucao;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;

class DevolucaoAgendadaService
{
    public static function salvaProdutoTrocaAgendada(
        int $idCliente,
        int $idProduto,
        string $nomeTamanho,
        string $dataBaseTroca,
        string $uuidProduto
    ): void {
        ['preco' => $precoCliente] = TransacaoFinanceiraItemProdutoService::buscaComissoesProduto(
            DB::getPdo(),
            $uuidProduto
        );
        $taxa = new TaxaDevolucao($precoCliente, $dataBaseTroca);

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

        DB::insert($sql, [
            ':id_produto' => $idProduto,
            ':id_cliente' => $idCliente,
            ':nome_tamanho' => $nomeTamanho,
            ':preco' => $precoCliente,
            ':taxa' => $taxa->getTaxa(),
            ':uuid' => $uuidProduto,
            ':data_hora' => $dataBaseTroca,
        ]);
    }

    // public static function removeProdutoTrocaAgendada(PDO $con, string $uuid, int $idCliente)
    // {
    //     $sql = "DELETE FROM troca_pendente_agendamento WHERE uuid = '{$uuid}' AND id_cliente={$idCliente};";
    //     $stm = $con->prepare($sql);
    //     $stm->execute();
    // }
}

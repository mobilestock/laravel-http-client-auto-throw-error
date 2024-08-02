<?php

namespace MobileStock\service\Item;

use Illuminate\Support\Facades\DB;
use MobileStock\model\TaxaDevolucao;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;

class DevolucaoAgendadaService
{
    public static function salvaProdutoTrocaAgendada(string $uuidProduto, int $idCliente): void
    {
        $produto = EntregasDevolucoesItemServices::buscarProdutoSemAgendamento($uuidProduto);

        ['preco' => $precoCliente] = TransacaoFinanceiraItemProdutoService::buscaComissoesProduto(
            DB::getPdo(),
            $uuidProduto
        );
        $taxa = new TaxaDevolucao($precoCliente, $produto['data_base_troca']);

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
            ':id_produto' => $produto['id_produto'],
            ':id_cliente' => $idCliente,
            ':nome_tamanho' => $produto['nome_tamanho'],
            ':preco' => $precoCliente,
            ':taxa' => $taxa->getTaxa(),
            ':uuid' => $produto['uuid_produto'],
            ':data_hora' => $produto['data_base_troca'],
        ]);
    }

    // public static function removeProdutoTrocaAgendada(PDO $con, string $uuid, int $idCliente)
    // {
    //     $sql = "DELETE FROM troca_pendente_agendamento WHERE uuid = '{$uuid}' AND id_cliente={$idCliente};";
    //     $stm = $con->prepare($sql);
    //     $stm->execute();
    // }
}

<?php

namespace MobileStock\service\TransacaoFinanceira;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceirasProdutosTroca;
use PDO;
use RuntimeException;

class TransacaoFinanceirasProdutosTrocasService extends TransacaoFinanceirasProdutosTroca
{
    public function buscaTrocasPendentes(PDO $conexao, $id_cliente_consumidor)
    {
        if (!$id_cliente_consumidor) {
            return [];
        }

        $stmt = $conexao->prepare(
            "INSERT INTO transacao_financeiras_produtos_trocas (
                transacao_financeiras_produtos_trocas.id_cliente,
                transacao_financeiras_produtos_trocas.id_transacao,
                transacao_financeiras_produtos_trocas.uuid
            ) SELECT
                troca_pendente_agendamento.id_cliente,
                0,
                troca_pendente_agendamento.uuid
            FROM troca_pendente_agendamento
            WHERE NOT EXISTS(SELECT 1 FROM transacao_financeiras_produtos_trocas WHERE transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid)
            AND troca_pendente_agendamento.id_cliente = :id_cliente_consumidor
            AND troca_pendente_agendamento.tipo_agendamento = 'ML'"
        );
        $stmt->bindParam(':id_cliente_consumidor', $id_cliente_consumidor, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "SELECT
            troca_pendente_agendamento.id_produto idProduto,
            troca_pendente_agendamento.nome_tamanho tamanho,
            transacao_financeiras_produtos_trocas.uuid,
            produtos.nome_comercial nome,
            (select produtos_foto.caminho  FROM produtos_foto WHERE produtos.id = produtos_foto.id ORDER BY produtos_foto.tipo_foto = 'MD' DESC LIMIT 1 )foto,
            (
                SELECT produtos_grade.cod_barras
                FROM produtos_grade
                WHERE produtos_grade.id_produto = troca_pendente_agendamento.id_produto
                    AND produtos_grade.nome_tamanho = troca_pendente_agendamento.nome_tamanho
            )codBarras,
            troca_pendente_agendamento.preco,
            transacao_financeiras_produtos_trocas.id_nova_transacao,
            0 bipado,
            CONCAT('produto/',pedido_item_meu_look.id_produto,'?w=',pedido_item_meu_look.uuid) AS `qrcode`
        FROM
            transacao_financeiras_produtos_trocas
            INNER JOIN troca_pendente_agendamento ON troca_pendente_agendamento.uuid = transacao_financeiras_produtos_trocas.uuid
            INNER JOIN produtos ON produtos.id = troca_pendente_agendamento.id_produto
            LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = troca_pendente_agendamento.uuid
        WHERE
            troca_pendente_agendamento.id_cliente = :id_cliente_consumidor AND transacao_financeiras_produtos_trocas.situacao = 'PE'
        GROUP BY uuid;";
        $prepare = $conexao->prepare($sql);
        $prepare->bindParam(':id_cliente_consumidor', $id_cliente_consumidor, PDO::PARAM_INT);
        $prepare->execute();
        $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);

        $listaDeTransacoes = [];

        foreach ($dados as $item) {
            if ($item['id_nova_transacao'] > 0 && !in_array($item['id_nova_transacao'], $listaDeTransacoes)) {
                $listaDeTransacoes[] = $item['id_nova_transacao'];
            }
        }
        $sqlTransacoesPendentes = "SELECT
            transacao_financeiras.id transacao,
            transacao_financeiras.qrcode_pix pix,
            transacao_financeiras.valor_liquido valor
        FROM transacao_financeiras
        WHERE transacao_financeiras.origem_transacao = 'ET'
        AND transacao_financeiras.status = 'PE'
        AND transacao_financeiras.pagador = :id_cliente_consumidor";

        $prepare2 = $conexao->prepare($sqlTransacoesPendentes);
        $prepare2->bindParam(':id_cliente_consumidor', $id_cliente_consumidor, PDO::PARAM_INT);
        $prepare2->execute();
        $transacoesPixRetorno = $prepare2->fetchAll(PDO::FETCH_ASSOC);
        $transacoesPix = array_map(function ($item) {
            $item['transacao'] = (int) $item['transacao'];
            $item['valor'] = (float) $item['valor'];
            return $item;
        }, $transacoesPixRetorno);

        return [
            'produtos' => array_map(function ($item) {
                unset($item['id_nova_transacao']);
                $item['bipado'] = false;
                $item['idProduto'] = (int) $item['idProduto'];
                $item['preco'] = (float) $item['preco'];
                return $item;
            }, $dados),
            'transacoesPendentes' => $transacoesPix,
        ];
    }

    public function atualizaNovaTransacaoTroca(PDO $conexao)
    {
        $conexao->exec(
            "UPDATE transacao_financeiras_produtos_trocas
                SET transacao_financeiras_produtos_trocas.id_nova_transacao = $this->id_nova_transacao
               WHERE transacao_financeiras_produtos_trocas.id_cliente = $this->id_cliente
                AND transacao_financeiras_produtos_trocas.situacao = 'PE'"
        );
    }

    public static function removeProdutosTrocaUuid(PDO $conexao, $uuid, $idCliente)
    {
        $conexao->exec(
            "DELETE FROM transacao_financeiras_produtos_trocas WHERE transacao_financeiras_produtos_trocas.uuid = $uuid AND transacao_financeiras_produtos_trocas.id_cliente = $idCliente"
        );
    }
    public static function removeProdutosTroca(PDO $conexao, int $idTransacao)
    {
        $conexao->exec(
            "DELETE FROM transacao_financeiras_produtos_trocas WHERE transacao_financeiras_produtos_trocas.id_transacao = $idTransacao"
        );
    }

    public static function converteDebitoPendenteParaNormalSeNecessario(int $idColaborador): void
    {
        $query = "SELECT
                lancamento_financeiro_pendente.id AS `sequencia`,
                lancamento_financeiro_pendente.tipo,
                lancamento_financeiro_pendente.documento,
                lancamento_financeiro_pendente.situacao,
                lancamento_financeiro_pendente.origem,
                lancamento_financeiro_pendente.id_colaborador,
                lancamento_financeiro_pendente.valor,
                lancamento_financeiro_pendente.valor_total,
                0 AS `valor_pago`,
                lancamento_financeiro_pendente.id_usuario,
                lancamento_financeiro_pendente.id_usuario_pag,
                lancamento_financeiro_pendente.observacao,
                lancamento_financeiro_pendente.tabela,
                lancamento_financeiro_pendente.pares,
                lancamento_financeiro_pendente.transacao_origem,
                lancamento_financeiro_pendente.cod_transacao,
                lancamento_financeiro_pendente.bloqueado,
                lancamento_financeiro_pendente.id_split,
                lancamento_financeiro_pendente.parcelamento,
                lancamento_financeiro_pendente.juros,
                COALESCE(lancamento_financeiro_pendente.numero_documento, '') AS `numero_documento`
            FROM
                lancamento_financeiro_pendente
            WHERE lancamento_financeiro_pendente.origem IN ( 'PC', 'ES' ) AND lancamento_financeiro_pendente.id_colaborador = :idColaborador

                AND NOT EXISTS(SELECT 1 FROM lancamento_financeiro WHERE lancamento_financeiro.sequencia = lancamento_financeiro_pendente.id)";

        $lancamentos = DB::select($query, ['idColaborador' => $idColaborador]);

        if (empty($lancamentos)) {
            return;
        }

        $lancamentoFinanceiro = DB::table('lancamento_financeiro');
        $lancamentoFinanceiroPendente = DB::table('lancamento_financeiro_pendente');

        $idsLancamentoPendente = array_column($lancamentos, 'sequencia');

        /**
         * lancamento_financeiro.sequencia,
         * lancamento_financeiro.tipo,
         * lancamento_financeiro.documento,
         * lancamento_financeiro.situacao,
         * lancamento_financeiro.origem,
         * lancamento_financeiro.id_colaborador,
         * lancamento_financeiro.valor,
         * lancamento_financeiro.valor_total,
         * lancamento_financeiro.valor_pago,
         * lancamento_financeiro.id_usuario,
         * lancamento_financeiro.id_usuario_pag,
         * lancamento_financeiro.observacao,
         * lancamento_financeiro.tabela,
         * lancamento_financeiro.pares,
         * lancamento_financeiro.transacao_origem,
         * lancamento_financeiro.cod_transacao,
         * lancamento_financeiro.bloqueado,
         * lancamento_financeiro.id_split,
         * lancamento_financeiro.parcelamento,
         * lancamento_financeiro.juros,
         * lancamento_financeiro.numero_documento
         */
        $stmt = DB::getPdo()->prepare(
            $lancamentoFinanceiro->grammar->compileInsert($lancamentoFinanceiro, $lancamentos) .
                ';' .
                $lancamentoFinanceiroPendente->grammar->compileDelete(
                    $lancamentoFinanceiroPendente->whereIn('lancamento_financeiro_pendente.id', $idsLancamentoPendente)
                )
        );

        $stmt->execute(
            array_merge(
                $lancamentoFinanceiro->cleanBindings(Arr::flatten($lancamentos, 1)),
                $lancamentoFinanceiroPendente->cleanBindings($idsLancamentoPendente)
            )
        );

        $linhasAtualizadas = 0;
        do {
            $linhasAtualizadas += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if (count($lancamentos) + count($idsLancamentoPendente) !== $linhasAtualizadas) {
            throw new RuntimeException('Falha ao inserir os produtos na logistica.');
        }
    }
    public static function sincronizaTrocaPendenteAgendamentoSeNecessario(int $idColaborador): void
    {
        DB::insert(
            "INSERT INTO transacao_financeiras_produtos_trocas (
                transacao_financeiras_produtos_trocas.id_cliente,
                transacao_financeiras_produtos_trocas.id_transacao,
                transacao_financeiras_produtos_trocas.uuid
            ) SELECT
                troca_pendente_agendamento.id_cliente,
                0,
                troca_pendente_agendamento.uuid
            FROM troca_pendente_agendamento
            WHERE
                troca_pendente_agendamento.id_cliente = :idColaborador
                AND troca_pendente_agendamento.tipo_agendamento = 'ML'
                AND NOT EXISTS(
                    SELECT 1
                    FROM transacao_financeiras_produtos_trocas
                    WHERE
                        transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid
                );",
            [
                'idColaborador' => $idColaborador,
            ]
        );
    }

    public static function desvinculaPixDeTroca(PDO $conexao, int $idTransacao, int $idCliente): void
    {
        $stmt = $conexao->prepare(
            "UPDATE transacao_financeiras_produtos_trocas
                SET transacao_financeiras_produtos_trocas.id_nova_transacao = NULL
             WHERE transacao_financeiras_produtos_trocas.id_nova_transacao = :id_transacao
               AND transacao_financeiras_produtos_trocas.id_cliente = :id_cliente"
        );
        $stmt->bindValue(':id_transacao', $idTransacao, PDO::PARAM_INT);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
    }
}

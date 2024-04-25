<?php

namespace MobileStock\service\Troca;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Globals;
use MobileStock\jobs\Troca;
use MobileStock\model\Entrega;
use MobileStock\model\LancamentoPendente;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\TrocaAgendadaItem;
use MobileStock\model\TrocaPendenteItemModel;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Lancamento\LancamentoConsultas;
use MobileStock\service\Pagamento\LancamentoPendenteService;
use PDO;

class TrocasService
{
    public $idCliente;

    public function filtraSomenteTrocasValidas(array $uuids): array
    {
        [$sql, $bind] = ConversorArray::criaBindValues($uuids);

        $uuids = DB::selectColumns(
            "SELECT transacao_financeiras_produtos_trocas.uuid
             FROM transacao_financeiras_produtos_trocas
             WHERE transacao_financeiras_produtos_trocas.situacao = 'PE'
               AND transacao_financeiras_produtos_trocas.uuid IN ($sql)",
            $bind
        );

        return $uuids;
    }

    public function pontoAceitaTroca(string $uuid): void
    {
        $rowCount = DB::update(
            "UPDATE transacao_financeiras_produtos_trocas
                SET transacao_financeiras_produtos_trocas.situacao = 'PA'
              WHERE transacao_financeiras_produtos_trocas.uuid = :uuid
                AND transacao_financeiras_produtos_trocas.situacao = 'PE'",
            ['uuid' => $uuid]
        );

        if ($rowCount !== 1) {
            throw new \DomainException('Não foi possível aceitar a troca');
        }

        $idCliente = DB::selectOneColumn(
            "SELECT logistica_item.id_cliente
             FROM logistica_item
             WHERE logistica_item.uuid_produto = :uuid",
            ['uuid' => $uuid]
        );

        $lancamentos = DB::select(
            "SELECT
                lancamento_financeiro_pendente.id,
                lancamento_financeiro_pendente.sequencia,
                lancamento_financeiro_pendente.tipo,
                lancamento_financeiro_pendente.documento,
                lancamento_financeiro_pendente.situacao,
                lancamento_financeiro_pendente.origem,
                lancamento_financeiro_pendente.id_colaborador,
                lancamento_financeiro_pendente.valor,
                lancamento_financeiro_pendente.valor_total,
                lancamento_financeiro_pendente.id_usuario_pag,
                lancamento_financeiro_pendente.observacao,
                lancamento_financeiro_pendente.tabela,
                lancamento_financeiro_pendente.pares,
                lancamento_financeiro_pendente.transacao_origem,
                lancamento_financeiro_pendente.pedido_origem,
                lancamento_financeiro_pendente.cod_transacao,
                lancamento_financeiro_pendente.bloqueado,
                lancamento_financeiro_pendente.id_split,
                lancamento_financeiro_pendente.parcelamento,
                lancamento_financeiro_pendente.juros,
                COALESCE(lancamento_financeiro_pendente.numero_documento, '') numero_documento
            FROM lancamento_financeiro_pendente
            WHERE (lancamento_financeiro_pendente.numero_documento = :uuid
                       OR lancamento_financeiro_pendente.origem = 'PC'
                              AND lancamento_financeiro_pendente.id_colaborador = :id_cliente
                );",
            ['uuid' => $uuid, 'id_cliente' => $idCliente]
        );

        $ids = array_column($lancamentos, 'id');

        $idUsuario = Auth::user()->id;
        foreach ($lancamentos as &$lancamento) {
            $lancamento['id_usuario'] = $idUsuario;
            $lancamento['valor_pago'] = 0;
            unset($lancamento['id']);
        }

        $pendenteBuilder = DB::table('lancamento_financeiro_pendente');
        $normalBuilder = DB::table('lancamento_financeiro');

        $stmt = DB::getPdo()->prepare(
            $pendenteBuilder->grammar->compileDelete(
                $pendenteBuilder->whereIn('lancamento_financeiro_pendente.id', $ids)
            ) .
                ';' .
                $normalBuilder->grammar->compileInsert($normalBuilder, $lancamentos)
        );

        $binds = $pendenteBuilder->cleanBindings($pendenteBuilder->grammar->prepareBindingsForDelete($ids));
        array_push($binds, ...$normalBuilder->cleanBindings(Arr::flatten($lancamentos, 1)));
        $stmt->execute($binds);

        $linhasAtualizadas = 0;
        do {
            $linhasAtualizadas += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if ($linhasAtualizadas !== count($lancamentos) * 2) {
            throw new \DomainException('Problema ao abater os lançamentos da troca');
        }

        $troca = new TrocaPendenteItemModel(
            DB::selectOne(
                "SELECT
                    troca_pendente_agendamento.id_produto,
                    troca_pendente_agendamento.nome_tamanho,
                    troca_pendente_agendamento.uuid,
                    troca_pendente_agendamento.preco,
                    troca_pendente_agendamento.defeito = 'T' defeito
                FROM troca_pendente_agendamento
                WHERE troca_pendente_agendamento.uuid = ?;",
                [$uuid]
            )
        );
        $troca->id_cliente = $idCliente;
        $troca->save();

        $linhasAlteradas = DB::delete(
            "DELETE FROM troca_pendente_agendamento
             WHERE troca_pendente_agendamento.uuid = ?",
            [$uuid]
        );

        if ($linhasAlteradas !== 1) {
            throw new \DomainException('Problema ao remover a troca agendada');
        }

        $logisticaItem = new LogisticaItemModel();
        $logisticaItem->exists = true;
        $logisticaItem->setKeyName('uuid_produto');
        $logisticaItem->setKeyType('string');

        $logisticaItem->uuid_produto = $uuid;
        $logisticaItem->situacao = $troca->defeito ? 'DF' : 'DE';

        $logisticaItem->update();
        dispatch(new Troca($uuid));
    }

    public static function buscaTrocasAgendadas(): array
    {
        $sql = "SELECT
                    saldo_cliente_bloqueado(colaboradores.id) saldo_cliente_bloqueado,
                    colaboradores.id id_cliente,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT(
                            'foto', (
                                SELECT
                                    produtos_foto.caminho
                                FROM produtos_foto
                                WHERE
                                    produtos_foto.id = troca_pendente_agendamento.id_produto
                                ORDER BY produtos_foto.tipo_foto = 'MD'
                                LIMIT 1
                            ),
                            'nome_comercial', produtos.nome_comercial,
                            'id_produto', produtos.id,
                            'data_limite', DATE_FORMAT(IF(troca_pendente_agendamento.defeito = 'T',
                                DATE_ADD(entregas_faturamento_item.data_base_troca, INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_defeito FROM configuracoes LIMIT 1) DAY),
                                DATE_ADD(entregas_faturamento_item.data_base_troca, INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1) DAY)
                                ), '%d/%m/%Y')
                            )),
                        ']'
                    ) json_produtos_agendados
                FROM colaboradores
                INNER JOIN troca_pendente_agendamento ON troca_pendente_agendamento.id_cliente = colaboradores.id
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = troca_pendente_agendamento.uuid
                INNER JOIN produtos ON produtos.id = troca_pendente_agendamento.id_produto
                WHERE colaboradores.id = :idCliente
                GROUP BY colaboradores.id
                HAVING saldo_cliente_bloqueado > 0";
        $dados = DB::selectOne($sql, [
            'idCliente' => Auth::user()->id_colaborador,
        ]);

        if (!$dados) {
            return [];
        }
        $dados['qrcode'] = Globals::geraQRCODE(Entrega::formataEtiquetaCliente($dados['id_cliente']));
        return $dados;
    }
    public function salvaAgendamento(array $infosProduto, array $produto, bool $forcaTroca = false): void
    {
        if (!$this->idCliente) {
            $this->idCliente = $produto['id_cliente'];
        }
        if (!isset($infosProduto['uuid'])) {
            $infosProduto['uuid'] = $produto['uuid'];
        }
        $produtoFaturamentoItem = $infosProduto[$produto['uuid']] ?? false;
        if (!$produtoFaturamentoItem) {
            $produtoFaturamentoItem = $infosProduto;
        }
        $produto['situacao'] ??= '';

        $preco = array_reduce(
            $produtoFaturamentoItem['debitos'],
            function (float $precoAtual, array $item) use (&$produtoFaturamentoItem, $produto) {
                if ($produto['situacao'] !== 'defeito' && $item['tipo_comissao'] === 'CM_ENTREGA') {
                    $key = array_search($item, $produtoFaturamentoItem['debitos']);
                    unset($produtoFaturamentoItem['debitos'][$key]);

                    return $precoAtual;
                }

                return $precoAtual + $item['preco_comissao'];
            },
            0
        );

        if ($produto['situacao'] === 'defeito') {
            $produtoFaturamentoItem['debitos'] = array_values(
                array_filter(
                    $produtoFaturamentoItem['debitos'],
                    fn(array $debito) => $debito['origem_lancamento'] === 'TF'
                )
            );

            $produtoFaturamentoItem['debitos'][0]['valor_debito'] = $preco;
        }

        foreach ($produtoFaturamentoItem['debitos'] as $debito) {
            $lancamento = new LancamentoPendente(
                'R',
                1,
                $debito['origem_lancamento'],
                $debito['id_colaborador'],
                date('Y-m-d H:i:s'),
                $debito['valor_debito'],
                1,
                15
            );
            $lancamento->numero_documento = $produto['uuid'];
            $lancamento->pedido_origem = $produtoFaturamentoItem['pedido_origem'];
            $lancamento->transacao_origem = $produtoFaturamentoItem['transacao_origem'];

            if ($produtoFaturamentoItem['origem'] !== 'MS') {
                LancamentoPendenteService::criar(DB::getPdo(), $lancamento);
            }
        }

        $creditoCliente = new LancamentoPendente('P', 1, 'TR', $this->idCliente, date('Y-m-d H:i:s'), $preco, 1, 15);

        $creditoCliente->pedido_origem = $produtoFaturamentoItem['pedido_origem'];
        $creditoCliente->transacao_origem = $produtoFaturamentoItem['transacao_origem'];
        $creditoCliente->numero_documento = $produto['uuid'];
        $creditoCliente->observacao = $produto['observacao'] ?? '';

        $agendamento = new TrocaAgendadaItem(
            $this->idCliente,
            $produtoFaturamentoItem['id_produto'],
            $produtoFaturamentoItem['nome_tamanho'],
            $preco,
            $produto['uuid'],
            date('Y-m-d H:i:s')
        );

        $diasTroca = ConfiguracaoService::calculaDiasTrocaPorDataEntrega(
            $produtoFaturamentoItem['data_atualizacao_entrega']
        );

        if ($produto['situacao'] === 'defeito') {
            $agendamento->setDefeitoAgendamento(true);
            $creditoCliente->observacao = $produto['descricao_defeito'];
            $agendamento->setDataVencimento($diasTroca['data_defeito']);
        } else {
            $agendamento->setDataVencimento($diasTroca['data_normal']);
        }

        if ($produtoFaturamentoItem['origem'] !== 'MS') {
            LancamentoPendenteService::criar(DB::getPdo(), $creditoCliente);
        }

        if (empty($produtoFaturamentoItem['origem'])) {
            throw new Exception('Não foi possível identificar a origem do produto');
        }
        $agendamento->setTipoAgendamento($produtoFaturamentoItem['origem']);
        TrocaPendenteCrud::salva($agendamento, DB::getPdo(), $forcaTroca);
    }
    public static function insereTrocaForcada(PDO $conexao, int $idCliente, string $uuid): void
    {
        $query = "SELECT 1
                FROM transacao_financeiras_produtos_trocas
                WHERE transacao_financeiras_produtos_trocas.uuid = :uuid
                AND transacao_financeiras_produtos_trocas.id_cliente = :idCliente";

        $prepare = $conexao->prepare($query);
        $prepare->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $prepare->bindValue(':idCliente', (int) $idCliente, PDO::PARAM_INT);
        $prepare->execute();
        $dados = (bool) $prepare->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            $sql = "INSERT INTO transacao_financeiras_produtos_trocas
            (
                transacao_financeiras_produtos_trocas.id_cliente,
                transacao_financeiras_produtos_trocas.id_transacao,
                transacao_financeiras_produtos_trocas.uuid,
                transacao_financeiras_produtos_trocas.situacao,
                transacao_financeiras_produtos_trocas.id_nova_transacao
            )
                VALUES(:idCliente, 0, :uuid, 'PE', 0)";

            $prepare = $conexao->prepare($sql);
            $prepare->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $prepare->bindValue(':idCliente', (int) $idCliente, PDO::PARAM_INT);
            $prepare->execute();
        }
    }
    public static function forcaProcessoDevolucaoMS(
        PDO $conexao,
        string $uuid,
        int $idTransacao,
        int $idProduto,
        string $nomeTamanho,
        int $idUsuario
    ): void {
        $sql = $conexao->prepare(
            "INSERT INTO entregas_devolucoes_item
            (
              entregas_devolucoes_item.id_produto,
              entregas_devolucoes_item.nome_tamanho,
              entregas_devolucoes_item.id_entrega,
              entregas_devolucoes_item.id_transacao,
              entregas_devolucoes_item.id_ponto_responsavel,
              entregas_devolucoes_item.id_usuario,
              entregas_devolucoes_item.origem,
              entregas_devolucoes_item.tipo,
              entregas_devolucoes_item.uuid_produto,
              entregas_devolucoes_item.situacao
            )
            VALUES
            (
              COALESCE((SELECT entregas_faturamento_item.id_produto FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid), :id_produto),
              COALESCE((SELECT entregas_faturamento_item.nome_tamanho FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid), :nome_tamanho),
              COALESCE((SELECT entregas_faturamento_item.id_entrega FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid),0),
              COALESCE((SELECT entregas_faturamento_item.id_transacao FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid),COALESCE(:idTransacao, 0)),
              COALESCE((SELECT entregas.id_tipo_frete FROM entregas INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id WHERE entregas_faturamento_item.uuid_produto = :uuid LIMIT 1),0),
              :id_usuario,
              'MS',
              'NO',
              :uuid,
              'CO'
            )"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $sql->execute();
    }
    public static function forcaProcessoDevolucaoML(
        PDO $conexao,
        string $uuid,
        int $idTransacao,
        int $idProduto,
        string $nomeTamanho,
        int $idUsuario
    ): void {
        $sql = $conexao->prepare(
            "INSERT INTO entregas_devolucoes_item
            (
              entregas_devolucoes_item.id_produto,
              entregas_devolucoes_item.nome_tamanho,
              entregas_devolucoes_item.id_entrega,
              entregas_devolucoes_item.id_transacao,
              entregas_devolucoes_item.id_ponto_responsavel,
              entregas_devolucoes_item.id_usuario,
              entregas_devolucoes_item.origem,
              entregas_devolucoes_item.tipo,
              entregas_devolucoes_item.uuid_produto,
              entregas_devolucoes_item.situacao
            )
            VALUES
            (
              COALESCE((SELECT entregas_faturamento_item.id_produto FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid), :id_produto),
              COALESCE((SELECT entregas_faturamento_item.nome_tamanho FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid), :nome_tamanho),
              COALESCE((SELECT entregas_faturamento_item.id_entrega FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid),0),
              COALESCE((SELECT entregas_faturamento_item.id_transacao FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid),COALESCE(:idTransacao, 0)),
              COALESCE((SELECT entregas.id_tipo_frete FROM entregas INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id WHERE entregas_faturamento_item.uuid_produto = :uuid LIMIT 1),0),
              :id_usuario,
              'ML',
              'NO',
              :uuid,
              'CO'
            )"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $sql->execute();
    }
    public static function iniciaProcessoDevolucaoMS(
        PDO $conexao,
        string $uuid,
        int $idTransacao,
        int $idProduto,
        string $nomeTamanho,
        int $idUsuario
    ): void {
        $sql = $conexao->prepare(
            "INSERT INTO entregas_devolucoes_item
            (
              entregas_devolucoes_item.id_produto,
              entregas_devolucoes_item.nome_tamanho,
              entregas_devolucoes_item.id_entrega,
              entregas_devolucoes_item.id_transacao,
              entregas_devolucoes_item.id_ponto_responsavel,
              entregas_devolucoes_item.id_usuario,
              entregas_devolucoes_item.origem,
              entregas_devolucoes_item.tipo,
              entregas_devolucoes_item.uuid_produto,
              entregas_devolucoes_item.situacao
            )
            VALUES
            (
              COALESCE((SELECT entregas_faturamento_item.id_produto FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid), :id_produto),
              COALESCE((SELECT entregas_faturamento_item.nome_tamanho FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid), :nome_tamanho),
              COALESCE((SELECT entregas_faturamento_item.id_entrega FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid),0),
              COALESCE((SELECT entregas_faturamento_item.id_transacao FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = :uuid),COALESCE(:idTransacao, 0)),
              COALESCE((SELECT entregas.id_tipo_frete FROM entregas INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id WHERE entregas_faturamento_item.uuid_produto = :uuid LIMIT 1),0),
              :id_usuario,
              'MS',
              IF((SELECT troca_pendente_item.defeito FROM troca_pendente_item WHERE troca_pendente_item.uuid = :uuid) = 0, 'NO', 'DE'),
              :uuid,
              IF((SELECT troca_pendente_item.defeito FROM troca_pendente_item WHERE troca_pendente_item.uuid = :uuid) = 0, 'CO', 'RE')
            )"
        );
        $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
        $sql->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $sql->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $sql->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $sql->execute();
    }
    public static function iniciaProcessoDevolucaoML(string $uuid): void
    {
        $resultado = DB::selectOne(
            "SELECT
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.nome_tamanho,
                entregas_faturamento_item.id_entrega,
                entregas_faturamento_item.id_transacao,
                IF((SELECT troca_pendente_item.defeito FROM troca_pendente_item WHERE troca_pendente_item.uuid = entregas_faturamento_item.uuid_produto) = 0, 'NO', 'DE') tipo,
                IF(EXISTS(SELECT 1 FROM pedido_item_meu_look WHERE pedido_item_meu_look.uuid = entregas_faturamento_item.uuid_produto),
                    'PE',
                    IF((SELECT troca_pendente_item.defeito FROM troca_pendente_item WHERE troca_pendente_item.uuid = entregas_faturamento_item.uuid_produto) = 0,
                        'CO',
                        'RE'
                    )
                ) situacao,
                entregas_faturamento_item.id_responsavel_estoque,
                (SELECT tipo_frete.id
                 FROM tipo_frete
                 WHERE tipo_frete.id_colaborador = :id_colaborador) id_ponto_responsavel
            FROM entregas_faturamento_item
            WHERE entregas_faturamento_item.uuid_produto = :uuid;",
            ['uuid' => $uuid, 'id_colaborador' => Auth::user()->id_colaborador]
        );
        $resultado['id_usuario'] = Auth::user()->id;
        $resultado['uuid_produto'] = $uuid;
        $resultado['origem'] = 'ML';

        /**
         * entregas_devolucoes_item.id_produto
         * entregas_devolucoes_item.nome_tamanho
         * entregas_devolucoes_item.id_entrega
         * entregas_devolucoes_item.id_transacao
         * entregas_devolucoes_item.id_ponto_responsavel
         * entregas_devolucoes_item.id_usuario
         * entregas_devolucoes_item.tipo
         * entregas_devolucoes_item.uuid_produto
         * entregas_devolucoes_item.situacao
         * entregas_devolucoes_item.id_responsavel_estoque
         */
        if (!DB::table('entregas_devolucoes_item')->insert($resultado)) {
            throw new \DomainException('Não foi possível iniciar o processo de devolução');
        }
    }
    public static function condicaoSeDefeito(PDO $conexao, $produto)
    {
        $defeito = !empty($produto['defeito']);

        $query = $conexao->prepare(
            "UPDATE troca_pendente_item
                SET troca_pendente_item.defeito = :defeito
                WHERE troca_pendente_item.uuid = :uuid"
        );
        $query->bindValue(':defeito', (int) $defeito, PDO::PARAM_INT);
        $query->bindValue(':uuid', $produto['uuid'], PDO::PARAM_STR);
        $query->execute();
    }
    // public static function verificaSeEstaCadastrada(PDO $conexao, string $uuid): bool
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             1
    //         FROM troca_pendente_agendamento
    //         WHERE troca_pendente_agendamento.`uuid` = :uuid");
    //     $stmt->bindValue(':uuid', $uuid);
    //     $stmt->execute();
    //     $result = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return !empty($result);
    // }
    public static function buscaUUIDAgendamentoParaAlerta(string $uuidProduto): array
    {
        $resultado = DB::selectOne(
            "SELECT
                DATE_FORMAT(troca_pendente_agendamento.data_vencimento, '%d/%m/%Y') AS `prazo`,
                CONCAT('R$ ', troca_pendente_agendamento.preco) AS `preco`,
                COALESCE(colaboradores.telefone, colaboradores.telefone2) AS `telefone`
            FROM troca_pendente_agendamento
            JOIN colaboradores ON colaboradores.id = troca_pendente_agendamento.id_cliente
            WHERE troca_pendente_agendamento.`uuid` = :uuidProduto",
            [
                ':uuidProduto' => $uuidProduto,
            ]
        );
        return $resultado ?? [];
    }

    public function consultaValorTransacaoDesistirTroca(int $idCliente, array $uuids): float
    {
        [$sql, $bind] = ConversorArray::criaBindValues($uuids);
        $saldoCliente = LancamentoConsultas::consultaCreditoCliente(DB::getPdo(), $idCliente);
        $resposta = DB::selectOne(
            "SELECT (
                    GREATEST($saldoCliente, 0) + COALESCE((
                    SELECT SUM(troca_pendente_agendamento.preco)
                FROM troca_pendente_agendamento
                INNER JOIN transacao_financeiras_produtos_trocas ON transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid
                    WHERE transacao_financeiras_produtos_trocas.id_cliente = :id_cliente AND transacao_financeiras_produtos_trocas.uuid NOT IN ($sql)
                    ), 0) + COALESCE((
                    SELECT SUM(IF(lancamento_financeiro_pendente.tipo = 'P', lancamento_financeiro_pendente.valor, lancamento_financeiro_pendente.valor * - 1))
                    FROM lancamento_financeiro_pendente
                    WHERE lancamento_financeiro_pendente.id_colaborador = :id_cliente
                        AND lancamento_financeiro_pendente.origem IN ('PC', 'ES')), 0)
                ) saldo",
            ['id_cliente' => $idCliente] + $bind
        );
        return $resposta['saldo'];
    }

    public static function verificaSeEstaAgendado(string $uuidProduto): bool
    {
        $query = "SELECT
                    1
                FROM troca_pendente_agendamento
                WHERE troca_pendente_agendamento.uuid = :uuid_produto";

        $agendado = DB::selectOneColumn($query, [
            'uuid_produto' => $uuidProduto,
        ]);

        return (bool) $agendado;
    }

    public static function buscaTransacoesEsqueciTroca(PDO $conexao, int $idCliente): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                transacao_financeiras.id,
                transacao_financeiras.cod_transacao transacao,
                transacao_financeiras.pagador id_pagador,
                transacao_financeiras.emissor_transacao tipo,
                transacao_financeiras.qrcode_pix,
                transacao_financeiras.qrcode_text_pix,
                transacao_financeiras.origem_transacao,
                transacao_financeiras.data_criacao,
                transacao_financeiras.valor_liquido
            FROM transacao_financeiras
            WHERE transacao_financeiras.status = 'PE'
            AND  transacao_financeiras.origem_transacao = 'ET'
            AND transacao_financeiras.pagador = :id_cliente"
        );
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        $resposta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resposta;
    }
}

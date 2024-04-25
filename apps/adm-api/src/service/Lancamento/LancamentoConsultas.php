<?php

namespace MobileStock\service\Lancamento;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MobileStock\helper\ConversorArray;
use MobileStock\model\LogisticaItem;
use MobileStock\model\Usuario;
use PDO;

class LancamentoConsultas
{
    /**
     * @deprecated
     */
    public static function consultaCreditoCliente(PDO $conexo, int $id_cliente)
    {
        $consulta = $conexo->prepare('SELECT saldo_cliente(:id_cliente) valor');
        $consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $consulta->execute();
        $valores = $consulta->fetch();
        return $valores['valor'];
    }

    public static function consultaSitucaoSaldoCliente(PDO $conexo, int $id_cliente)
    {
        return $conexo->query('CALL saldo_cliente_detalhe(' . $id_cliente . ')')->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscaSaldoBloqueadoCliente(int $idCliente): float
    {
        $valor = DB::selectOneColumn('SELECT saldo_cliente_bloqueado(:id_cliente)', ['id_cliente' => $idCliente]);
        return $valor;
    }


    public static function buscaCreditosPorClientePay(
        PDO $conexao,
        int $idCliente,
        int $all = 0,
        string $inicio,
        string $fim,
        int $pagina
    ): array {
        $permissaoFornecedor = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;

        if ($inicio == '') {
            $inicio = date('Y-m-d', strtotime('-30 days'));
        }
        if ($fim == '') {
            $fim = date('Y-m-d');
        }
        if ($all == 0) {
            $filtro = " AND DATE(lancamento_financeiro.data_emissao) >= '{$inicio}' AND DATE(lancamento_financeiro.data_emissao) <= '{$fim}'";
        } else {
            $filtro = '';
        }

        $porPagina = 50;
        $offset = ($pagina - 1) * $porPagina;
        if ($offset > PHP_INT_MAX) {
            $offset = 0;
        }

        $query = "SELECT
            lancamento_financeiro.id,
            lancamento_financeiro.tipo,
            lancamento_financeiro.origem,
            lancamento_financeiro.id_prioridade_saque,
            lancamento_financeiro.valor preco,
            lancamento_financeiro.observacao,
            lancamento_financeiro.pedido_origem pedido_origem,
            lancamento_financeiro.transacao_origem transacao_origem,
            DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y') data_credito,
            UNIX_TIMESTAMP(lancamento_financeiro.data_emissao) utc,
            EXISTS(
                SELECT 1
                FROM usuarios
                WHERE usuarios.id_colaborador = lancamento_financeiro.id_colaborador
                AND usuarios.permissao REGEXP '$permissaoFornecedor'
            ) AS `eh_fornecedor`,
            CASE WHEN lancamento_financeiro.origem IN ('TC', 'TL', 'TE', 'CE', 'CC') THEN (
                SELECT produtos_foto.caminho
                    FROM produtos_foto
                    JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
                    AND transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro.numero_documento
                    WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                ORDER BY produtos_foto.tipo_foto IN ('MD', 'SM') DESC
                LIMIT 1
            ) ELSE
            (
                SELECT GROUP_CONCAT(
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                        ORDER BY produtos_foto.tipo_foto IN ('MD', 'SM') DESC
                        LIMIT 1
                    )
                )
                FROM transacao_financeiras_produtos_itens
                WHERE transacao_financeiras_produtos_itens.id_transacao = lancamento_financeiro.transacao_origem
                    AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                    AND CASE
                        WHEN lancamento_financeiro.origem = 'SC' THEN transacao_financeiras_produtos_itens.id_fornecedor = lancamento_financeiro.id_colaborador
                        WHEN lancamento_financeiro.origem IN ('TF', 'TR', 'ES', 'TR_LOGISTICA') THEN transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro.numero_documento
                        ELSE true
                    END
            )
            END imagens,
            CASE
                WHEN (lancamento_financeiro.origem IN ('PF','EM')) THEN
                    COALESCE((SELECT CONCAT(conta_bancaria_colaboradores.agencia, ' , ' , conta_bancaria_colaboradores.conta) FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = (SELECT colaboradores_prioridade_pagamento.id_conta_bancaria FROM colaboradores_prioridade_pagamento WHERE colaboradores_prioridade_pagamento.id = lancamento_financeiro.id_prioridade_saque LIMIT 1)), 0)
                ELSE 0
            END conta,
            lancamento_financeiro.valor_pago,
            lancamento_financeiro.faturamento_criado_pago,
            IF((SELECT 1 FROM entregas_devolucoes_item WHERE entregas_devolucoes_item.uuid_produto = lancamento_financeiro.numero_documento AND entregas_devolucoes_item.tipo = 'DE' LIMIT 1), 'Defeito', 'Normal') motivo_cancelamento
        FROM lancamento_financeiro
        LEFT JOIN transacao_financeiras ON transacao_financeiras.id = lancamento_financeiro.transacao_origem
        WHERE
            lancamento_financeiro.id_colaborador = :idCliente AND
            lancamento_financeiro.origem NOT IN ('AU', 'FA')
            {$filtro}
        GROUP BY lancamento_financeiro.id
        ORDER BY utc DESC
        LIMIT :por_pagina OFFSET :offset";

        $stm = $conexao->prepare($query);
        $stm->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        $stm->bindValue(':por_pagina', $porPagina, PDO::PARAM_INT);
        $stm->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stm->execute();
        $retorno = $stm->fetchAll(PDO::FETCH_ASSOC);
        $retorno = array_map(function ($resultado) {
            $resultado['eh_fornecedor'] = (bool) $resultado['eh_fornecedor'];
            return $resultado;
        }, $retorno);
        return $retorno;
    }

    public static function buscaRecebivelLancamento(PDO $conexao, array $lancamentos)
    {
        $lancamentos_novo = $lancamentos;
        foreach ($lancamentos as $key => $lanc) {
            $lancamentos_novo[$key]['recebivel'] = [];
            if ($lanc['id_prioridade_saque'] > 0) {
                $query =
                    "SELECT colaboradores_prioridade_pagamento.id,
                                colaboradores_prioridade_pagamento.id_conta_bancaria,
                                SUM(colaboradores_prioridade_pagamento.valor_pagamento)valor,
                                SUM(colaboradores_prioridade_pagamento.valor_pago)valor_pago,
                                colaboradores_prioridade_pagamento.situacao,
                                DATE(colaboradores_prioridade_pagamento.data_criacao) data_transferencia,
                                DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao,'%d/%m/%Y')data_gerado,
                                DATE(colaboradores_prioridade_pagamento.data_atualizacao)data_,
                                DATE_FORMAT(colaboradores_prioridade_pagamento.data_atualizacao,'%d/%m/%Y')data_vencimento,
                                1 as num_parcela
                                FROM colaboradores_prioridade_pagamento WHERE colaboradores_prioridade_pagamento.id = " .
                    $lanc['id_prioridade_saque'];
                $stm = $conexao->prepare($query);
                $stm->execute();
                $lista = $stm->fetchAll(PDO::FETCH_ASSOC);
                $lancamentos_novo[$key]['recebivel'] = $lista ? $lista : [];
            }
        }
        return $lancamentos_novo;
    }

    public static function buscaHistoricoTransferencia(PDO $conexao, int $idCliente)
    {
        $query = "SELECT lancamento_financeiro.id_colaborador
                    FROM lancamento_financeiro
                        WHERE lancamento_financeiro.id IN
                            (
                                SELECT lancamento_financeiro.lancamento_origem
                                    FROM lancamento_financeiro
                                        WHERE lancamento_financeiro.origem = 'PI'
                                        AND lancamento_financeiro.id_colaborador = $idCliente
                            )";
        $stm = $conexao->prepare($query);
        $stm->execute();
        $lista = $stm->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }

    public static function buscaLancamentosDia()
    {
        $lista = DB::select(
            "SELECT
                lancamento_financeiro.*,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = lancamento_financeiro.id_colaborador
                ) as Nome
            FROM lancamento_financeiro
            WHERE DATE(lancamento_financeiro.data_emissao) = DATE(NOW() - INTERVAL 1 DAY)"
        );

        $colaboradores = array_column($lista, 'id_colaborador');
        $colaboradores = array_unique($colaboradores);

        [$sql, $bind] = ConversorArray::criaBindValues($colaboradores);
        Event::listenOnce(function (StatementPrepared $event) {
            $event->statement->setFetchMode(PDO::FETCH_KEY_PAIR);
        });
        $saldos = DB::select(
            "SELECT
                 colaboradores.id,
                 saldo_cliente(colaboradores.id)
             FROM colaboradores
             WHERE colaboradores.id IN ($sql);",
            $bind
        );

        $lancamentosAgrupados = [];
        foreach ($lista as &$lancamento) {
            $idColaborador = $lancamento['id_colaborador'];
            $lancamentosAgrupados[$idColaborador][] = &$lancamento;
        }

        foreach ($saldos as $idColaborador => $saldo) {
            $lancamentosColaborador = &$lancamentosAgrupados[$idColaborador];

            foreach ($lancamentosColaborador as &$lancamento) {
                $lancamento['saldo_dia'] = $saldo;
            }
        }

        return $lista;
    }

    public static function buscaLancamentosFuturos(int $idCliente, bool $geral): array
    {
        $binds = [];
        $where = '';
        if (!$geral) {
            $where = 'AND transacao_financeiras_produtos_itens.id_fornecedor = :id_colaborador';
            $binds['id_colaborador'] = $idCliente;
        }
        $situacaoLogistica = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $resposta = DB::select(
            "SELECT DATE_FORMAT(
                    DATE_ADD(entregas_faturamento_item.data_entrega, INTERVAL 8 DAY),
                    '%d/%m/%Y'
                ) AS `data_previsao`,
                SUM(transacao_financeiras_produtos_itens.comissao_fornecedor) valor,
                CONCAT(
                    '[',
                        GROUP_CONCAT(
                                JSON_OBJECT(
                            'valor', transacao_financeiras_produtos_itens.comissao_fornecedor,
                            'recebedor', (SELECT colaboradores.razao_social
                                        FROM colaboradores
                                        WHERE colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor)
                            )
                        ),
                    ']'
                ) valores_json
            FROM transacao_financeiras_produtos_itens
                LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            WHERE 1=1
                $where
                AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL
                AND transacao_financeiras_produtos_itens.tipo_item <> 'AC'
                AND logistica_item.situacao <= $situacaoLogistica
                AND entregas_faturamento_item.situacao = 'EN'
                AND transacao_financeiras.origem_transacao = 'ML'
                AND DATE(entregas_faturamento_item.data_entrega + INTERVAL 7 DAY) >= CURRENT_DATE()
            GROUP BY DATE(entregas_faturamento_item.data_entrega)
            ORDER BY entregas_faturamento_item.data_entrega",
            $binds
        );
        return $resposta;
    }

    public static function buscaValorTotalLancamentosPendentes(int $idColaborador): float
    {
        $valorTotal = DB::selectOneColumn(
            "SELECT SUM(lancamento_financeiro_pendente.valor) AS `valor_total_lancamentos`
            FROM lancamento_financeiro_pendente
            LEFT JOIN entregas_faturamento_item ON lancamento_financeiro_pendente.numero_documento = entregas_faturamento_item.uuid_produto
              AND entregas_faturamento_item.situacao = 'EN'
            WHERE lancamento_financeiro_pendente.id_colaborador = ?
              AND lancamento_financeiro_pendente.tipo = 'P'
              AND entregas_faturamento_item.id IS NULL",
            [$idColaborador]
        );

        return $valorTotal ?: 0;
    }

    public function consultaCreditoNormalOuPendente(int $idLancamento): ?array
    {
        $consulta = DB::selectOne(
            "SELECT
                lancamento_financeiro_pendente.id,
                lancamento_financeiro_pendente.valor,
                lancamento_financeiro_pendente.data_emissao,
                lancamento_financeiro_pendente.transacao_origem,
                'BLOQUEADO' AS `tipo`
            FROM lancamento_financeiro_pendente
            WHERE lancamento_financeiro_pendente.tipo = 'P'
              AND lancamento_financeiro_pendente.id = :id_lancamento
            UNION ALL
            SELECT
                lancamento_financeiro.id,
                lancamento_financeiro.valor,
                lancamento_financeiro.data_emissao,
                lancamento_financeiro.transacao_origem,
                'NORMAL' AS `tipo`
            FROM lancamento_financeiro
            WHERE lancamento_financeiro.tipo = 'P'
              AND lancamento_financeiro.id = :id_lancamento
            ORDER BY tipo DESC
            LIMIT 1",
            [
                'id_lancamento' => $idLancamento,
            ]
        );

        return $consulta;
    }

    public function consultaAbatesCredito(array $credito): array
    {
        if ($credito['tipo'] === 'NORMAL') {
            $select = "DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y %H:%i:%s') data_emissao,
             lancamento_financeiro.id,
             lancamento_financeiro.transacao_origem";
            $join =
                'INNER JOIN lancamento_financeiro ON lancamento_financeiro.id = lancamento_financeiro_abates.id_lancamento_debito';
        } else {
            $select = "DATE_FORMAT(lancamento_financeiro_pendente.data_emissao, '%d/%m/%Y %H:%i:%s') data_emissao,
             lancamento_financeiro_pendente.id,
             lancamento_financeiro_pendente.transacao_origem";
            $join =
                'INNER JOIN lancamento_financeiro_pendente ON lancamento_financeiro_pendente.id = lancamento_financeiro_abates.id_lancamento_debito';
        }

        $consulta = DB::select(
            "SELECT
                lancamento_financeiro_abates.valor_pago * -1 valor_pago,
                $select
             FROM lancamento_financeiro_abates
             $join
             WHERE lancamento_financeiro_abates.id_lancamento_credito = ?",
            [$credito['id']]
        );

        return $consulta;
    }

    public static function temSaldo(): bool
    {
        $temSaldo = DB::selectOneColumn('SELECT saldo_cliente(:id_cliente) > 0 AS `tem_saldo`', [
            'id_cliente' => Auth::user()->id_colaborador,
        ]);

        return $temSaldo;
    }
}

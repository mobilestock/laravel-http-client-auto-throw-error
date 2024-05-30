<?php
namespace MobileStock\service;

use Arr;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use MobileStock\model\Usuario;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\Recebiveis\RecebivelService;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransferenciasService
{
    /**
     * Essa função se comunica com a Iugu para transferir dinheiro, portanto precisa de um commit
     * após a efetivação da transferência na api externa.
     */
    public static function pagaTransferencia(int $idTransferencia): void
    {
        DB::beginTransaction();

        Log::withContext(['id_transferencia' => $idTransferencia]);
        $informacoes = DB::selectOne(
            "SELECT
                lancamento_financeiro.id AS `id_lancamento`,
                conta_bancaria_colaboradores.id_iugu,
                colaboradores_prioridade_pagamento.id AS `id_zoop_recebivel`,
                colaboradores_prioridade_pagamento.id_conta_bancaria AS `id_recebedor`,
                (colaboradores_prioridade_pagamento.valor_pagamento - colaboradores_prioridade_pagamento.valor_pago) AS `valor_recebivel`
            FROM colaboradores_prioridade_pagamento
            INNER JOIN lancamento_financeiro ON lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
            INNER JOIN conta_bancaria_colaboradores ON conta_bancaria_colaboradores.id = colaboradores_prioridade_pagamento.id_conta_bancaria
            WHERE colaboradores_prioridade_pagamento.id = :id_transferencia;",
            ['id_transferencia' => $idTransferencia]
        );
        if (empty($informacoes)) {
            throw new InvalidArgumentException('Informações não encontradas');
        }

        $linhasAlteradas = DB::update(
            "UPDATE colaboradores_prioridade_pagamento
            SET colaboradores_prioridade_pagamento.valor_pago = colaboradores_prioridade_pagamento.valor_pago + :valor_pagar
            WHERE colaboradores_prioridade_pagamento.id = :id_transferencia;",
            ['valor_pagar' => $informacoes['valor_recebivel'], 'id_transferencia' => $idTransferencia]
        );
        if ($linhasAlteradas !== 1) {
            throw new RuntimeException('Erro ao tentar atualizar o valor pago');
        }

        $recebivel = new RecebivelService();
        $recebivel->id_lancamento = $informacoes['id_lancamento'];
        $recebivel->id_zoop_recebivel = (string) $informacoes['id_zoop_recebivel'];
        $recebivel->id_recebedor = $informacoes['id_recebedor'];
        $recebivel->valor_pago = $informacoes['valor_recebivel'];
        $recebivel->valor = $informacoes['valor_recebivel'];
        $recebivel->situacao = 'PA';
        $recebivel->tipo = 'IM';
        $recebivel->num_parcela = 1;
        $recebivel->recebivel_adiciona(DB::getPdo());

        $iugu = new IuguHttpClient();
        $iugu->listaCodigosPermitidos = [200];
        $iugu->post('transfers', [
            'amount_cents' => round($informacoes['valor_recebivel'] * 100),
            'custom_variables' => [
                [
                    'name' => 'tipo',
                    'value' => 'mobile inteira transferencia',
                ],
                [
                    'name' => 'id_transferencia',
                    'value' => $idTransferencia,
                ],
            ],
            'receiver_id' => $informacoes['id_iugu'],
            'account_id' => env('DADOS_PAGAMENTO_IUGUCONTAMOBILE'),
            'test' => !App::isProduction(),
        ]);
        DB::commit();
    }

    public static function sqlBasePrioridadeTransferencia(bool $verificarExisteNaFila): string
    {
        $permissaoFornecedor = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;
        $permissaoEntregador = Usuario::VERIFICA_PERMISSAO_ENTREGADOR;

        $diasPagamento = ConfiguracaoService::buscaDiasTransferenciaColaboradores();

        if ($verificarExisteNaFila) {
            $join =
                ' INNER JOIN fila_transferencia_automatica ON fila_transferencia_automatica.id_transferencia = colaboradores_prioridade_pagamento.id ';
            $where = '';
            $order = ' ORDER BY fila_transferencia_automatica.id ASC ';
        } else {
            $join =
                ' LEFT JOIN fila_transferencia_automatica ON fila_transferencia_automatica.id_transferencia = colaboradores_prioridade_pagamento.id ';
            $where = ' AND fila_transferencia_automatica.id IS NULL ';
            $order = " ORDER BY
                eh_entregador DESC,
                eh_antecipacao DESC,
                eh_cliente DESC,
                eh_melhor_fabricante DESC,
                eh_excelente DESC,
                eh_novato DESC,
                eh_regular DESC,
                eh_ruim DESC,
                dias_diferenca DESC,
                colaboradores_prioridade_pagamento.data_criacao ASC; ";
        }

        $sql = "
                colaboradores_prioridade_pagamento.id,
                colaboradores_prioridade_pagamento.valor_pagamento,
                colaboradores_prioridade_pagamento.valor_pago,
                DATEDIFF_DIAS_UTEIS(CURDATE(), DATE(colaboradores_prioridade_pagamento.data_criacao)) AS `dias_diferenca`,
                EXISTS(
                    SELECT 1
                    FROM usuarios
                    WHERE usuarios.permissao REGEXP '$permissaoEntregador'
                        AND usuarios.id_colaborador = colaboradores_prioridade_pagamento.id_colaborador
                ) AND DATEDIFF_DIAS_UTEIS(
                    CURDATE(),
                    colaboradores_prioridade_pagamento.data_criacao
                ) >= {$diasPagamento['dias_pagamento_transferencia_ENTREGADOR']} AS `eh_entregador`,
                EXISTS(
                    SELECT 1
                    FROM emprestimo
                    INNER JOIN lancamento_financeiro ON lancamento_financeiro.id = emprestimo.id_lancamento
                    WHERE lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
                        AND emprestimo.situacao = 'PE'
                ) AND colaboradores_prioridade_pagamento.situacao = 'EM'
                AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_antecipacao']} AS `eh_antecipacao`,
                @cliente := EXISTS(
                    SELECT 1
                    FROM usuarios
                    WHERE usuarios.id_colaborador = colaboradores_prioridade_pagamento.id_colaborador
                        AND NOT usuarios.permissao REGEXP '$permissaoFornecedor'
                ) AND colaboradores_prioridade_pagamento.situacao = 'CR'
                AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_CLIENTE']} AS `eh_cliente`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'MELHOR_FABRICANTE'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE']}
                    )
                ), 0) AS `eh_melhor_fabricante`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'EXCELENTE'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_EXCELENTE']}
                    )
                ), 0) AS `eh_excelente`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao IS NULL
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_NOVATO']}
                    )
                ), 0) AS `eh_novato`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'REGULAR'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_REGULAR']}
                    )
                ), 0) AS `eh_regular`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'RUIM'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_RUIM']}
                    )
                ), 0) AS `eh_ruim`
            FROM colaboradores_prioridade_pagamento
            $join
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores_prioridade_pagamento.id_colaborador
            WHERE colaboradores_prioridade_pagamento.situacao IN ('CR', 'EM')
                AND colaboradores_prioridade_pagamento.valor_pago = 0
                AND NOT EXISTS(
                    SELECT 1
                    FROM conta_bancaria_colaboradores
                    WHERE conta_bancaria_colaboradores.pagamento_bloqueado = 'T'
                        AND conta_bancaria_colaboradores.id = colaboradores_prioridade_pagamento.id_conta_bancaria
                )
                $where
            GROUP BY colaboradores_prioridade_pagamento.id
            HAVING TRUE IN (entregador, antecipacao, eh_cliente, melhor_fabricante, excelente, novato, regular, ruim)
            $order;";

        return $sql;
    }

    public static function prioridadePagamentoAutomatico(): array
    {
        $baseSql = self::sqlBasePrioridadeTransferencia(false);
        $contemplados = DB::select("SELECT $baseSql");

        if (!empty($contemplados)) {
            $contemplados = array_map(function (array $contemplado): array {
                switch (true) {
                    case $contemplado['eh_entregador']:
                        $contemplado['origem'] = 'ENTREGADOR';
                        break;
                    case $contemplado['eh_antecipacao']:
                        $contemplado['origem'] = 'ANTECIPACAO';
                        break;
                    case $contemplado['eh_cliente']:
                        $contemplado['origem'] = 'CLIENTE';
                        break;
                    case $contemplado['eh_melhor_fabricante']:
                        $contemplado['origem'] = 'MELHOR_FABRICANTE';
                        break;
                    case $contemplado['eh_excelente']:
                        $contemplado['origem'] = 'EXCELENTE';
                        break;
                    case $contemplado['eh_novato']:
                        $contemplado['origem'] = 'NOVATO';
                        break;
                    case $contemplado['eh_regular']:
                        $contemplado['origem'] = 'REGULAR';
                        break;
                    case $contemplado['eh_ruim']:
                        $contemplado['origem'] = 'RUIM';
                        break;
                }
                $contemplado['id_transferencia'] = $contemplado['id'];
                $contemplado = Arr::only($contemplado, ['id_transferencia', 'valor_pagamento', 'valor_pago', 'origem']);

                return $contemplado;
            }, $contemplados);

            /**
             * fila_transferencia_automatica.id_transferencia
             * fila_transferencia_automatica.valor_pagamento
             * fila_transferencia_automatica.valor_pago
             * fila_transferencia_automatica.origem
             */
            DB::table('fila_transferencia_automatica')->insert($contemplados);
        }

        unset($contemplados);

        $baseSql = self::sqlBasePrioridadeTransferencia(true);
        $contemplados = DB::select("SELECT $baseSql");
        $contemplados = array_map(function (array $contemplado): array {
            unset(
                $contemplado['entregador'],
                $contemplado['antecipacao'],
                $contemplado['eh_cliente'],
                $contemplado['melhor_fabricante'],
                $contemplado['excelente'],
                $contemplado['regular'],
                $contemplado['ruim']
            );

            return $contemplado;
        }, $contemplados);

        return $contemplados;
    }

    public static function proximosContempladosAutomaticamente(): array
    {
        $baseSql = self::sqlBasePrioridadeTransferencia(true);
        $contemplados = DB::select(
            "SELECT
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao, '%d/%m/%Y às %H:%s') AS `data_criacao`,
                DATEDIFF_DIAS_UTEIS(CURDATE(), DATE(colaboradores_prioridade_pagamento.data_criacao)) AS `dias_diferenca`,
                (
                    SELECT JSON_OBJECT(
                        'id_colaborador', colaboradores.id,
                        'nome_colaborador', colaboradores.razao_social,
                        'endereco', JSON_OBJECT(
                            'cidade', colaboradores_enderecos.cidade,
                            'uf', colaboradores_enderecos.uf
                        )
                    )
                    FROM colaboradores
                    INNER JOIN colaboradores_enderecos ON
                        colaboradores_enderecos.id_colaborador = colaboradores.id AND
                        colaboradores_enderecos.eh_endereco_padrao = 1
                    WHERE colaboradores.id = colaboradores_prioridade_pagamento.id_colaborador
                ) AS `json_sacador`,
                (
                    SELECT JSON_OBJECT(
                        'id_conta_bancaria', conta_bancaria_colaboradores.id,
                        'nome_conta_bancaria', conta_bancaria_colaboradores.nome_titular
                    )
                    FROM conta_bancaria_colaboradores
                    WHERE conta_bancaria_colaboradores.id = colaboradores_prioridade_pagamento.id_conta_bancaria
                ) AS `json_recebedor`,
                fila_transferencia_automatica.origem,
                IF (@cliente, 'CLIENTE', COALESCE(reputacao_fornecedores.reputacao, 'NOVATO')) AS `reputacao`,
                $baseSql"
        );

        $index = 1;
        $contemplados = array_map(function (array $contemplado) use (&$index): array {
            if (!empty($contemplado['sacador']['endereco'])) {
                $contemplado['endereco'] = $contemplado['sacador']['endereco']['cidade'];
                $contemplado['endereco'] .= " ({$contemplado['sacador']['endereco']['uf']})";
            }
            $contemplado['sacador'] =
                "({$contemplado['sacador']['id_colaborador']}) " . trim($contemplado['sacador']['nome_colaborador']);

            $contemplado['id_saque'] = $contemplado['id'];
            $contemplado['id'] = $index;
            unset(
                $contemplado['entregador'],
                $contemplado['antecipacao'],
                $contemplado['eh_cliente'],
                $contemplado['melhor_fabricante'],
                $contemplado['excelente'],
                $contemplado['regular'],
                $contemplado['ruim']
            );
            $index++;

            return $contemplado;
        }, $contemplados);

        $resultados = [
            'contemplados' => $contemplados,
            'valor_total_saques' => array_sum(array_column($contemplados, 'valor_pagamento')),
        ];

        return $resultados;
    }
    public static function pagamentoManual(PDO $conexao, int $idTransferencia, int $idUsuario): void
    {
        $sql = "UPDATE colaboradores_prioridade_pagamento SET
                    colaboradores_prioridade_pagamento.id_transferencia = 'PAGO_MANUALMENTE',
                    colaboradores_prioridade_pagamento.situacao = 'PA',
                    colaboradores_prioridade_pagamento.valor_pago = colaboradores_prioridade_pagamento.valor_pagamento,
                    colaboradores_prioridade_pagamento.usuario = :id_usuario
                WHERE colaboradores_prioridade_pagamento.id = :id_transferencia";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id_transferencia', $idTransferencia, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Erro ao efetuar pagamento manualmente!!!');
        }
    }
    public static function buscaTransferenciasNaoTransferidasIugu(): array
    {
        $transferencias = DB::select(
            "SELECT
                conta_bancaria_colaboradores.iugu_token_live,
                conta_bancaria_colaboradores.id_iugu,
                colaboradores_prioridade_pagamento.valor_pagamento,
                colaboradores_prioridade_pagamento.id
            FROM colaboradores_prioridade_pagamento
            INNER JOIN lancamento_financeiro ON lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
            INNER JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_lancamento = lancamento_financeiro.id
            INNER JOIN conta_bancaria_colaboradores ON conta_bancaria_colaboradores.id = colaboradores_prioridade_pagamento.id_conta_bancaria
            WHERE colaboradores_prioridade_pagamento.id_transferencia = '0'
                AND lancamentos_financeiros_recebiveis.situacao = 'PA'
                AND conta_bancaria_colaboradores.pagamento_bloqueado = 'F'
                AND conta_bancaria_colaboradores.conta_iugu_verificada = 'T'
                AND colaboradores_prioridade_pagamento.situacao IN ('CR', 'EM')
                AND colaboradores_prioridade_pagamento.valor_pagamento = colaboradores_prioridade_pagamento.valor_pago
            GROUP BY colaboradores_prioridade_pagamento.id
            HAVING SUM(lancamentos_financeiros_recebiveis.valor_pago) >= colaboradores_prioridade_pagamento.valor_pagamento;"
        );

        return $transferencias;
    }
    public static function atualizaTransferenciaSaque(
        int $idColaboradoresPrioridadePagamento,
        string $idTransferencia
    ): void {
        $linhasAfetadas = DB::update(
            "UPDATE colaboradores_prioridade_pagamento
            SET colaboradores_prioridade_pagamento.id_transferencia = :id_transferencia
            WHERE colaboradores_prioridade_pagamento.id = :id_colaboradores_prioridade_pagamento;",
            [
                'id_transferencia' => $idTransferencia,
                'id_colaboradores_prioridade_pagamento' => $idColaboradoresPrioridadePagamento,
            ]
        );

        if ($linhasAfetadas !== 1) {
            Log::withContext([
                'id_colaboradores_prioridade_pagamento' => $idColaboradoresPrioridadePagamento,
                'id_transferencia' => $idTransferencia,
                'linhas_afetadas' => $linhasAfetadas,
            ]);
            throw new RuntimeException('Erro ao atualizar transferência');
        }
    }
    public static function consultaTransferencia(string $idTransferencia): array
    {
        $transferencia = DB::selectOne(
            "SELECT
                colaboradores_prioridade_pagamento.id_colaborador,
                colaboradores_prioridade_pagamento.valor_pago,
                conta_bancaria_colaboradores.iugu_token_live,
                conta_bancaria_colaboradores.conta,
                conta_bancaria_colaboradores.nome_titular,
                colaboradores_prioridade_pagamento.situacao
            FROM colaboradores_prioridade_pagamento
            INNER JOIN conta_bancaria_colaboradores ON conta_bancaria_colaboradores.id = colaboradores_prioridade_pagamento.id_conta_bancaria
            WHERE colaboradores_prioridade_pagamento.id_transferencia = :id_transferencia;",
            ['id_transferencia' => $idTransferencia]
        );
        if (empty($transferencia)) {
            throw new NotFoundHttpException('Transferência não encontrada');
        }

        return $transferencia;
    }
    public static function atualizaSituacaoTransferencia(string $idTransferencia, string $situacao): void
    {
        $linhasAfetadas = DB::update(
            "UPDATE colaboradores_prioridade_pagamento
            SET colaboradores_prioridade_pagamento.situacao = :situacao
            WHERE colaboradores_prioridade_pagamento.id_transferencia = :id_transferencia;",
            ['situacao' => $situacao, 'id_transferencia' => $idTransferencia]
        );

        if ($linhasAfetadas !== 1) {
            Log::withContext([
                'id_transferencia' => $idTransferencia,
                'situacao' => $situacao,
                'linhas_afetadas' => $linhasAfetadas,
            ]);
            throw new RuntimeException('Erro ao atualizar situação da transferência');
        }
    }

    public static function listaTransferencias(): array
    {
        $diasPagamento = ConfiguracaoService::buscaDiasTransferenciaColaboradores();

        $diasPagamento = array_map(fn($dias) => $dias + 1, $diasPagamento);

        $sql = "SELECT
                colaboradores_prioridade_pagamento.id AS `id_prioridade`,
                colaboradores_prioridade_pagamento.valor_pago,
                colaboradores_prioridade_pagamento.valor_pagamento,
                lancamento_financeiro.id_prioridade_saque,
                lancamento_financeiro.id AS `id_lancamento`,
                colaboradores_prioridade_pagamento.id_colaborador,
                IF(COALESCE(colaboradores_prioridade_pagamento.situacao, 'NA') = 'CR'
                    AND LENGTH(COALESCE(colaboradores_prioridade_pagamento.id_transferencia, '')) > 1,
                    'ET', colaboradores_prioridade_pagamento.situacao
                ) situacao,
                colaboradores_prioridade_pagamento.id_transferencia,
                conta_bancaria_colaboradores.nome_titular,
                conta_bancaria_colaboradores.cpf_titular,
                conta_bancaria_colaboradores.conta,
                conta_bancaria_colaboradores.agencia,
                conta_bancaria_colaboradores.id,
                COALESCE((colaboradores_prioridade_pagamento.valor_pagamento - colaboradores_prioridade_pagamento.valor_pago),0) valor_pendente,
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao, '%d/%m/%Y %H:%i:%s') AS `data_criacao`,
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_atualizacao,'%d/%m/%Y %H:%i:%s') AS `data_atualizacao`,
                colaboradores.razao_social,
                conta_bancaria_colaboradores.pagamento_bloqueado,
                reputacao_fornecedores.reputacao,
                CASE
                    WHEN reputacao_fornecedores.reputacao = 'MELHOR_FABRICANTE' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'EXCELENTE' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_EXCELENTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'REGULAR' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_REGULAR']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'RUIM' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_RUIM']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    ELSE
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_CLIENTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                END AS `proximo_pagamento`,
                saldo_cliente(colaboradores_prioridade_pagamento.id_colaborador) saldo
            FROM conta_bancaria_colaboradores
            INNER JOIN colaboradores_prioridade_pagamento ON colaboradores_prioridade_pagamento.id_conta_bancaria = conta_bancaria_colaboradores.id
            INNER JOIN colaboradores ON colaboradores.id = colaboradores_prioridade_pagamento.id_colaborador
            INNER JOIN lancamento_financeiro ON lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores_prioridade_pagamento.id_colaborador
            WHERE colaboradores_prioridade_pagamento.situacao IN ('CR','EM')
                AND colaboradores_prioridade_pagamento.id_transferencia = '0'
            GROUP BY colaboradores_prioridade_pagamento.id
            ORDER BY colaboradores_prioridade_pagamento.id DESC";
        $resultado = DB::select($sql);
        return $resultado;
    }
}

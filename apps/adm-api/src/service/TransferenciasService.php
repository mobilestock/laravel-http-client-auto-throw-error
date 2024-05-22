<?php
namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
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
                throw new NotFoundHttpException('Informações não encontradas');
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
            $recebivel->id_zoop_recebivel = $informacoes['id_zoop_recebivel'];
            $recebivel->id_recebedor = $informacoes['id_recebedor'];
            $recebivel->valor_pago = $informacoes['valor_recebivel'];
            $recebivel->valor = $informacoes['valor_recebivel'];
            $recebivel->situacao = 'PA';
            $recebivel->tipo = 'IM';
            $recebivel->num_parcela = 1;
            $recebivel->recebivel_adiciona(DB::getPdo());

            $iugu = new IuguHttpClient();
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
                'account_id' => $_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'],
                'test' => $_ENV['AMBIENTE'] !== 'producao',
            ]);
            DB::commit();
    }
    public static function sqlBasePrioridadeTransferencia(PDO $conexao, bool $verificarExisteNaFila): string
    {
        $permissaoFornecedor = Usuario::VERIFICA_PERMISSAO_FORNECEDOR;
        $permissaoEntregador = Usuario::VERIFICA_PERMISSAO_ENTREGADOR;

        $diasPagamento = ConfiguracaoService::buscaDiasTransferenciaColaboradores($conexao);

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
                entregador DESC,
                antecipacao DESC,
                eh_cliente DESC,
                melhor_fabricante DESC,
                excelente DESC,
                novato DESC,
                regular DESC,
                ruim DESC,
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
                ) >= {$diasPagamento['dias_pagamento_transferencia_ENTREGADOR']} AS `entregador`,
                EXISTS(
                    SELECT 1
                    FROM emprestimo
                    INNER JOIN lancamento_financeiro ON lancamento_financeiro.id = emprestimo.id_lancamento
                    WHERE lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
                        AND emprestimo.situacao = 'PE'
                ) AND colaboradores_prioridade_pagamento.situacao = 'EM'
                AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= 3 AS `antecipacao`,
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
                ), 0) AS `melhor_fabricante`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'EXCELENTE'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_EXCELENTE']}
                    )
                ), 0) AS `excelente`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao IS NULL
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_NOVATO']}
                    )
                ), 0) AS `novato`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'REGULAR'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_REGULAR']}
                    )
                ), 0) AS `regular`,
                COALESCE(IF (
                    @cliente,
                    0,
                    (
                        reputacao_fornecedores.reputacao = 'RUIM'
                        AND colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND DATEDIFF_DIAS_UTEIS(CURDATE(), colaboradores_prioridade_pagamento.data_criacao) >= {$diasPagamento['dias_pagamento_transferencia_fornecedor_RUIM']}
                    )
                ), 0) AS `ruim`
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
    public static function prioridadePagamentoAutomatico(PDO $conexao): array
    {
        $baseSql = self::sqlBasePrioridadeTransferencia($conexao, false);
        $sql = $conexao->prepare("SELECT $baseSql");
        $sql->execute();
        $contemplados = $sql->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($contemplados)) {
            $sql = '';
            $bind = [];
            foreach ($contemplados as $index => $contemplado) {
                switch (true) {
                    case !!$contemplado['entregador']:
                        $situacao = 'ENTREGADOR';
                        break;
                    case !!$contemplado['antecipacao']:
                        $situacao = 'ANTECIPACAO';
                        break;
                    case !!$contemplado['eh_cliente']:
                        $situacao = 'CLIENTE';
                        break;
                    case !!$contemplado['melhor_fabricante']:
                        $situacao = 'MELHOR_FABRICANTE';
                        break;
                    case !!$contemplado['excelente']:
                        $situacao = 'EXCELENTE';
                        break;
                    case !!$contemplado['novato']:
                        $situacao = 'NOVATO';
                        break;
                    case !!$contemplado['regular']:
                        $situacao = 'REGULAR';
                        break;
                    case !!$contemplado['ruim']:
                        $situacao = 'RUIM';
                        break;
                }

                $sql .= "INSERT INTO fila_transferencia_automatica (
                        fila_transferencia_automatica.id_transferencia,
                        fila_transferencia_automatica.valor_pagamento,
                        fila_transferencia_automatica.valor_pago,
                        fila_transferencia_automatica.origem
                    ) VALUES (
                        :id_transferencia_$index,
                        :valor_pagamento_$index,
                        :valor_pago_$index,
                        :situacao_$index
                    );";
                $bind = array_merge($bind, [
                    ":id_transferencia_$index" => $contemplado['id'],
                    ":valor_pagamento_$index" => $contemplado['valor_pagamento'],
                    ":valor_pago_$index" => $contemplado['valor_pago'],
                    ":situacao_$index" => $situacao,
                ]);
            }

            $sql = $conexao->prepare($sql);
            $sql->execute($bind);
            $linhasAfetadas = 0;

            do {
                $linhasAfetadas += $sql->rowCount();
            } while ($sql->nextRowset());

            if ($linhasAfetadas !== sizeof($contemplados)) {
                throw new Exception('Informações não inseridas na fila');
            }
        }
        unset($contemplados);
        $baseSql = self::sqlBasePrioridadeTransferencia($conexao, true);
        $sql = $conexao->prepare("SELECT $baseSql");
        $sql->execute();
        $contemplados = $sql->fetchAll(PDO::FETCH_ASSOC);
        $contemplados = array_map(function (array $contemplado): array {
            $contemplado['id'] = (int) $contemplado['id'];
            $contemplado['valor_pagamento'] = (float) $contemplado['valor_pagamento'];
            $contemplado['valor_pago'] = (float) $contemplado['valor_pago'];
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
        $baseSql = self::sqlBasePrioridadeTransferencia(DB::getPdo(), true);
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
}

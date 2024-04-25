<?php

namespace MobileStock\service\Lancamento;

use Illuminate\Support\Facades\DB;

class LancamentoFinanceiroAbates
{
    public function buscaTrocasTransacao(int $idTransacao, int $idColaborador): array
    {
        /**
         * Devido ao select ser complexo, foi combinado com @gustavo210 que o sql será feito com uma função centralizada
         * que recebe o nome da tabela que será utilizada no select. E para seguir a convenção de sempre ter o nome da
         * tabela antes do campo será escrito abaixo os campos utilizados nesse select:
         *
         * lancamento_financeiro_pendente.id               lancamento_financeiro.id
         * lancamento_financeiro_pendente.valor            lancamento_financeiro.valor
         * lancamento_financeiro_pendente.origem           lancamento_financeiro.origem
         * lancamento_financeiro_pendente.id_usuario       lancamento_financeiro.id_usuario
         * lancamento_financeiro_pendente.data_emissao     lancamento_financeiro.data_emissao
         * lancamento_financeiro_pendente.numero_documento lancamento_financeiro.numero_documento
         * lancamento_financeiro_pendente.transacao_origem lancamento_financeiro.transacao_origem
         *
         * OBS: Essa lista deve ser atualizada conforme a atualização do código.
         */
        $jsonObject = function (string $tabela): string {
            $situacaoTroca = $tabela === 'lancamento_financeiro' ? 'TROCA_ACEITA' : 'TROCA_PENDENTE';
            return "JSON_OBJECT(
                'id', $tabela.id,
                'valor', $tabela.valor,
                'valor_pago', SUM(lancamento_financeiro_abates.valor_pago),
                'tipo_lancamento', lancamento_financeiro_abates.tipo_lancamento,
                'situacao', CASE
                                WHEN $tabela.origem IN ('TR', 'ES') AND $tabela.numero_documento THEN '$situacaoTroca'
                                WHEN EXISTS(SELECT 1
                                            FROM transacao_financeiras
                                            WHERE transacao_financeiras.id = $tabela.transacao_origem
                                              AND transacao_financeiras.origem_transacao = 'ET'
                                              AND transacao_financeiras.valor_liquido = $tabela.valor
                                           ) THEN 'PIX_ESQUECI_TROCA'
                            END,
                'produto_json', IF($tabela.origem IN ('TR', 'ES') AND $tabela.numero_documento, (
                    SELECT JSON_OBJECT(
                        'foto', (SELECT produtos_foto.caminho
                                 FROM produtos_foto
                                 WHERE produtos_foto.id = logistica_item.id_produto
                                 ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                                 LIMIT 1),
                        'id_produto', logistica_item.id_produto,
                        'nome_tamanho', logistica_item.nome_tamanho
                    )
                    FROM logistica_item
                    WHERE logistica_item.uuid_produto = $tabela.numero_documento
                ), NULL),
                'usuario', (SELECT CONCAT('(', usuarios.id, ') ', usuarios.nome)
                               FROM usuarios
                               WHERE usuarios.id = $tabela.id_usuario
                               LIMIT 1),
                'data_atualizacao', DATE_FORMAT($tabela.data_emissao, '%d/%m/%Y ás %H:%i:%s'),
                'transacao_origem', $tabela.transacao_origem
            )";
        };

        $consulta = DB::select(
            "SELECT
                    IF(lancamento_financeiro_abates.tipo_lancamento = 'NORMAL',
                        (SELECT {$jsonObject('lancamento_financeiro')}
                         FROM lancamento_financeiro
                         WHERE lancamento_financeiro.id = lancamento_financeiro_abates.id_lancamento_credito),
                        (SELECT {$jsonObject('lancamento_financeiro_pendente')}
                         FROM lancamento_financeiro_pendente
                         WHERE lancamento_financeiro_pendente.id = lancamento_financeiro_abates.id_lancamento_credito)
                      ) creditos_json
                FROM
                (
                    SELECT
                        lancamento_financeiro_pendente.id,
                        lancamento_financeiro_pendente.valor * -1 valor,
                        'PENDENTE' tipo
                    FROM lancamento_financeiro_pendente
                    WHERE lancamento_financeiro_pendente.transacao_origem = :id_transacao
                      AND lancamento_financeiro_pendente.id_colaborador = :id_cliente
                      AND lancamento_financeiro_pendente.tipo = 'R'
                      AND lancamento_financeiro_pendente.origem = 'PC'
                    UNION ALL
                    SELECT
                        lancamento_financeiro.id,
                        lancamento_financeiro.valor * -1 valor,
                        'NORMAL' tipo
                    FROM lancamento_financeiro
                    WHERE lancamento_financeiro.transacao_origem = :id_transacao
                      AND lancamento_financeiro.id_colaborador = :id_cliente
                      AND lancamento_financeiro.tipo = 'R'
                      AND lancamento_financeiro.origem = 'PC'

                    ORDER BY tipo = 'PENDENTE' DESC, id DESC
                ) debito_lancamento_financeiro
                INNER JOIN lancamento_financeiro_abates ON lancamento_financeiro_abates.id_lancamento_debito = debito_lancamento_financeiro.id
                GROUP BY lancamento_financeiro_abates.id_lancamento_credito",
            [
                'id_transacao' => $idTransacao,
                'id_cliente' => $idColaborador,
            ]
        );

        $consulta = array_map(function (array $troca) {
            $troca = $troca['creditos'];
            $troca = array_merge($troca, $troca['produto'] ?: []);
            unset($troca['produto']);

            return $troca;
        }, $consulta);

        return $consulta;
    }

    /**
     * lancamento_financeiro.faturamento_criado_pago
     * lancamento_financeiro.origem
     * lancamento_financeiro_pendente.origem
     * lancamento_financeiro.tipo
     * lancamento_financeiro_pendente.tipo
     * lancamento_financeiro.valor
     * lancamento_financeiro_pendente.valor
     * lancamento_financeiro.id
     * lancamento_financeiro_pendente.id
     * lancamento_financeiro.id_colaborador
     * lancamento_financeiro_pendente.id_colaborador
     */
    public function abateLancamentosSeNecessario(int $idColaborador): void
    {
        $precisamAtualizar = DB::select(
            "SELECT
                lancamento_financeiro_abates_grupo.id IS NULL
                OR lancamento_financeiro_abates_grupo.id_ultimo_lancamento <>
                    IF(lancamento_financeiro_abates_grupo.tipo_lancamento = 'NORMAL', (
                        SELECT lancamento_financeiro.id
                        FROM lancamento_financeiro
                        WHERE lancamento_financeiro.id_colaborador = lancamento_financeiro_abates_grupo.id_colaborador
                          AND lancamento_financeiro.faturamento_criado_pago = 'F'
                          AND lancamento_financeiro.origem <> 'AU'
                        ORDER BY lancamento_financeiro.id DESC
                        LIMIT 1
                    ), (
                        SELECT lancamento_financeiro_pendente.id
                        FROM lancamento_financeiro_pendente
                        WHERE lancamento_financeiro_pendente.id_colaborador = lancamento_financeiro_abates_grupo.id_colaborador
                        ORDER BY lancamento_financeiro_pendente.id DESC
                        LIMIT 1
                    )) precisa_atualizar,
                lancamento_financeiro_abates_grupo.id_ultimo_lancamento,
                lancamento_financeiro_abates_grupo.modelo_serializado,
                `_tipos_lancamentos`.tipo_lancamento
            FROM (
                SELECT 'NORMAL' tipo_lancamento
                UNION ALL
                SELECT 'PENDENTE'
            ) `_tipos_lancamentos`
            LEFT JOIN lancamento_financeiro_abates_grupo ON lancamento_financeiro_abates_grupo.tipo_lancamento = `_tipos_lancamentos`.tipo_lancamento
                  AND lancamento_financeiro_abates_grupo.id_colaborador = :id_colaborador
            HAVING precisa_atualizar = TRUE;",
            [
                'id_colaborador' => $idColaborador,
            ]
        );

        foreach ($precisamAtualizar as $ultimoCalculo) {
            $tipoLancamento = $ultimoCalculo['tipo_lancamento'];

            /** @var ProcessoAbate $processoAbate */
            $processoAbate = empty($ultimoCalculo['modelo_serializado'])
                ? new ProcessoAbate($tipoLancamento)
                : unserialize($ultimoCalculo['modelo_serializado']);

            $ultimoLancamento = $ultimoCalculo['id_ultimo_lancamento'];
            $tabela = 'lancamento_financeiro_pendente';

            $where = '';

            if ($tipoLancamento === 'NORMAL') {
                $tabela = 'lancamento_financeiro';
                $where .= "AND $tabela.faturamento_criado_pago = 'F'
                   AND $tabela.origem <> 'AU'";
            }

            if ($ultimoLancamento) {
                $where .= "AND $tabela.id > :ultimo_lancamento";
            }

            $lancamentos = DB::cursor(
                "SELECT
                    $tabela.id,
                    IF($tabela.tipo = 'R', $tabela.valor * -1, $tabela.valor) valor
                 FROM $tabela
                 WHERE $tabela.id_colaborador = :id_colaborador
                   $where
                 ORDER BY $tabela.id ASC;",
                [
                    'id_colaborador' => $idColaborador,
                ] + ($ultimoLancamento ? ['ultimo_lancamento' => $ultimoLancamento] : [])
            );

            $idUltimoLancamento = null;
            foreach ($lancamentos as $lancamento) {
                $fila = $lancamento['valor'] > 0 ? $processoAbate->filaPositiva : $processoAbate->filaNegativa;
                $fila->enqueue($lancamento);
                $idUltimoLancamento = $lancamento['id'];
            }

            if ($idUltimoLancamento === null) {
                continue;
            }

            $sqlAbate = $processoAbate->abate();

            if (!empty($sqlAbate)) {
                DB::insert(
                    "INSERT INTO lancamento_financeiro_abates (
                    lancamento_financeiro_abates.id_lancamento_debito,
                    lancamento_financeiro_abates.id_lancamento_credito,
                    lancamento_financeiro_abates.valor_pago,
                    lancamento_financeiro_abates.tipo_lancamento
                 ) VALUES $sqlAbate;"
                );
            }

            DB::insert(
                "INSERT INTO lancamento_financeiro_abates_grupo (
                        lancamento_financeiro_abates_grupo.id_colaborador,
                        lancamento_financeiro_abates_grupo.tipo_lancamento,
                        lancamento_financeiro_abates_grupo.id_ultimo_lancamento,
                        lancamento_financeiro_abates_grupo.modelo_serializado
                ) VALUES (
                    :id_colaborador,
                    :tipo_lancamento,
                    :id_ultimo_lancamento,
                    :modelo_serializado
                ) ON DUPLICATE KEY UPDATE
                    lancamento_financeiro_abates_grupo.id_ultimo_lancamento = :id_ultimo_lancamento,
                    lancamento_financeiro_abates_grupo.modelo_serializado = :modelo_serializado;",
                [
                    'id_colaborador' => $idColaborador,
                    'tipo_lancamento' => $tipoLancamento,
                    'id_ultimo_lancamento' => $idUltimoLancamento,
                    'modelo_serializado' => serialize($processoAbate),
                ]
            );
        }
    }
}

<?php

namespace MobileStock\service\EntregaService;

use DomainException;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use PDO;

class EntregasFilaProcessoAlterarEntregadorService
{
    public static function listaProdutos(PDO $conexao): array
    {
        $stmt = $conexao->query(
            "SELECT
               GROUP_CONCAT(entregas_fila_processo_alterar_entregador.uuid_produto) lista_produtos,
                entregas_fila_processo_alterar_entregador.id_colaborador_tipo_frete,
                IF(pedido_item_meu_look.id IS NULL, 'MS', 'ML') AS `origem`
            FROM entregas_fila_processo_alterar_entregador
            LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = entregas_fila_processo_alterar_entregador.uuid_produto
            WHERE entregas_fila_processo_alterar_entregador.situacao = 'PE'
            GROUP BY entregas_fila_processo_alterar_entregador.id_colaborador_tipo_frete"
        );

        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lista as &$item) {
            $item['lista_produtos'] = explode(',', $item['lista_produtos']);
        }

        return $lista;
    }

    /**
     * * Débito Técnico:
     * https://github.com/mobilestock/backend/issues/196
     */
    public static function alterarEntregadorEmTabelas(
        PDO $conexao,
        string $tabela,
        array $listaProdutos,
        int $idColaboradorTipoFrete
    ): void {
        [$sqlParam, $values] = ConversorArray::criaBindValues($listaProdutos);
        $values[':id_colaborador_tipo_frete'] = $idColaboradorTipoFrete;
        $sql = '';
        switch ($tabela) {
            case 'transacao_financeiras_produtos_itens':
                $sql = "UPDATE transacao_financeiras_produtos_itens
                        SET transacao_financeiras_produtos_itens.id_fornecedor = :id_colaborador_tipo_frete
                        WHERE transacao_financeiras_produtos_itens.tipo_item = 'CM_ENTREGA'
                        AND transacao_financeiras_produtos_itens.uuid_produto IN ($sqlParam);";
                break;
            case 'lancamento_financeiro_pendente':
                $sql = "UPDATE lancamento_financeiro_pendente
                        SET lancamento_financeiro_pendente.id_colaborador = :id_colaborador_tipo_frete,
                            lancamento_financeiro_pendente.id_recebedor = :id_colaborador_tipo_frete
                        WHERE lancamento_financeiro_pendente.numero_documento IN ($sqlParam)
                        AND lancamento_financeiro_pendente.origem = 'CM_ENTREGA';";
                break;
            case 'logistica_item':
                $sql = "UPDATE logistica_item
                        SET logistica_item.id_colaborador_tipo_frete = :id_colaborador_tipo_frete
                        WHERE logistica_item.uuid_produto IN ($sqlParam);";
                break;
            case 'transacao_financeiras_metadados':
                $sql = "UPDATE transacao_financeiras_metadados
                        JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras_metadados.id_transacao
                        SET transacao_financeiras_metadados.valor = :id_colaborador_tipo_frete
                        WHERE transacao_financeiras_produtos_itens.uuid_produto IN ($sqlParam)
                            AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE';";
                break;
            case 'pedido_item_meu_look':
                $sql = "UPDATE pedido_item_meu_look
                        SET pedido_item_meu_look.id_ponto = :id_colaborador_tipo_frete
                        WHERE pedido_item_meu_look.uuid IN ($sqlParam);";
                break;
        }
        $stmt = $conexao->prepare($sql);
        $stmt->execute($values);
        // if ($stmt->rowCount() !== count($listaProdutos)) {
        //     throw new \DomainException("Não foi possivel atualizar todos os registros na tabela $tabela");
        // }
    }

    public static function atualizaComissaoEntregaEPontoColeta(array $listaProdutos, int $idColaboradorTipoFrete): void
    {
        [$sqlParam, $values] = ConversorArray::criaBindValues($listaProdutos);
        $values[':id_colaborador_tipo_frete'] = $idColaboradorTipoFrete;
        $linhasAlteradas = DB::update(
            "UPDATE transacao_financeiras_produtos_itens
             SET
                transacao_financeiras_produtos_itens.id_fornecedor = CASE
                    WHEN transacao_financeiras_produtos_itens.tipo_item = 'CM_ENTREGA' THEN :id_colaborador_tipo_frete
                    ELSE (SELECT
                            tipo_frete.id_colaborador_ponto_coleta
                          FROM tipo_frete
                          WHERE tipo_frete.id_colaborador = :id_colaborador_tipo_frete
                          LIMIT 1)
                END
             WHERE transacao_financeiras_produtos_itens.tipo_item IN ('CM_ENTREGA', 'CM_PONTO_COLETA')
                AND transacao_financeiras_produtos_itens.uuid_produto IN ($sqlParam);",
            $values
        );

        if (!$linhasAlteradas) {
            throw new DomainException('Não foi possivel atualizar comissão de entrega e ponto de coleta');
        }
    }

    public static function concluirFilaProcessoAlterarEntregador(PDO $conexao, array $listaProdutos): void
    {
        [$sqlParam, $values] = ConversorArray::criaBindValues($listaProdutos);
        $stmt = $conexao->prepare(
            "UPDATE entregas_fila_processo_alterar_entregador
            SET entregas_fila_processo_alterar_entregador.situacao = 'PR'
            WHERE entregas_fila_processo_alterar_entregador.uuid_produto IN ($sqlParam);"
        );
        $stmt->execute($values);

        if ($stmt->rowCount() !== count($listaProdutos)) {
            throw new DomainException('Não foi possivel atualizar nenhum registro');
        }
    }
}

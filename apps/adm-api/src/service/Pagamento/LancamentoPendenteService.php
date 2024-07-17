<?php

namespace MobileStock\service\Pagamento;

use Exception;
use MobileStock\helper\ConversorArray;
use MobileStock\model\LancamentoPendente;
use PDO;

class LancamentoPendenteService
{
    public static function criar(PDO $conexao, LancamentoPendente $lancamento): LancamentoPendente
    {
        $query = '';

        $dados = $lancamento->extrair();

        //$lancamento = array_filter($lancamento);

        $dados = array_filter($dados, function ($i) {
            return $i !== null;
        });
        $size = sizeof($dados);

        $count = 0;
        $query = 'INSERT INTO lancamento_financeiro_pendente (';
        foreach ($dados as $key => $l) {
            $count++;
            $query .= $size > $count ? $key . ', ' : $key;
        }

        $count = 0;
        $query .= ')VALUES(';
        foreach ($dados as $key => $l) {
            $count++;
            $query .= $size > $count ? ':' . $key . ', ' : ':' . $key;
        }

        $query .= ')';
        //        echo '<pre>';
        //        echo $query;
        //        var_dump($lancamento);
        $sth = $conexao->prepare($query);
        foreach ($dados as $key => $l) {
            $sth->bindValue($key, $l, (new LancamentoPendenteService())->typeof($l));
        }

        if (!$sth->execute()) {
            throw new Exception('Erro ao gerar lancamento financeiro', 1);
        }

        $lancamento->id = $conexao->lastInsertId();
        return $lancamento;
    }
    public static function atualizar()
    {
    }
    public static function remover()
    {
    }
    public static function buscar()
    {
    }
    public function typeof($value)
    {
        switch (gettype($value)) {
            case 'float':
                return PDO::PARAM_STR;
                break;

            case 'double':
                return PDO::PARAM_STR;
                break;

            case 'string':
                return PDO::PARAM_STR;
                break;

            case 'integer':
                return PDO::PARAM_INT;
                break;
            default:
                return PDO::PARAM_STR;
                break;
        }
    }

    public static function removeLancamentos(PDO $conexao, array $lancamentos): int
    {
        [$lancamentosStr, $bind] = ConversorArray::criaBindValues($lancamentos);

        $stmt = $conexao->prepare(
            "DELETE FROM lancamento_financeiro_pendente WHERE lancamento_financeiro_pendente.id IN ($lancamentosStr)"
        );
        $stmt->execute($bind);

        return $stmt->rowCount();
    }

    public static function buscaLancamentosPendentesProduto(PDO $conexao, string $uuidProduto): array
    {
        $query = "SELECT
                    lancamento_financeiro_pendente.id AS `sequencia`,
                    lancamento_financeiro_pendente.tipo,
                    lancamento_financeiro_pendente.documento,
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
                    lancamento_financeiro_pendente.numero_documento
                FROM
                    lancamento_financeiro_pendente
                WHERE lancamento_financeiro_pendente.numero_documento = :uuid_produto";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':uuid_produto', $uuidProduto, PDO::PARAM_STR);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $resultado;
    }

    public static function buscaLancamentosPendentes(PDO $conexao): array
    {
        $query = "SELECT
            lancamento_financeiro_pendente.id,
            lancamento_financeiro_pendente.tipo,
            lancamento_financeiro_pendente.documento,
            lancamento_financeiro_pendente.situacao,
            lancamento_financeiro_pendente.origem,
            lancamento_financeiro_pendente.id_colaborador,
            lancamento_financeiro_pendente.valor,
            lancamento_financeiro_pendente.valor_total,
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
            lancamento_financeiro_pendente.numero_documento
        FROM lancamento_financeiro_pendente
        WHERE EXISTS(SELECT 1
                    FROM transacao_financeiras_produtos_itens
                    WHERE transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro_pendente.numero_documento
                    AND transacao_financeiras_produtos_itens.sigla_lancamento = lancamento_financeiro_pendente.origem)
            AND NOT EXISTS(SELECT 1 FROM lancamento_financeiro WHERE lancamento_financeiro.sequencia = lancamento_financeiro_pendente.id)
            AND (
                (COALESCE(DATEDIFF(NOW(), (SELECT entregas_faturamento_item.data_entrega
                    FROM entregas_faturamento_item
                        WHERE entregas_faturamento_item.uuid_produto = lancamento_financeiro_pendente.numero_documento
                        AND entregas_faturamento_item.situacao = 'EN'
                LIMIT 1)), 0) >= (SELECT configuracoes.qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1) + 1)
            )
        GROUP BY lancamento_financeiro_pendente.id;";

        $stmt = $conexao->prepare($query);
        $stmt->execute();
        $lancamentosPendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $lancamentosPendentes;
    }
}

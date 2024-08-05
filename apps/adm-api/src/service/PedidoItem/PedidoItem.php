<?php

namespace MobileStock\service\PedidoItem;

use Exception;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use PDO;

class PedidoItem extends \MobileStock\model\Pedido\PedidoItem
{
    public $linhas;

    // public static function buscaValorAcrescimoCnpj(PDO $conexao, int $idCliente): float
    // {
    //     $stmt = $conexao->prepare(
    //         "SELECT
    //             IF(colaboradores.regime = 1, COALESCE((
    //                 SELECT
    //                     SUM(produtos.valor_venda_cpf) - SUM(produtos.valor_venda_cnpj)
    //                 FROM pedido_item
    //                 INNER JOIN produtos ON produtos.id = pedido_item.id_produto
    //                 WHERE pedido_item.id_cliente = :idCliente
    //                   AND pedido_item.situacao IN ('2', 'DI')
    //                 HAVING COUNT(pedido_item.uuid) < (SELECT configuracoes.qtd_produtos_permite_compra_valor_cnpj
    //                                                   FROM configuracoes
    //                                                   LIMIT 1)), 0),
    //             0)
    //         FROM colaboradores
    //         WHERE colaboradores.id = :idCliente"
    //     );
    //     $stmt->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
    //     $stmt->execute();

    //     $valor = $stmt->fetchColumn();
    //     return $valor;
    // }

    public function adicionaPedidoItem(PDO $conexao)
    {
        $this->linhas = $this->transformaGradeEmItem();

        if (count($this->linhas) === 0) {
            return false;
        }

        $sql = $this->geraSqlAdicionar();

        $stmt = $conexao->prepare($sql);
        $bind = $this->transformaBindValues($this->linhas);

        //        echo ($sql);
        //        var_dump($bind);
        //        exit();
        return $stmt->execute($bind);
    }

    protected function transformaGradeEmItem(): array
    {
        $dados = array_reduce(
            $this->grade,
            function (array $total, $item) {
                $situacao = 0;
                for ($i = 0; $i < $item['qtd']; $i++) {
                    $situacao++;
                    $total[] = [
                        'nome_tamanho' => (string) $item['nome_tamanho'],
                        'id_produto' => $this->id_produto,
                        'id_cliente' => $this->id_cliente,
                        'cliente' => $this->cliente ?? '',
                        'uuid' => $this->id_cliente . '_' . uniqid(rand(), true),
                        'preco' => $this->preco ?? '',
                        'situacao' => $this->situacao,
                        'sequencia' => $situacao,
                        'id_transacao' => $this->id_transacao ?? 0,
                        'tipo_adicao' => $item['tipo_adicao'],
                        'observacao' => $this->observacao ?? '',
                    ];
                }
                return $total;
            },
            []
        );

        return $this->grade = $dados;
    }

    protected function transformaBindValues(array $linhas): array
    {
        $bind = [];
        array_walk($linhas, function ($linha, $indice) use (&$bind) {
            foreach ($linha as $key => $value) {
                if (!$value) {
                    continue;
                }

                $bind[":{$indice}_{$key}"] = $value;
            }
        });
        return $bind;
    }

    protected function geraSqlAdicionar(): string
    {
        $sql = 'INSERT INTO pedido_item (' . implode(',', array_keys(array_filter($this->linhas[0]))) . ') VALUES';
        foreach ($this->linhas as $indice => $linha) {
            $sql .= '
            (';
            foreach ($linha as $key => $value) {
                if (!$value) {
                    continue;
                }

                $sql .= ":{$indice}_{$key},";
            }
            $sql = mb_substr($sql, 0, mb_strlen($sql) - 1);
            $sql .= '),';
        }

        $sql = mb_substr($sql, 0, mb_strlen($sql) - 1);
        return $sql;
    }

    // public function atualizaClientePedidoItem(\PDO $conexao): void
    // {
    //     $stmt = $conexao->prepare(
    //         "CALL atualiza_cliente_pedido_item(:uuid, :cliente, :id_cliente_final)"
    //     );
    //     $stmt->execute([
    //         'cliente' => $this->cliente,
    //         'uuid' => $this->uuid,
    //         'id_cliente_final' => $this->id_cliente_final
    //     ]);
    // }

    /**
     * @param PDO $conexao
     * @param string[] $listaUuidsProdutosTransacao
     * @return void
     */
    public function atualizaIdTransacaoPI(array $listaUuidsProdutosTransacao): void
    {
        [$sql, $binds] = ConversorArray::criaBindValues($listaUuidsProdutosTransacao);
        $linhas = DB::update(
            "UPDATE pedido_item
                          SET
                              pedido_item.id_transacao =
                              CASE
                                  WHEN pedido_item.id_transacao IS NULL
                                  THEN :id_transacao
                                  ELSE pedido_item.id_transacao
                              END,
                             pedido_item.situacao     = :situacao
                      WHERE pedido_item.uuid IN ($sql)",
            [':id_transacao' => $this->id_transacao, ':situacao' => $this->situacao] + $binds
        );

        if (count($listaUuidsProdutosTransacao) !== $linhas) {
            throw new \InvalidArgumentException('Não conseguimos reservar os produtos.');
        }
    }

    public function removeProdutoPago(PDO $conexao): void
    {
        ($stmt = $conexao->prepare(
            "SELECT
                    pedido_item.data_atualizacao
                   FROM pedido_item
                   WHERE pedido_item.id_cliente = :id_cliente
                     AND pedido_item.uuid       = :uuid
                     AND pedido_item.situacao   = 'DI';

                   UPDATE pedido_item
                   SET pedido_item.situacao = 1
                   WHERE pedido_item.id_cliente = :id_cliente
                     AND pedido_item.uuid       = :uuid
                     AND pedido_item.situacao   IN ('DI', 'FR');

                   DELETE FROM pedido_item
                   WHERE pedido_item.id_cliente = :id_cliente
                     AND pedido_item.uuid       = :uuid
                     AND pedido_item.situacao   = '1'"
        ))->execute([
            ':id_cliente' => $this->id_cliente,
            ':uuid' => $this->uuid,
        ]);

        $this->data_atualizacao = $stmt->fetchColumn();

        while ($stmt->nextRowset()) {
            if ($stmt->rowCount() !== 1) {
                app(Logger::class)->withContext([
                    'uuid_produto' => $this->uuid,
                    'id_cliente' => $this->id_cliente,
                    'data_atualizacao' => $this->data_atualizacao,
                    'row_count' => $stmt->rowCount(),
                ]);
                throw new \RuntimeException('Não foi possivel remover produto pago');
            }
        }
    }

    public static function buscaItensCarrinho(PDO $conexao, int $idCliente, string $origem, int $idTransacao): array
    {
        $condicaoTransacao = "EXISTS(SELECT 1
                               FROM transacao_financeiras_produtos_itens
                               WHERE transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
                                 AND transacao_financeiras_produtos_itens.id_transacao = :idTransacao)";

        $where =
            $origem === 'ML'
                ? "pedido_item_meu_look.id IS NOT NULL AND $condicaoTransacao"
                : 'pedido_item_meu_look.id IS NULL';
        $stmt = $conexao->prepare(
            "SELECT pedido_item.uuid, pedido_item.situacao
            FROM pedido_item
            LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = pedido_item.uuid
            WHERE pedido_item.id_cliente = :idCliente
              AND (pedido_item.situacao = 'DI'
                    OR (pedido_item.situacao = '3' AND $condicaoTransacao))
              AND $where"
        );
        $stmt->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $stmt->execute();
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $consulta;
    }

    public static function atualizaFraudeParaDireitoDeItem(int $idTransacao): void
    {
        $rowCount = DB::update(
            "UPDATE pedido_item
            SET pedido_item.situacao = 'DI'
            WHERE pedido_item.id_transacao = :idTransacao
                AND pedido_item.situacao = 'FR'",
            [':idTransacao' => $idTransacao]
        );
        if ($rowCount === 0) {
            throw new Exception('Erro ao atualizar direito de item');
        }
    }
}

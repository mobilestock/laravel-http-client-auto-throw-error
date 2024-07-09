<?php

namespace MobileStock\service\TransacaoFinanceira;

use DomainException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraProdutosItens;
use MobileStock\service\ReputacaoFornecedoresService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue https://github.com/mobilestock/backend/issues/109
 * @deprecated
 */
class TransacaoFinanceiraItemProdutoService extends TransacaoFinanceiraProdutosItens
{
    public static function buscaComissoesProduto(PDO $conexao, string $uuid): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                transacao_financeiras_produtos_itens.id_transacao,
                transacao_financeiras_produtos_itens.id_fornecedor,
                transacao_financeiras_produtos_itens.comissao_fornecedor,
                transacao_financeiras_produtos_itens.preco,
                transacao_financeiras_produtos_itens.sigla_estorno
                FROM transacao_financeiras_produtos_itens
                WHERE transacao_financeiras_produtos_itens.uuid_produto = :uuid"
        );
        $stmt->execute([
            ':uuid' => $uuid,
        ]);

        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $comissoesProduto = array_filter($consulta, fn($comissao) => $comissao['sigla_estorno'] !== null);

        if (empty($comissoesProduto)) {
            throw new DomainException('Não foi possivel encontrar comissões para fazer o estorno.');
        }

        $precoCliente = array_sum(array_column($comissoesProduto, 'preco'));

        return [
            'comissoes' => $consulta,
            'preco' => $precoCliente,
        ];
    }

    public static function buscaValorCobradorProdutoTransacao(PDO $conexao, string $uuid): float
    {
        $valor = $conexao
            ->query(
                "SELECT
                        SUM(transacao_financeiras_produtos_itens.preco)
                      FROM transacao_financeiras_produtos_itens
                      WHERE transacao_financeiras_produtos_itens.uuid_produto = '$uuid'"
            )
            ->fetchColumn();

        return $valor;
    }

    /**
     * @param   int  $idTransacao
     * @param array  $listaTipoItem
     *
     * @return string[]
     */
    public static function buscaProdutosTransacao(int $idTransacao, array $listaTipoItem = ['PR']): array
    {
        [$binds, $valores] = ConversorArray::criaBindValues($listaTipoItem, 'tipo_item');
        $valores['id_transacao'] = $idTransacao;
        $consulta = DB::selectColumns(
            "SELECT
                transacao_financeiras_produtos_itens.uuid_produto
             FROM transacao_financeiras_produtos_itens
             WHERE transacao_financeiras_produtos_itens.id_transacao = :id_transacao
               AND transacao_financeiras_produtos_itens.tipo_item IN ($binds);",
            $valores
        );

        return $consulta;
    }
    public static function verificaDadosItemCancelamento(string $uuidProduto): void
    {
        $consulta = DB::selectOne(
            "SELECT
                EXISTS(
                    SELECT 1
                    FROM pedido_item
                    WHERE pedido_item.uuid = :uuid_produto
                        AND pedido_item.situacao IN ('DI', 'FR')
                ) AS `existe_pedido_item`,
                (
                    SELECT logistica_item.situacao
                    FROM logistica_item
                    WHERE logistica_item.uuid_produto = :uuid_produto
                ) AS `situacao_logistica_item`;",
            ['uuid_produto' => $uuidProduto]
        );
        if (empty($consulta['existe_pedido_item']) && empty($consulta['situacao_logistica_item'])) {
            throw new NotFoundHttpException('Esse produto já foi cancelado');
        } elseif (!empty($consulta['situacao_logistica_item']) && $consulta['situacao_logistica_item'] !== 'PE') {
            throw new BadRequestHttpException('Este produto não pode ser cancelado');
        }
    }

    public static function buscaInfoProdutoCancelamento(array $produtos): array
    {
        [$sql, $bind] = ConversorArray::criaBindValues($produtos);
        $sqlCriterioAfetarReputacao = ReputacaoFornecedoresService::sqlCriterioCancelamentoAfetarReputacao(
            'fornecedor_colaboradores.id'
        );
        $consulta = DB::select(
            "SELECT
                transacao_financeiras.pagador id_cliente,
                transacao_financeiras_produtos_itens.id_transacao,
                transacao_financeiras_produtos_itens.nome_tamanho,
                transacao_financeiras_produtos_itens.id_produto,
                transacao_financeiras_produtos_itens.id_responsavel_estoque,
                JSON_OBJECT(
                    'telefone', fornecedor_colaboradores.telefone,
                    'razao_social', fornecedor_colaboradores.razao_social
                ) AS json_fornecedor,
                (SELECT colaboradores.telefone
                 FROM colaboradores
                 WHERE colaboradores.id = transacao_financeiras.pagador) AS telefone_cliente,
                (SELECT produtos_foto.caminho
                 FROM produtos_foto
                 WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                 ORDER BY produtos_foto.tipo_foto IN ('MD', 'SM') DESC
                 LIMIT 1) AS foto,
                $sqlCriterioAfetarReputacao IS NOT NULL AS afetou_reputacao
             FROM transacao_financeiras_produtos_itens
             LEFT JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                 AND logistica_item_data_alteracao.situacao_nova = 'RE'
             LEFT JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
             INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            INNER JOIN colaboradores AS `fornecedor_colaboradores` ON fornecedor_colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
             WHERE transacao_financeiras_produtos_itens.uuid_produto IN ($sql)
               AND transacao_financeiras_produtos_itens.tipo_item = 'PR'",
            $bind
        );

        foreach ($consulta as &$item) {
            $item['sou_cliente'] = Gate::allows('CLIENTE') && $item['id_cliente'] === Auth::user()->id_colaborador;
            $item['sou_responsavel_estoque'] =
                Gate::allows('FORNECEDOR') && $item['id_responsavel_estoque'] === Auth::user()->id_colaborador;
        }
        return $consulta;
    }

    public function criaTransacaoItemProduto(PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($geradorSql->bind);

        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Não foi possivel inserir o item na transação');
        }
    }

    //    public function atualizaTransacaoItemProduto(pdo $conexao)
    //    {
    //        $dados = [];
    //        $sql = "UPDATE transacao_financeiras_produtos_itens SET ";
    //
    //        foreach ($this as $key => $valor) {
    //            if (!$valor || in_array($key,['pagador','status_transacao'])) {
    //                continue;
    //            }
    //            if (gettype($valor) == 'string') {
    //                $valor = "'" . $valor . "'";
    //            }
    //            array_push($dados, $key . " = " . $valor);
    //        }
    //        if (sizeof($dados) === 0) {
    //            throw new Error('Não Existe informações para ser atualizada');
    //        }
    //
    //        $sql .= " " . implode(',', $dados) . " WHERE transacao_financeiras_produtos_itens.id = '" . $this->id. "'";
    //
    //        return $conexao->exec($sql);
    //    }

    //    public function atualizaIdTransacaoComArrayDeUuidPedidoItemNaTabelaMedVendaProdutosConsumidorFinal(PDO $conexao, $arrayProdutos = [])
    //    {
    //        if (!isset($this->id_transacao)) throw new Error('Erro interno: Id da transação inválido');
    //        $sql = "UPDATE med_venda_produtos_consumidor_final
    //                SET med_venda_produtos_consumidor_final.id_transacao = {$this->id_transacao}
    //                WHERE med_venda_produtos_consumidor_final.uuid_pedido_item
    //                IN (" . str_replace(array("[", "]"), "", json_encode($arrayProdutos)) . ")";
    //        $prepare = $conexao->prepare($sql);
    //        $prepare->execute();
    //    }

    public static function buscaFreteTransacao(PDO $conexao, int $idTransacao): int
    {
        $consulta = $conexao->prepare(
            "SELECT transacao_financeiras_metadados.valor
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.id_transacao = :id_transacao
                AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
            LIMIT 1"
        );
        $consulta->bindValue(':id_transacao', $idTransacao, PDO::PARAM_INT);
        $consulta->execute();
        $frete = $consulta->fetchColumn();

        return $frete;
    }

    /**
     * @param TransacaoFinanceiraProdutosItens[] $transacoesProdutosItem
     */
    public static function insereVarios(PDO $conexao, array $transacoesProdutosItem): void
    {
        // TO-DO: Refatorar para usar um gerador padrão
        if (empty($transacoesProdutosItem)) {
            return;
        }

        $camposTransacao = array_reduce(
            $transacoesProdutosItem,
            function (array $total, $transacaoProdutoItem) {
                return array_merge(
                    $total,
                    array_values(array_diff(array_keys(array_filter($transacaoProdutoItem->extrair())), $total))
                );
            },
            []
        );
        sort($camposTransacao);
        $camposTransacaoSql = implode(',', $camposTransacao);
        $bindValues = [];

        $valores = implode(
            ',',
            array_map(
                function ($transacaoProdutoItem, int $key) use ($camposTransacao, &$bindValues): string {
                    $itens = array_filter($transacaoProdutoItem->extrair());
                    $itensNaoAdicionados = array_diff($camposTransacao, array_keys($itens));
                    foreach ($itensNaoAdicionados as $itemNaoAdicionado) {
                        $itens[$itemNaoAdicionado] = ConversorArray::CAMPO_PADRAO_CRIA_BIND_VALUES;
                    }
                    ksort($itens);
                    [$bind, $valores] = ConversorArray::criaBindValues(
                        $itens,
                        "transacao_financeiras_produtos_item_$key"
                    );
                    $bindValues = array_merge($valores, $bindValues);

                    return "($bind)";
                },
                $transacoesProdutosItem,
                array_keys($transacoesProdutosItem)
            )
        );

        $sql = $conexao->prepare(
            "INSERT INTO transacao_financeiras_produtos_itens (
                $camposTransacaoSql
            ) VALUES $valores;"
        );
        $sql->execute($bindValues);

        if ($sql->rowCount() !== sizeof($transacoesProdutosItem)) {
            throw new Exception('Quantidade de itens da transação inseridos está inconsistente');
        }
    }
    public static function buscaDadosProdutosTransacao(PDO $conexao, int $idTransacao, int $idCliente): array
    {
        $query = "SELECT
                        transacao_financeiras_produtos_itens.uuid_produto,
                        produtos.valor_custo_produto
                    FROM transacao_financeiras_produtos_itens
                    INNER JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
                    WHERE transacao_financeiras_produtos_itens.id_transacao = :idTransacao
                    AND transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
                    UNION ALL
                    SELECT
                        pedido_item.uuid,
                        produtos.valor_custo_produto
                    FROM pedido_item
                    INNER JOIN produtos ON produtos.id = pedido_item.id_produto
                    WHERE pedido_item.id_cliente = :idCliente
                    AND pedido_item.situacao = 'DI'";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $stmt->execute();

        $total = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $total;
    }

    public static function buscaFretesParaImpressao(array $idsFretes): array
    {
        [$binds, $valores] = ConversorArray::criaBindValues($idsFretes);
        $resultado = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id `id_frete`,
                transacao_financeiras_produtos_itens.id_transacao,
                transacao_financeiras_produtos_itens.uuid_produto,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`
            FROM transacao_financeiras_produtos_itens
            LEFT JOIN logistica_item ON transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
            JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            WHERE transacao_financeiras_produtos_itens.id IN ($binds)
                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND logistica_item.situacao = 'PE';",
            $valores
        );

        return $resultado;
    }
}

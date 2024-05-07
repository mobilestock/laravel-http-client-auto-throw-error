<?php

namespace MobileStock\service\PedidoItem;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\model\PedidoItem;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\Frete\FreteService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class TransacaoPedidoItem extends PedidoItem
{
    public function criaTransacaoProduto(PDO $conexao, int $usuario, int $idCliente): int
    {
        if ($this->array_uuid) {
            [$binds, $valores] = ConversorArray::criaBindValues($this->array_uuid, 'uuid_produto');
            $sql = $conexao->prepare(
                "UPDATE pedido_item
                SET pedido_item.situacao = :situacao
                WHERE pedido_item.situacao = 1
                    AND pedido_item.uuid IN ($binds);"
            );
            $sql->bindValue(':situacao', $this->situacao, PDO::PARAM_STR);
            foreach ($valores as $key => $value) {
                $sql->bindValue($key, $value, PDO::PARAM_STR);
            }
            $sql->execute();
            if ($sql->rowCount() !== sizeof($this->array_uuid)) {
                throw new Exception('Registros não atualizados corretamente!');
            }
        }

        $sql = $conexao->prepare(
            "SELECT
                pedido_item.id_produto,
                pedido_item.nome_tamanho,
                pedido_item.id_responsavel_estoque,
                pedido_item.uuid,
                calcula_valor_venda(pedido_item.id_cliente, pedido_item.id_produto) AS `preco`,
                produtos.valor_custo_produto,
                produtos.valor_venda_ms,
                produtos.id_fornecedor
            FROM pedido_item
            INNER JOIN produtos ON produtos.id = pedido_item.id_produto
            WHERE pedido_item.situacao = 2
                AND pedido_item.id_cliente = :id_cliente;"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        if (empty($produtos)) {
            throw new Exception('Nenhum produto encontrado');
        }

        $transacao = new TransacaoFinanceiraService();
        $transacao->id_usuario = $usuario;
        $transacao->metodos_pagamentos_disponiveis = 'CA,CR,PX';
        $transacao->origem_transacao = 'MP';
        $transacao->pagador = $idCliente;
        $transacao->valor_comissao_fornecedor = array_sum(array_column($produtos, 'valor_custo_produto'));
        $transacao->valor_itens = array_sum(array_column($produtos, 'valor_venda_ms'));
        $idTransacao = $transacao->criaTransacao($conexao);

        foreach ($produtos as $produto) {
            $produto['id_fornecedor'] = (int) $produto['id_fornecedor'];

            $ehProprioProduto = $idCliente === $produto['id_fornecedor'];
            $transacaoProdutosItens = new TransacaoFinanceiraItemProdutoService();
            $transacaoProdutosItens->comissao_fornecedor = $ehProprioProduto ? 0 : $produto['valor_custo_produto'];
            $transacaoProdutosItens->id_fornecedor = $produto['id_fornecedor'];
            $transacaoProdutosItens->id_produto = $produto['id_produto'];
            $transacaoProdutosItens->id_responsavel_estoque = $produto['id_responsavel_estoque'];
            $transacaoProdutosItens->id_transacao = $idTransacao;
            $transacaoProdutosItens->nome_tamanho = $produto['nome_tamanho'];
            $transacaoProdutosItens->preco = $produto['preco'];
            $transacaoProdutosItens->tipo_item = $ehProprioProduto ? 'RF' : 'PR';
            $transacaoProdutosItens->uuid_produto = $produto['uuid'];
            $transacaoProdutosItens->criaTransacaoItemProduto($conexao);
        }

        return $idTransacao;
    }
    public static function retornaEstoqueDisponivel(array $produtos): array
    {
        [$binds, $valores] = ConversorArray::criaBindValues($produtos, 'uuid_produto');
        $valores[':id_cliente'] = Auth::user()->id_colaborador;
        $estoqueDisponivel = [];
        $sqlConsultaEstoqueUnificado = ProdutosRepository::sqlConsultaEstoqueProdutos();

        $itens = DB::select(
            "SELECT
                JSON_OBJECT(
                    'id_produto', pedido_item.id_produto,
                    'nome_tamanho', pedido_item.nome_tamanho
                ) AS `json_produto`,
                COALESCE(_estoque_grade.qtd_estoque_fulfillment, 0) AS `qtd_estoque_fulfillment`,
                COALESCE(
                    _estoque_grade.externo,
                    JSON_OBJECT(
                        'id_responsavel_estoque', NULL,
                        'qtd_estoque_externo', NULL
                    )
                ) AS `json_externo`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT CONCAT('\"', pedido_item.uuid, '\"')),
                    ']'
                ) AS `json_informacoes_unitarias`
            FROM pedido_item
            LEFT JOIN ( $sqlConsultaEstoqueUnificado ) AS `_estoque_grade`
                ON _estoque_grade.id_produto = pedido_item.id_produto
                AND _estoque_grade.nome_tamanho = pedido_item.nome_tamanho
            WHERE pedido_item.id_cliente = :id_cliente
                AND pedido_item.uuid IN ($binds)
            GROUP BY json_produto;",
            $valores
        );

        foreach ($itens as $item) {
            $item = array_merge($item, $item['externo']);
            $qtdTotalEstoque = $item['qtd_estoque_fulfillment'] + $item['qtd_estoque_externo'];
            unset($item['externo']);

            foreach ($item['informacoes_unitarias'] as $uuidProduto) {
                if ($qtdTotalEstoque <= 0) {
                    throw new UnprocessableEntityHttpException(
                        'Infelizmente o produto que você estava comprando foi reservado por outro cliente.'
                    );
                }

                $qtdTotalEstoque--;
                if ($item['qtd_estoque_fulfillment'] > 0) {
                    $item['qtd_estoque_fulfillment']--;
                    $estoqueDisponivel[] = [
                        'uuid_produto' => $uuidProduto,
                        'id_responsavel_estoque' => 1,
                    ];
                } else {
                    $item['qtd_estoque_externo']--;
                    $estoqueDisponivel[] = [
                        'uuid_produto' => $uuidProduto,
                        'id_responsavel_estoque' => $item['id_responsavel_estoque'],
                    ];
                }
            }
        }

        return $estoqueDisponivel;
    }
    public static function reservaEAtualizaPrecosProdutosCarrinho(array $produtos): void
    {
        $situacaoProdutoReservado = PedidoItem::PRODUTO_RESERVADO;
        foreach ($produtos as $produto) {
            $pedidoItem = new PedidoItem();
            $pedidoItem->exists = true;
            $pedidoItem->uuid = $produto['uuid_produto'];
            $pedidoItem->situacao = $situacaoProdutoReservado;
            $pedidoItem->id_responsavel_estoque = $produto['id_responsavel_estoque'];
            $pedidoItem->update();
        }

        DB::update(
            "UPDATE pedido_item_meu_look
            INNER JOIN produtos ON produtos.id = pedido_item_meu_look.id_produto
            INNER JOIN pedido_item ON pedido_item.id_cliente = pedido_item_meu_look.id_cliente
                AND pedido_item.uuid = pedido_item_meu_look.uuid
            LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.uuid_produto = pedido_item_meu_look.uuid
            SET
                pedido_item_meu_look.preco = produtos.valor_venda_ml,
                pedido_item.preco = produtos.valor_venda_sem_comissao
            WHERE pedido_item_meu_look.id_cliente = :id_cliente
                AND transacao_financeiras_produtos_itens.id IS NULL
                AND (
                    pedido_item_meu_look.preco <> produtos.valor_venda_ml
                    OR pedido_item.preco <> produtos.valor_venda_sem_comissao
                );",
            ['id_cliente' => Auth::user()->id_colaborador]
        );
    }
    public static function buscaInformacoesFreteColaborador(): array
    {
        $freteColaborador = DB::selectOne(
            "SELECT
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.latitude,
                colaboradores_enderecos.longitude,
                tipo_frete.id_colaborador,
                IF (
                    tipo_frete.id = 2,
                    'MS',
                    tipo_frete.tipo_ponto
                ) AS `tipo_ponto`,
                configuracoes.porcentagem_comissao_ponto_coleta,
                IF (
                    tipo_frete.id = 2,
                    municipios.valor_frete,
                    0
                ) AS `valor_frete`,
                IF (
                    tipo_frete.id = 2,
                    municipios.valor_adicional,
                    0
                ) AS `valor_adicional`,
                COALESCE(
                    IF(
                        tipo_frete.id = 2,
                        NULL,
                        (
                            SELECT JSON_OBJECT(
                                'valor', transportadores_raios.valor,
                                'distancia', IF (
                                    tipo_frete.tipo_ponto = 'PM',
                                    distancia_geolocalizacao(
                                        colaboradores_enderecos.latitude,
                                        colaboradores_enderecos.longitude,
                                        transportadores_raios.latitude,
                                        transportadores_raios.longitude
                                    ) * 1000,
                                    NULL
                                )
                            ) AS `informacoes`
                            FROM transportadores_raios
                            WHERE transportadores_raios.esta_ativo
                                AND transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                            ORDER BY JSON_EXTRACT(informacoes, '$.distancia') ASC
                            LIMIT 1
                        )
                    ),
                    JSON_OBJECT(
                        'valor', 0,
                        'distancia', NULL
                    )
                ) AS `json_transporte`,
                COALESCE(tipo_frete.id_colaborador_ponto_coleta, 0) AS `id_colaborador_ponto_coleta`,
                COALESCE(pontos_coleta.porcentagem_frete, 0) AS `porcentagem_frete_ponto_coleta`
            FROM colaboradores
            INNER JOIN configuracoes
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN tipo_frete ON tipo_frete.id = colaboradores.id_tipo_entrega_padrao
            LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
            INNER JOIN municipios ON municipios.id = colaboradores_enderecos.id_cidade
            WHERE colaboradores.id = :id_cliente
            GROUP BY colaboradores.id
            LIMIT 1;",
            ['id_cliente' => Auth::user()->id_colaborador]
        );
        if (empty($freteColaborador)) {
            throw new NotFoundHttpException('Informações do frete não encontradas!');
        }
        $freteColaborador['valor_transporte'] = $freteColaborador['transporte']['valor'];
        unset($freteColaborador['transporte']);

        return $freteColaborador;
    }
    public static function buscaProdutosReservadosMeuLook(): array
    {
        $produtos = DB::select(
            "SELECT
                pedido_item.id_produto,
                pedido_item.nome_tamanho,
                pedido_item.id_responsavel_estoque,
                pedido_item.uuid,
                produtos.id_fornecedor,
                produtos.valor_custo_produto,
                ROUND(produtos.valor_venda_sem_comissao * (1 + (produtos.porcentagem_comissao_ml / 100)), 2) AS `preco`
            FROM pedido_item
            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = pedido_item.uuid
            INNER JOIN produtos ON produtos.id = pedido_item.id_produto
            WHERE pedido_item.situacao = :situacao
                AND pedido_item.id_cliente = :id_cliente
            GROUP BY pedido_item.uuid;",
            ['id_cliente' => Auth::user()->id_colaborador, 'situacao' => PedidoItem::PRODUTO_RESERVADO]
        );
        if (empty($produtos)) {
            throw new NotFoundResourceException('Nenhum produto está reservado para esse cliente');
        }

        return $produtos;
    }
    public function calculaComissoesMeuLook(array $freteColaborador, array $produtosReservados): array
    {
        foreach ($produtosReservados as $produto) {
            // Cria a comissão de produto
            $transacoesProdutosItem[] = $this->criaComissao(
                $produto['id_fornecedor'],
                'PR',
                $produto['valor_custo_produto'],
                $produto['preco'],
                $produto['uuid'],
                $produto['id_produto'],
                $produto['id_responsavel_estoque'],
                $produto['nome_tamanho']
            );

            if ($freteColaborador['valor_transporte'] > 0) {
                // Cria a comissão do entregador
                $transacoesProdutosItem[] = $this->criaComissao(
                    $freteColaborador['id_colaborador'],
                    $freteColaborador['tipo_ponto'] === 'PP' ? 'CE' : 'CM_ENTREGA',
                    $freteColaborador['valor_transporte'],
                    $freteColaborador['valor_transporte'],
                    $produto['uuid']
                );
            }

            if ($freteColaborador['porcentagem_comissao_ponto_coleta'] > 0) {
                // Cria a comissão do ponto de coleta
                $valorComissao = round(
                    $produto['valor_custo_produto'] * ($freteColaborador['porcentagem_comissao_ponto_coleta'] / 100),
                    2
                );
                $valorCustoFrete = round(
                    $produto['valor_custo_produto'] * ($freteColaborador['porcentagem_frete_ponto_coleta'] / 100),
                    2
                );
                $precoComissao = round($valorComissao + $valorCustoFrete, 2);
                $transacoesProdutosItem[] = $this->criaComissao(
                    $freteColaborador['id_colaborador_ponto_coleta'],
                    'CM_PONTO_COLETA',
                    $valorComissao,
                    $precoComissao,
                    $produto['uuid']
                );
            }
        }

        if ($freteColaborador['valor_frete'] > 0) {
            // Cria a comissão de transportadora
            $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();
            $qtdItensNaoExpedidos = count($itensNaoExpedidos);
            if ($qtdItensNaoExpedidos > 0) {
                $freteColaborador['valor_frete'] = 0;
            }

            $freteColaborador['valor_frete'] = FreteService::calculaValorFrete(
                $qtdItensNaoExpedidos,
                count($produtosReservados),
                $freteColaborador['valor_frete'],
                $freteColaborador['valor_adicional']
            );

            if ($freteColaborador['valor_frete'] > 0) {
                $transacoesProdutosItem[] = $this->criaComissao(
                    $freteColaborador['id_colaborador'],
                    'FR',
                    $freteColaborador['valor_frete'],
                    $freteColaborador['valor_frete']
                );
            }
        }

        return $transacoesProdutosItem;
    }
    protected function criaComissao(
        int $idColaboradorComissionado,
        string $tipoItem,
        float $valorComissao,
        float $preco,
        ?string $uuidProduto = null,
        ?int $idProduto = null,
        ?int $idResponsavelEstoque = null,
        ?string $nomeTamanho = null
    ): TransacaoFinanceiraItemProdutoService {
        $comissao = new TransacaoFinanceiraItemProdutoService();
        $comissao->id_transacao = $this->id_transacao;
        $comissao->id_fornecedor = $idColaboradorComissionado;
        $comissao->tipo_item = $tipoItem;
        $comissao->uuid_produto = $uuidProduto;
        $comissao->comissao_fornecedor = $valorComissao;
        $comissao->preco = $preco;
        $comissao->id_produto = $idProduto;
        $comissao->id_responsavel_estoque = $idResponsavelEstoque;
        $comissao->nome_tamanho = $nomeTamanho;

        return $comissao;
    }
}

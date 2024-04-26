<?php

namespace MobileStock\service\PedidoItem;

use DomainException;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use MobileStock\model\Origem;
use MobileStock\model\Pedido\PedidoItemMeuLook;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\Frete\FreteService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\ProdutoService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PedidoItemMeuLookService extends PedidoItemMeuLook
{
    public function insereProdutos(PDO $conexao)
    {
        $produtos = $this->produtos;
        unset($this->produtos);

        $sql = '';
        $bindValues = [];

        foreach ($produtos as $key => $produto) {
            Validador::validar($produto, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [Validador::OBRIGATORIO],
            ]);

            $infoProduto = ProdutoService::buscaPrecoEResponsavelProduto(
                $produto['id_produto'],
                $produto['nome_tamanho']
            );
            $pedidoItemMeuLook = new PedidoItemMeuLookService();
            $pedidoItemMeuLook->id_cliente = $this->id_cliente;
            $pedidoItemMeuLook->id_produto = $produto['id_produto'];
            $pedidoItemMeuLook->nome_tamanho = $produto['nome_tamanho'];
            $pedidoItemMeuLook->tipo_adicao = $produto['fila'] ?? false === true ? 'FL' : 'PR';
            $pedidoItemMeuLook->id_responsavel_estoque = $infoProduto['id_responsavel'];

            $pedidoItemMeuLook->uuid = $this->id_cliente . '_' . uniqid(rand(), true);
            $pedidoItemMeuLook->preco = $infoProduto['preco'];
            $pedidoItemMeuLook->observacao = $produto['observacao'] ?? null;
            ['sql' => $sqlItem, 'bind_values' => $dados] = $pedidoItemMeuLook->salvaPedidoItemMeuLook($key);
            $sql .= $sqlItem;
            $bindValues = array_merge($bindValues, $dados);
        }

        // echo '<pre>';
        // echo $sql;
        // var_dump($bindValues);
        // exit;
        $stmt = $conexao->prepare($sql);
        $stmt->execute($bindValues);
    }

    public function salvaPedidoItemMeuLook($prefixo)
    {
        $camposTabelaMeuLook = [];
        $dadosTabelaMeuLook = [];

        $camposItemPedido = ['id_produto', 'id_cliente', 'nome_tamanho', 'uuid', 'preco', 'tipo_adicao'];
        if (!is_null($this->observacao)) {
            $camposItemPedido[] = 'observacao';
        }
        $dadosItemPedido = [];
        $dados = [];

        foreach ($this as $key => $value) {
            if (!$value) {
                continue;
            }

            if (array_search($key, $camposItemPedido) !== false) {
                $keyAux = ":{$prefixo}_PI_{$key}";
                $dadosItemPedido[] = $keyAux;
                $dados[$keyAux] = $value;
            }

            if ($key !== 'tipo_adicao' && $key !== 'observacao') {
                $camposTabelaMeuLook[] = $key;
                $key = $prefixo . '_' . $key;
                $dadosTabelaMeuLook[] = ":{$key}";
                $dados[$key] = $value;
            }
        }

        $sql =
            'INSERT INTO pedido_item_meu_look (' .
            implode(',', $camposTabelaMeuLook) .
            ') VALUES (' .
            implode(',', $dadosTabelaMeuLook) .
            ');' .
            'INSERT INTO pedido_item (' .
            implode(',', $camposItemPedido) .
            ') VALUES (' .
            implode(',', $dadosItemPedido) .
            ');';

        return [
            'sql' => $sql,
            'bind_values' => $dados,
        ];
    }
    public static function consultaCarrinhoBasico(PDO $conexao, int $idCliente): array
    {
        $sql = $conexao->prepare(
            "SELECT
                pedido_item.id_produto,
                estoque_grade.nome_tamanho
            FROM pedido_item
            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = pedido_item.uuid
            INNER JOIN estoque_grade ON estoque_grade.estoque > 0
                AND estoque_grade.id_produto = pedido_item.id_produto
                AND estoque_grade.nome_tamanho = pedido_item.nome_tamanho
            WHERE pedido_item.id_cliente = :id_cliente
            GROUP BY pedido_item.id_produto, pedido_item.nome_tamanho;"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
        $produtos = array_map(function (array $produto): array {
            $previsao = app(PrevisaoService::class);
            $produto['id_produto'] = (int) $produto['id_produto'];
            $produto['medias_envio'] = $previsao->calculoDiasSeparacaoProduto(
                $produto['id_produto'],
                $produto['nome_tamanho']
            );

            return $produto;
        }, $produtos);

        return $produtos;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/136
     */
    public static function consultaQuantidadeProdutosNoCarrinhoMeuLook(int $idCliente): int
    {
        $binds = [':id_cliente' => $idCliente];

        $sql = "SELECT COUNT(DISTINCT pedido_item.uuid) as qtd_produtos
                FROM pedido_item
                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = pedido_item.uuid
                INNER JOIN estoque_grade ON estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = pedido_item.id_produto
                    AND estoque_grade.nome_tamanho = pedido_item.nome_tamanho
                WHERE pedido_item.id_cliente = :id_cliente";

        $qtdProdutos = DB::selectOneColumn($sql, $binds);
        return $qtdProdutos;
    }
    /**
     * @see https://github.com/mobilestock/backend/issues/136
     */
    public static function consultaProdutosCarrinho(bool $consultarPrevisoes)
    {
        $idCliente = Auth::user()->id_colaborador;
        if ($consultarPrevisoes) {
            $previsao = app(PrevisaoService::class);
            $transportador = $previsao->buscaTransportadorPadrao($idCliente);
        }

        $sqlConsultaEstoqueUnificado = ProdutosRepository::sqlConsultaEstoqueProdutos();
        $carrinho = [];
        $filaDeEspera = [];
        $separacaoResponsavel = [];
        if (app(Origem::class)->ehMobileEntregas()) {
            $where = ' AND produtos.id = :id_produto_frete ';
        } else {
            $where = ' AND produtos.id <> :id_produto_frete ';
        }

        $itens = DB::select(
            "SELECT
                pedido_item.id_produto,
                pedido_item.nome_tamanho,
                produtos.nome_comercial,
                produtos.valor_venda_ml AS `valor`,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = pedido_item.id_produto
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) AS `foto`,
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
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'uuid', pedido_item.uuid,
                        'observacao', pedido_item.observacao,
                        'tipo_adicao', pedido_item.tipo_adicao
                    )),
                    ']'
                ) AS `json_informacoes_unitarias`
            FROM pedido_item
            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = pedido_item.uuid
            INNER JOIN produtos ON produtos.id = pedido_item.id_produto
            LEFT JOIN ( $sqlConsultaEstoqueUnificado ) AS `_estoque_grade` ON _estoque_grade.id_produto = pedido_item.id_produto
                AND _estoque_grade.nome_tamanho = pedido_item.nome_tamanho
            LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
            WHERE pedido_item_meu_look.situacao = 'CR'
                AND pedido_item.id_cliente = :id_cliente
                $where
            AND pedido_item.situacao = '1'
            AND transacao_financeiras_produtos_itens.id IS NULL
            GROUP BY pedido_item.id_produto, pedido_item.nome_tamanho
            ORDER BY pedido_item.id DESC;",
            ['id_cliente' => $idCliente, 'id_produto_frete' => FreteService::PRODUTO_FRETE]
        );

        foreach ($itens as $item) {
            $item = array_merge($item, $item['externo']);
            $qtdTotalEstoque = $item['qtd_estoque_fulfillment'] + $item['qtd_estoque_externo'];
            unset($item['externo']);

            foreach ($item['informacoes_unitarias'] as $unitario) {
                $unitario['full'] = false;
                $unitario['previsoes'] = null;
                $unitario['tipo_adicao'] = $qtdTotalEstoque <= 0 ? 'FL' : 'PR';
                if (!empty($unitario['observacao'])) {
                    $unitario['observacao'] = json_decode($unitario['observacao']);
                }

                if ($qtdTotalEstoque <= 0) {
                    $produtoTemp = Arr::except($item, [
                        'informacoes_unitarias',
                        'id_responsavel_estoque',
                        'qtd_estoque_fulfillment',
                        'qtd_estoque_externo',
                    ]);
                    $filaDeEspera[] = array_merge($produtoTemp, $unitario);
                    continue;
                }

                $qtdTotalEstoque--;
                if ($item['qtd_estoque_fulfillment'] > 0) {
                    $item['qtd_estoque_fulfillment']--;
                    $unitario['full'] = true;
                    $idResponsavelEstoque = 1;
                } else {
                    $item['qtd_estoque_externo']--;
                    $idResponsavelEstoque = $item['id_responsavel_estoque'];
                }
                if ($consultarPrevisoes && !empty($transportador['horarios'])) {
                    if (!isset($separacaoResponsavel[$idResponsavelEstoque])) {
                        $separacaoResponsavel[$idResponsavelEstoque] = $previsao->calculoDiasSeparacaoProduto(
                            $item['id_produto'],
                            $item['nome_tamanho'],
                            $idResponsavelEstoque
                        );
                    }

                    $diasProcessoEntrega = Arr::only($transportador, [
                        'dias_entregar_cliente',
                        'dias_pedido_chegar',
                        'dias_margem_erro',
                    ]);
                    $unitario['previsoes'] = $previsao->calculaPorMediasEDias(
                        $separacaoResponsavel[$idResponsavelEstoque],
                        $diasProcessoEntrega,
                        $transportador['horarios']
                    );
                }

                $produtoTemp = Arr::except($item, [
                    'informacoes_unitarias',
                    'id_responsavel_estoque',
                    'qtd_estoque_fulfillment',
                    'qtd_estoque_externo',
                ]);
                $carrinho[] = array_merge($produtoTemp, $unitario);
            }
        }

        return [
            'carrinho' => $carrinho,
            'fila_espera' => $filaDeEspera,
        ];
    }

    public function itemExiste(PDO $conexao): bool
    {
        if (empty($this->uuid)) {
            throw new NotFoundHttpException('É necessário informar qual o produto que deseja remover.');
        }
        $sql = $conexao->prepare(
            "SELECT 1
            FROM pedido_item
            WHERE pedido_item.uuid = :uuid_produto
              AND pedido_item.situacao = '1';"
        );
        $sql->bindParam(':uuid_produto', $this->uuid, PDO::PARAM_STR);
        $sql->execute();
        $itemExiste = (bool) $sql->fetchColumn();

        return $itemExiste;
    }

    public function removeProdutos(PDO $conexao): void
    {
        if (empty($this->uuid)) {
            throw new NotFoundHttpException('É necessário informar qual o produto que deseja remover.');
        }
        $sql = $conexao->prepare(
            "DELETE FROM pedido_item
            WHERE pedido_item.uuid = :uuid_produto;"
        );
        $sql->bindParam(':uuid_produto', $this->uuid, PDO::PARAM_STR);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new DomainException('Não foi possível remover o produto do carrinho.');
        }
    }

    // public function removePedidoItemMeuLook(): array
    // {
    //     $sql = 'DELETE FROM pedido_item WHERE pedido_item.uuid = :uuid;';
    //     $dados = ['uuid' => $this->uuid];

    //     return [
    //         'sql' => $sql,
    //         'bind_values' => $dados,
    //     ];
    // }

    // public static function buscaLogLinksMeulook(PDO $conexao)
    // {
    //     $resultado = $conexao->query(
    //         "SELECT
    //             pedido_item_meu_look.id,
    //             transacao_financeiras_produtos_itens.id_transacao,
    //             CONCAT(cliente.razao_social, ' (', cliente.usuario_meulook, ')') cliente,
    //             CONCAT(compartilhador.razao_social, ' (', compartilhador.usuario_meulook, ')') compartilhador,
    //             DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i:%s') data,
    //             CASE transacao_financeiras.status
    //                 WHEN 'PA' THEN 'Pago'
    //                 WHEN 'PE' THEN 'Pendente'
    //                 ELSE 'Outro'
    //             END status
    //         FROM pedido_item_meu_look
    //         INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid = pedido_item_meu_look.uuid
    //         INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
    //         INNER JOIN colaboradores cliente ON cliente.id = pedido_item_meu_look.id_cliente
    //         INNER JOIN colaboradores compartilhador ON compartilhador.id = pedido_item_meu_look.id_colaborador_compartilhador_link
    //         GROUP BY pedido_item_meu_look.id
    //         ORDER BY pedido_item_meu_look.id DESC
    //         LIMIT 200"
    //     )->fetchAll(PDO::FETCH_ASSOC);

    //     return $resultado;
    // }

    public static function buscaTipoFreteMaisBaratoCarrinho(string $tipo, ?int $idTipoFretePadrao = null): array
    {
        $orderSql = $idTipoFretePadrao ? ' tipo_frete.id = :id_tipo_frete_padrao DESC ' : '1=1';
        $query = '';
        $binds = [];

        switch ($tipo) {
            case 'PM':
                $query = "SELECT
                        tipo_frete.id,
                        transportadores_raios.id_colaborador,
                        transportadores_raios.valor,
                        COALESCE(pontos_coleta.porcentagem_frete, 0) AS `porcentagem_frete`,
                        distancia_geolocalizacao(
                            `consulta_colaboradores`.latitude,
                            `consulta_colaboradores`.longitude,
                            transportadores_raios.latitude,
                            transportadores_raios.longitude
                        ) * 1000 AS `distancia`,
                        `consulta_colaboradores`.latitude AS `latitude_origem`,
                        `consulta_colaboradores`.longitude AS `longitude_origem`,
                        transportadores_raios.latitude AS `latitude_destino`,
                        transportadores_raios.longitude AS `longitude_destino`,
                        transportadores_raios.raio,
                        transportadores_raios.esta_ativo
                    FROM (
                        SELECT
                            colaboradores_enderecos.id_cidade,
                            colaboradores_enderecos.latitude,
                            colaboradores_enderecos.longitude
                        FROM colaboradores_enderecos
                        WHERE
                            colaboradores_enderecos.id_colaborador = :id_cliente
                            AND colaboradores_enderecos.eh_endereco_padrao = 1
                    ) AS `consulta_colaboradores`
                    JOIN transportadores_raios ON transportadores_raios.id_cidade = `consulta_colaboradores`.id_cidade
                    JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                        AND tipo_frete.categoria = 'ML'
                        AND tipo_frete.tipo_ponto = :tipo_ponto
                        AND transportadores_raios.esta_ativo
                    LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                    ORDER BY `distancia` <= transportadores_raios.raio DESC, `distancia` ASC
                    LIMIT 1";

                $binds[':id_cliente'] = Auth::user()->id_colaborador;
                $binds[':tipo_ponto'] = $tipo;
                break;

            case 'PP':
                $query = "SELECT
                            tipo_frete.id,
                            transportadores_raios.valor,
                            COALESCE(pontos_coleta.porcentagem_frete, 0) AS `porcentagem_frete`
                        FROM transportadores_raios
                        JOIN tipo_frete ON tipo_frete.id_colaborador = transportadores_raios.id_colaborador
                            AND tipo_frete.categoria = 'ML'
                            AND tipo_frete.tipo_ponto = :tipo_ponto
                        LEFT JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                        WHERE transportadores_raios.esta_ativo
                        ORDER BY $orderSql, transportadores_raios.valor
                        LIMIT 1";

                $binds[':tipo_ponto'] = $tipo;
                break;
        }

        if ($idTipoFretePadrao !== null) {
            $binds[':id_tipo_frete_padrao'] = $idTipoFretePadrao;
        }

        $resultado = DB::selectOne($query, $binds);

        return $resultado ?: [];
    }

    public static function buscaDadosProdutoPorUuid(PDO $conexao, string $uuid, string $origem): array
    {
        $where = 'pedido_item_meu_look.uuid = :uuid_produto';
        if ($origem === 'MS') {
            $select = "
                transacao_financeiras_produtos_itens.id_produto, transacao_financeiras_produtos_itens.nome_tamanho,
                (
                    CURRENT_DATE() - INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_normal_ms FROM configuracoes LIMIT 1) DAY <
                    DATE(entregas_faturamento_item.data_base_troca)
                ) periodo_solicitar_troca_normal_disponivel,
                (
                    CURRENT_DATE() - INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_defeito_ms FROM configuracoes LIMIT 1) DAY <
                    DATE(entregas_faturamento_item.data_base_troca)
                ) periodo_solicitar_troca_defeito_disponivel,
            ";
            $from = ' transacao_financeiras_produtos_itens ';
            $joins = "
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            ";
            $where = ' transacao_financeiras_produtos_itens.uuid_produto = :uuid_produto ';
        } elseif ($origem === 'ML') {
            $select = "
                pedido_item_meu_look.id_produto, pedido_item_meu_look.nome_tamanho,
                (
                    CURRENT_DATE() - INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1) DAY <
                    DATE(entregas_faturamento_item.data_base_troca)
                ) periodo_solicitar_troca_normal_disponivel,
                (
                    CURRENT_DATE() - INTERVAL (SELECT configuracoes.qtd_dias_disponiveis_troca_defeito FROM configuracoes LIMIT 1) DAY <
                    DATE(entregas_faturamento_item.data_base_troca)
                ) periodo_solicitar_troca_defeito_disponivel,
                EXISTS(SELECT 1 FROM troca_pendente_agendamento WHERE troca_pendente_agendamento.uuid = :uuid_produto
                ) existe_agendamento,
            ";
            $from = ' pedido_item_meu_look ';
            $joins = "
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = pedido_item_meu_look.uuid
                LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = pedido_item_meu_look.uuid
            ";
        }
        $stmt = $conexao->prepare(
            "SELECT
                $select
                troca_fila_solicitacoes.id id_solicitacao,
                troca_fila_solicitacoes.id IS NOT NULL solicitacao_existe,
                troca_fila_solicitacoes.situacao
            FROM $from
            $joins
            WHERE $where"
        );
        $stmt->bindValue(':uuid_produto', $uuid, PDO::PARAM_STR);
        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($produto === false) {
            throw new Exception('Compra relacionada não encontrada!');
        }
        $produto['periodo_solicitar_troca_normal_disponivel'] =
            (bool) $produto['periodo_solicitar_troca_normal_disponivel'];
        $produto['periodo_solicitar_troca_defeito_disponivel'] =
            (bool) $produto['periodo_solicitar_troca_defeito_disponivel'];
        $produto['solicitacao_existe'] = (bool) $produto['solicitacao_existe'];
        return $produto;
    }

    /**
     * @deprecated
     * Esse função só existe por que ainda existem consultas utilizando os campos depreciados do pi_ml.
     * @see https://github.com/mobilestock/backend/issues/136
     * @param PDO $conexao
     * @param int $idTransacao
     * @param int $idColaboradorTipoFrete
     * @param array $listaProdutos
     * @return void
     *
     */
    public static function atualizaInfoPagamentoProduto(
        PDO $conexao,
        int $idTransacao,
        int $idColaboradorTipoFrete,
        array $listaProdutos
    ): void {
        $listaProdutosSql = implode(',', array_map(ConversorArray::mapEnvolvePorString("'"), $listaProdutos));
        $linhasAfetadas = $conexao->exec(
            "UPDATE pedido_item_meu_look
                SET pedido_item_meu_look.situacao = 'PA',
                    pedido_item_meu_look.id_transacao = $idTransacao,
                    pedido_item_meu_look.id_ponto = $idColaboradorTipoFrete
             WHERE pedido_item_meu_look.uuid IN ($listaProdutosSql)"
        );

        if (count($listaProdutos) !== $linhasAfetadas) {
            throw new DomainException('Não foi possivel atualizar o pedido.');
        }
    }
}

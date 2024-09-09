<?php

namespace api_cliente\Models;

use MobileStock\helper\DB;
use PDO;

class Painel
{
    public static $consultaBancoPainel = [];

    public static function consultaProdutosPedido(PDO $conn, int $idCliente): array
    {
        $sql = "SELECT
					pedido_item.id_produto,
					pedido_item.uuid,
					produtos.nome_comercial,
					produtos.descricao,
					(
						SELECT produtos_foto.caminho
						FROM produtos_foto
						WHERE produtos_foto.id = pedido_item.id_produto
						ORDER BY produtos_foto.tipo_foto = 'MD' DESC
						LIMIT 1
					) AS `foto`,
					@VALOR_UNITARIO := (SELECT calcula_valor_venda(pedido_item.id_cliente, pedido_item.id_produto)) AS `preco`,
					pedido_item.situacao,
					pedido_item.situacao = 'DI' AS `par_pago`,
					COALESCE(transacao_financeiras_produtos_itens.data_criacao, pedido_item.data_criacao) AS `data_hora`,
					estoque_grade.nome_tamanho,
					COALESCE(pedido_item.cliente, '') AS `cliente`,
					IF (pedido_item.situacao = 'DI', 0, 1) AS `selecionado`,
					IF (pedido_item.situacao = 'DI', 0, 1) AS `selecionavel`,
					estoque_grade.estoque,
					(
						SELECT COUNT(pedido_item_2.uuid)
						FROM pedido_item AS pedido_item_2
						WHERE pedido_item_2.id_produto = pedido_item.id_produto
							AND pedido_item_2.nome_tamanho = pedido_item.nome_tamanho
							AND pedido_item_2.id_responsavel_estoque = 1
					) AS `pedido`,
					DATEDIFF(CURRENT_DATE, transacao_financeiras_produtos_itens.data_atualizacao) > (SELECT configuracoes.num_dias_remover_produto_pago FROM configuracoes) AS `sera_cobrado_taxa`,
                    pedido_item.observacao
				FROM pedido_item
				JOIN produtos ON produtos.id = pedido_item.id_produto
				JOIN estoque_grade ON estoque_grade.id_produto = pedido_item.id_produto
					AND estoque_grade.nome_tamanho = pedido_item.nome_tamanho
					AND estoque_grade.id_responsavel = pedido_item.id_responsavel_estoque
				LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
				LEFT JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = pedido_item.id_transacao
				WHERE pedido_item.id_cliente = :idCliente
				AND pedido_item.id_responsavel_estoque = 1
				AND NOT EXISTS (SELECT 1 FROM pedido_item_meu_look WHERE pedido_item_meu_look.uuid = pedido_item.uuid)
					AND transacao_financeiras_metadados.id IS NULL
					AND pedido_item.situacao IN ('1', 'DI')
				ORDER BY pedido_item.nome_tamanho, pedido_item.id_produto";
        $stm = $conn->prepare($sql);
        $stm->bindValue(':idCliente', $idCliente, PDO::PARAM_INT);
        $stm->execute();
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
        $resultado = array_map(function ($item) {
            $item['preco'] = (float) $item['preco'];
            $item['observacao'] = json_decode($item['observacao'], true);
            $item['par_pago'] = (bool) $item['par_pago'];
            $item['selecionavel'] = (bool) $item['selecionavel'];
            $item['selecionado'] = (bool) $item['selecionado'];
            return $item;
        }, $resultado);
        return $resultado;
    }

    public static function deletaItensPainel(PDO $conexao, int $idCliente, string $uuid)
    {
        $stmt = $conexao->prepare('DELETE FROM pedido_item WHERE id_cliente= :id_cliente AND uuid = :uuid');
        return $stmt->execute([':id_cliente' => $idCliente, ':uuid' => $uuid]);
    }

    public static function buscaValorTaxaProdutoPago(): float
    {
        return DB::select('SELECT valor_taxa_remove_produto_pago FROM configuracoes', [], null, 'fetch')[
            'valor_taxa_remove_produto_pago'
        ];
    }

    public static function analisaEstoquePedido(PDO $conn, array $pedido)
    {
        $idProdutoTemp = 0;
        $tamanhoTemp = 0;
        $estoque = 0;
        $painel = [];
        $reservados = [];
        foreach ($pedido as $key => $p) {
            if (
                $idProdutoTemp != $p['id_produto'] ||
                ($idProdutoTemp == $p['id_produto'] && $tamanhoTemp != $p['nome_tamanho'])
            ) {
                $tamanhoTemp = $p['nome_tamanho'];
                $idProdutoTemp = $p['id_produto'];
                $estoque = $p['estoque'];
                $quant = 0;
            }

            if ($p['par_pago'] == 1 || $p['situacao'] != 1 || ($quant <= $p['estoque'] && $estoque > 0)) {
                $pedido[$key]['lista'] = 'Lista de pedidos';
                $painel[] = $pedido[$key];
                $estoque--;
                $quant++;
            } else {
                $pedido[$key]['lista'] = 'Lista de espera';
                $reservados[] = $pedido[$key];
            }
        }

        usort($painel, function ($p1, $p2) {
            $data1 = strtotime($p1['data_hora']);
            $data2 = strtotime($p2['data_hora']);
            return $data2 - $data1;
        });

        $pedido = ['pedido' => $painel, 'reservados' => $reservados];
        return $pedido;
    }

    public static function listaFreteiros(PDO $conexao)
    {
        $query = 'SELECT id, nome FROM freteiro ORDER BY nome;';
        $resultado = $conexao->query($query);
        $freteiro = $resultado->fetchAll(PDO::FETCH_ASSOC);
        return $freteiro;
    }
}

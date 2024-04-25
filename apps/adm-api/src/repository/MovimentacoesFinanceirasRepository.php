<?php
/*
namespace MobileStock\repository;

use MobileStock\database\TraitConexao;
use PDO;
use DateTime;

class MovimentacoesFinanceirasRepository
{
    use TraitConexao;

    public function buscaFornecedores()
    {
        $query = "SELECT razao_social FROM colaboradores WHERE TIPO='F' ORDER BY RAZAO_SOCIAL";
        $stmt = $this->criarConexao()->query($query);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }

    function buscaSaldoProdutosDefeituosos(int $id_fornecedor)
    {
        $sql = "SELECT   d.id,
                    d.id_fornecedor,
                    d.id_cliente,
                    d.id_produto,
                    p.descricao referencia,
                    d.descricao_defeito,
                    d.data_hora,
                    d.tamanho,
                    d.preco,
                    d.sequencia,
                    d.abater,
                    d.uuid,
                    u.nome,
                    p.preco custo,
                    d.status,
                    (SELECT caminho FROM produtos_foto WHERE  id = p.id AND foto_calcada = 0 LIMIT  1) caminho
                FROM defeitos d
                    INNER JOIN produtos p ON (p.id = d.id_produto)
                    INNER JOIN usuarios u ON (u.id = d.id_vendedor)
                WHERE d.id_fornecedor = {$id_fornecedor} AND abater = 0 AND status = 'A' GROUP BY d.uuid;";
        $resultado = $this->criarConexao()->query($sql);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    function getResumoFinanceiro(int $id_fornecedor, string $data, int $pagina)
    {
        $offSet = $pagina ? $pagina * 5 - 5 : 0;
        $d = new DateTime($data);
        $data = $d->format('Y-m-d');
        $sql = "SELECT
                    cf.*,
                    (SELECT count(id_fornecedor) FROM movimentacoes_financeiras where id_fornecedor = cf.id_fornecedor  and MONTH(data) = MONTH('{$data}') and YEAR(data) = YEAR('{$data}') AND TIPO = 1) qtd_paginas
                FROM movimentacoes_financeiras cf where id_fornecedor = {$id_fornecedor} and MONTH(data) = MONTH('{$data}') and YEAR(data) = YEAR('{$data}') AND TIPO = 1 ORDER BY id DESC LIMIT 5 OFFSET {$offSet}";
        $resultado = $this->criarConexao()->query($sql);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    function somaLancamentosByTipo(int $id_fornecedor, string $data = '', int $tipo)
    {
        $sql = "SELECT sum(valor_lancamento) valor_total_pago FROM movimentacoes_financeiras WHERE id_fornecedor = $id_fornecedor";
        if ($data) {
            $d = new DateTime($data);
            $data = $d->format('Y-m-d');
            $sql .= " AND MONTH(data) = MONTH('{$data}')";
        }
        $sql .= "  AND TIPO = $tipo";
        $resultado = $this->criarConexao()->query($sql);
        $retorno =  $resultado->fetchAll(PDO::FETCH_ASSOC);
        return $retorno[0]['valor_total_pago'] ? $retorno[0]['valor_total_pago'] : 0;
    }

    function novoLancamentoFinanceiro(array $lancamento)
    {
        $sql = "INSERT INTO movimentacoes_financeiras(
                id_fornecedor,
                data,
                valor_lancamento,
                saldo_anterior,
                saldo,
                saldo_defeitos,
                tipo
              )VALUES(
                  {$lancamento['id_fornecedor']}, -- id_fornecedor - IN int(11)
                  '{$lancamento['data']}', -- data - IN datetime
                  {$lancamento['valor_lancamento']}, -- lancamento - IN double
                  {$lancamento['saldo_anterior']}, -- saldo - IN double
                  {$lancamento['saldo']}, -- saldo - IN double
                  {$lancamento['saldo_defeitos']}, -- saldo - IN double
                  {$lancamento['tipo']} -- tipo - IN int(1)
                );";
        $conexao = $this->criarConexao();
        return $conexao->exec($sql) ? $this->getLastInsertID($conexao) : 0;
    }

    function editarLancamentoFinanceiro(array $lancamento)
    {
        $sql = "UPDATE movimentacoes_financeiras
              SET
                id_fornecedor = {$lancamento['id_fornecedor']} 
                ,data = '{$lancamento['data']}'
                ,valor_lancamento = {$lancamento['valor_lancamento']}
                ,saldo_anterior = {$lancamento['saldo_anterior']}
                ,saldo = {$lancamento['saldo']}
                ,saldo_defeitos = {$lancamento['saldo_defeitos']}
              WHERE id = {$lancamento['id']};";

        // $sql .= " UPDATE movimentacoes_financeiras set saldo_anterior = saldo_anterior + {$lancamento['valor_lancamento']}, saldo = saldo + {$lancamento['valor_lancamento']} where id_fornecedor = {$lancamento['id_fornecedor']} and id > {$lancamento['id']};";
        return $this->criarConexao()->exec($sql);
    }
    // function buscaMovimentacaoFornecedor(int $id_fornecedor, string $data, string $data_config)
    // {
    //     $retorno = [];
    //     $d = new DateTime($data);
    //     $data = $d->format('Y-m-d');
    //     $d->modify('first day of this month');
    //     $primeiro_dia = $d->format('Y-m-d');
    //     $d->modify('last day of this month');
    //     $ultimo_dia = $d->format('Y-m-d');
    //     //BUSCA TODAS AS MOVIMENTAÇÕES DE ESTOQUE DESDE O PRIMEIRO DIA(01/01/2020) ATE A DATA ATUAL
    //     $sql = "SELECT p.id, me.tipo,me.origem, me.data, sum(mi.quantidade) quantidade from movimentacao_estoque_item mi
    //             inner join movimentacao_estoque me ON me.id = mi.id_mov
    //             inner join produtos p on p.id = mi.id_produto
    //             where p.id_fornecedor = {$id_fornecedor} and me.origem is not null AND me.data BETWEEN '{$data_config}' AND '{$ultimo_dia} 23:59:59'
    //             group by p.id, me.origem, me.data
    //             order by p.id , me.data desc";
    //     $resultado = $this->criarConexao()->query($sql);
    //     $movimentacao =  $resultado->fetchAll(PDO::FETCH_ASSOC);

    //     //BUSCA OS DETALHES DE CADA PRODUTO
    //     $sql = "SELECT p.id,
    //                 p.descricao,
    //                 p.preco_custo,
    //                 (SELECT   AVG(IF(fi.comissao_fornecedor, fi.comissao_fornecedor, p.preco_custo))
    //                 FROM     faturamento_item fi
    //                 WHERE    fi.id_produto = p.id AND fi.data_hora 
    //                 GROUP BY id_produto)
    //                 preco_medio,
    //                 (SELECT   count(id_produto)
    //                 FROM     faturamento_item fi
    //                 WHERE    fi.id_produto = p.id AND fi.data_hora BETWEEN '{$data_config}  00:00:00' AND '{$ultimo_dia} 23:59:59'
    //                 GROUP BY id_produto)
    //                 total_vendidos,
    //                 (SELECT   count(id_produto)
    //                 FROM     faturamento_item fi
    //                 WHERE    fi.id_produto = p.id AND Month(fi.data_hora) = Month('{$data}')
    //                 GROUP BY id_produto)
    //                 vendidos,
    //                 (SELECT   sum(IF(fi.comissao_fornecedor, fi.comissao_fornecedor, p.preco_custo))
    //                 FROM     faturamento_item fi
    //                 WHERE    fi.id_produto = p.id AND Month(fi.data_hora) = Month('{$data}')
    //                 GROUP BY fi.id_produto)
    //                 total_a_pagar,
    //                 (SELECT sum(estoque)
    //                 FROM   estoque_grade
    //                 WHERE  id_produto = p.id AND id_responsavel = 1)
    //                 estoque,
    //                 (SELECT count(id_produto)
    //                 FROM   pedido_item
    //                 WHERE  id_produto = p.id AND situacao = 6 AND Month(data_hora) = Month('{$data}'))
    //                 separados,
    //                 (SELECT   count(id_produto)
    //                 FROM     devolucao_item di
    //                 WHERE    di.id_produto = p.id AND di.defeito = 0 AND data_hora BETWEEN '{$data_config}  00:00:00' AND '{$ultimo_dia} 23:59:59'
    //                 GROUP BY id_produto)
    //                 qtd_total_devolvidos,
    //                 (SELECT   count(id_produto)
    //                 FROM     devolucao_item di
    //                 WHERE    di.id_produto = p.id AND di.defeito = 0 AND Month(data_hora) = Month('{$data}')
    //                 GROUP BY id_produto)
    //                 qtd_devolvidos,
    //                 (SELECT   sum(preco)
    //                 FROM     devolucao_item di
    //                 WHERE    di.id_produto = p.id AND di.defeito = 0 AND Month(data_hora) = Month('{$data}')
    //                 GROUP BY id_produto)
    //                 valor_devolucoes,
    //                 (SELECT   count(id_produto)
    //                 FROM     troca_pendente_item ti
    //                 WHERE    ti.id_produto = p.id AND data_hora BETWEEN '{$data_config}  00:00:00' AND '{$ultimo_dia} 23:59:59'
    //                 GROUP BY id_produto)
    //                 qtd_total_trocados,
    //                 (SELECT   count(id_produto)
    //                 FROM     troca_pendente_item ti
    //                 WHERE    ti.id_produto = p.id AND Month(data_hora) = Month('{$data}')
    //                 GROUP BY id_produto)
    //                 qtd_trocados,
    //                 (SELECT   sum(preco)
    //                 FROM     troca_pendente_item ti
    //                 WHERE    ti.id_produto = p.id AND Month(data_hora) = Month('{$data}')
    //                 GROUP BY id_produto)
    //                 valor_troca,
    //                 (SELECT caminho
    //                 FROM   produtos_foto
    //                 WHERE  id = p.id AND foto_calcada = 0
    //                 LIMIT  1)
    //                 caminho
    //         FROM   PRODUTOS p
    //         WHERE    p.id_fornecedor = {$id_fornecedor} AND p.consignado = 1";

    //     $sql .= " GROUP BY p.id";
    //     $resultado = $this->criarConexao()->query($sql);
    //     $produtos =  $resultado->fetchAll(PDO::FETCH_ASSOC);

    //     if ($movimentacao && $produtos) {
    //         foreach ($produtos as $prod) {
    //             $prod['total_estoque'] = $prod['estoque'] + $prod['separados'];
    //             $prod['qtd_vendidos'] = $prod['vendidos'] - ($prod['qtd_devolvidos'] + $prod['qtd_trocados']);
    //             $prod['qtd_total_vendidos'] = $prod['total_vendidos'] - ($prod['qtd_total_devolvidos'] + $prod['qtd_total_trocados']);
    //             $prod['valor_a_pagar'] = floatval($prod['total_a_pagar'] - ($prod['valor_devolucoes'] + $prod['valor_troca']));
    //             foreach ($movimentacao as $mov) {
    //                 if ($prod['id'] == $mov['id']) {
    //                     $data_mov = new DateTime($mov['data']);
    //                     $mes =  $data_mov->format('m');
    //                     $dia =  $data_mov->format('d/m/Y');
    //                     $prod['movimentacoes'][$mes]['detalhes'][$dia][$mov['origem']] = $prod['movimentacoes'][$mes]['detalhes'][$dia][$mov['origem']] + $mov['quantidade'];
    //                     $prod['movimentacoes'][$mes][$mov['tipo']] = $prod['movimentacoes'][$mes][$mov['tipo']] + $mov['quantidade'];
    //                 }
    //             }
    //             if ($prod['movimentacoes']) {
    //                 ksort($prod['movimentacoes']);
    //                 foreach ($prod['movimentacoes'] as $key => $mov) {
    //                     ksort($prod['movimentacoes'][$key]['detalhes']);
    //                 }
    //             }

    //             $retorno[$prod['id']] = $prod;
    //         }
    //     }

    //     return $retorno;
    // }

    function buscaSaldoDevedorFornecedor(int $id_fornecedor, string $data)
    {
        $sql = "SELECT sum(IF(fi.comissao_fornecedor,fi.comissao_fornecedor, p.preco_custo)) total
                FROM   produtos p INNER JOIN faturamento_item fi ON fi.id_produto = p.id
                WHERE  p.consignado = 1 AND p.id_fornecedor = {$id_fornecedor} AND fi.data_hora > '{$data}';";
        $stmt = $this->criarConexao()->query($sql);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return (-$resultado[0]['total']);
    }

    function buscaSaldoDevedorFornecedorMensal(int $id_fornecedor, string $data)
    { //somar os devolvidos e trocados e abater no total

        $sql = "SELECT
        p.id,
        (SELECT sum( IF( fi.comissao_fornecedor, fi.comissao_fornecedor, p.preco_custo))
            FROM faturamento_item fi
            WHERE fi.id_produto = p.id AND fi.data_hora AND  Month(fi.data_hora) = '{$data}'
        ) valor_vendas,
        (SELECT sum(preco) 
            FROM devolucao_item di
            WHERE di.id_produto = p.id AND di.defeito = 0 AND Month(di.data_hora) = '{$data}'
            GROUP BY id_produto
        ) valor_devolucoes,
        (SELECT sum(preco) 
            FROM troca_pendente_item ti
            WHERE ti.id_produto = p.id AND Month(ti.data_hora) = '{$data}'
            GROUP BY id_produto 
        ) valor_troca
      FROM
        produtos p
      WHERE
        p.consignado = 1 AND
        p.id_fornecedor = {$id_fornecedor};";
        $stmt = $this->criarConexao()->query($sql);
        if ($resultado = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            $total = 0;
            foreach ($resultado as $key => $value) {
                $total += $value['valor_vendas'] - ($value['valor_devolucoes'] + $value['valor_troca']);
            }
        }
        return (-$total);
    }

    function buscaLancamento(int $id_fornecedor, string $data = '', int $tipo = 0, string $order_by = 'DESC')
    {
        $sql = "SELECT * FROM movimentacoes_financeiras WHERE id_fornecedor = {$id_fornecedor}";
        $tipo ? $sql .= " AND tipo = $tipo" : '';
        if ($data) {
            $d = new DateTime($data);
            $data = $d->format('Y-m-d');
            $sql .= " AND MONTH(data) = MONTH('{$data}')";
        }
        $sql .= " ORDER BY id $order_by LIMIT 1";
        $stmt = $this->criarConexao()->query($sql);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado[0];
    }

    function buscaSaldoMesAnterior(int $id_fornecedor, string $data)
    {
        $sql = "SELECT saldo FROM movimentacoes_financeiras WHERE id_fornecedor = {$id_fornecedor} AND MONTH(data) = $data-1 order by id DESC limit 1";
        $stmt = $this->criarConexao()->query($sql);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado[0]['saldo'];
    }

    function atualizaSaldos(int $id_fornecedor)
    {
        $sql = " SELECT *
        FROM   movimentacoes_financeiras
        WHERE  id_fornecedor = $id_fornecedor order by data;";
        $stmt = $this->criarConexao()->query($sql);
        if ($resultado = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            $conexao = $this->criarConexao();
            foreach ($resultado as $key => $value) {
                $saldo = $resultado[$key]['saldo'];
                if ($resultado[$key + 1]) {
                    $sql = "UPDATE movimentacoes_financeiras SET saldo_anterior = $saldo, saldo =  valor_lancamento + $saldo where id = {$resultado[$key + 1]['id']}";
                    $conexao->exec($sql);
                    $resultado[$key + 1]['saldo_anterior'] = $saldo;
                    $resultado[$key + 1]['saldo'] = $resultado[$key + 1]['valor_lancamento'] + $saldo;
                }
            }
        }
    }

    function buscaResumoSaldos(int $id_fornecedor, string $data)
    {
        $retorno = [];
        $d = new DateTime($data);
        $data = $d->format('Y-m-d');
        $sql = "SELECT saldo_anterior from movimentacoes_financeiras where id =(SELECT min(id)
        FROM   movimentacoes_financeiras
        WHERE  id_fornecedor = $id_fornecedor AND MONTH(data) = MONTH('{$data}'));";
        $stmt = $this->criarConexao()->query($sql);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $retorno['saldo_anterior'] = $resultado[0]['saldo_anterior'];

        $sql = "SELECT saldo from movimentacoes_financeiras where id =(SELECT max(id)
        FROM   movimentacoes_financeiras
        WHERE  id_fornecedor = $id_fornecedor AND MONTH(data) = MONTH('{$data}'));";
        $stmt = $this->criarConexao()->query($sql);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $retorno['saldo'] = $resultado[0]['saldo'];

        return $retorno;
    }

    function buscaExtratoVendas(int $id_produto, string $data)
    {
        $d = new DateTime($data);
        $data = $d->format('Y-m-d');
        $sql = "SELECT   data_hora data, tamanho, IF(comissao_fornecedor, comissao_fornecedor, p.preco_custo) preco
                FROM     faturamento_item INNER JOIN produtos p ON p.id = faturamento_item.id_produto
                WHERE    id_produto = $id_produto AND Month(data_hora) = MONTH('{$data}') ORDER BY data_hora;";

        $stmt = $this->criarConexao()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function buscaUltimoRegistroTodosFornecedores()
    {
        $sql = "SELECT  c.razao_social fornecedor, m1.saldo
                FROM movimentacoes_financeiras m1 LEFT JOIN movimentacoes_financeiras m2
                ON (m1.id_fornecedor = m2.id_fornecedor AND m1.id < m2.id)
                left join colaboradores c on c.id = m1.id_fornecedor
                WHERE m2.id IS NULL order by c.razao_social;";
        $stmt = $this->criarConexao()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
*/
<?php

namespace MobileStock\service;

use Exception;
use PDO;
use MobileStock\service\ConfiguracaoService;
/**
 * @deprecated
 */
class CreditosDebitosService
{
    //    public function abaterCreditosNoSplit(PDO $conexao, int $tipoPagamento, array $produtosMarket, int $idUsuario, int $idPedido=0)
    //    {
    //        //abato creditos somente em pagamentos da zoop
    //        if ($tipoPagamento==1 || $tipoPagamento==3) {
    //            $valorParaSplit = $this->somaValorCustoProdutos($produtosMarket);
    //
    //            //calcula debitos e creditos dos fornecedores
    //            $lancService = new LancamentoService();
    //            foreach ($produtosMarket as $key => $p) {
    //                //baixar lancamentos de credito
    //                $produtosMarket[$key]['saldo'] = $p['custo'] + $credito;
    //                $lancService->baixarLancamentosColaborador($conexao, 'CF', $p['id_fornecedor'], 'P', $idUsuario);
    //            }
    //
    //            //ordenar por maior saldo
    //            foreach ($produtosMarket as $key => $row) {
    //                $saldo[$key]  = $row['saldo'];
    //            }
    //
    //            array_multisort($saldo, SORT_DESC, $produtosMarket);
    //
    //            //pagar quem tem maior saldo com saldo do split
    //            //se existir credito do cliente e existe debito do fornecedor, abater esse credito e debito e lancar o restante
    //            foreach ($produtosMarket as $key => $p):
    //            if ($valorParaSplit >= $p['saldo']) {
    //                $produtosMarket[$key]['custo'] = $p['saldo'];
    //            } else {
    //                if ($valorParaSplit > 0) {
    //                    $produtosMarket[$key]['custo'] = $valorParaSplit;
    //                    $saldo = $p['saldo'] - $valorParaSplit;
    //                    $lancService->gerarLancamentoPorColaborador($conexao, 'CF', $p['id_fornecedor'], $idUsuario, $saldo, 'P', 1, $p, $idPedido);
    //                    $valorParaSplit = 0;
    //                } else {
    //                    $valorParaSplit = $valorParaSplit *- 1;
    //                    $lancService->gerarLancamentoPorColaborador($conexao, 'CF', $p['id_fornecedor'], $idUsuario, $valorParaSplit, 'P', 1, $p, $idPedido);
    //                    unset($produtosMarket[$key]);
    //                }
    //            }
    //            endforeach;
    //        }
    //        return $produtosMarket;
    //    }

    //    public function abateDebitosFornecedor(PDO $conexao, float $preco, int $idUsuario)
    //    {
    //        $query = "SELECT SUM(lf.valor) valor, lf.id_colaborador FROM lancamento_financeiro lf WHERE lf.origem='DF' AND lf.situacao=1 AND tipo='R' GROUP BY lf.id_colaborador ORDER BY valor DESC;";
    //        $resultado = $conexao->query($query);
    //        $debitos = $resultado->fetchAll(PDO::FETCH_ASSOC);
    //        $lancService = new LancamentoService();
    //
    //        $dataVenc = DATE('Y-m-d H:i:s');
    //        foreach ($debitos as $key => $d) {
    //            if($preco<=0)break;
    //            $lancService->baixarLancamentosColaborador($conexao, 'DF', $d['id_colaborador'], 'R', $idUsuario);
    //            if($d['valor']<=$preco){
    //                $preco -= $d['valor'];
    //            }else{
    //                $valor = $d['valor'] - $preco;
    //                $lanc = new Lancamento('R', 1, 'DF', $d['id_colaborador'], $dataVenc, $valor, $idUsuario, 13);
    //                $lancamento = $lanc->recuperaLancamento();
    //                $lancService->insereLancamentoFinanceiro($conexao, $lancamento);
    //            }
    //        }
    //    }

//    public function somaValorCustoProdutos(array $produtos)
//    {
//        $custo = 0;
//        foreach ($produtos as $key => $p) {
//            $custo += $p['custo'];
//        }
//        return $custo;
//    }

//    public function buscaProdutoTroca(PDO $conexao, int $lancamento)
//    {
//        $sql = "SELECT
//            produtos.id id_produto,
//            produtos.descricao,
//            faturamento_item.preco,
//            DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//            faturamento_item.comissao_fornecedor,
//            faturamento_item.nome_tamanho tamanho,
//            COALESCE((faturamento_item.lote),0) lote,
//            CASE WHEN lancamento_financeiro.numero_documento <> '0'
//                THEN (SELECT troca_pendente_item.descricao_defeito FROM troca_pendente_item WHERE troca_pendente_item.uuid = lancamento_financeiro.numero_documento)
//                ELSE 0
//            END observacao,
//            faturamento_item.situacao,
//            (SELECT nome FROM usuarios WHERE usuarios.id = lancamento_financeiro.id_usuario)usuario,
//            (SELECT nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador)separador,
//            (SELECT razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador)razao_social
//                FROM lancamento_financeiro
//                INNER JOIN faturamento_item ON(faturamento_item.id_faturamento = lancamento_financeiro.pedido_origem)
//                INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id)
//                    WHERE lancamento_financeiro.id = {$lancamento} AND CASE WHEN lancamento_financeiro.numero_documento <> '0'
//                        THEN lancamento_financeiro.numero_documento = faturamento_item.uuid
//                        ELSE  faturamento_item.situacao = 19
//                        END";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaProdutoTrocaTransacao(PDO $conexao, int $lancamento)
//    {
//        $sql = "SELECT produtos.descricao,transacao_financeiras_produtos_itens.preco,
//                    DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//                    transacao_financeiras_produtos_itens.comissao_fornecedor,transacao_financeiras_produtos_itens.nome_tamanho tamanho,
//                    '0' AS lote,
//                    'Venda do produto cancelada' as observacao,
//                    transacao_financeiras_produtos_itens.situacao,
//                    '-' as usuario,
//                    '-' as separador,
//                    (SELECT razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador)razao_social
//                        FROM lancamento_financeiro
//                        INNER JOIN transacao_financeiras_produtos_itens ON(transacao_financeiras_produtos_itens.id_transacao = lancamento_financeiro.transacao_origem)
//                        INNER JOIN produtos ON(transacao_financeiras_produtos_itens.id_produto = produtos.id)
//                            WHERE lancamento_financeiro.id = {$lancamento} AND CASE WHEN lancamento_financeiro.numero_documento <> '0'
//                                THEN lancamento_financeiro.numero_documento = transacao_financeiras_produtos_itens.uuid
//                                ELSE  transacao_financeiras_produtos_itens.situacao = 'CA'
//                                END";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaProdutoTrocaCliente(PDO $conexao, int $lancamento)
//    {
//        $sql = "SELECT produtos.descricao,faturamento_item.preco,
//                    DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//                    faturamento_item.comissao_fornecedor,faturamento_item.nome_tamanho tamanho,
//                    (SELECT nome FROM usuarios WHERE usuarios.id = lancamento_financeiro.id_usuario)usuario,
//                    (SELECT nome FROM usuarios WHERE usuarios.id = faturamento_item.id_separador)separador,
//                    (SELECT razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor)fornecedor
//                        FROM lancamento_financeiro
//                        INNER JOIN faturamento_item ON(faturamento_item.id_faturamento = lancamento_financeiro.pedido_origem)
//                        INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id)
//                            WHERE lancamento_financeiro.id = {$lancamento}
//                                AND CASE WHEN lancamento_financeiro.numero_documento <> '0'
//                                        THEN lancamento_financeiro.numero_documento = faturamento_item.uuid
//                                        ELSE
//                                            CASE WHEN lancamento_financeiro.origem = 'CP'
//                                                THEN faturamento_item.situacao = 19
//                                                ELSE 1
//                                        END
//                                END";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaProdutosExtrato(PDO $conexao, int $pedido, int $id_colaborador)
//    {
//        $sql = "SELECT produtos.descricao,
//                    faturamento_item.preco,
//                    COALESCE((faturamento_item.lote),0) lote,
//                    faturamento_item.comissao_fornecedor,
//                    faturamento_item.nome_tamanho tamanho,
//                    (SELECT nome FROM situacao WHERE situacao.id=faturamento_item.situacao)nome_situacao,
//                    faturamento_item.situacao
//                        FROM faturamento_item
//                            INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id)
//                                WHERE faturamento_item.id_faturamento = {$pedido} AND produtos.id_fornecedor = {$id_colaborador};";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//
//    public function buscaProdutosExtratoTransacao(PDO $conexao, int $transacao_origem, int $id_colaborador)
//    {
//        $sql = "SELECT
//                       produtos.descricao,
//                       transacao_financeiras_produtos_itens.id_produto,
//                       transacao_financeiras_produtos_itens.preco,
//                       transacao_financeiras_produtos_itens.comissao_fornecedor,
//                       transacao_financeiras_produtos_itens.nome_tamanho,
//                       (SELECT nome FROM situacao WHERE situacao.id=transacao_financeiras_produtos_itens.situacao)nome_situacao,
//                       transacao_financeiras_produtos_itens.situacao
//                        FROM transacao_financeiras_produtos_itens
//                            INNER JOIN produtos ON(transacao_financeiras_produtos_itens.id_produto = produtos.id)
//                                WHERE transacao_financeiras_produtos_itens.id_transacao = {$transacao_origem} AND produtos.id_fornecedor = {$id_colaborador};";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaProdutosExtratoMobile(PDO $conexao, int $pedido)
//    {
//        $sql = "SELECT produtos.descricao,
//                    faturamento_item.preco,
//                    faturamento_item.comissao_fornecedor,
//                    faturamento_item.nome_tamanho tamanho,
//                    (SELECT nome FROM situacao WHERE situacao.id=faturamento_item.situacao)nome_situacao,
//                    faturamento_item.situacao
//                        FROM faturamento_item
//                            INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id)
//                                WHERE faturamento_item.id_faturamento = {$pedido};";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }


//    public function buscaProdutosExtratoCliente(PDO $conexao, int $pedido, int $id_colaborador)
//    {
//        $sql = "SELECT produtos.descricao,
//                    (SELECT razao_social FROM colaboradores WHERE colaboradores.id = produtos.id_fornecedor ) fornecedor,
//                    faturamento_item.preco,
//                    faturamento_item.comissao_fornecedor,
//                    faturamento_item.nome_tamanho tamanho,
//                    (SELECT nome FROM situacao WHERE situacao.id=faturamento_item.situacao)nome_situacao,
//                    faturamento_item.situacao
//                        FROM faturamento_item
//                            INNER JOIN produtos ON(faturamento_item.id_produto = produtos.id)
//                                WHERE faturamento_item.id_faturamento = {$pedido};";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaProdutosExtratoClienteTransacao(PDO $conexao,
//        int $transacao
//    ) {
//        $sql = "SELECT  produtos.descricao,
//                        (
//                            SELECT razao_social
//                                FROM colaboradores
//                                    WHERE colaboradores.id = produtos.id_fornecedor
//                        ) fornecedor,
//                        transacao_financeiras_produtos_itens.preco,
//                        transacao_financeiras_produtos_itens.comissao_fornecedor,
//                        transacao_financeiras_produtos_itens.nome_tamanho tamanho,
//                        (
//                            CASE WHEN transacao_financeiras_produtos_itens.situacao = 'CA'
//                                THEN 'CANCELADO'
//                                ELSE 'COMPRADO'
//                            END
//                        )nome_situacao,
//                    transacao_financeiras_produtos_itens.situacao
//                        FROM transacao_financeiras_produtos_itens
//                            INNER JOIN produtos ON(transacao_financeiras_produtos_itens.id_produto = produtos.id)
//                                WHERE transacao_financeiras_produtos_itens.id_transacao = {$transacao};";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaFaturamento(PDO $conexao, int $pedido)
//    {
//        $sql = "SELECT id, (SELECT razao_social FROM colaboradores WHERE colaboradores.id = id_cliente)razao_social FROM faturamento WHERE faturamento.id={$pedido}";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//    public function buscaTransacao(PDO $conexao, int $transacao)
//    {
//        $sql = "SELECT  transacao_financeiras.id,
//                        (
//                            SELECT razao_social
//                                FROM colaboradores
//                                    WHERE colaboradores.id = transacao_financeiras.pagador
//                        )razao_social, transacao_financeiras.emissor_transacao
//                            FROM transacao_financeiras WHERE transacao_financeiras.id={$transacao}";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//    public function buscaDetalhes(PDO $conexao, int $pedido, int $id_colaborador)
//    {
//        $sql = "SELECT *,
//                (
//                    SELECT razao_social
//                        FROM colaboradores
//                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                )razao_social,
//                (
//                    SELECT nome
//                        FROM usuarios
//                            WHERE usuarios.id = lancamento_financeiro.id_usuario
//                )usuario,
//                (
//                    SELECT nome
//                        FROM documentos
//                            WHERE documentos.id=lancamento_financeiro.documento
//                )nome_documento
//                    FROM lancamento_financeiro
//                        WHERE
//                          pedido_origem = {$pedido}
//                            AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    ORDER BY lancamento_financeiro.id DESC";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaLancamento(PDO $conexao, int $lancamento, int $id_colaborador)
//    {
//        $sql = "SELECT *,
//                (
//                    SELECT razao_social
//                        FROM colaboradores
//                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                )razao_social,
//                (
//                    SELECT nome
//                        FROM usuarios
//                            WHERE usuarios.id = lancamento_financeiro.id_usuario
//                )usuario,
//                (
//                    SELECT nome
//                        FROM documentos
//                            WHERE documentos.id=lancamento_financeiro.documento
//                )nome_documento
//                    FROM lancamento_financeiro
//                        WHERE
//                          lancamento_financeiro.id = {$lancamento}
//                            AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    ORDER BY lancamento_financeiro.id DESC";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//    public function buscaDetalhesTransacao(PDO $conexao, int $transacao, int $id_colaborador)
//    {
//        $sql = "SELECT *,
//                (
//                    SELECT razao_social
//                        FROM colaboradores
//                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                )razao_social,
//                (
//                    SELECT nome
//                        FROM usuarios
//                            WHERE usuarios.id = lancamento_financeiro.id_usuario
//                )usuario,
//                (
//                    SELECT nome
//                        FROM documentos
//                            WHERE documentos.id=lancamento_financeiro.documento
//                )nome_documento
//                    FROM lancamento_financeiro
//                        WHERE
//                          transacao_origem = {$transacao}
//                            AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    ORDER BY lancamento_financeiro.id DESC";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaDetalheCM(PDO $conexao, int $lancamento)
//    {
//        $sql = "
//                SELECT  lancamento_financeiro.id,
//                        lancamento_financeiro.pedido_origem,
//                        lancamento_financeiro.situacao,
//                        lancamento_financeiro.valor_pago as valor,
//                        lancamento_financeiro.origem,
//                        lancamento_financeiro.observacao,
//                        (
//                            SELECT razao_social
//                                FROM colaboradores
//                                    WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                        )razao_social,
//                        (
//                            SELECT nome
//                                FROM usuarios
//                                    WHERE usuarios.id = lancamento_financeiro.id_usuario
//                        )usuario,
//                        (
//                            SELECT nome
//                                FROM documentos
//                                    WHERE documentos.id=lancamento_financeiro.documento
//                        )nome_documento
//
//                            FROM lancamento_financeiro
//                                WHERE lancamento_financeiro.id_lancamento_pag IN (
//                                                                        SELECT LF.id
//                                                                            FROM lancamento_financeiro LF
//                                                                                WHERE LF.lancamento_origem = {$lancamento}
//                                                                        )
//                                    OR lancamento_financeiro.id_lancamento_pag = {$lancamento}
//                                    OR lancamento_financeiro.id = (
//                                                                    SELECT LR.id_lancamento_pag
//                                                                        FROM lancamento_financeiro LR
//                                                                            WHERE LR.id = {$lancamento}
//                                                                    )";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//    public function buscaDetalhesFornecedor(PDO $conexao, int $pedido, int $id_colaborador)
//    {
//        $sql = "SELECT *,
//                (
//                    SELECT razao_social
//                        FROM colaboradores
//                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                )razao_social,
//                (
//                    SELECT nome
//                        FROM usuarios
//                            WHERE usuarios.id = lancamento_financeiro.id_usuario
//                )usuario,
//                (
//                    SELECT nome
//                        FROM documentos
//                            WHERE documentos.id=lancamento_financeiro.documento
//                )nome_documento
//                    FROM lancamento_financeiro
//                        WHERE
//                           pedido_origem = {$pedido}
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    ORDER BY lancamento_financeiro.id DESC";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscaDetalhesMobile(PDO $conexao, int $pedido, int $id_colaborador)
//    {
//        $sql = "SELECT *,
//                (
//                    SELECT razao_social 
//                        FROM colaboradores 
//                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                )razao_social,
//                (
//                    SELECT nome 
//                        FROM usuarios 
//                            WHERE usuarios.id = lancamento_financeiro.id_usuario
//                )usuario,
//                (
//                    SELECT nome 
//                        FROM documentos 
//                            WHERE documentos.id=lancamento_financeiro.documento
//                )nome_documento
//                    FROM lancamento_financeiro 
//                        WHERE 
//                            pedido_origem = {$pedido} AND
//                            CASE WHEN (lancamento_financeiro.id_recebedor = {$id_colaborador} or lancamento_financeiro.id_pagador  = {$id_colaborador})
//                                THEN 1 
//                                ELSE 0 
//                            END 
//                    ORDER BY lancamento_financeiro.id DESC";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
    
//public function buscaDetalhesMobileTransacao(PDO $conexao, int $transacao, int $id_colaborador)
//    {
//        $sql = "SELECT *,
//                (
//                    SELECT razao_social 
//                        FROM colaboradores 
//                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                )razao_social,
//                (
//                    SELECT nome 
//                        FROM usuarios 
//                            WHERE usuarios.id = lancamento_financeiro.id_usuario
//                )usuario,
//                (
//                    SELECT nome 
//                        FROM documentos 
//                            WHERE documentos.id=lancamento_financeiro.documento
//                )nome_documento
//                    FROM lancamento_financeiro 
//                        WHERE 
//                            lancamento_financeiro.transacao_origem = {$transacao} AND
//                            CASE WHEN (lancamento_financeiro.id_recebedor = {$id_colaborador} or lancamento_financeiro.id_pagador  = {$id_colaborador})
//                                THEN 1 
//                                ELSE 0 
//                            END 
//                    ORDER BY lancamento_financeiro.id DESC";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function totalFornecedor(PDO $conexao, int $id_colaborador, string $data)
//    {
//        if ($data == '') {
//            $data = date('Y-m');
//        }
//        $sql = "SELECT  SUM(CASE WHEN tipo = 'R'  THEN valor ELSE 0 END )receber,
//                        SUM(CASE WHEN tipo = 'P THEN valor ELSE 0 END)pagar
//                            FROM lancamento_financeiro
//                                WHERE  lancamento_financeiro.id_colaborador = {$id_colaborador}
//                            AND lancamento_financeiro.data_emissao LIKE '{$data}%' AND lancamento_financeiro.situacao = 1";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $pagar = $resultado[0]['pagar'];
//        $receber = $resultado[0]['receber'];
//        $total =   floatVal($receber) - floatVal($pagar);
//        return $total;
//    }

    // public function totalMobileStock(PDO $conexao, string $data, string $data_ate)
    // {
    //     if ($data == '' && $data_ate == '') {
    //         $data = "DATE(NOW())";
    //         $data_ate = "DATE(NOW())";
    //     }
    //     $sql = "SELECT 
    //                     'periodo' periodo,
    //                             COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo   
    //                             FROM lancamento_financeiro
    //                             WHERE lancamento_financeiro.situacao = 1
    //                                 AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
    //                                 AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}}'
    //                             UNION ALL
    //                             SELECT 'anterior' periodo,
    //                             COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo   
    //                             FROM lancamento_financeiro
    //                             WHERE lancamento_financeiro.situacao = 1
    //                                 AND DATE(lancamento_financeiro.data_emissao) < '{$data}'
    //                             UNION ALL
    //                             SELECT  'posterior' periodo,
    //                             COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo   
    //                             FROM lancamento_financeiro
    //                             WHERE lancamento_financeiro.situacao = 1
    //                                 AND DATE(lancamento_financeiro.data_emissao) > '{$data_ate}' ";


    //     $stm = $conexao->prepare($sql);
    //     $stm->execute();
    //     $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);

    //     return $resultado;
    // }


//    public function saldoAnteriorMobileStock(PDO $conexao, int $id_colaborador, string $data)
//    {
//        if ($data != '') {
//            $sql = "SELECT  SUM(CASE WHEN tipo = 'R' THEN valor ELSE 0 END )pagar,
//                        SUM(CASE WHEN tipo = 'P' THEN valor ELSE 0 END)receber
//                            FROM lancamento_financeiro  WHERE
//                                    CASE WHEN (lancamento_financeiro.id_recebedor = {$id_colaborador} or lancamento_financeiro.id_pagador  = {$id_colaborador})
//                                        THEN 1
//                                        ELSE 0
//                                    END AND DATE(lancamento_financeiro.data_emissao) < '{$data}'  ";
//        }
//        $sql = "SELECT  SUM(CASE WHEN tipo = 'R' THEN valor ELSE 0 END )pagar,
//                        SUM(CASE WHEN tipo = 'P' THEN valor ELSE 0 END)receber
//                            FROM lancamento_financeiro  WHERE
//                                    CASE WHEN (lancamento_financeiro.id_recebedor = {$id_colaborador} or lancamento_financeiro.id_pagador  = {$id_colaborador})
//                                        THEN 1
//                                        ELSE 0
//                                    END AND MONTH(lancamento_financeiro.data_emissao) = MONTH(NOW())
//                                    AND YEAR(lancamento_financeiro.data_emissao) = YEAR(NOW())  ";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $pagar = $resultado[0]['pagar'];
//        $receber = $resultado[0]['receber'];
//        $saldoAnterior =  floatVal($pagar) - floatVal($receber);
//        return $saldoAnterior;
//    }


//    public function total(PDO $conexao, int $id_colaborador, string $data, string $data_fim)
//    {
//
//        $sql = "SELECT  SUM(CASE WHEN tipo = 'R' THEN valor ELSE 0 END )receber,
//                        SUM(CASE WHEN tipo = 'P' THEN valor ELSE 0 END)pagar
//                            FROM lancamento_financeiro  WHERE lancamento_financeiro.id_colaborador = {$id_colaborador}
//                          AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                          AND DATE(lancamento_financeiro.data_emissao) <= '{$data_fim}
//                          AND lancamento_financeiro.situacao = 1'
//                          ";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $pagar = $resultado[0]['pagar'];
//        $receber = $resultado[0]['receber'];
//        $total =  floatVal($pagar) - floatVal($receber);
//        return $total;
//    }


//    public function saldoAnterior(PDO $conexao, int $id_colaborador, string $data)
//    {
//        if ($data == '') {
//            $data = date('Y-m-d');
//        }
//        $sql = "SELECT  SUM(CASE WHEN tipo = 'R' THEN valor ELSE 0 END )receber,
//                        SUM(CASE WHEN tipo = 'P' THEN valor ELSE 0 END)pagar
//                            FROM lancamento_financeiro WHERE lancamento_financeiro.id_colaborador = {$id_colaborador}
//                          AND lancamento_financeiro.data_emissao < '{$data}%'";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $pagar = $resultado[0]['pagar'];
//        $receber = $resultado[0]['receber'];
//        $saldoAnterior =  floatVal($pagar) - floatVal($receber);
//        return $saldoAnterior;
//    }

    public static function saldoCliente(PDO $conexao, int $id_colaborador, string $data, string $data_ate): array
    {
        if ($data == '' && $data_ate == '') {
            $data = "DATE(NOW())";
            $data_ate = "DATE(NOW())";
        }
        $sql = "SELECT 
                        'periodo' periodo,
                                COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo   
                                FROM lancamento_financeiro
                                WHERE lancamento_financeiro.situacao = 1
                                    AND lancamento_financeiro.id_colaborador = {$id_colaborador} 
                                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
                                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
                                UNION ALL
                                SELECT 
                                'anterior' periodo,
                                COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo   
                                FROM lancamento_financeiro
                                WHERE lancamento_financeiro.situacao = 1
                                    AND lancamento_financeiro.id_colaborador = {$id_colaborador} 
                                    AND DATE(lancamento_financeiro.data_emissao) < '{$data}'
                                UNION ALL
                                SELECT 
                                'posterior' periodo,
                                COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo   
                                FROM lancamento_financeiro
                                WHERE lancamento_financeiro.situacao = 1
                                    AND lancamento_financeiro.id_colaborador = {$id_colaborador} 
                                    AND DATE(lancamento_financeiro.data_emissao) > '{$data_ate}' ";


        $stm = $conexao->prepare($sql);
        $stm->execute();
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }


    // public static function totalCliente(PDO $conexo, int $id_cliente, string $data_ini, string $data_fim)
    // {

    //     $consulta = $conexo->prepare("SELECT 
    //                                         'periodo' periodo,
    //                                         COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo	 
    //                                     FROM lancamento_financeiro
    //                                         WHERE lancamento_financeiro.situacao = 1
    //                                             AND lancamento_financeiro.id_colaborador = :id_cliente
    //                                             AND DATE(lancamento_financeiro.data_emissao) >= ':data_ini'
    //                                             AND DATE(lancamento_financeiro.data_emissao) <= 'data_fim'
    //                                     UNION ALL
    //                                     SELECT 
    //                                         'total' periodo,
    //                                         COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',-lancamento_financeiro.valor,lancamento_financeiro.valor)),0) saldo	 
    //                                     FROM lancamento_financeiro
    //                                         WHERE lancamento_financeiro.situacao = 1
    //                                             AND lancamento_financeiro.id_colaborador = :id_cliente");
    //     $consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    //     $consulta->bindParam(':data_ini', $data_ini, PDO::PARAM_STR);
    //     $consulta->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
    //     $consulta->execute();
    //     $valores = $consulta->fetch(PDO::FETCH_ASSOC);
    //     return $valores;
    // }




//    public function totalCliente(PDO $conexao, int $id_colaborador, string $data, string $data_ate)
//    {
//
//        $sql = "SELECT  SUM(CASE WHEN tipo = 'R'  THEN valor ELSE 0 END )receber,
//                        SUM(CASE WHEN tipo = 'P'  THEN valor ELSE 0 END)pagar
//                            FROM lancamento_financeiro
//                                WHERE
//                                    lancamento_financeiro.id_colaborador = {$id_colaborador}
//                                    AND DATE(lancamento_financeiro.data_emissao) BETWEEN '{$data}' AND '{$data_ate}'
//                                    AND lancamento_financeiro.situacao = 1";
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $pagar = $resultado[0]['pagar'];
//        $receber = $resultado[0]['receber'];
//        $total =  floatVal($pagar) - floatVal($receber);
//        return $total;
//    }



//    public function buscaExtratoFornecedor(PDO $conexao, int $id_colaborador, string $date, int $pagina)
//    {
//
//        if ($id_colaborador != '') { //AND lancamento_financeiro.situacao='1'
//            $sql = "SELECT  colaboradores.id id_colaborador,
//                            COALESCE((lancamento_financeiro.transacao_origem),0),
//                            COALESCE(DATE_FORMAT(MAX(lancamento_financeiro.data_emissao),'%d/%m/%Y'),'Conta não movimentada') AS ultima_modificacao,
//                                colaboradores.razao_social,
//                            COALESCE(SUM(
//                                CASE WHEN lancamento_financeiro.tipo = 'R' AND lancamento_financeiro.situacao = 1
//                                THEN lancamento_financeiro.valor
//                                ELSE 0
//                                END
//                                ),0)receber,
//                            COALESCE(SUM(
//                                CASE WHEN lancamento_financeiro.tipo = 'P' AND lancamento_financeiro.situacao = 1
//                                THEN lancamento_financeiro.valor
//                                ELSE 0
//                                END
//                                ),0) pagar
//                            FROM colaboradores
//                            LEFT JOIN lancamento_financeiro ON lancamento_financeiro.id_colaborador = colaboradores.id
//                                WHERE colaboradores.id = {$id_colaborador}
//                                    AND colaboradores.tipo = 'F'
//                                        GROUP BY colaboradores.id LIMIT 1;";
//            $stm = $conexao->prepare($sql);
//            $stm->execute();
//            $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        } else {
//            if ($pagina == 2) {
//                $sql = "SELECT  colaboradores.id id_colaborador,
//                                COALESCE(DATE_FORMAT(MAX(lancamento_financeiro.data_emissao),'%d/%m/%Y'),'Conta não modificada') AS ultima_modificacao,
//                                    colaboradores.razao_social,
//                                COALESCE(SUM(
//                                    CASE WHEN lancamento_financeiro.tipo = 'R' AND lancamento_financeiro.situacao = 1  THEN lancamento_financeiro.valor ELSE 0 END
//                                    ),0)receber,
//                                COALESCE(SUM(
//                                    CASE WHEN lancamento_financeiro.tipo = 'P' AND lancamento_financeiro.situacao = 1  THEN lancamento_financeiro.valor ELSE 0 END
//                                    ),0)pagar
//                                        FROM colaboradores
//                                            LEFT JOIN lancamento_financeiro ON lancamento_financeiro.id_colaborador = colaboradores.id
//                                                WHERE colaboradores.tipo = 'F'
//                                                    GROUP BY colaboradores.id
//                                                        ORDER BY pagar DESC";
//            } else {
//                $sql = "SELECT  colaboradores.id id_colaborador,
//                                COALESCE(DATE_FORMAT(MAX(lancamento_financeiro.data_emissao),'%d/%m/%Y'),'Conta não modificada') AS ultima_modificacao,
//                                    colaboradores.razao_social,
//                                COALESCE(SUM(
//                                    CASE WHEN lancamento_financeiro.tipo = 'R' AND  lancamento_financeiro.situacao = 1
//                                    THEN valor
//                                    ELSE 0
//                                    END
//                                    ),0)receber,
//                                COALESCE(SUM(
//                                    CASE WHEN lancamento_financeiro.tipo = 'P' AND  lancamento_financeiro.situacao = 1
//                                    THEN lancamento_financeiro.valor
//                                    ELSE 0
//                                    END
//                                    ),0)pagar
//                                        FROM colaboradores
//                                            LEFT JOIN lancamento_financeiro ON lancamento_financeiro.id_colaborador = colaboradores.id
//                                                WHERE colaboradores.tipo = 'F'
//                                                    GROUP BY colaboradores.id
//                                                        ORDER BY lancamento_financeiro.data_emissao DESC LIMIT 20";
//            }
//
//            $stm = $conexao->prepare($sql);
//            $stm->execute();
//            $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        }
//        return $resultado;
//    }



    // public function buscaExtratoCliente(PDO $conexao, int $id_colaborador, string $date, int $pagina)
    // {

    //     if ($id_colaborador != 0) { //AND lancamento_financeiro.situacao='1'
    //         $sql = "SELECT colaboradores.id id_colaborador, 
    //                        COALESCE(DATE_FORMAT(MAX(lancamento_financeiro.data_emissao),'%d/%m/%Y'),'Conta não movimentada') AS ultima_modificacao,
    //                            colaboradores.razao_social, 
    //                        COALESCE(SUM(
    //                            CASE WHEN lancamento_financeiro.tipo = 'R' AND lancamento_financeiro.situacao = 1 THEN valor ELSE 0 END
    //                            ),0) receber, 
    //                        COALESCE(SUM(
    //                             CASE WHEN lancamento_financeiro.tipo = 'P' AND lancamento_financeiro.situacao = 1 THEN valor ELSE 0 END
    //                             ),0) pagar 
    //                                 FROM colaboradores 
    //                                     LEFT JOIN lancamento_financeiro ON lancamento_financeiro.id_colaborador = colaboradores.id
    //                                         WHERE colaboradores.id = {$id_colaborador} AND colaboradores.tipo = 'C'
    //                                             GROUP BY colaboradores.id LIMIT 1;"; // Não tem data o cliente, traz tudo.
    //         $stm = $conexao->prepare($sql);
    //         $stm->execute();
    //         $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
    //     } else {
    //         if ($pagina == 3) {
    //             $sql = "SELECT colaboradores.id id_colaborador, 
    //                     COALESCE(DATE_FORMAT(MAX(lancamento_financeiro.data_emissao),'%d/%m/%Y'),'Conta não modificada') AS ultima_modificacao,
    //                         colaboradores.razao_social, 
    //                     COALESCE(SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'R' AND lancamento_financeiro.situacao = 1  THEN lancamento_financeiro.valor ELSE 0 END
    //                     ),0) receber, 
    //                     COALESCE(SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'P' AND lancamento_financeiro.situacao = 1  THEN lancamento_financeiro.valor ELSE 0 END
    //                     ),0) pagar 
    //                         FROM colaboradores  
    //                             LEFT JOIN lancamento_financeiro ON lancamento_financeiro.id_colaborador = colaboradores.id
    //                                 WHERE colaboradores.tipo = 'C'
    //                                     GROUP BY colaboradores.id 
    //                                         ORDER BY pagar DESC";
    //         } else {
    //             $sql = "SELECT colaboradores.id id_colaborador, 
                
    //                     COALESCE(DATE_FORMAT(MAX(lancamento_financeiro.data_emissao),'%d/%m/%Y'),'Conta não movimentada') AS ultima_modificacao,
    //                         colaboradores.razao_social, 
    //                     COALESCE(SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'R' AND lancamento_financeiro.situacao = 1  THEN lancamento_financeiro.valor ELSE 0 END
    //                     ),0) receber, 
    //                     COALESCE(SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'P' AND lancamento_financeiro.situacao = 1  THEN lancamento_financeiro.valor ELSE 0 END
    //                     ),0)pagar 
    //                         FROM colaboradores
    //                             LEFT JOIN lancamento_financeiro on lancamento_financeiro.id_colaborador = colaboradores.id
    //                                 WHERE colaboradores.tipo = 'C'
    //                                     GROUP BY colaboradores.id 
    //                                         ORDER BY lancamento_financeiro.data_emissao DESC LIMIT 20";
    //         }
    //     }
    //     $stm = $conexao->prepare($sql);
    //     $stm->execute();
    //     $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
    //     return $resultado;
    // }


    // public function buscaExtratoMobile(PDO $conexao, int $id_colaborador, string $data = '', string $data_ate, string $ordenar = '')
    // {
    //     if ($ordenar != '') {
    //         if ($ordenar == 1) {
    //             $ordena = "ORDER BY valor ASC";
    //         } else {
    //             $ordena = "ORDER BY valor DESC";
    //         }
    //     } else {
    //         $ordena = "ORDER BY data_emissao DESC;";
    //     }
    //     if ($data != '' && $data_ate != '') {

    //         $sql = "SELECT lancamento_financeiro.*, 
    //             COALESCE((lancamento_financeiro.transacao_origem),0)transacao_origem,
    //             (
    //                 SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
    //             ) nome, 
    //              (
    //                     SELECT razao_social FROM colaboradores WHERE colaboradores.id =  lancamento_financeiro.id_colaborador
    //             )razao_social, 
    //             DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas
    //                 FROM lancamento_financeiro 
    //                     WHERE 
    //                      lancamento_financeiro.origem <> 'AU' AND
    //                          DATE(lancamento_financeiro.data_emissao) BETWEEN '{$data}' AND '{$data_ate}'  {$ordena}";
    //     } else {
    //         $sql = "SELECT lancamento_financeiro.*, 
    //             (
    //                 SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
    //             ) nome, 
    //             DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas
    //                 FROM lancamento_financeiro 
    //                     WHERE 
    //                      lancamento_financeiro.origem <> 'AU'      
    //                                  {$ordena}";
    //     }
    //     $stm = $conexao->prepare($sql);
    //     $stm->execute();
    //     $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
    //     return $resultado;
    // }


//    public function buscaExtratoAreaFornecedor(PDO $conexao, int $id_colaborador, string $data = '', string $data_ate = '', string $ordenar = '')
//    {
//        if ($ordenar != '') {
//            if ($ordenar == 1) {
//                $ordena = "ORDER BY valor ASC";
//            } else {
//                $ordena = "ORDER BY valor DESC";
//            }
//        } else {
//            $ordena = "ORDER BY data_emissao DESC;";
//        }
//        if ($data != '' && $data_ate != '') {
//            $sql = "SELECT lancamento_financeiro.*,
//                (
//                    SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
//                ) nome,
//                DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//                (
//                    SELECT razao_social FROM colaboradores WHERE colaboradores.id = {$id_colaborador}
//                ) razao_social
//                    FROM lancamento_financeiro
//
//                        WHERE
//                            lancamento_financeiro.id_colaborador = {$id_colaborador} AND
//                            lancamento_financeiro.origem <> 'AU'
//                            AND DATE(lancamento_financeiro.data_emissao) BETWEEN '{$data}' AND '{$data_ate}'  {$ordena}";
//        } else {
//            $sql = "SELECT lancamento_financeiro.*,
//                        (
//                            SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
//                        )nome,
//                        DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//                        (
//                            SELECT razao_social FROM colaboradores WHERE colaboradores.id = {$id_colaborador}
//                        )razao_social
//                        FROM lancamento_financeiro
//
//                                WHERE
//                                    lancamento_financeiro.id_colaborador = {$id_colaborador} AND
//                                    lancamento_financeiro.origem <> 'AU'
//
//                                    {$ordena}";
//        }
//        $stm = $conexao->prepare($sql);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

    // public function MobileStockSaldo(PDO $conexao)
    // {
    //     $sql = "SELECT SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'R' 
    //                             THEN lancamento_financeiro.valor 
    //                             ELSE 0
    //                         END) receber,
    //                         SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'P' 
    //                             THEN lancamento_financeiro.valor 
    //                             ELSE 0
    //                         END) pagar
    //                             FROM lancamento_financeiro WHERE lancamento_financeiro.situacao=1";
    //     $stm = $conexao->prepare($sql);
    //     $stm->execute();
    //     $resultado = $stm->fetch(PDO::FETCH_ASSOC);
    //     $total = $resultado['receber'] - $resultado['pagar'];
    //     return $total;
    // }

//    public function buscaExtratoFornecedorDetalhes(PDO $conexao, int $id_colaborador, string $data = '', string $data_ate = '',  string $ordenar = '')
//    {
//        if ($ordenar != '') {
//            if ($ordenar == 1) {
//                $ordena = "ORDER BY lancamento_financeiro.valor ASC";
//            } else {
//                $ordena = "ORDER BY lancamento_financeiro.valor DESC";
//            }
//        } else {
//            $ordena = "ORDER BY lancamento_financeiro.data_emissao DESC;";
//        }
//        if ($data != '' && $data_ate != '') {
//
//            $sql = "SELECT colaboradores.id id_colaborador,
//                            COALESCE((lancamento_financeiro.id),'')id,
//                            lancamento_financeiro.transacao_origem,
//                            lancamento_financeiro.id_usuario,
//                            lancamento_financeiro.data_emissao,
//                            COALESCE((lancamento_financeiro.id_colaborador),colaboradores.id)id_colaborador,
//                            COALESCE((lancamento_financeiro.origem),'SI')origem,
//                            COALESCE((lancamento_financeiro.valor),'0,00')valor,
//                            lancamento_financeiro.faturamento_criado_pago,
//                            lancamento_financeiro.situacao,
//                            lancamento_financeiro.tipo,
//                            lancamento_financeiro.pedido_origem,
//                            lancamento_financeiro.juros,
//                            CASE WHEN (lancamento_financeiro.origem = 'TF')
//			                    THEN (
//                                        SELECT transacao_financeiras_produtos_itens.situacao
//				                            FROM transacao_financeiras_produtos_itens
//					                            WHERE transacao_financeiras_produtos_itens.uuid = lancamento_financeiro.numero_documento
//                                                AND transacao_financeiras_produtos_itens.situacao = 'CO'
//                                    )
//			                    ELSE 0
//		                    END situacao_produto,
//                            COALESCE((SELECT faturamento_item.situacao FROM faturamento_item WHERE faturamento_item.id_faturamento = lancamento_financeiro.pedido_origem AND lancamento_financeiro.origem='TF' AND faturamento_item.uuid = lancamento_financeiro.numero_documento AND faturamento_item.situacao IN (8,9,11,10,12)),0) devolucao,
//                (
//                    SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
//                ) nome,
//                COALESCE((DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y %H:%i:%s')),'') AS datas,
//                razao_social,
//                lancamento_financeiro.faturamento_criado_pago
//                    FROM colaboradores
//                        LEFT JOIN lancamento_financeiro ON colaboradores.id = lancamento_financeiro.id_colaborador
//                        WHERE
//                            colaboradores.id = :id_colaborador AND  COALESCE(lancamento_financeiro.origem,'') <> 'AU'
//                            AND DATE( COALESCE((lancamento_financeiro.data_emissao),NOW())) BETWEEN :data_n AND :data_ate  {$ordena}";
//        } else {
//            $sql = "SELECT colaboradores.id id_colaborador,
//                            COALESCE((lancamento_financeiro.id),'')id,
//                            COALESCE((lancamento_financeiro.transacao_origem),0)transacao_origem,
//                            lancamento_financeiro.id_usuario,
//                            lancamento_financeiro.data_emissao,
//                            COALESCE((lancamento_financeiro.id_colaborador),colaboradores.id)id_colaborador,
//                            COALESCE((lancamento_financeiro.origem),'SI')origem,
//                            COALESCE((lancamento_financeiro.valor),'0,00')valor,
//                            lancamento_financeiro.faturamento_criado_pago,
//                            lancamento_financeiro.situacao,
//                            lancamento_financeiro.tipo,
//                            lancamento_financeiro.pedido_origem,
//                        (
//                            SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
//                        )nome,
//                        COALESCE(DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s'),'') AS datas,
//                        razao_social,
//                        lancamento_financeiro.faturamento_criado_pago
//                        FROM colaboradores
//                        LEFT JOIN lancamento_financeiro ON colaboradores.id = lancamento_financeiro.id_colaborador
//
//                                WHERE
//                                    lancamento_financeiro.id_colaborador = :id_colaborador  AND COALESCE(lancamento_financeiro.origem,'') <> 'AU'
//
//                                   {$ordena}";
//        }
//        $stm = $conexao->prepare($sql);
//        $stm->execute([
//            ':id_colaborador'=>$id_colaborador,
//            ':data_n'=>$data,
//            ':data_ate'=>$data_ate
//        ]);
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $linha_adiciona = [];
//        $linha_adiciona['datas'] = date('d/m/Y H:i:s');
//        $linha_adiciona['razao_social'] = $resultado[0]['razao_social'];
//        $linha_adiciona['origem'] = 'FIM';
//        $linha_adiciona['id'] = $resultado[0]['id_colaborador'];
//        $linha_adiciona['pedido_origem'] = '';
//        $linha_adiciona['tipo'] = 'S';
//        $linha_adiciona['faturamento_criado_pago'] = 'T';
//        $linha_adiciona['valor'] = '0';
//        array_push($resultado, $linha_adiciona);
//        return $resultado;
//    }


    public static function buscaExtratoClienteDetalhes(PDO $conexao, int $idColaborador, string $dataDe, string $dataAte): array
    {
        $bind[':id_colaborador'] = $idColaborador;
        $filtro = "AND lancamento_financeiro.data_emissao BETWEEN NOW() - INTERVAL 30 DAY AND NOW()";

        if($dataDe !== '' || $dataAte !== '') {
            $filtro = "AND lancamento_financeiro.data_emissao BETWEEN DATE(:de) AND DATE_ADD(:ate, INTERVAL 1 DAY)";
            $bind[':de'] = $dataDe;
            $bind[':ate'] = $dataAte;
        }

        $stm = $conexao->prepare(
            "SELECT 
                GROUP_CONCAT(transacao_financeiras_produtos_itens.id SEPARATOR ', ') AS `id_comissao`,
                colaboradores.id id_colaborador,
                COALESCE(lancamento_financeiro.id, '') AS `id`,
                lancamento_financeiro.id_usuario,
                DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y %H:%i:%s') data_emissao,
                COALESCE((lancamento_financeiro.origem),'SI')origem,
                COALESCE((lancamento_financeiro.valor),'0,00')valor,
                lancamento_financeiro.faturamento_criado_pago,
                lancamento_financeiro.situacao,
                lancamento_financeiro.tipo,
                lancamento_financeiro.pedido_origem,
                COALESCE((lancamento_financeiro.transacao_origem),0)transacao_origem,
                IF(
                lancamento_financeiro.origem = 'MA',
                COALESCE(lancamento_financeiro.observacao, 'Motivo desconhecido'),
                ''
                ) motivo_lancamento,
                (
                    SELECT usuarios.nome
                    FROM usuarios
                    WHERE usuarios.id=lancamento_financeiro.id_usuario
                )nome,
                colaboradores.razao_social,
                lancamento_financeiro.faturamento_criado_pago
            FROM colaboradores
            INNER JOIN lancamento_financeiro ON colaboradores.id = lancamento_financeiro.id_colaborador
            LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = lancamento_financeiro.transacao_origem
                AND lancamento_financeiro.origem IN (transacao_financeiras_produtos_itens.sigla_lancamento, transacao_financeiras_produtos_itens.sigla_estorno)
                AND transacao_financeiras_produtos_itens.id_fornecedor = lancamento_financeiro.id_colaborador
                AND IF( lancamento_financeiro.numero_documento, transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro.numero_documento , 1)
            WHERE lancamento_financeiro.id_colaborador = :id_colaborador
                AND COALESCE(lancamento_financeiro.origem,'') <> 'AU'
                $filtro
            GROUP BY lancamento_financeiro.id
            ORDER BY lancamento_financeiro.id DESC");
        $stm->execute($bind);
        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
        $linha_adiciona = [];
        $linha_adiciona['datas'] = date('d/m/Y H:i:s');
        $linha_adiciona['origem'] = 'FIM';
        $linha_adiciona['pedido_origem'] = '';
        $linha_adiciona['tipo'] = 'S';
        $linha_adiciona['faturamento_criado_pago'] = 'T';
        $linha_adiciona['valor'] = '0';
        array_push($resultado, $linha_adiciona);
        return $resultado;
    }
    // public function saldo_geral(PDO $conexao)
    // {
    //     $sql = "SELECT 'cliente' saldo,
    //                 SUM(
    //                         CASE WHEN lancamento_financeiro.tipo = 'R'  
    //                             THEN lancamento_financeiro.valor 
    //                             ELSE 0 
    //                         END) pagar, 
    //                 SUM(
    //                             CASE WHEN lancamento_financeiro.tipo = 'P' 
    //                                 THEN lancamento_financeiro.valor 
    //                                 ELSE 0 
    //                             END)receber
    //                     FROM lancamento_financeiro 
    //                             INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
    //                             WHERE 
    //                                             CASE WHEN colaboradores.tipo = 'C' 
    //                                                     THEN 1
    //                                                     ELSE 0
    //                                                 END AND
    //                                     lancamento_financeiro.situacao = 1
                                        
    //                                     UNION ALL
                        
    //             SELECT 
    //                     'fornecedor' saldo,
    //                     SUM(
    //                             CASE WHEN lancamento_financeiro.tipo = 'R'  
    //                                 THEN lancamento_financeiro.valor 
    //                                 ELSE 0 
    //                             END) pagar, 
    //                     SUM(
    //                                 CASE WHEN lancamento_financeiro.tipo = 'P' 
    //                                     THEN lancamento_financeiro.valor 
    //                                     ELSE 0 
    //                                 END)receber 
    //                         FROM lancamento_financeiro 
    //                                 INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
    //                                 WHERE 
    //                                                 CASE WHEN colaboradores.tipo = 'F' 
    //                                                         THEN 1
    //                                                         ELSE 0
    //                                                     END AND
    //                                         lancamento_financeiro.situacao = 1
    //                                 UNION ALL
                        
    //             SELECT 
    //                     'mobile' saldo,
    //                     SUM(
    //                             CASE WHEN lancamento_financeiro.tipo = 'R'  
    //                                 THEN lancamento_financeiro.valor 
    //                                 ELSE 0 
    //                             END) pagar, 
    //                 SUM(
    //                             CASE WHEN lancamento_financeiro.tipo = 'P' 
    //                                 THEN lancamento_financeiro.valor 
    //                                 ELSE 0 
    //                             END)receber 
    //                     FROM lancamento_financeiro 
    //                             INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
    //                             WHERE 
    //                                         lancamento_financeiro.situacao = 1";
    //     $stm = $conexao->prepare($sql);
    //     $stm->execute();
    //     $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
    //     $saldo = [];
    //     $saldo['cliente'] = (floatVal($resultado[0]['pagar']) - floatVal($resultado[0]['receber'])) * -1;
    //     $saldo['fornecedor'] = (floatVal($resultado[1]['pagar']) - floatVal($resultado[1]['receber'])) * -1;
    //     $saldo['mobile'] = (floatVal($resultado[2]['receber']) - floatVal($resultado[2]['pagar'])) * -1;
    //     //cliente positivo
    //     return $saldo;
    // }

//    public function buscarDetalheMobile(PDO $conexao, string $de, string $ate, string $condicao)
//    {
//        switch ($condicao) {
//            case 'ABERTO':
//                $condition = "AND lancamento_financeiro.situacao = 1 ";
//                break;
//            case 'FECHADO':
//                $condition = "AND lancamento_financeiro.situacao = 2 ";
//                break;
//            case '0':
//                $condition = "";
//                break;
//        }
//
//        if ($de != '' && $ate != '') {
//            $data = "DATE(lancamento_financeiro.data_emissao) BETWEEN '{$de}' AND '{$ate}'";
//        } else if ($de != '') {
//            $data = "DATE(lancamento_financeiro.data_emissao) >='{$de}'";
//        } else if ($ate != '') {
//            $data = "DATE(lancamento_financeiro.data_emissao) <='{$de}'";
//        } else {
//            $data = " 1 = 1";
//        }
//        $total =
//            "SELECT 'total' saldo,
//                    SUM(0) pagar,
//                    SUM(lancamento_financeiro.valor) receber
//                        FROM lancamento_financeiro
//                        INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                            WHERE
//                                {$data}
//                                {$condition}
//                    UNION ALL
//
//            SELECT 'pagar' saldo,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'P'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            ) pagar,
//            SUM(0) receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        {$condition}
//
//                UNION ALL
//
//            SELECT 'receber' saldo,
//            SUM(0) pagar,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'R'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            )receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        {$condition}
//
//                UNION ALL
//
//            SELECT 'lucro' saldo,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'P'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            ) pagar,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'R'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            )receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        {$condition}
//            UNION ALL
//
//            SELECT 'pagar_seller' saldo,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'P'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            ) pagar,
//            SUM(0) receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        AND colaboradores.tipo = 'F'
//                        {$condition}
//            UNION ALL
//
//            SELECT 'receber_seller' saldo,
//            SUM(0) pagar,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'R'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            ) receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        AND colaboradores.tipo = 'F'
//                        {$condition}
//            UNION ALL
//
//            SELECT 'pagar_cliente' saldo,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'P'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            ) pagar,
//            SUM(0) receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        AND colaboradores.tipo = 'C'
//                        {$condition}
//            UNION ALL
//
//            SELECT 'receber_cliente' saldo,
//            SUM(0) pagar,
//            SUM(
//                CASE WHEN lancamento_financeiro.tipo = 'R'
//                    THEN lancamento_financeiro.valor
//                    ELSE 0
//                END
//            ) receber
//                FROM lancamento_financeiro
//                INNER JOIN colaboradores ON (colaboradores.id = lancamento_financeiro.id_colaborador)
//                    WHERE
//                        {$data}
//                        AND colaboradores.tipo = 'C'
//                        {$condition}";
//        $stm = $conexao->prepare($total);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $valores['total'] = floatVal($resultado[0]['receber']) - floatVal($resultado[0]['pagar']);
//        $valores['pagar'] = floatVal($resultado[1]['receber']) - floatVal($resultado[1]['pagar']);
//        $valores['receber'] = floatVal($resultado[2]['receber']) - floatVal($resultado[2]['pagar']);
//        $valores['lucro'] = floatVal($resultado[3]['receber']) - floatVal($resultado[3]['pagar']);
//        $valores['pagar_seller'] = floatVal($resultado[4]['receber']) - floatVal($resultado[4]['pagar']);
//        $valores['receber_seller'] = floatVal($resultado[5]['receber']) - floatVal($resultado[5]['pagar']);
//        $valores['pagar_cliente'] = floatVal($resultado[6]['receber']) - floatVal($resultado[6]['pagar']);
//        $valores['receber_cliente'] = floatVal($resultado[7]['receber']) - floatVal($resultado[7]['pagar']);
//        return $valores;
//    }
//    public function buscarDetalheMobileQtd(PDO $conexao, string $de, string $ate, string $condicao)
//    {
//        switch ($condicao) {
//            case '0':
//                $condition = "";
//                break;
//        }
//        if ($de != '' && $ate != '') {
//            $data = "AND DATE(faturamento.data_fechamento) BETWEEN '{$de}' AND '{$ate}'";
//        } else if ($de != '') {
//            $data = "AND DATE(faturamento.data_fechamento) >='{$de}'";
//        } else if ($ate != '') {
//            $data = "AND DATE(faturamento.data_fechamento) <='{$de}'";
//        } else {
//            $data = "";
//        }
//        $consulta = "SELECT 'total' faturamento,
//                        COUNT(faturamento.id) qtd
//                            FROM faturamento
//                                WHERE
//                                    faturamento.situacao = 2
//                                    {$data}
//                                    {$condition}
//                        UNION ALL
//                    SELECT 'pares' faturamento,
//                        COUNT(faturamento_item.id_faturamento) qtd
//                            FROM faturamento_item
//                                INNER JOIN faturamento ON(faturamento_item.id_faturamento = faturamento.id)
//                                    WHERE
//                                        faturamento.situacao = 2
//                                        {$data}
//                                        {$condition}
//                    UNION ALL
//                    SELECT 'correcao' faturamento,
//                        COUNT(faturamento_item.id_faturamento) qtd
//                            FROM faturamento_item
//                                INNER JOIN faturamento ON(faturamento_item.id_faturamento = faturamento.id)
//                                    WHERE
//                                        faturamento_item.situacao = 19 AND
//                                        faturamento.situacao = 2
//                                        {$data}
//                                        {$condition}
//                    UNION ALL
//                    SELECT 'troca' faturamento,
//                        COUNT(faturamento_item.id_faturamento) qtd
//                            FROM faturamento_item
//                                INNER JOIN faturamento ON(faturamento_item.id_faturamento = faturamento.id)
//                                    WHERE
//                                        faturamento_item.situacao IN (8,9,10,11,12,16,17) AND
//                                        faturamento.situacao = 2
//                                        {$data}
//                                        {$condition}";
//        $stm = $conexao->prepare($consulta);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        $quantidades['faturamento'] = $resultado[0]['qtd'];
//        $quantidades['pares'] = $resultado[1]['qtd'];
//        $quantidades['correcao'] = $resultado[2]['qtd'];
//        $quantidades['troca'] = $resultado[3]['qtd'];
//        return $quantidades;
//    }
//    public function buscarDetalheMobileLista(PDO $conexao, string $de, string $ate, string $condicao)
//    {
//        switch ($condicao) {
//            case 'ABERTO':
//                $condition = "AND lancamento_financeiro.situacao = 1 ";
//                break;
//            case 'FECHADO':
//                $condition = "AND lancamento_financeiro.situacao = 2 ";
//                break;
//            case '0':
//                $condition = "";
//                break;
//        }
//        if ($de != '' && $ate != '') {
//            $data = "AND DATE(lancamento_financeiro.data_emissao) BETWEEN '{$de}' AND '{$ate}'";
//        } else if ($de != '') {
//            $data = "AND DATE(lancamento_financeiro.data_emissao) >='{$de}'";
//        } else if ($ate != '') {
//            $data = "AND DATE(lancamento_financeiro.data_emissao) <='{$de}'";
//        } else {
//            $data = "";
//        }
//        $consulta = "SELECT lancamento_financeiro.id,
//                            lancamento_financeiro.id_usuario,
//                            lancamento_financeiro.data_emissao,
//                            lancamento_financeiro.id_colaborador,
//                            lancamento_financeiro.origem,
//                            lancamento_financeiro.valor,
//                            lancamento_financeiro.faturamento_criado_pago,
//                            lancamento_financeiro.situacao,
//                            lancamento_financeiro.tipo,
//                            lancamento_financeiro.pedido_origem,
//                (
//                    SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
//                ) nome,
//                DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//                (
//                    SELECT razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador
//                ) razao_social, lancamento_financeiro.faturamento_criado_pago
//                    FROM lancamento_financeiro
//
//                        WHERE
//                             lancamento_financeiro.origem <> 'AU'
//                            {$data}
//                            {$condition}";
//        $stm = $conexao->prepare($consulta);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//    public function buscarDetalheMobileLista_(PDO $conexao, string $de, string $ate, string $condicao, int $parametros)
//    {
//        $condition = "";
//        switch ($condicao) {
//            case 'ABERTO':
//                $condition = " AND lancamento_financeiro.situacao = 1 ";
//                break;
//            case 'FECHADO':
//                $condition = " AND lancamento_financeiro.situacao = 2 ";
//                break;
//            default:
//                $condition = "";
//                break;
//        }
//
//        switch ($parametros) {
//            case '2':
//                $condition .= " AND lancamento_financeiro.tipo = 'P' ";
//                break;
//            case '3':
//                $condition .= " AND lancamento_financeiro.tipo = 'R' ";
//                break;
//            case '4':
//                $condition .= " AND lancamento_financeiro.tipo = 'P' AND colaboradores.tipo = 'F' ";
//                break;
//            case '5':
//                $condition .= " AND lancamento_financeiro.tipo = 'R' AND colaboradores.tipo = 'F' ";
//                break;
//            case '6':
//                $condition .= " AND lancamento_financeiro.tipo = 'P' AND colaboradores.tipo = 'C' ";
//                break;
//            case '7':
//                $condition .= " AND lancamento_financeiro.tipo = 'R' AND colaboradores.tipo = 'C' ";
//                break;
//        }
//        if ($de != '' && $ate != '') {
//            $data = "AND DATE(lancamento_financeiro.data_emissao) BETWEEN '{$de}' AND '{$ate}'";
//        } else if ($de != '') {
//            $data = "AND DATE(lancamento_financeiro.data_emissao) >='{$de}'";
//        } else if ($ate != '') {
//            $data = "AND DATE(lancamento_financeiro.data_emissao) <='{$de}'";
//        } else {
//            $data = "";
//        }
//        $consulta = "SELECT lancamento_financeiro.id,
//                            lancamento_financeiro.id_usuario,
//                            lancamento_financeiro.data_emissao,
//                            lancamento_financeiro.id_colaborador,
//                            lancamento_financeiro.origem,
//                            lancamento_financeiro.valor,
//                            lancamento_financeiro.faturamento_criado_pago,
//                            lancamento_financeiro.situacao,
//                            lancamento_financeiro.tipo,
//                            lancamento_financeiro.pedido_origem,
//                (
//                    SELECT nome FROM usuarios WHERE usuarios.id=lancamento_financeiro.id_usuario
//                ) nome,
//                DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') AS datas,
//                razao_social, lancamento_financeiro.faturamento_criado_pago
//                    FROM lancamento_financeiro
//                        INNER JOIN colaboradores ON(colaboradores.id = lancamento_financeiro.id_colaborador)
//                            WHERE
//                                lancamento_financeiro.origem <> 'AU'
//                                {$data}
//                                {$condition}";
//
//        $condition = "";
//        $stm = $conexao->prepare($consulta);
//        $stm->execute();
//        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

    // public function buscarFaturamentoMobileLista_(PDO $conexao, string $de, string $ate, string $condicao, int $parametros)
    // {
    //     $condition = "";
    //     switch ($condicao) {
    //         case 'ABERTO':

    //             $condition = ($parametros == '8' ? " AND faturamento.situacao = 1 " : "");
    //             break;
    //         case 'FECHADO':
    //             $condition = ($parametros == '8' ? " AND faturamento.situacao = 2 " : "");
    //             break;
    //         default:
    //             $condition = "";
    //             break;
    //     }
    //     if ($de != '' && $ate != '') {
    //         $data = ($parametros == '8' ? "AND DATE(faturamento.data_fechamento) BETWEEN '{$de}' AND '{$ate}'" : "AND DATE(faturamento_item.data_hora) BETWEEN '{$de}' AND '{$ate}'");
    //     } else if ($de != '') {
    //         $data = ($parametros == '8' ? "AND DATE(faturamento.data_fechamento) >='{$de}'" : "AND DATE(faturamento_item.data_hora) >='{$de}'");
    //     } else if ($ate != '') {
    //         $data = ($parametros == '8' ? "AND DATE(faturamento.data_fechamento) <='{$de}'" : "AND DATE(faturamento_item.data_hora) <='{$de}'");
    //     } else {
    //         $data = "";
    //     }

    //     switch ($parametros) {
    //         case '8':
    //             $consulta = "SELECT faturamento.id,
    //                         faturamento.id_usuario,
    //                         faturamento.situacao,
    //                         faturamento.valor_produtos,
    //                         faturamento.valor_liquido,
    //                         faturamento.pares,
    //             (
    //                 SELECT nome FROM usuarios WHERE usuarios.id=faturamento.id_usuario
    //             ) nome,
    //             DATE_FORMAT(faturamento.data_fechamento,'%d/%m/%Y %H:%i:%s') AS datas,
    //             razao_social
    //                 FROM faturamento
    //                     INNER JOIN colaboradores ON(colaboradores.id = faturamento.id_cliente)
    //                         WHERE
    //                             colaboradores.id = faturamento.id_cliente
    //                             {$data}
    //                             {$condition}";
    //             break;
    //         case '9':
    //             $consulta = "SELECT faturamento_item.id_faturamento,
    //                         faturamento_item.id_produto,
    //                         faturamento_item.nome_tamanho tamanho,
    //                         faturamento_item.preco,
    //                         faturamento_item.id_cliente,
    //             (
    //                 SELECT descricao FROM produtos WHERE produtos.id=faturamento_item.id_produto
    //             ) produto,
    //             DATE_FORMAT(faturamento_item.data_hora,'%d/%m/%Y %H:%i:%s') AS datas,
    //             razao_social
    //                 FROM faturamento_item
    //                     INNER JOIN colaboradores ON(colaboradores.id = faturamento_item.id_cliente)
    //                         WHERE
    //                             colaboradores.id = faturamento_item.id_cliente
    //                             {$data}
    //                             {$condition}";

    //             break;
    //         case '11':
    //             $consulta = "SELECT faturamento_item.id_faturamento,
    //                         faturamento_item.id_produto,
    //                         faturamento_item.nome_tamanho tamanho,
    //                         faturamento_item.preco,
    //                         faturamento_item.id_cliente,
    //             (
    //                 SELECT descricao FROM produtos WHERE produtos.id=faturamento_item.id_produto
    //             ) produto,
    //             DATE_FORMAT(faturamento_item.data_hora,'%d/%m/%Y %H:%i:%s') AS datas,
    //             razao_social
    //                 FROM faturamento_item
    //                     INNER JOIN colaboradores ON(colaboradores.id = faturamento_item.id_cliente)
    //                         WHERE
    //                             faturamento_item.situacao = 19
    //                             {$data}
    //                             {$condition}";

    //             break;
    //         case '10':
    //             $consulta = "SELECT faturamento_item.id_faturamento,
    //                         faturamento_item.id_produto,
    //                         faturamento_item.nome_tamanho tamanho,
    //                         faturamento_item.preco,
    //                         faturamento_item.id_cliente,
    //             (
    //                 SELECT descricao FROM produtos WHERE produtos.id=faturamento_item.id_produto
    //             ) produto,
    //             DATE_FORMAT(faturamento_item.data_hora,'%d/%m/%Y %H:%i:%s') AS datas,
    //             razao_social
    //                 FROM faturamento_item
    //                     INNER JOIN colaboradores ON(colaboradores.id = faturamento_item.id_cliente)
    //                         WHERE
    //                             faturamento_item.situacao IN (9,10,11,12,16,17,18)
    //                             {$data}
    //                             {$condition}";

    //             break;
    //         case '12':

    //             break;
    //     }

    //     $stm = $conexao->prepare($consulta);
    //     $stm->execute();
    //     $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
    //     return $resultado;
    // }

//    public function buscaDireitoBoletoSellers(PDO $conexao)
//    {
//        $sql = "WITH
//                    tabela_1 AS(SELECT colaboradores.id,
//                                        colaboradores.razao_social,
//                                        COALESCE((SELECT DATE_FORMAT(MAX(lancamento_financeiro_pendente.data_emissao),'%d/%m/%Y %H:%i:%s') FROM lancamento_financeiro_pendente WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id AND lancamento_financeiro_pendente.origem = 'PF' GROUP BY lancamento_financeiro_pendente.data_emissao LIMIT 1),0) data,
//                                        COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0) debito,
//                                        COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0) -
//                                        COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0)  -
//                                        COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                    FROM lancamento_financeiro_pendente
//                                                    WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                        AND lancamento_financeiro_pendente.origem = 'PF'), 0) credito,
//                                        COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                    FROM lancamento_financeiro_pendente
//                                                    WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                        AND lancamento_financeiro_pendente.origem = 'PF'), 0) valor_pendente,
//                                        api_colaboradores.id_zoop
//                                    FROM lancamento_financeiro
//                                        INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
//                                        INNER JOIN api_colaboradores ON api_colaboradores.id_colaborador = colaboradores.id
//                                    WHERE lancamento_financeiro.situacao = 1
//                                        AND colaboradores.tipo = 'F'
//                                        AND colaboradores.id <> 12
//                                        AND colaboradores.pagamento_bloqueado = 'F'
//                                    GROUP BY colaboradores.id
//                                    HAVING credito > 0
//                                    ORDER BY lancamento_financeiro.data_emissao),
//                    tabela_2 AS(SELECT colaboradores.id,
//                                        colaboradores.razao_social,
//                                        COALESCE((SELECT DATE_FORMAT(MAX(lancamento_financeiro_pendente.data_emissao),'%d/%m/%Y %H:%i:%s') FROM lancamento_financeiro_pendente WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id AND lancamento_financeiro_pendente.origem = 'PF' GROUP BY lancamento_financeiro_pendente.data_emissao LIMIT 1),0) data,
//                                        COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0) debito,
//                                        COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0) -
//                                        COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0) -
//                                        COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                    FROM lancamento_financeiro_pendente
//                                                    WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                        AND lancamento_financeiro_pendente.origem = 'PF'), 0) credito,
//                                        COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                    FROM lancamento_financeiro_pendente
//                                                    WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                        AND lancamento_financeiro_pendente.origem = 'PF'), 0) valor_pendente,
//                                        api_colaboradores.id_zoop
//                                    FROM lancamento_financeiro
//                                        INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
//                                        INNER JOIN api_colaboradores ON api_colaboradores.id_colaborador = colaboradores.id
//                                    WHERE lancamento_financeiro.situacao = 1
//                                        AND colaboradores.tipo = 'F'
//                                        AND colaboradores.id <> 12
//                                        AND colaboradores.pagamento_bloqueado = 'F'
//                                GROUP BY colaboradores.id
//                                HAVING COALESCE((SELECT SUM(produtos.valor_custo_produto * (estoque_grade.estoque + estoque_grade.vendido)) valor
//                                            FROM produtos
//                                                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
//                                            WHERE produtos.bloqueado = 0
//                                                AND produtos.id_fornecedor = colaboradores.id),0) > ABS(credito)
//                                AND credito <= 0
//                                ORDER BY debito)
//                SELECT tabela_1.* FROM tabela_1
//                UNION ALL
//                SELECT tabela_2.* FROM tabela_2;";
//        $stmt = $conexao->prepare($sql);
//        $stmt->execute();
//        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//    public function buscaDireitoCartaoSellers(PDO $conexao)
//    {
//        $sql = "WITH
//                    tabela_1 AS(SELECT colaboradores.id,
//                                    colaboradores.razao_social,
//                                    COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0) debito,
//                                    COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0) -
//                                    COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                FROM lancamento_financeiro_pendente
//                                                WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                    AND lancamento_financeiro_pendente.origem = 'PF'), 0) credito,
//                                    COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                FROM lancamento_financeiro_pendente
//                                                WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                    AND lancamento_financeiro_pendente.origem = 'PF'), 0) valor_pendente,
//                                    api_colaboradores.id_zoop
//                                FROM lancamento_financeiro
//                                    INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
//                                    INNER JOIN api_colaboradores ON api_colaboradores.id_colaborador = colaboradores.id
//                                WHERE lancamento_financeiro.situacao = 1
//                                    AND colaboradores.tipo = 'F'
//                                    AND colaboradores.id = 12
//                                    AND colaboradores.pagamento_bloqueado = 'F'
//                                GROUP BY colaboradores.id
//                                HAVING credito > 0),
//                    tabela_2 AS(SELECT colaboradores.id,
//                                    colaboradores.razao_social,
//                                    COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0) debito,
//                                    COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0) -
//                                    COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                FROM lancamento_financeiro_pendente
//                                                WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                    AND lancamento_financeiro_pendente.origem = 'PF'), 0) credito,
//                                    COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
//                                                FROM lancamento_financeiro_pendente
//                                                WHERE lancamento_financeiro_pendente.id_colaborador = colaboradores.id
//                                                    AND lancamento_financeiro_pendente.origem = 'PF'), 0) valor_pendente,
//                                    api_colaboradores.id_zoop
//                                FROM lancamento_financeiro
//                                    INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
//                                    INNER JOIN api_colaboradores ON api_colaboradores.id_colaborador = colaboradores.id
//                                WHERE lancamento_financeiro.situacao = 1
//                                    AND colaboradores.tipo = 'F'
//                                    AND colaboradores.id <> 12
//                                    AND colaboradores.pagamento_bloqueado = 'F'
//                                GROUP BY colaboradores.id
//                                HAVING COALESCE((SELECT SUM(produtos.valor_custo_produto * (estoque_grade.estoque + estoque_grade.vendido)) valor
//                                            FROM produtos
//                                                INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos.id
//                                            WHERE produtos.bloqueado = 0
//                                                AND produtos.id_fornecedor = colaboradores.id),0) > ABS(credito)
//                                ORDER BY credito DESC, debito)
//                SELECT tabela_1.* FROM tabela_1
//                UNION ALL
//                SELECT tabela_2.* FROM tabela_2;";
//        $stmt = $conexao->prepare($sql);
//        $stmt->execute();
//        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
//    public function listaPendentes(PDO $conexao, int $colaborador){
//        $query= "SELECT lancamento_financeiro_pendente.id,
//                    lancamento_financeiro_pendente.valor,
//                     lancamento_financeiro_pendente.transacao_origem,
//                    lancamento_financeiro_pendente.origem,
//                    DATE_FORMAT(lancamento_financeiro_pendente.data_vencimento,'%d/%m/%Y') dataVencimento,
//                   lancamento_financeiro_pendente.pedido_origem,
//                   lancamento_financeiro_pendente.id_colaborador,
//                   (SELECT razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro_pendente.id_colaborador) razao_social,
//                    0 valor_recebe
//                    FROM lancamento_financeiro_pendente
//                   WHERE lancamento_financeiro_pendente.id_colaborador = {$colaborador}
//                AND lancamento_financeiro_pendente.origem = 'PF'
//                ";
//        $stmt = $conexao->prepare($query);
//        $stmt->execute();
//        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }
    public static function listaTransferencias(PDO $conexao): array
    {

        $diasPagamento = ConfiguracaoService::buscaDiasTransferenciaColaboradores($conexao);

        $diasPagamento = array_map(fn ($dias) => $dias + 1, $diasPagamento);

        $sql="SELECT
                colaboradores_prioridade_pagamento.id AS `id_prioridade`,
                colaboradores_prioridade_pagamento.valor_pago,
                colaboradores_prioridade_pagamento.valor_pagamento,
                lancamento_financeiro.id_prioridade_saque,
                lancamento_financeiro.id AS `id_lancamento`,
                colaboradores_prioridade_pagamento.id_colaborador,
                IF(COALESCE(colaboradores_prioridade_pagamento.situacao, 'NA') = 'CR'
                    AND LENGTH(COALESCE(colaboradores_prioridade_pagamento.id_transferencia, '')) > 1,
                    'ET', colaboradores_prioridade_pagamento.situacao
                ) situacao,
                colaboradores_prioridade_pagamento.id_transferencia,
                conta_bancaria_colaboradores.nome_titular,
                conta_bancaria_colaboradores.cpf_titular,
                conta_bancaria_colaboradores.conta,
                conta_bancaria_colaboradores.agencia,
                conta_bancaria_colaboradores.id,
                COALESCE((colaboradores_prioridade_pagamento.valor_pagamento - colaboradores_prioridade_pagamento.valor_pago),0) valor_pendente,
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_criacao, '%d/%m/%Y %H:%i:%s') AS `data_criacao`,
                DATE_FORMAT(colaboradores_prioridade_pagamento.data_atualizacao,'%d/%m/%Y %H:%i:%s') AS `data_atualizacao`,
                colaboradores.razao_social,
                conta_bancaria_colaboradores.pagamento_bloqueado,
                reputacao_fornecedores.reputacao,
                CASE
                    WHEN reputacao_fornecedores.reputacao = 'MELHOR_FABRICANTE' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'EXCELENTE' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_EXCELENTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'REGULAR' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_REGULAR']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    WHEN reputacao_fornecedores.reputacao = 'RUIM' THEN
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_fornecedor_RUIM']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                    ELSE
                        DATE_FORMAT(DATEADD_DIAS_UTEIS({$diasPagamento['dias_pagamento_transferencia_CLIENTE']}, colaboradores_prioridade_pagamento.data_criacao), '%d/%m/%Y')
                END AS `proximo_pagamento`,
                saldo_cliente(colaboradores_prioridade_pagamento.id_colaborador) saldo
            FROM conta_bancaria_colaboradores
            INNER JOIN colaboradores_prioridade_pagamento ON colaboradores_prioridade_pagamento.id_conta_bancaria = conta_bancaria_colaboradores.id
            INNER JOIN colaboradores ON colaboradores.id = colaboradores_prioridade_pagamento.id_colaborador
            INNER JOIN lancamento_financeiro ON lancamento_financeiro.id_prioridade_saque = colaboradores_prioridade_pagamento.id
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = colaboradores_prioridade_pagamento.id_colaborador
            WHERE colaboradores_prioridade_pagamento.situacao IN ('CR','EM')
                AND colaboradores_prioridade_pagamento.id_transferencia = '0'
            GROUP BY colaboradores_prioridade_pagamento.id
            ORDER BY colaboradores_prioridade_pagamento.id DESC";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado ?: [];
    }
//    public function listaLancamento(PDO $conexao,int $colaborador){
//        $query= "SELECT lancamento_financeiro.id,
//                    lancamento_financeiro.transacao_origem,
//                    lancamento_financeiro.valor,
//                    lancamento_financeiro.id_colaborador,
//                    lancamento_financeiro.origem,
//                    DATE_FORMAT(lancamento_financeiro.data_vencimento,'%d/%m/%Y') dataVencimento,
//                   lancamento_financeiro.pedido_origem,
//                   (SELECT razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador) razao_social,
//                    0 valor_recebe
//                    FROM lancamento_financeiro
//                   WHERE lancamento_financeiro.id_colaborador =  {$colaborador}
//                AND lancamento_financeiro.situacao = 1
//                AND lancamento_financeiro.tipo = 'P'";
//        $stmt = $conexao->prepare($query);
//        $stmt->execute();
//        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

    public static function alternaBloquearContaBancaria(PDO $conexao, bool $bloquear, int $idContaBancaria): void
    {

        $bloquear = $bloquear ? 'T' : 'F';

        $sql= "UPDATE conta_bancaria_colaboradores
                SET conta_bancaria_colaboradores.pagamento_bloqueado = :valor
                WHERE conta_bancaria_colaboradores.id = :id_conta_bancaria";

        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':valor', $bloquear, PDO::PARAM_STR);
        $stmt->bindValue(':id_conta_bancaria', $idContaBancaria, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) throw new Exception("Erro ao bloquear a conta bancária $idContaBancaria");

        if ($bloquear === 'T') {
            $sql = $conexao->prepare(
                "DELETE FROM fila_transferencia_automatica
                WHERE fila_transferencia_automatica.id_transferencia IN (
                    SELECT colaboradores_prioridade_pagamento.id
                    FROM colaboradores_prioridade_pagamento
                    WHERE colaboradores_prioridade_pagamento.situacao = 'CR'
                        AND colaboradores_prioridade_pagamento.id_conta_bancaria = :id_conta_bancaria
                );"
            );
            $sql->bindValue(':id_conta_bancaria', $idContaBancaria, PDO::PARAM_INT);
            $sql->execute();
        }
    }

//    public static function resumoOrigensSellers(PDO $conexao, int $id_colaborador, string $data, string $data_ate){
//        $sql= "SELECT 'venda' tipo, SUM(lancamento_financeiro.valor_total)AS total
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem IN ('SC', 'SP')
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//
//                    UNION ALL
//
//            SELECT 'devolucao' tipo, SUM(lancamento_financeiro.valor_total)AS devolucao
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'TF'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//
//                    UNION ALL
//
//            SELECT 'reembolso' tipo, SUM(lancamento_financeiro.valor_total)AS reembolsos
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'RE'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//
//                    UNION ALL
//
//            SELECT 'split' tipo, SUM(lancamento_financeiro.valor_total)AS split
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'SP'
//                    AND lancamento_financeiro.tipo = 'R'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//                    UNION ALL
//
//            SELECT 'pagamento' tipo, SUM(lancamento_financeiro.valor_total)AS pagamento
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'PF'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//            UNION ALL
//
//            SELECT 'pendentes' tipo, SUM(lancamento_financeiro_pendente.valor_total)AS pendente
//                FROM lancamento_financeiro_pendente
//                    WHERE lancamento_financeiro_pendente.id_colaborador = {$id_colaborador}
//                    AND lancamento_financeiro_pendente.tipo = 'R'
//
//
//
//            UNION ALL
//
//            SELECT 'defeito' tipo, SUM(lancamento_financeiro.valor_total)AS defeito
//                FROM lancamento_financeiro
//                LEFT JOIN faturamento_item ON(faturamento_item.uuid = lancamento_financeiro.numero_documento)
//                    WHERE lancamento_financeiro.origem = 'TF'
//                        AND faturamento_item.situacao = 9
//                        AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                        AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                        AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//            UNION ALL
//
//            SELECT 'correcao' tipo, SUM(lancamento_financeiro.valor_total)AS correcao
//                FROM lancamento_financeiro
//                LEFT JOIN faturamento_item ON(faturamento_item.uuid = lancamento_financeiro.numero_documento)
//                    WHERE lancamento_financeiro.origem = 'CP'
//                            AND faturamento_item.situacao = 19
//                            AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                            AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                            AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//            UNION ALL
//
//
//			SELECT  'produto_vendido' tipo, SUM(transacao_financeiras_produtos_itens.preco)AS produto_vendido
//                	FROM transacao_financeiras_produtos_itens
//                    WHERE transacao_financeiras_produtos_itens.id_transacao NOT IN(
//						  							SELECT 1
//													  	FROM transacao_financeiras_faturamento
//														  	WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao
//     											)
//                                                 	AND transacao_financeiras_produtos_itens.situacao = 'CO'
//                                                     AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
//                                                    AND  DATE(transacao_financeiras_produtos_itens.data_criacao) >= '{$data}'
//                                                      AND  DATE(transacao_financeiras_produtos_itens.data_criacao) <= '{$data_ate}'
//												  AND transacao_financeiras_produtos_itens.id_fornecedor = {$id_colaborador}
//             UNION ALL
//
//
//			SELECT  'estorno' tipo, SUM(lancamento_financeiro.valor_total)AS estorno
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'ES'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//
//             UNION ALL
//
//
//			SELECT  'manual_credito' tipo, SUM(lancamento_financeiro.valor_total)AS credito
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'MA'
//                    AND lancamento_financeiro.tipo = 'R'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'
//            UNION ALL
//
//
//			SELECT  'manual_debito' tipo, SUM(lancamento_financeiro.valor_total)AS debito
//                FROM lancamento_financeiro
//                    WHERE lancamento_financeiro.origem = 'MA'
//                    AND lancamento_financeiro.tipo = 'P'
//                    AND lancamento_financeiro.id_colaborador = {$id_colaborador}
//                    AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
//                    AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}';";
//        $stmt = $conexao->prepare($sql);
//        $stmt->execute();
//        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        return $resultado;
//
//    }

    // public static function listaVendaFornecedor(PDO $conexao,int $id_colaborador,string $data, string $data_ate){
    //     $filtro_data = "AND DATE(lancamento_financeiro.data_emissao) >= '{$data}'
    //                         AND DATE(lancamento_financeiro.data_emissao) <= '{$data_ate}'";
        
        
    //     $pares= " SELECT COUNT(faturamento_item.uuid) total 
    //                 FROM faturamento_item
    //                   INNER JOIN lancamento_financeiro ON lancamento_financeiro.pedido_origem = faturamento_item.id_faturamento
    //                                           AND lancamento_financeiro.transacao_origem = 0
    //                                           AND lancamento_financeiro.origem IN ('SP','SC')
    //                                           AND lancamento_financeiro.id_colaborador =  faturamento_item.id_fornecedor
    //                     WHERE  lancamento_financeiro.id_colaborador = {$id_colaborador}
    //                         $filtro_data

    //                 UNION ALL 

    //                 SELECT COUNT(transacao_financeiras_produtos_itens.uuid)
    //                 FROM transacao_financeiras_produtos_itens
    //                   INNER JOIN lancamento_financeiro ON lancamento_financeiro.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
    //                                           AND lancamento_financeiro.origem IN ('SP','SC')
    //                                           AND lancamento_financeiro.id_colaborador =  transacao_financeiras_produtos_itens.id_fornecedor
    //                 WHERE transacao_financeiras_produtos_itens.tipo_item = 'PR'
    //                   $filtro_data
    //                   AND lancamento_financeiro.id_colaborador = {$id_colaborador}";
    //     $stmt = $conexao->prepare($pares);
    //     $stmt->execute();
    //     $pares = $stmt->fetchAll(PDO::FETCH_ASSOC);  
    //     $total = ($pares ? floatval($pares[0]['total'])+ floatVal($pares[1]['total']) : 0);

    //     // if ($produto != 0) {
    //     //     $filtro_produto = "AND lancamento_fina nceiro.transacao_origem IN (SELECT transacao_financeiras_produtos_itens.id_transacao FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.id_produto = {$produto} AND transacao_financeiras_produtos_itens.id_transacao = lancamento_financeiro.transacao_origem) ";
    //     // }else{
    //     //     $filtro_produto = "";
    //     // }

    //     $sql = "SELECT DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y')data_emissao, 
    //                     SUM(lancamento_financeiro.valor)total 
    //                         FROM lancamento_financeiro 
    //                             WHERE lancamento_financeiro.id_colaborador = {$id_colaborador} 
    //                                     AND lancamento_financeiro.origem IN ('SC','SP') 
    //                                     {$filtro_data}
                                        
    //                                         GROUP BY DATE(data_emissao) 
    //                                         ORDER BY DATE(data_emissao) DESC;";

    //     $stmt = $conexao->prepare($sql);
    //     $stmt->execute();
    //     $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);     
    //     if($temp = $resultado){
    //         $sql = "SELECT  lancamento_financeiro.situacao, 
    //                         lancamento_financeiro.id,
    //                         lancamento_financeiro.valor_pago, 
    //                         lancamento_financeiro.valor_total, 
    //                         lancamento_financeiro.pedido_origem,
    //                         lancamento_financeiro.transacao_origem,
    //                         DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y')data_emissao  
    //                         FROM lancamento_financeiro 
    //                             WHERE lancamento_financeiro.id_colaborador = {$id_colaborador} 
    //                                     AND lancamento_financeiro.origem IN ('SC','SP') 
    //                                     {$filtro_data} 
                                       
    //                                     ORDER BY DATE(data_emissao) DESC;";
    //         $stmt = $conexao->prepare($sql);
    //         $stmt->execute();
    //         $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);  

    //         foreach($temp as $item){
    //             $retorno[$item['data_emissao']]['data'] = $item['data_emissao'];
    //             $retorno[$item['data_emissao']]['total'] = $item['total'];
    //             $retorno[$item['data_emissao']]['pares'] = $total;
    //             $retorno[$item['data_emissao']]['lancamentos'] = [];
    //            foreach($lancamentos as $i){
    //                 if($item['data_emissao'] == $i['data_emissao']){
    //                     array_push($retorno[$item['data_emissao']]['lancamentos'],$i);
    //                 }
    //            }
    //         }
    //     }
    //     return $retorno;
    // }
    
    public static function deletarTransferencia(PDO $conexao, int $idTransferencia): void
    {
        $sql="DELETE FROM colaboradores_prioridade_pagamento
                WHERE colaboradores_prioridade_pagamento.id = :idTransferencia";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':idTransferencia', $idTransferencia, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() !== 1) throw new Exception("Erro ao deletar transferência $idTransferencia");
    }
}

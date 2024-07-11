<?php

namespace MobileStock\service\TransacaoFinanceira;

use api_estoque\Cript\Cript;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\LogisticaItem;
use MobileStock\model\Origem;
use MobileStock\model\ProdutoModel;
use MobileStock\model\TipoFrete;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\Recebiveis\RecebiveisConsultas;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransacaoConsultasService
{
    //
    //    public static function  buscaTransacoes(PDO $conexao, int $idFaturamento)
    //    {
    //        $consulta = "SELECT transacao_financeiras.status,
    //                            transacao_financeiras.id,
    //                            transacao_financeiras.cod_transacao,
    //                            transacao_financeiras.valor_total,
    //                            transacao_financeiras.valor_credito,
    //                            transacao_financeiras.valor_acrescimo,
    //                            transacao_financeiras.valor_comissao_fornecedor,
    //                            transacao_financeiras.valor_liquido,
    //                            transacao_financeiras.valor_itens,
    //                            transacao_financeiras.valor_taxas,
    //                            transacao_financeiras.juros_pago_split,
    //                            transacao_financeiras.numero_transacao,
    //                            transacao_financeiras.responsavel,
    //                            transacao_financeiras.pagador,
    //                            transacao_financeiras.metodo_pagamento,
    //                            transacao_financeiras.numero_parcelas,
    //                            DATE_FORMAT(transacao_financeiras.data_criacao,'%d/%m/%Y %H:%i:%s') data1,
    //                            transacao_financeiras.qrcode_text_pix
    //                                FROM transacao_financeiras
    //                                    WHERE transacao_financeiras.id IN (SELECT transacao_financeiras_faturamento.id_transacao FROM transacao_financeiras_faturamento WHERE transacao_financeiras_faturamento.id_faturamento = {$idFaturamento});";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //

    public static function buscaRecebiveisTransacao(PDO $conexao, int $idTransacao)
    {
        $sqlSituacaoSplit = RecebiveisConsultas::sqlSelectSituacaoSplit();
        $consulta = "SELECT
                        transacao_financeira_split.id,
                        $sqlSituacaoSplit,
                        transacao_financeira_split.valor,
                        SUM(COALESCE(lancamentos_financeiros_recebiveis.valor_pago, 0)) valor_pago,
                        (SELECT conta_bancaria_colaboradores.nome_titular FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = transacao_financeira_split.id_colaborador LIMIT 1) recebedor,
                        transacao_financeira_split.id_colaborador id_conta,
                        transacao_financeira_split.id_transferencia
                    FROM transacao_financeira_split
                    INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeira_split.id_transacao
                    LEFT JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_transacao = transacao_financeiras.id AND lancamentos_financeiros_recebiveis.id_recebedor = transacao_financeira_split.id_colaborador
                    WHERE transacao_financeira_split.id_transacao = ?
                    GROUP BY transacao_financeira_split.id
                    ORDER BY transacao_financeira_split.id DESC";
        $stmt = $conexao->prepare($consulta);
        $stmt->execute([$idTransacao]);
        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    //     public static function  buscaTransacaoID(PDO $conexao, int $idTransacao)
    //     {
    //         $consulta = "SELECT transacao_financeiras.status,
    //                             (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id =  transacao_financeiras.pagador) pagador,
    //                             transacao_financeiras.id,
    //                             COALESCE((SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id =  pedido_item_meu_look.id_colaborador_compartilhador_link),'Sem autor do link') autor_link,
    //                             transacao_financeiras.cod_transacao,
    //                             transacao_financeiras.valor_total,
    //                             transacao_financeiras.valor_credito,
    //                             transacao_financeiras.valor_acrescimo,
    //                             transacao_financeiras.valor_comissao_fornecedor,
    //                             transacao_financeiras.valor_liquido,
    //                             transacao_financeiras.valor_itens,
    //                             transacao_financeiras.valor_taxas,
    //                             transacao_financeiras.juros_pago_split,
    //                             transacao_financeiras.numero_transacao,
    //                             transacao_financeiras.metodos_pagamentos_disponiveis,
    //                             transacao_financeiras.valor_comissao_fornecedor,
    //                             transacao_financeiras.responsavel,
    //                             (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id =  transacao_financeiras.responsavel) responsavel,
    //                             transacao_financeiras.metodo_pagamento,
    //                             transacao_financeiras.numero_parcelas,
    //                             transacao_financeiras.url_boleto,
    //                             transacao_financeiras.origem_transacao,
    //                             transacao_financeiras.qrcode_pix,
    //                             transacao_financeiras.qrcode_text_pix,
    //                             transacao_financeiras.emissor_transacao,
    //                             transacao_financeiras.pagador as id_pagador,
    //                             DATE_FORMAT(transacao_financeiras.data_criacao,'%d/%m/%Y %H:%i:%s') data1,
    //                             DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y %H:%i:%s') data_atualizacao
    //                                 FROM transacao_financeiras
    //                                 left JOIN pedido_item_meu_look ON pedido_item_meu_look.id_transacao = transacao_financeiras.id
    //                                     WHERE transacao_financeiras.id = $idTransacao
    //                                     GROUP BY transacao_financeiras.id";
    //         $stmt = $conexao->prepare($consulta);
    //         $stmt->execute();
    //         $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
    //         return $retorno;
    //     }

    //    public static function buscaLancamentosFaturamento(PDO $conexao, int $idFaturamento)
    //    {
    //        $consulta = " SELECT  lancamento_financeiro.id,
    //                            lancamento_financeiro.tipo,
    //                            lancamento_financeiro.situacao,
    //                            lancamento_financeiro.origem,
    //                            (
    //                                SELECT colaboradores.razao_social
    //                                    FROM colaboradores
    //                                        WHERE colaboradores.id = lancamento_financeiro.id_colaborador
    //                            )colaborador,
    //                            DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') data_emissao,
    //                            DATE_FORMAT(lancamento_financeiro.data_vencimento,'%d/%m/%Y %H:%i:%s')data_vencimento,
    //                            lancamento_financeiro.valor,
    //                            lancamento_financeiro.juros,
    //                            lancamento_financeiro.valor_total,
    //                            lancamento_financeiro.valor_pago,
    //                            lancamento_financeiro.transacao_origem,
    //                            lancamento_financeiro.id_pagador,
    //                            lancamento_financeiro.id_recebedor
    //                                FROM lancamento_financeiro
    //                                    WHERE lancamento_financeiro.transacao_origem IN
    //                                        (
    //                                            SELECT transacao_financeiras_faturamento.id_transacao
    //                                                FROM transacao_financeiras_faturamento
    //                                                    WHERE transacao_financeiras_faturamento.id_faturamento = {$idFaturamento}
    //                                        )";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //    public static function buscaSellersPago(PDO $conexao, int $idFaturamento)
    //    {
    //        $consulta = " SELECT    lancamento_financeiro.id,
    //                                lancamento_financeiro.situacao,
    //                                lancamento_financeiro.origem,
    //                                (
    //                                    SELECT colaboradores.razao_social
    //                                        FROM colaboradores
    //                                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
    //                                )colaborador,
    //                                lancamento_financeiro.valor_total,
    //                                lancamento_financeiro.valor_pago,
    //                                lancamento_financeiro.transacao_origem
    //                                FROM lancamento_financeiro
    //                                    WHERE lancamento_financeiro.transacao_origem IN
    //                                        (
    //                                            SELECT transacao_financeiras_faturamento.id_transacao
    //                                                FROM transacao_financeiras_faturamento
    //                                                    WHERE transacao_financeiras_faturamento.id_faturamento = {$idFaturamento}
    //                                        ) AND lancamento_financeiro.origem = 'PF'";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //    public static function buscaCredito(PDO $conexao, int $idFaturamento)
    //    {
    //        $consulta = " SELECT    lancamento_financeiro.id,
    //                                lancamento_financeiro.situacao,
    //                                lancamento_financeiro.origem,
    //                                (
    //                                    SELECT colaboradores.razao_social
    //                                        FROM colaboradores
    //                                            WHERE colaboradores.id = lancamento_financeiro.id_colaborador
    //                                )colaborador,
    //                               lancamento_financeiro.valor_total,
    //                                lancamento_financeiro.valor_pago,
    //                                lancamento_financeiro.transacao_origem
    //                                FROM lancamento_financeiro
    //                                    WHERE lancamento_financeiro.transacao_origem IN
    //                                        (
    //                                            SELECT transacao_financeiras_faturamento.id_transacao
    //                                                FROM transacao_financeiras_faturamento
    //                                                    WHERE transacao_financeiras_faturamento.id_faturamento = {$idFaturamento}
    //                                        ) AND lancamento_financeiro.origem = 'SC' ";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //    public static function buscaProdutosTransacao(PDO $conexao, int $idTransacao)
    //    {
    //        $consulta = "SELECT
    //                        transacao_financeiras_produtos_itens.id_produto,
    //                        (
    //                            SELECT produtos.descricao
    //                            FROM produtos
    //                            WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto
    //                        )nome_produto,
    //                        transacao_financeiras_produtos_itens.id_fornecedor,
    //                        (
    //                            SELECT colaboradores.razao_social
    //                            FROM colaboradores
    //                            WHERE colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
    //                        )seller,
    //                        transacao_financeiras_produtos_itens.tipo_item,
    //                        transacao_financeiras_produtos_itens.nome_tamanho tamanho,
    //                        transacao_financeiras_produtos_itens.preco,
    //                        transacao_financeiras_produtos_itens.valor_custo_produto,
    //                        transacao_financeiras_produtos_itens.comissao_fornecedor,
    //                        transacao_financeiras_produtos_itens.uuid_produto,
    //                        transacao_financeiras_produtos_itens.id_transacao,
    //                        transacao_financeiras_faturamento.id_faturamento
    //                    FROM transacao_financeiras_produtos_itens
    //                    left join transacao_financeiras_faturamento ON transacao_financeiras_faturamento.id_transacao = transacao_financeiras_produtos_itens.id_transacao
    //                    WHERE transacao_financeiras_produtos_itens.id_transacao = {$idTransacao}
    //                        AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
    //                    GROUP BY transacao_financeiras_produtos_itens.uuid_produto, transacao_financeiras_produtos_itens.id
    //                    ORDER BY transacao_financeiras_faturamento.id_faturamento";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }

    // public static function buscaProdutosTransacaoFornecedor(PDO $conexao, int $idTransacao, int $colaborador)
    // {
    //     $consulta = "SELECT transacao_financeiras_produtos_itens.id_produto,
    //                         (
    //                             SELECT produtos.descricao
    //                                 FROM produtos
    //                                     WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto
    //                         )nome_produto,
    //                         transacao_financeiras_produtos_itens.id_fornecedor,
    //                         (
    //                             SELECT colaboradores.razao_social
    //                                 FROM colaboradores
    //                                     WHERE colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
    //                         )seller,
    //                         transacao_financeiras_produtos_itens.situacao,
    //                         transacao_financeiras_produtos_itens.tipo_item,
    //                         transacao_financeiras_produtos_itens.tamanho,
    //                         (
    //                             SELECT estoque_grade.nome_tamanho
    //                             FROM estoque_grade
    //                             WHERE estoque_grade.id_produto = transacao_financeiras_produtos_itens.id_produto
    //                             AND estoque_grade.tamanho = transacao_financeiras_produtos_itens.tamanho
    //                             LIMIT 1
    //                         ) nome_tamanho,
    //                         transacao_financeiras_produtos_itens.preco,
    //                         transacao_financeiras_produtos_itens.valor_custo_produto,
    //                         transacao_financeiras_produtos_itens.comissao_fornecedor,
    //                         transacao_financeiras_produtos_itens.uuid,
    //                         transacao_financeiras_produtos_itens.id_transacao
    //                         FROM transacao_financeiras_produtos_itens
    //                             WHERE transacao_financeiras_produtos_itens.id_transacao = {$idTransacao}
    //                             AND transacao_financeiras_produtos_itens.id_fornecedor = {$colaborador}";
    //     $stmt = $conexao->prepare($consulta);
    //     $stmt->execute();
    //     $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $retorno;
    // }
    //
    //    public static function buscaProdutosFaturamento(PDO $conexao, int $idFaturamento)
    //    {
    //       $consulta = "SELECT transacao_financeiras_produtos_itens.id_produto,
    //                            (
    //                                SELECT produtos.descricao
    //                                    FROM produtos
    //                                        WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto
    //                            )nome_produto,
    //                            (SELECT faturamento_item.id_cliente FROM faturamento_item WHERE faturamento_item.id_faturamento = {$idFaturamento} AND faturamento_item.uuid = transacao_financeiras_produtos_itens.uuid GROUP BY faturamento_item.id_cliente) id_cliente,
    //                            transacao_financeiras_produtos_itens.id_fornecedor,
    //                            (
    //                                SELECT colaboradores.razao_social
    //                                    FROM colaboradores
    //                                        WHERE colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
    //                            )seller,
    //                            transacao_financeiras_produtos_itens.tipo_item,
    //                            transacao_financeiras_produtos_itens.nome_tamanho,
    //                            transacao_financeiras_produtos_itens.preco,
    //                            transacao_financeiras_produtos_itens.valor_custo_produto,
    //                            transacao_financeiras_produtos_itens.comissao_fornecedor,
    //                            transacao_financeiras_produtos_itens.uuid,
    //                            COALESCE((SELECT faturamento_item.situacao FROM faturamento_item WHERE faturamento_item.id_faturamento = {$idFaturamento} AND faturamento_item.uuid = transacao_financeiras_produtos_itens.uuid),transacao_financeiras_produtos_itens.situacao)situacao,
    //                            transacao_financeiras_produtos_itens.id_transacao
    //                            FROM transacao_financeiras_produtos_itens
    //                                WHERE transacao_financeiras_produtos_itens.id_transacao  IN
    //                                        (
    //                                            SELECT transacao_financeiras_faturamento.id_transacao
    //                                                FROM transacao_financeiras_faturamento
    //                                                    WHERE transacao_financeiras_faturamento.id_faturamento = {$idFaturamento}
    //                                        )";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //

    //    public static function buscaLancamentoTransacao(PDO $conexao, int $idTransacao)
    //    {
    //        $consulta = "SELECT
    //            'NA' tipo_lancamento,
    //            lancamento_financeiro.id,
    //            lancamento_financeiro.valor,
    //            lancamento_financeiro.situacao,
    //            lancamento_financeiro.origem,
    //            lancamento_financeiro.id_colaborador,
    //            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador)colaborador,
    //            DATE_FORMAT(lancamento_financeiro.data_vencimento, '%d/%m/%Y') data_vencimento
    //            FROM lancamento_financeiro
    //                WHERE lancamento_financeiro.transacao_origem=$idTransacao
    //        UNION ALL
    //        SELECT
    //            'PE',
    //            lancamento_financeiro_pendente.id,
    //            lancamento_financeiro_pendente.valor,
    //            lancamento_financeiro_pendente.situacao,
    //            lancamento_financeiro_pendente.origem,
    //            lancamento_financeiro_pendente.id_colaborador,
    //            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro_pendente.id_colaborador)colaborador,
    //            DATE_FORMAT(lancamento_financeiro_pendente.data_vencimento, '%d/%m/%Y') data_vencimento
    //            FROM lancamento_financeiro_pendente
    //                WHERE lancamento_financeiro_pendente.transacao_origem=$idTransacao";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }

    public static function retornaTransacaoProduto(PDO $conexao, string $uuid)
    {
        $consulta = "SELECT transacao_financeiras_produtos_itens.id_transacao
                        FROM transacao_financeiras_produtos_itens
                            WHERE transacao_financeiras_produtos_itens.uuid = '{$uuid}' ";
        $stmt = $conexao->prepare($consulta);
        $stmt->execute();
        $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
        return $retorno['id_transacao'];
    }
    public static function buscaUuid(PDO $conexao, int $faturamento)
    {
        $consulta = "SELECT lancamento_financeiro.numero_documento
                            FROM lancamento_financeiro
                                WHERE lancamento_financeiro.pedido_origem = {$faturamento}
                                    AND lancamento_financeiro.tipo = 'R'
                                    AND lancamento_financeiro.origem = 'CP' LIMIT 1";
        $stmt = $conexao->prepare($consulta);
        $stmt->execute();
        $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
        return $retorno['numero_documento'];
    }

    //
    //    public static function buscaLancamentoTransacaoOrigem(PDO $conexao, int $idTransacao, string $origem)
    //    {
    //        $consulta = "SELECT  lancamento_financeiro.id,
    //                            lancamento_financeiro.valor,
    //                            lancamento_financeiro.tipo,
    //                             (
    //                                    CASE WHEN  lancamento_financeiro.situacao = 1
    //                                        THEN 'Aberto'
    //                                        ELSE 'Pago'
    //                                    END
    //                                )situacao,
    //                            lancamento_financeiro.origem,
    //                            lancamento_financeiro.id_colaborador,
    //                            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro.id_colaborador)colaborador
    //                                FROM lancamento_financeiro
    //                                    WHERE transacao_origem={$idTransacao} AND lancamento_financeiro.origem='{$origem}'";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //

    //
    //    public static function buscaLancamentoPendenteTransacao(PDO $conexao, int $idTransacao)
    //    {
    //        $consulta = "SELECT  lancamento_financeiro_pendente.id,
    //                            lancamento_financeiro_pendente.tipo,
    //                            lancamento_financeiro_pendente.valor,
    //                             (
    //                                    CASE WHEN  lancamento_financeiro_pendente.situacao = 1
    //                                        THEN 'Aberto'
    //                                        ELSE 'Pago'
    //                                    END
    //                                )situacao,
    //                            lancamento_financeiro_pendente.origem,
    //                            lancamento_financeiro_pendente.id_colaborador,
    //                            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro_pendente.id_colaborador)colaborador
    //                                FROM lancamento_financeiro_pendente
    //                                    WHERE transacao_origem={$idTransacao}";
    //        $stmt = $conexao->prepare($consulta);
    //        $stmt->execute();
    //        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }
    //

    public static function buscaTransacaoFiltros(
        PDO $conexao,
        int $id,
        string $pagador,
        string $codTransacao,
        int $entrega,
        string $pagamento,
        string $status,
        string $data1,
        string $data2
    ): array {
        $sql = '';
        $bind = [];

        // $filtros = json_decode($filtros);
        // foreach ($filtros as $key => $chave) :
        //     foreach ($chave as $colum => $value) :
        //         if ($colum == 'tipo_item') {
        //             $sql .= " AND transacao_financeiras.id IN (SELECT transacao_financeiras_produtos_itens.id_transacao FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.{$colum} = 'AC')";
        //         } else {
        //             $sql .= ($colum != 'id_faturamento'
        //                 ? " AND transacao_financeiras.{$colum} = {$value}"
        //                 : " AND transacao_financeiras.id IN (SELECT transacao_financeiras_faturamento.id_transacao FROM transacao_financeiras_faturamento WHERE transacao_financeiras_faturamento.{$colum} = {$value})");
        //         }

        //     endforeach;
        // endforeach;
        if ($data1 && $data2) {
            $sql .= ' AND DATE(transacao_financeiras.data_criacao) BETWEEN :data1 AND :data2 ';
            $bind[':data1'] = $data1;
            $bind[':data2'] = $data2;
        } elseif ($data1) {
            $sql .= ' AND DATE(transacao_financeiras.data_criacao) >= :data1';
            $bind[':data1'] = $data1;
        } elseif ($data2) {
            $sql .= ' AND DATE(transacao_financeiras.data_criacao) <= :data2';
            $bind[':data2'] = $data2;
        }

        if ($id) {
            $sql .= ' AND transacao_financeiras.id = :id';
            $bind[':id'] = $id;
        }

        if ($pagador) {
            $sql .= ' AND colaboradores.razao_social REGEXP :pagador';
            $bind[':pagador'] = $pagador;
        }

        if ($codTransacao) {
            $sql .= ' AND transacao_financeiras.cod_transacao = :cod_transacao';
            $bind[':cod_transacao'] = $codTransacao;
        }

        if ($entrega) {
            $sql .= ' AND entregas_faturamento_item.id_entrega = :entrega';
            $bind[':entrega'] = $entrega;
        }

        if ($pagamento) {
            $sql .= ' AND transacao_financeiras.metodo_pagamento = :pagamento';
            $bind[':pagamento'] = $pagamento;
        }

        if ($status) {
            $sql .= ' AND transacao_financeiras.status = :status';
            $bind[':status'] = $status;
        }

        $stmt = $conexao->prepare("SELECT transacao_financeiras.id,
                            transacao_financeiras.cod_transacao,
                            colaboradores.razao_social pagador,
                            transacao_financeiras.status,
                            DATE_FORMAT(transacao_financeiras.data_atualizacao, '%d/%m/%Y')data_atualizacao,
                            transacao_financeiras.valor_liquido,
                            transacao_financeiras.valor_credito,
                            transacao_financeiras.metodo_pagamento,
                            (
                                SELECT colaboradores.razao_social
                                    FROM colaboradores
                                        WHERE colaboradores.id = transacao_financeiras.responsavel
                            ) responsavel,
                            transacao_financeiras.metodo_pagamento
                                FROM transacao_financeiras
                                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.id_transacao = transacao_financeiras.id
                                INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
                                WHERE transacao_financeiras.metodo_pagamento <> 'CR'
                                $sql
                                GROUP BY transacao_financeiras.id
                                ORDER BY transacao_financeiras.id DESC");
        $stmt->execute($bind);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }
    public static function buscaTransacao(PDO $conexao)
    {
        $consulta = "SELECT transacao_financeiras.id,
                            transacao_financeiras.cod_transacao,
                            transacao_financeiras.status,
                            transacao_financeiras.valor_liquido,
                            transacao_financeiras.valor_credito,
                            (
                                SELECT colaboradores.razao_social
                                    FROM colaboradores
                                        WHERE colaboradores.id = transacao_financeiras.pagador
                            )pagador,
                            (
                                SELECT colaboradores.razao_social
                                    FROM colaboradores
                                        WHERE colaboradores.id = transacao_financeiras.responsavel
                            )responsavel,
                            transacao_financeiras.metodo_pagamento
                                FROM transacao_financeiras WHERE transacao_financeiras.metodo_pagamento <> 'CR' ORDER BY transacao_financeiras.id DESC";
        $stmt = $conexao->prepare($consulta);
        $stmt->execute();
        $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $retorno;
    }

    //
    //    public static function buscaInfoTransacaoParaPagamento($id_transacao)
    //    {
    //        return Conexao::criarConexao()->query('SELECT valor_liquido, id FROM transacao_financeiras WHERE id = ' . $id_transacao)->fetch(PDO::FETCH_ASSOC);
    //    }
    //

    public static function filtroBuscaTransacaoMarketplace(PDO $conexao, string $pesquisa): array
    {
        $filtro = '';
        if (!empty($pesquisa)) {
            $filtro = " AND ( transacao_financeiras.id REGEXP :pesquisa
                OR colaboradores.razao_social REGEXP :pesquisa ) ";
        }
        $sql = "SELECT
            transacao_financeiras.id AS `id_transacao`,
            colaboradores.razao_social,
            transacao_financeiras.pagador AS `id_cliente`,
            COUNT(DISTINCT transacao_financeiras_produtos_itens.id) AS `total`,
            transacao_financeiras.valor_liquido,
            transacao_financeiras.status AS `situacao`,
            transacao_financeiras.metodo_pagamento,
            transacao_financeiras.valor_itens,
            transacao_financeiras.valor_comissao_fornecedor,
            transacao_financeiras.juros_pago_split,
            transacao_financeiras.valor_credito,
            transacao_financeiras.valor_acrescimo,
            transacao_financeiras.url_boleto,
            DATE_FORMAT(transacao_financeiras.data_criacao,'%d/%m/%Y %H:%i:%s') data_criacao,
            transacao_financeiras.cod_transacao
        FROM transacao_financeiras
        INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
        INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
        WHERE transacao_financeiras.status = 'PA'
        $filtro
        GROUP BY transacao_financeiras.id
        ORDER BY transacao_financeiras.id DESC LIMIT 500";
        $stmt = $conexao->prepare($sql);
        if (!empty($pesquisa)) {
            $stmt->bindValue(':pesquisa', $pesquisa, PDO::PARAM_STR);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $dados;
    }

    //    public static function buscaTransacaoMarketingPlace()
    //    {
    //        return Conexao::criarConexao()->query(
    //            "SELECT COALESCE((select transacao_financeiras_faturamento.id_faturamento
    //                                                            FROM transacao_financeiras_faturamento
    //                                                            WHERE transacao_financeiras_faturamento.id_transacao = transacao_financeiras.id),'') id_faturamento,
    //                                                    transacao_financeiras.id id_transacao,
    //                                                    colaboradores.razao_social cliente,
    //                                                    COUNT(DISTINCT transacao_financeiras_produtos_itens.id) total,
    //                                                    transacao_financeiras.valor_liquido,
    //                                                    transacao_financeiras.pagador id_cliente,
    //                                                    transacao_financeiras.status situacao,
    //                                                    CASE transacao_financeiras.metodo_pagamento
    //                                                    WHEN 'CA' THEN 1
    //                                                    WHEN 'BL' THEN 3
    //                                                    WHEN 'DE' THEN 2
    //                                                    ELSE 0
    //                                                    END pagamento,
    //                                                    transacao_financeiras.valor_itens valor_produtos,
    //                                                    transacao_financeiras.valor_comissao_fornecedor,
    //                                                    transacao_financeiras.juros_pago_split,
    //                                                    transacao_financeiras.valor_credito credito,
    //                                                    transacao_financeiras.valor_acrescimo,
    //                                                    transacao_financeiras.url_boleto boleto,
    //                                                    DATE_FORMAT(transacao_financeiras.data_criacao,'%d/%m/%Y %H:%i:%s') data_criacao,
    //                                                    transacao_financeiras.cod_transacao
    //                                                FROM transacao_financeiras
    //                                                    INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
    //                                                    INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
    //                                                WHERE transacao_financeiras.status = 'PA'
    //	                                                AND EXISTS(SELECT 1 FROM lancamento_financeiro WHERE lancamento_financeiro.transacao_origem = transacao_financeiras.id AND lancamento_financeiro.origem IN ('PF','SC'))
    //                                                GROUP BY transacao_financeiras.id
    //                                                ORDER BY transacao_financeiras.id DESC LIMIT 1000"
    //        )->fetchAll(PDO::FETCH_ASSOC);
    //    }

    /**
     * @param BL|CA|DE $pagamento
     * @param string $pesquisa
     * @return array
     */
    public static function buscaTransacoesPendentes(PDO $conexao, string $pagamento, string $pesquisa): array
    {
        $filtro = '';
        $filtro .= $pagamento ? "AND (transacao_financeiras.metodo_pagamento = ':pagamento')" : '';
        $filtro .= $pesquisa
            ? 'AND (transacao_financeiras.id REGEXP :pesquisa OR colaboradores.razao_social REGEXP :pesquisa)'
            : '';
        $sql = $conexao->prepare("SELECT
		transacao_financeiras.id,
	    transacao_financeiras.cod_transacao,
	    colaboradores.razao_social pagador,
	    DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i:%s') data_criacao,
	    transacao_financeiras.origem_transacao tipo_item,
	    transacao_financeiras.url_boleto,
	    transacao_financeiras.valor_total,
	    transacao_financeiras.valor_credito,
	    transacao_financeiras.valor_acrescimo,
	    transacao_financeiras.valor_comissao_fornecedor,
	    transacao_financeiras.valor_liquido,
	    transacao_financeiras.valor_itens,
	    transacao_financeiras.valor_taxas,
	    transacao_financeiras.metodo_pagamento
	FROM transacao_financeiras
	INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
	WHERE transacao_financeiras.status = 'PE'
        $filtro
	GROUP BY transacao_financeiras.id
	ORDER BY id DESC");
        $sql->bindParam(':pagamento', $pagamento, PDO::PARAM_INT);
        $sql->bindParam(':pesquisa', $pesquisa);
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }

    // public static function  buscaTransacaoAbertas(PDO $conexao = NULL, array $filtros)
    // {
    //     $conexao = $conexao ?? Conexao::criarConexao();
    //     $sql = "SELECT
    //                 CASE transacao_financeiras.status
    //                     WHEN 'PA' THEN 'Pago'
    //                     WHEN 'PE' THEN 'Pendente'
    //                     WHEN 'CA' THEN 'Cancelado'
    //                     ELSE 'Idefinido'
    //                 END Situcao,
    //                 IF(transacao_financeiras.metodo_pagamento = 'PX', 'PIX','BOLETO') metodo_pagamento,
    //                 transacao_financeiras.id id_transacao,
    //                 colaboradores.razao_social pagador,
    //                 IF(colaboradores.regime = 1,colaboradores.cnpj,colaboradores.cpf) documento,
    //                 CONCAT(colaboradores.telefone,' / ',colaboradores.telefone2) telefone,
    //                 colaboradores.email,
    //                 transacao_financeiras.valor_liquido valor,
    //                 transacao_financeiras.numero_parcelas,
    //                 transacao_financeiras.url_boleto,
    //                 transacao_financeiras.barcode cod_barras,
    //                 transacao_financeiras.qrcode_pix,
    //                 transacao_financeiras.qrcode_text_pix,
    //                 transacao_financeiras.url_fatura,
    //                 DATE_FORMAT(transacao_financeiras.data_criacao,'%d/%m/%Y %H:%i:%s') data_criacao,
    //                 DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y %H:%i:%s') data_atualizacao
    //             FROM transacao_financeiras
    //                 INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras.pagador
    //             WHERE transacao_financeiras.metodo_pagamento IN (".$filtros['metodo'].")";
    //     if ($filtros['dataIni'])
    //         $sql .= " AND DATE(transacao_financeiras.data_atualizacao) >= '" . $filtros['dataIni'] . "'";
    //     if ($filtros['dataFim'])
    //         $sql .= " AND DATE(transacao_financeiras.data_atualizacao) <= '" . $filtros['dataFim'] . "'";
    //     if ($filtros['situacao'])
    //         $sql .= " AND transacao_financeiras.status = '" . $filtros['situacao'] . "'";

    //     $sql .= " ORDER BY  transacao_financeiras.id DESC LIMIT 500";
    //     $resposta = $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    //     return $resposta;
    // }

    //
    //    public static function  buscaIdtransacaoComCodTransacao(string $codTransacao)
    //    {
    //        return Conexao::criarConexao()->query("SELECT transacao_financeiras.id id_transacao FROM transacao_financeiras WHERE transacao_financeiras.cod_transacao = {$codTransacao}")->fetch(PDO::FETCH_ASSOC);
    //    }
    //

    public static function buscaDepositoAberto(PDO $conexao, int $idCliente)
    {
        $sql = "SELECT transacao_financeiras.pagador,
                        transacao_financeiras.qrcode_pix,
                        transacao_financeiras.qrcode_text_pix,
                        transacao_financeiras.metodo_pagamento,
                        transacao_financeiras.status,
                        transacao_financeiras.valor_total,
                        transacao_financeiras.url_boleto,
                        transacao_financeiras.id
                            FROM transacao_financeiras
                                WHERE transacao_financeiras.pagador={$idCliente}
                                    AND transacao_financeiras.origem_transacao = 'MC'
                                    AND transacao_financeiras.status = 'PE'
                                    AND transacao_financeiras.metodo_pagamento ='PX' ";
        return $conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscaProdutosPedidoMobileStockSemEntrega(): array
    {
        $where = '';

        $caseSituacao = self::sqlCaseSituacao(DB::getPdo());
        $caseSituacaoDatas = self::sqlCaseSituacaoDatas();

        $query = "SELECT entregas.id_tipo_frete
            FROM entregas
            WHERE entregas.id_cliente = :id_cliente
                AND entregas.id_tipo_frete IN (1, 2)
                AND entregas.situacao = 'AB';";

        $idColaborador = Auth::user()->id_colaborador;
        $emAberto = DB::selectColumns($query, [':id_cliente' => $idColaborador]);

        if (!empty($emAberto)) {
            [$bind, $valores] = ConversorArray::criaBindValues($emAberto, 'id_tipo_frete');
            $where = " AND tipo_frete.id NOT IN ($bind) ";
        }
        $valores[':id_cliente'] = $idColaborador;

        $query = "SELECT
                tipo_frete.tipo_ponto,
                tipo_frete.id AS `id_tipo_frete`,
                tipo_frete.nome AS `nome_tipo_frete`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'id_transacao', transacao_financeiras.id,
                        'valor_total', transacao_financeiras.valor_total,
                        'data_criacao', DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %H:%i'),
                        'status', transacao_financeiras.status,
                        'id_produto', transacao_financeiras_produtos_itens.id_produto,
                        'nome_tamanho', transacao_financeiras_produtos_itens.nome_tamanho,
                        'preco', transacao_financeiras_produtos_itens.preco,
                        'uuid_produto', transacao_financeiras_produtos_itens.uuid_produto,
                        'ja_estornado', FALSE,
                        'situacao', JSON_EXTRACT($caseSituacao, '$.situacao'),
                        'situacao_datas', $caseSituacaoDatas,
                        'descricao', CONCAT(
                            COALESCE(produtos.nome_comercial, produtos.descricao),
                            IF(
                                COALESCE(produtos.cores, '') <> '',
                                CONCAT(' ', produtos.cores),
                                ''
                            )
                        ),
                        'foto', (
                            SELECT produtos_foto.caminho
                            FROM produtos_foto
                            WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                            ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                            LIMIT 1
                        ),
                        'json_consumidor_final', COALESCE(logistica_item.observacao, pedido_item.observacao)
                    ) ORDER BY COALESCE(logistica_item.data_atualizacao, transacao_financeiras_produtos_itens.data_atualizacao) ASC),
                    ']'
                ) AS `json_transacoes`,
                (
                    SELECT
                        transacao_financeiras_metadados.valor
                    FROM transacao_financeiras_metadados
                    WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    LIMIT 1
                ) AS `json_endereco_transacao`
            FROM transacao_financeiras
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                AND transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
                AND transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
            INNER JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
            LEFT JOIN logistica_item ON logistica_item.id_cliente = :id_cliente
                AND logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN pedido_item ON pedido_item.situacao IN (3, 'FR')
                AND pedido_item.uuid = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            WHERE transacao_financeiras.origem_transacao = 'MP'
                AND transacao_financeiras.status IN ('PE', 'PA')
                AND transacao_financeiras.pagador = :id_cliente
                AND DATE(transacao_financeiras_metadados.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                AND logistica_item.id_entrega IS NULL
                AND (
                    logistica_item.id IS NOT NULL
                    OR pedido_item.id_cliente IS NOT NULL
                )
                $where
            GROUP BY transacao_financeiras_metadados.valor
            ORDER BY transacao_financeiras.id DESC;";

        $pedidos = DB::select($query, $valores);

        $pedidos = array_map(function (array $pedido): array {
            $transacoes = [];
            foreach ($pedido['transacoes'] as $transacao) {
                [
                    'data_criacao' => $transacoes[$transacao['id_transacao']]['data_criacao'],
                    'id_transacao' => $transacoes[$transacao['id_transacao']]['id_transacao'],
                    'status' => $transacoes[$transacao['id_transacao']]['status'],
                    'valor_total' => $transacoes[$transacao['id_transacao']]['valor_total'],
                ] = $transacao;

                $transacoes[$transacao['id_transacao']]['produtos'][] = [
                    'descricao' => $transacao['descricao'],
                    'foto' => $transacao['foto'],
                    'id_produto' => $transacao['id_produto'],
                    'ja_estornado' => $transacao['ja_estornado'],
                    'nome_tamanho' => $transacao['nome_tamanho'],
                    'preco' => $transacao['preco'],
                    'situacao' => $transacao['situacao'],
                    'situacao_datas' => $transacao['situacao_datas'],
                    'uuid_produto' => $transacao['uuid_produto'],
                    'entrega_cliente' => in_array(
                        $pedido['id_tipo_frete'],
                        explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE)
                    ),
                    'tipo_ponto' => $pedido['tipo_ponto'],
                    'consumidor_final' => $transacao['consumidor_final'],
                ];
            }
            $pedido['transacoes'] = array_values($transacoes);

            switch ($pedido['id_tipo_frete']) {
                case 2:
                    $pedido['tipo_entrega'] = 'Transportadora';
                    break;
                case 3:
                    $pedido['tipo_entrega'] = 'Retirada na Mobile Stock';
                    break;
                default:
                    if ($pedido['tipo_ponto'] === 'PM') {
                        $pedido['tipo_entrega'] = 'Entregador';
                    } else {
                        $pedido['tipo_entrega'] = 'Retirada no ponto ' . $pedido['nome_tipo_frete'];
                    }
            }

            return $pedido;
        }, $pedidos);

        return $pedidos;
    }
    public static function buscaProdutosPedidoMobileStockComEntrega(int $idEntrega): array
    {
        $caseSituacao = self::sqlCaseSituacao(DB::getPdo());
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $caseSituacaoDatas = self::sqlCaseSituacaoDatas();
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $idCliente = Auth::user()->id_colaborador;

        $tipoFrete = DB::selectOne(
            "SELECT
                entregas.situacao,
                entregas.id_tipo_frete,
                tipo_frete.id_colaborador,
                tipo_frete.nome,
                tipo_frete.tipo_ponto,
                colaboradores.foto_perfil,
                colaboradores.telefone,
                CONCAT(
                    colaboradores_enderecos.logradouro,',',
                    colaboradores_enderecos.numero,' - ',
                    colaboradores_enderecos.bairro,', ',
                    colaboradores_enderecos.cidade,' - ',
                    colaboradores_enderecos.uf
                ) AS `endereco`
            FROM entregas
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE entregas.id = :id_entrega;",
            ['id_entrega' => $idEntrega]
        );
        if (empty($tipoFrete)) {
            throw new NotFoundHttpException('Entrega não encontrada');
        }

        switch ($tipoFrete['id_tipo_frete']) {
            case 2:
                $tipoFrete['tipo_entrega'] = 'Transportadora';
                break;
            case 3:
                $tipoFrete['tipo_entrega'] = 'Retirada na Mobile Stock';
                break;
            default:
                if ($tipoFrete['tipo_ponto'] === 'PM') {
                    $tipoFrete['tipo_entrega'] = 'Entregador';
                } else {
                    $tipoFrete['tipo_entrega'] = 'Ponto retirada';
                }
        }

        $transacoes = DB::select(
            "SELECT
                logistica_item.id_transacao,
                DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                transacao_financeiras.valor_total,
                transacao_financeiras.status,
                (
                    SELECT correios_atendimento.numeroColeta
                    FROM correios_atendimento
                    WHERE correios_atendimento.id_cliente = logistica_item.id_cliente
                        AND correios_atendimento.status = 'A'
                        AND correios_atendimento.prazo > NOW()
                    ORDER BY correios_atendimento.data_verificacao DESC
                    LIMIT 1
                ) ultimo_numero_coleta,
                CONCAT(
                    '[',
                    GROUP_CONCAT(DISTINCT JSON_OBJECT(
                        'id_produto', logistica_item.id_produto,
                        'nome_tamanho', logistica_item.nome_tamanho,
                        'uuid_produto', logistica_item.uuid_produto,
                        'preco', transacao_financeiras_produtos_itens.preco,
                        'situacao', JSON_EXTRACT($caseSituacao, '$.situacao'),
                        'situacao_datas', $caseSituacaoDatas,
                        'ja_estornado', logistica_item.situacao > $situacao,
                        'situacao_devolucao', CASE
                                                WHEN logistica_item.situacao = 'DF' THEN 'DEFEITO'
                                                WHEN logistica_item.situacao = 'DE' THEN 'NORMAL'
                                            END,
                        'data_atualizacao', DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y %H:%i'),
                        'em_processo_troca', troca_fila_solicitacoes.situacao = 'APROVADO' AND logistica_item.situacao <= $situacao,
                        'consumidor_final', logistica_item.observacao,
                        'descricao', CONCAT_WS(
                            ' ',
                            COALESCE(produtos.nome_comercial, produtos.descricao, ''),
                            produtos.cores
                        ),
                        'foto', (
                            SELECT produtos_foto.caminho
                            FROM produtos_foto
                            WHERE produtos_foto.id = logistica_item.id_produto
                            ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                            LIMIT 1
                        ),
                        'tipo_ponto', tipo_frete.tipo_ponto,
                        'bool_entrega_cliente', tipo_frete.id IN ($idTipoFreteEntregaCliente)
                    ) ORDER BY logistica_item.data_atualizacao ASC),
                    ']'
                ) AS `json_produtos`
            FROM logistica_item
            INNER JOIN produtos ON produtos.id = logistica_item.id_produto
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
                AND transacao_financeiras.origem_transacao = 'MP'
            INNER JOIN transacao_financeiras_produtos_itens ON
                transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto AND
                transacao_financeiras_produtos_itens.tipo_item IN ('PR','RF')
            INNER JOIN entregas ON entregas.id = logistica_item.id_entrega
            INNER JOIN entregas_faturamento_item ON
                entregas_faturamento_item.id_entrega = entregas.id AND
                entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = logistica_item.uuid_produto
            WHERE logistica_item.id_cliente = :id_cliente
                AND logistica_item.id_entrega = :id_entrega
            GROUP BY logistica_item.id_transacao
            ORDER BY logistica_item.id_transacao DESC;",
            ['id_cliente' => $idCliente, 'id_entrega' => $idEntrega]
        );
        $transacoes = array_map(function (array $transacao): array {
            $transacao['produtos'] = array_map(function (array $produto): array {
                $produto['consumidor_final'] = json_decode($produto['consumidor_final'], true);

                return $produto;
            }, $transacao['produtos']);

            return $transacao;
        }, $transacoes);

        $transacoesPromissoras = [];
        if ($tipoFrete['situacao'] === 'AB' && in_array($tipoFrete['id_tipo_frete'], [1, 2])) {
            $transacoesPromissoras = DB::select(
                "SELECT
                    transacao_financeiras_produtos_itens.id_transacao,
                    transacao_financeiras.status,
                    DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                    transacao_financeiras.valor_total,
                    CONCAT(
                        '[',
                        GROUP_CONCAT(DISTINCT JSON_OBJECT(
                            'id_produto', transacao_financeiras_produtos_itens.id_produto,
                            'nome_tamanho', transacao_financeiras_produtos_itens.nome_tamanho,
                            'uuid_produto', transacao_financeiras_produtos_itens.uuid_produto,
                            'preco', transacao_financeiras_produtos_itens.preco,
                            'situacao', (
                                CASE
                                    WHEN EXISTS(
                                        SELECT 1
                                        FROM logistica_item_data_alteracao
                                        WHERE logistica_item_data_alteracao.situacao_nova = 'RE'
                                            AND logistica_item_data_alteracao.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                                    ) THEN 'CANCELADO'
                                    WHEN logistica_item.situacao = 'CO' THEN 'CONFERIDO'
                                    WHEN logistica_item.situacao = 'SE' THEN 'SEPARADO'
                                    WHEN transacao_financeiras.status = 'PA' THEN 'PAGO'
                                    WHEN transacao_financeiras.status = 'PE' THEN 'PENDENTE'
                                END
                            ),
                            'ja_estornado', FALSE,
                            'descricao', CONCAT_WS(
                                ' ',
                                COALESCE(produtos.nome_comercial, produtos.descricao, ''),
                                produtos.cores
                            ),
                            'foto', (
                                SELECT produtos_foto.caminho
                                FROM produtos_foto
                                WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto
                                ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                                LIMIT 1
                            )
                        ) ORDER BY logistica_item.data_atualizacao ASC),
                        ']'
                    ) AS `json_produtos`
                FROM transacao_financeiras
                INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                    AND transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF')
                INNER JOIN produtos ON produtos.id = transacao_financeiras_produtos_itens.id_produto
                INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                    AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
                AND transacao_financeiras_metadados.valor = :id_colaborador_tipo_frete
                LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                WHERE transacao_financeiras.origem_transacao = 'MP'
                    AND transacao_financeiras.pagador = :id_cliente
                    AND DATE(transacao_financeiras.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                    AND logistica_item.id_entrega IS NULL
                GROUP BY transacao_financeiras.id
                ORDER BY transacao_financeiras.id ASC;",
                ['id_cliente' => $idCliente, 'id_colaborador_tipo_frete' => $tipoFrete['id_colaborador']]
            );
        }
        unset($tipoFrete['situacao'], $tipoFrete['id_colaborador']);

        $retorno = [
            'frete' => $tipoFrete,
            'transacoes' => $transacoes,
            'transacoes_promissoras' => $transacoesPromissoras,
        ];

        return $retorno;
    }
    public static function buscaPedidosMobileStockSemEntrega(PDO $conexao, int $idCliente): array
    {
        $where = '';

        $sql = $conexao->prepare(
            "SELECT entregas.id_tipo_frete
            FROM entregas
            WHERE entregas.id_cliente = :id_cliente
                AND entregas.id_tipo_frete IN (1, 2)
                AND entregas.situacao = 'AB';"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $emAberto = $sql->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($emAberto)) {
            [$bind, $valores] = ConversorArray::criaBindValues(
                array_column($emAberto, 'id_tipo_frete'),
                'id_tipo_frete'
            );
            $where = " AND tipo_frete.id NOT IN ($bind) ";
        }

        $sql = $conexao->prepare(
            "SELECT
                COUNT(DISTINCT transacao_financeiras_produtos_itens.uuid_produto) AS `qtd_produtos`,
                SUM(
                    DISTINCT
                    (
                        SELECT transacao_financeiras_metadados.valor
                        FROM transacao_financeiras_metadados
                        WHERE transacao_financeiras_metadados.chave = 'VALOR_FRETE'
                            AND transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                    )
                ) AS `valor_frete`,
                SUM(IF (transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF'), transacao_financeiras_produtos_itens.preco, 0)) AS `valor_produtos`,
                EXISTS(
                    SELECT 1
                    FROM acompanhamento_temp
                    INNER JOIN transacao_financeiras_metadados ON
                        transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    WHERE acompanhamento_temp.id_cidade = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
                        AND acompanhamento_temp.id_tipo_frete = 3
                        AND acompanhamento_temp.id_destinatario = :id_cliente
                ) AS `possui_acompanhamento`,
                GROUP_CONCAT(DISTINCT(tipo_frete.id)) AS `existe_retirada`
            FROM transacao_financeiras
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                AND transacao_financeiras_produtos_itens.tipo_item IN ('FR', 'PR', 'RF')
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
                $where
            LEFT JOIN logistica_item ON logistica_item.id_cliente = :id_cliente
                AND logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN pedido_item ON pedido_item.situacao IN (3, 'FR')
                AND pedido_item.uuid = transacao_financeiras_produtos_itens.uuid_produto
            WHERE transacao_financeiras.origem_transacao = 'MP'
                AND transacao_financeiras.status IN ('PE', 'PA')
                AND transacao_financeiras.pagador = :id_cliente
                AND DATE(transacao_financeiras_metadados.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                AND logistica_item.id_entrega IS NULL
                AND (
                    logistica_item.id IS NOT NULL
                    OR pedido_item.id_cliente IS NOT NULL
                );"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        if (!empty($emAberto)) {
            foreach ($valores as $key => $valor) {
                $sql->bindValue($key, $valor, PDO::PARAM_INT);
            }
        }
        $sql->execute();
        $pedidos = $sql->fetch(PDO::FETCH_ASSOC);
        if ($pedidos['qtd_produtos'] < 1) {
            return [];
        }

        $pedidos['qtd_produtos'] = (int) $pedidos['qtd_produtos'];
        $pedidos['valor_frete'] = (float) $pedidos['valor_frete'];
        $pedidos['valor_produtos'] = (float) $pedidos['valor_produtos'];
        $pedidos['valor_total'] = (float) $pedidos['valor_frete'] + $pedidos['valor_produtos'];
        $pedidos['possui_acompanhamento'] = (bool) $pedidos['possui_acompanhamento'];
        $pedidos['existe_retirada'] = in_array(3, explode(',', $pedidos['existe_retirada']));

        return $pedidos;
    }

    public static function buscaPedidosComEntrega(int $pagina): array
    {
        $idCliente = Auth::user()->id_colaborador;
        $itensPorPag = 20;
        $offset = ($pagina - 1) * $itensPorPag;

        $caseSituacao = self::sqlCaseSituacao(DB::getPdo());
        $caseSituacaoDatas = self::sqlCaseSituacaoDatas();
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $pedidosSql = "SELECT
                logistica_item.id_entrega,
                DATE_FORMAT(logistica_item.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                COUNT(DISTINCT logistica_item.uuid_produto) AS `qtd_produtos`,
                SUM(
                    IF(transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF'),
                        transacao_financeiras_produtos_itens.preco,
                        0
                    )
                ) AS `valor_produtos`,
                COALESCE(
                    (
                        SELECT SUM(transacao_financeiras_metadados.valor)
                        FROM transacao_financeiras_metadados
                        WHERE
                            transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                            AND transacao_financeiras_metadados.chave = 'VALOR_FRETE'
                    ),
                    0
                ) AS `valor_frete`,
                $caseSituacao AS `json_situacoes`,
                $caseSituacaoDatas AS `json_situacao_datas`,
                tipo_frete.tipo_ponto,
                tipo_frete.id IN ( $idTipoFreteEntregaCliente ) AS `eh_entrega_cliente`,
                entregas_faturamento_item.nome_recebedor AS `recebedor`,
                SUM(
                    IF(transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF'),
                        transacao_financeiras_produtos_itens.preco,
                        0
                    )
                ) + COALESCE(
                    (
                        SELECT SUM(transacao_financeiras_metadados.valor)
                        FROM transacao_financeiras_metadados
                        WHERE
                            transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                            AND transacao_financeiras_metadados.chave = 'VALOR_FRETE'
                    ),
                    0
                ) AS `valor_total`
            FROM logistica_item
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
            INNER JOIN entregas ON entregas.id = logistica_item.id_entrega
            INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                AND entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
            INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = logistica_item.uuid_produto
            WHERE
                logistica_item.id_cliente = :idCliente
                AND IF(entregas.situacao >= 2,
                    entregas_faturamento_item.origem = 'MS',
                    TRUE
                )
                AND logistica_item.id_entrega <> 40261
            GROUP BY logistica_item.id_entrega
            ORDER BY logistica_item.id_entrega DESC
            LIMIT :itensPorPag OFFSET :offset";

        $pedidos = DB::select($pedidosSql, [
            'idCliente' => $idCliente,
            'itensPorPag' => $itensPorPag,
            'offset' => $offset,
        ]);

        $totalPagsSql = "SELECT
                            CEIL(COUNT(DISTINCT logistica_item.id_entrega) / :itensPorPag) AS qtd_paginas
                        FROM logistica_item
                        INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
                            AND transacao_financeiras.origem_transacao = 'MP'
                        WHERE
                            logistica_item.id_cliente = :idCliente";

        $totalPags = DB::select($totalPagsSql, ['itensPorPag' => $itensPorPag, 'idCliente' => $idCliente]);
        $totalPags = $totalPags[0]->qtd_paginas ?? 0;

        return [
            'pedidos' => $pedidos,
            'mais_pags' => $totalPags - $pagina > 0,
        ];
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/99
     */
    public static function buscaPedidosMeuLook(int $pagina): array
    {
        $caseSituacao = self::sqlCaseSituacao(DB::getPdo());
        $caseSituacaoDatas = self::sqlCaseSituacaoDatas();
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
        $consulta = DB::select(
            "SELECT
                    transacao_financeiras.id,
                        transacao_financeiras.valor_total,
                    COALESCE(
                        (
                            SELECT transacao_financeiras_metadados.valor
                            FROM transacao_financeiras_metadados
                            WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                                AND transacao_financeiras_metadados.chave = 'VALOR_FRETE'
                        ),
                        0
                    ) float_valor_frete,
                    COALESCE(CASE
                        WHEN transacao_financeiras.status = 'PE' AND transacao_financeiras.metodo_pagamento = 'PX' THEN transacao_financeiras.qrcode_text_pix
                        ELSE transacao_financeiras.url_fatura
                    END,
                    '') url_pagamento,
                    DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y') data_criacao,
                    CASE
                        WHEN transacao_financeiras.status IN ('CA', 'ES') THEN 'cancelado'
                        WHEN transacao_financeiras.status = 'PE' THEN 'pendente'
                        WHEN transacao_financeiras.status = 'PA' THEN 'pago'
                    END situacao,
                    (
                        SELECT
                            transacao_financeiras_metadados.valor
                        FROM transacao_financeiras_metadados
                        WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                            AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                        LIMIT 1
                    ) json_endereco_transacao,
                    (
                        SELECT transacao_financeiras_metadados.valor
                        FROM transacao_financeiras_metadados
                        WhERE transacao_financeiras_metadados.chave = 'PRODUTOS_JSON'
                            AND transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                        LIMIT 1
                    ) json_produtos_transacao,
                    (
                        SELECT
                            JSON_OBJECT(
                                'id_tipo_frete', tipo_frete.id,
                                'id_colaborador', tipo_frete.id_colaborador,
                                'tipo_ponto', IF(tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto),
                                'nome', tipo_frete.nome,
                                'foto_ponto', COALESCE(tipo_frete.foto, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'),
                                'foto_colaborador', COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}images/avatar-padrao-mobile.jpg'),
                                'endereco', tipo_frete.mensagem,
                                'horario_de_funcionamento', tipo_frete.horario_de_funcionamento,
                                'telefone', colaboradores.telefone
                            )
                        FROM colaboradores
                        WHERE colaboradores.id = tipo_frete.id_colaborador
                        LIMIT 1
                    ) json_ponto,
                CONCAT('[',
                    GROUP_CONCAT( DISTINCT IF(transacao_financeiras_produtos_itens.id IS NULL, NULL, JSON_OBJECT(

                    'id_produto', transacao_financeiras_produtos_itens.id_produto,
                    'fornecedor', (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = transacao_financeiras_produtos_itens.id_responsavel_estoque),
                    'tamanho', transacao_financeiras_produtos_itens.nome_tamanho,
                    'foto', (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto LIMIT 1),
                    'nome', (SELECT produtos.nome_comercial FROM produtos WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto LIMIT 1),
                    'dados_rastreio', (SELECT
                                            JSON_OBJECT (
                                                'cpf_cnpj', entregas_transportadoras.cnpj,
                                                'nota_fiscal', entregas_transportadoras.nota_fiscal
                                            )
                                            FROM entregas_faturamento_item
                                            INNER JOIN entregas_transportadoras ON entregas_transportadoras.id_entrega = entregas_faturamento_item.id_entrega
                                            WHERE entregas_faturamento_item.uuid_produto = pedido_item_meu_look.uuid LIMIT 1),
                    'uuid', transacao_financeiras_produtos_itens.uuid_produto,
                    'situacao', $caseSituacao,
                    'situacao_datas', $caseSituacaoDatas,
                    'telefone_responsavel_entrega', (SELECT colaboradores.telefone
                                                    FROM transacao_financeiras_metadados
                                                    JOIN colaboradores ON colaboradores.id = transacao_financeiras_metadados.valor
                                                    WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                                                        AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
                                                    LIMIT 1),
                    'preco', pedido_item_meu_look.preco,
                    'em_processo_troca', troca_fila_solicitacoes.situacao = 'APROVADO' AND logistica_item.situacao <= :situacao,
                    'ultimo_numero_coleta', (
                        SELECT correios_atendimento.numeroColeta
                        FROM correios_atendimento
                        WHERE correios_atendimento.id_cliente = logistica_item.id_cliente
                            AND correios_atendimento.status = 'A'
                            AND correios_atendimento.prazo > NOW()
                        ORDER BY correios_atendimento.data_verificacao DESC
                        LIMIT 1
                    ),
                    'consumidor_final', logistica_item.observacao,
                    'recebedor', entregas_faturamento_item.nome_recebedor
                    ))),
                ']') json_produtos
            FROM transacao_financeiras
            LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
            LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = pedido_item_meu_look.id_ponto
            LEFT JOIN troca_fila_solicitacoes ON troca_fila_solicitacoes.uuid_produto = logistica_item.uuid_produto
            WHERE
                transacao_financeiras.pagador = :idCliente
                AND transacao_financeiras.origem_transacao = 'ML'
            GROUP BY transacao_financeiras.id
            ORDER BY transacao_financeiras.id DESC
            LIMIT :porPagina OFFSET :offset",
            [
                'idCliente' => Auth::user()->id_colaborador,
                'situacao' => $situacao,
                'porPagina' => $porPagina,
                'offset' => $offset,
            ]
        );

        $resultadoFiltrado = array_map(function (array $item) {
            if ($item['situacao'] === 'pendente') {
                $item['id_cript'] = @Cript::criptInt($item['id']);
            }

            $item['produtos_transacao'] ??= [];
            if (!empty($item['produtos'])) {
                $produtos = $item['produtos'];
                $produtosTransacao = $item['produtos_transacao'];
                $listaDeProdutos = array_map(function (array $item) use ($produtosTransacao): array {
                    if ($item['situacao']) {
                        $item = array_merge($item, json_decode($item['situacao'], true));
                    }
                    if ($item['dados_rastreio']) {
                        $item['dados_rastreio'] = json_decode($item['dados_rastreio'], true);
                    }

                    $produtoJson = array_filter(
                        $produtosTransacao,
                        fn(array $prodJson): bool => $prodJson['uuid_produto'] === $item['uuid']
                    );
                    $produtoJson = reset($produtoJson);
                    if (!empty($produtoJson['previsao'])) {
                        $item['previsoes'] = [$produtoJson['previsao']];
                    } else {
                        $item['previsoes'] = null;
                    }

                    return $item;
                }, $produtos);
                $item['produtos'] = $listaDeProdutos;
            }

            if (!empty($item['ponto'])) {
                if (mb_strlen($item['ponto']['foto_colaborador'])) {
                    $item['ponto']['foto'] = $item['ponto']['foto_colaborador'];
                }

                if (mb_strlen($item['ponto']['foto_ponto'])) {
                    $item['ponto']['foto'] = $item['ponto']['foto_ponto'];
                }

                unset($item['ponto']['foto_ponto'], $item['ponto']['foto_colaborador']);
            }

            unset($item['produtos_transacao']);

            return $item;
        }, $consulta);

        return $resultadoFiltrado;
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public static function buscaPedidosMobileEntregas(int $pagina, ?int $telefone): array
    {
        $enderecoCentral = ColaboradorEndereco::buscaEnderecoPadraoColaborador(TipoFrete::ID_COLABORADOR_CENTRAL);
        $caseSituacao = self::sqlCaseSituacao(DB::getPdo());
        $caseSituacaoDatas = self::sqlCaseSituacaoDatas();
        $auxiliarBuscarPedidos = TransportadoresRaio::retornaSqlAuxiliarPrevisaoMobileEntregas();
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;
        $idTipoFreteTransportadora = TipoFrete::ID_TIPO_FRETE_TRANSPORTADORA;

        [$binds, $valores] = ConversorArray::criaBindValues(
            [ProdutoModel::ID_PRODUTO_FRETE, ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO],
            'id_produto'
        );

        if (!$telefone) {
            $where = 'AND transacao_financeiras.pagador = :id_cliente';
            $valores['id_cliente'] = Auth::user()->id_colaborador;
        } else {
            [$bindTelefone, $valorTelefone] = ConversorArray::criaBindValues([$telefone], 'telefone_destinatario');
            $where = "AND JSON_VALUE(endereco_transacao_financeiras_metadados.valor, '$.telefone_destinatario') = $bindTelefone";
            $valores[$bindTelefone] = $valorTelefone[$bindTelefone];
        }

        $valores['itens_por_pag'] = $porPagina;
        $valores['offset'] = $offset;
        $valores['id_tipo_frete_transportadora'] = $idTipoFreteTransportadora;

        $pedidos = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id_transacao,
                CONCAT (
                    '[',
                    GROUP_CONCAT(
                        (
                            SELECT
                                JSON_OBJECT(
                                    'uuid_produto', logistica_item.uuid_produto,
                                    'nome_conferente', colaboradores.razao_social,
                                    'telefone_conferente', colaboradores.telefone
                                ) AS `json_dados_conferente`
                            FROM logistica_item_data_alteracao
                            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
                            INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
                            WHERE logistica_item_data_alteracao.uuid_produto = logistica_item.uuid_produto
                                AND logistica_item_data_alteracao.situacao_anterior = 'SE'
                                AND logistica_item_data_alteracao.situacao_nova = 'CO'
                            )
                        ),
                    ']'
                ) AS `json_conferentes`,
                transacao_financeiras.valor_total,
                transacao_financeiras.qrcode_text_pix,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                $auxiliarBuscarPedidos,
                transacao_financeiras_metadados.valor AS `json_produtos`,
                endereco_transacao_financeiras_metadados.valor AS `json_endereco_destino`,
                coleta_transacao_financeiras_metadados.valor AS `json_endereco_coleta`,
                IF (
                    coleta_transportadores_raios.id IS NULL,
                    NULL,
                    JSON_OBJECT(
                        'dias_coleta_cliente', coleta_transportadores_raios.dias_entregar_cliente,
                        'dias_margem_erro', coleta_transportadores_raios.dias_margem_erro
                    )
                ) AS `json_dias_processo_coleta`,
                transacao_financeiras.status,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'id_comissao', transacao_financeiras_produtos_itens.id,
                        'uuid_produto', transacao_financeiras_produtos_itens.uuid_produto,
                        'nome_recebedor', entregas_faturamento_item.nome_recebedor,
                        'situacao', JSON_EXTRACT($caseSituacao, '$.situacao'),
                        'data_situacao', $caseSituacaoDatas
                    )),
                    ']'
                ) AS `json_comissoes`,
                CONCAT (
                    '[',
                    GROUP_CONCAT(CONCAT('\"', transacao_financeiras_produtos_itens.uuid_produto, '\"')),
                    ']'
                ) AS `json_uuids_produtos`
            FROM transacao_financeiras
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'PRODUTOS_JSON'
                AND transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
            INNER JOIN transacao_financeiras_metadados AS `endereco_transacao_financeiras_metadados` ON
                endereco_transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND endereco_transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
            LEFT JOIN transacao_financeiras_metadados AS `coleta_transacao_financeiras_metadados` ON
                coleta_transacao_financeiras_metadados.chave = 'ENDERECO_COLETA_JSON'
                AND coleta_transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
            INNER JOIN transacao_financeiras_metadados AS `id_colaborador_tipo_frete_transacao_financeiras_metadados` ON
                id_colaborador_tipo_frete_transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE'
                AND id_colaborador_tipo_frete_transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = id_colaborador_tipo_frete_transacao_financeiras_metadados.valor
            LEFT JOIN transportadores_raios ON transportadores_raios.id = JSON_EXTRACT(endereco_transacao_financeiras_metadados.valor, '$.id_raio')
            LEFT JOIN transportadores_raios AS `coleta_transportadores_raios` ON coleta_transportadores_raios.id = JSON_EXTRACT(coleta_transacao_financeiras_metadados.valor, '$.id_raio')
            LEFT JOIN logistica_item ON logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
            INNER JOIN municipios ON municipios.id = JSON_EXTRACT(endereco_transacao_financeiras_metadados.valor, '$.id_cidade')
            WHERE
                transacao_financeiras_produtos_itens.id_produto IN ($binds)
                $where
                AND transacao_financeiras.status <> 'CR'
            GROUP BY transacao_financeiras.id
            ORDER BY transacao_financeiras.id DESC, transacao_financeiras_produtos_itens.id ASC
            LIMIT :itens_por_pag OFFSET :offset;",
            $valores
        );
        if (empty($pedidos)) {
            return [];
        }

        $uuidsProdutos = array_merge(...array_column($pedidos, 'uuids_produtos'));
        [$binds, $valores] = ConversorArray::criaBindValues($uuidsProdutos, 'uuids');

        $uuidsEtiquetasImpressas = DB::selectColumns(
            "SELECT logistica_item_impressos_temp.uuid_produto
            FROM logistica_item_impressos_temp
            WHERE logistica_item_impressos_temp.uuid_produto IN ($binds)",
            $valores
        );

        $previsao = app(PrevisaoService::class);
        $agenda = app(PontosColetaAgendaAcompanhamentoService::class);

        $pedidos = array_map(function (array $pedido) use ($agenda, $enderecoCentral, $previsao, $uuidsEtiquetasImpressas): array {
            $situacoesPendente = ['SEPARADO', 'LIBERADO_LOGISTICA', 'AGUARDANDO_LOGISTICA', 'AGUARDANDO_PAGAMENTO'];
            $pedido['codigo_transacao'] = @Cript::criptInt($pedido['id_transacao']);
            $pedido['data_limite'] = null;
            $existePendente = !empty(
                array_filter(
                    $pedido['comissoes'],
                    fn(array $comissao): bool => in_array($comissao['situacao'], $situacoesPendente)
                )
            );

            $pedido['produtos'] = array_values(
                array_filter(
                    $pedido['produtos'],
                    fn(array $produto): bool => in_array($produto['id'], [
                        ProdutoModel::ID_PRODUTO_FRETE,
                        ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
                    ])
                )
            );

            if ($existePendente) {
                $agenda->id_colaborador = $pedido['id_colaborador_ponto_coleta'];
                $pontoColeta = $agenda->buscaPrazosPorPontoColeta();
                $proximoEnvio = $previsao->calculaProximoDiaEnviarPontoColeta($pontoColeta['agenda']);
                $dataEnvio = $proximoEnvio['data_envio'];
                $horarioEnvio = current($proximoEnvio['horarios_disponiveis'])['horario'];
                $pedido['data_limite'] = "$dataEnvio às $horarioEnvio";

                $pedido['produtos'] = $previsao->processoCalcularPrevisaoResponsavelFiltrado(
                    $pedido['id_colaborador_ponto_coleta'],
                    [
                        'dias_entregar_cliente' => $pedido['dias_processo_entrega']['dias_entregar_cliente'],
                        'dias_coleta_cliente' => $pedido['dias_processo_coleta']['dias_coleta_cliente'] ?? 0,
                        'dias_margem_erro' =>
                            $pedido['dias_processo_entrega']['dias_margem_erro'] +
                            ($pedido['dias_processo_coleta']['dias_margem_erro'] ?? 0),
                    ],
                    $pedido['produtos']
                );
            }

            $pedido['produtos'] = array_map(function (array $produto) use ($pedido, $uuidsEtiquetasImpressas): array {
                $comissao = current(
                    array_filter(
                        $pedido['comissoes'],
                        fn(array $comissao): bool => $comissao['uuid_produto'] === $produto['uuid_produto']
                    )
                );
                if (!empty($pedido['conferentes'])) {
                    $produto['dados_conferente'] = current(
                        array_filter(
                            $pedido['conferentes'],
                            fn(array $conferente): bool => $conferente['uuid_produto'] === $produto['uuid_produto']
                        )
                    );
                }
                $produto['etiqueta_impressa'] = in_array($produto['uuid_produto'], $uuidsEtiquetasImpressas);
                if (!empty($produto['previsao'])) {
                    $produto['previsao']['media_previsao_inicial'] = substr($produto['previsao']['media_previsao_inicial'], 0, 5);
                    $produto['previsao']['media_previsao_final'] = substr($produto['previsao']['media_previsao_final'], 0, 5);
                }
                $produto = $produto + Arr::except($comissao, ['uuid_produto']);

                $produto = Arr::only($produto, [
                    'data_situacao',
                    'id_comissao',
                    'nome_recebedor',
                    'previsao',
                    'situacao',
                    'uuid_produto',
                    'dados_conferente',
                    'etiqueta_impressa',
                ]);
                return $produto;
            }, $pedido['produtos']);

            $formatarEndereco = fn(array $endereco): string => "{$endereco['logradouro']} {$endereco['numero']}, " .
                "{$endereco['bairro']} - {$endereco['cidade']} ({$endereco['uf']})";
            $pedido['endereco_central'] = $formatarEndereco($enderecoCentral->toArray());
            $pedido['telefone_destinatario'] = $pedido['endereco_destino']['telefone_destinatario'];
            $pedido['endereco_destino'] = $formatarEndereco($pedido['endereco_destino']);
            if (!empty($pedido['endereco_coleta'])) {
                $pedido['endereco_coleta'] = $formatarEndereco($pedido['endereco_coleta']);
            }
            unset(
                $pedido['comissoes'],
                $pedido['dias_entregar_cliente'],
                $pedido['dias_margem_erro'],
                $pedido['id_colaborador_ponto_coleta'],
                $pedido['conferentes'],
                $pedido['etiquetas_impressas']
            );

            return $pedido;
        }, $pedidos);

        return $pedidos;
    }

    //    public static function cancelaTransacaoFaturamento(PDO $conexao, array $data)
    //    // Confere se o faturamento existe na tabela transacao_financeiras_faturamento
    //    {
    //        if ($data['id_faturamento'] <> null) {
    //            $sql = "SELECT transacao_financeiras_faturamento.id_faturamento FROM transacao_financeiras_faturamento
    //			WHERE transacao_financeiras_faturamento.id_faturamento = {$data['id_faturamento']}";
    //
    //            $prepare = $conexao->prepare($sql);
    //            $prepare->execute();
    //            $resultado = $prepare->fetch(PDO::FETCH_ASSOC);
    //        } else {
    //            $resultado = null;
    //        }
    //
    //        return $resultado;
    //    }

    public static function buscaPagamentosAbertos(): array
    {
        $origem = app(Origem::class);
        if ($origem->ehMs()) {
            $where = " AND transacao_financeiras.origem_transacao = 'MP' ";
        } elseif ($origem->ehMobileEntregas()) {
            $where = " AND transacao_financeiras.origem_transacao = 'ML' ";
        } else {
            $where = " AND transacao_financeiras.origem_transacao IN ('ML', 'ET') ";
        }

        $transacoes = DB::select(
            "SELECT
                transacao_financeiras.id AS `id_transacao`,
                transacao_financeiras.pagador,
                transacao_financeiras.cod_transacao AS `transacao`,
                transacao_financeiras.emissor_transacao AS `tipo`,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                transacao_financeiras.qrcode_pix,
                transacao_financeiras.qrcode_text_pix,
                transacao_financeiras.valor_liquido,
                transacao_financeiras.origem_transacao,
                transacao_financeiras.data_criacao AS `data_nao_formatada`
            FROM transacao_financeiras
            WHERE transacao_financeiras.pagador = :id_cliente
                AND transacao_financeiras.status = 'PE'
                $where
                AND transacao_financeiras.metodo_pagamento = 'PX';",
            [':id_cliente' => Auth::user()->id_colaborador]
        );

        $transacoes = array_map(function (array $transacao): array {
            $transacao['codigo_transacao'] = @Cript::criptInt($transacao['id_transacao']);

            return $transacao;
        }, $transacoes);

        return $transacoes;
    }
    public static function sqlCaseSituacao(PDO $conexao): string
    {
        $consultaUsuario = fn(string $campo) => "(SELECT CONCAT('(', usuarios.id, ') ', usuarios.nome)
                                                  FROM usuarios
                                                  WHERE usuarios.id = $campo
                                                  LIMIT 1)";

        $formataDataSql = fn(string $campo) => "DATE_FORMAT($campo, '%d/%m/%Y às %H:%i:%s')";

        $ehTransacaoDetalhe = debug_backtrace(0, 2)[1]['function'] === 'buscaInfoTransacaoDetalhe';

        $sqlCaseEntregasELogistica = '';
        $sqlCaseIndefinido = '';
        $sqlCasePadrao = '';
        if ($ehTransacaoDetalhe) {
            $condicaoEntrega =
                "entregas.situacao IN ('AB', 'EX') OR entregas_faturamento_item.situacao = 'PE' AND entregas.situacao IN ('EN', 'PT')";
            $sqlCaseEntregasELogistica = ",
            'usuario', {$consultaUsuario(
                "COALESCE(IF($condicaoEntrega, entregas.id_usuario, NULL), entregas_faturamento_item.id_usuario, logistica_item.id_usuario)"
            )},
            'data_atualizacao', {$formataDataSql(
                "COALESCE(IF($condicaoEntrega, entregas.data_atualizacao, NULL), entregas_faturamento_item.data_atualizacao, logistica_item.data_atualizacao)"
            )}";

            $sqlCaseIndefinido = ",
            'usuario', 'INDEFINIDO',
            'data_atualizacao', 'INDEFINIDO'";

            $sqlCasePadrao = ",
            'usuario', {$consultaUsuario('transacao_financeiras.id_usuario')},
            'data_atualizacao', {$formataDataSql('transacao_financeiras.data_atualizacao')}";
        }
        $configuracoes = ConfiguracaoService::consultaDatasDeTroca($conexao);

        $trocaDefeito = (int) $configuracoes[0]['qtd_dias_disponiveis_troca_defeito'];
        $trocaNormal = (int) $configuracoes[0]['qtd_dias_disponiveis_troca_normal'];

        return "CASE
        WHEN transacao_financeiras.status = 'PA' AND EXISTS(SELECT 1 FROM logistica_item WHERE logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto) THEN
            (SELECT
                JSON_OBJECT(
                    'situacao',
                        CASE
                            WHEN entregas_faturamento_item.situacao = 'EN' THEN 'ENTREGUE'
                            WHEN entregas.situacao = 'PT' AND entregas_faturamento_item.situacao = 'PE' THEN 'EXPEDIDO'
                            WHEN entregas_faturamento_item.situacao = 'AR' AND tipo_frete.tipo_ponto = 'PP' THEN 'PONTO_RETIRADA'
                            WHEN entregas_faturamento_item.situacao = 'AR' AND tipo_frete.tipo_ponto = 'PM' THEN 'ENTREGADOR'
                            WHEN entregas.situacao IN ('AB', 'EX') AND entregas_faturamento_item.situacao = 'PE' THEN 'PREPARADO_ENVIO'
                            WHEN entregas.situacao = 'EN' AND entregas_faturamento_item.situacao = 'PE' THEN 'ENTREGUE_AO_DESTINATARIO'
                            WHEN logistica_item.situacao = 'SE' THEN 'SEPARADO'
                            WHEN logistica_item.situacao = 'CO' THEN 'CONFERIDO'
                            ELSE 'LIBERADO_LOGISTICA'
                        END,
                    'situacao_troca',
                        CASE
                            WHEN COALESCE(entregas_faturamento_item.situacao, '') <> 'EN' THEN 'NAO_ENTREGUE'
                            WHEN logistica_item.situacao <> 'CO' THEN 'TROCADO'
                            WHEN CURRENT_DATE() - INTERVAL $trocaDefeito DAY > DATE(entregas_faturamento_item.data_base_troca) THEN 'PRAZO_EXPIRADO_GARANTIA'
                            WHEN CURRENT_DATE() - INTERVAL $trocaNormal DAY > DATE(entregas_faturamento_item.data_base_troca) THEN 'PRAZO_EXPIRADO'
                            WHEN logistica_item.situacao = 'CO' THEN 'DISPONIVEL'
                            ELSE 'INDEFINIDO'
                        END
                    $sqlCaseEntregasELogistica
                    )
                FROM logistica_item
                LEFT JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = logistica_item.uuid_produto
                    AND entregas_faturamento_item.id_entrega = logistica_item.id_entrega
                LEFT JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega
                LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
                WHERE logistica_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                GROUP BY logistica_item.uuid_produto
            )
            WHEN transacao_financeiras.status = 'PA'
                AND EXISTS(
                    SELECT 1
                    FROM pedido_item
                    WHERE pedido_item.uuid = transacao_financeiras_produtos_itens.uuid_produto
                        AND pedido_item.situacao IN ('FR', 'DI')
                )
            THEN JSON_OBJECT(
                'situacao','AGUARDANDO_LOGISTICA',
                'situacao_troca','INDISPONIVEL'
                $sqlCaseIndefinido
            )
            WHEN (
                SELECT 1 FROM pedido_item
                WHERE pedido_item.uuid = transacao_financeiras_produtos_itens.uuid_produto
                AND pedido_item.situacao = 3
            ) THEN JSON_OBJECT(
            'situacao', 'AGUARDANDO_PAGAMENTO',
            'situacao_troca', 'INDISPONIVEL'
            $sqlCasePadrao
            )
            WHEN transacao_financeiras.status IN ('PA', 'ES') THEN JSON_OBJECT(
                'situacao', 'CANCELADO',
                'situacao_troca', 'INDISPONIVEL',
                'situacao_cancelamento', IF(EXISTS(
                    SELECT 1
                    FROM logistica_item_data_alteracao
                    WHERE logistica_item_data_alteracao.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                    AND logistica_item_data_alteracao.situacao_nova = 'RE'
                    AND logistica_item_data_alteracao.id_usuario = 2),
                    'AUTOMATICO',
                    'MANUAL')
                $sqlCasePadrao
                )
        ELSE
            JSON_OBJECT(
                'situacao', 'TRANSACAO_CRIADA',
                'situacao_troca', 'NAO_ENTREGUE'
                $sqlCasePadrao
            )
        END";
    }

    public static function sqlCaseSituacaoDatas(): string
    {
        return "
                JSON_OBJECT(
                    'entregue', IF (entregas_faturamento_item.situacao = 'EN', DATE_FORMAT(entregas_faturamento_item.data_entrega, '%d/%m/%Y %H:%i'), NULL),
                    'ponto_retirada',  (SELECT
                                            DATE_FORMAT(entregas_log_faturamento_item.data_criacao, '%d/%m/%Y %H:%i')
                                        FROM entregas_log_faturamento_item
                                        WHERE entregas_log_faturamento_item.id_entregas_fi = entregas_faturamento_item.id
                                            AND entregas_log_faturamento_item.situacao_nova = 'AR'
                                        LIMIT 1),
                    'expedido', (SELECT
                                    DATE_FORMAT(entregas_logs.data_criacao, '%d/%m/%Y %H:%i')
                                FROM entregas_logs
                                WHERE entregas_logs.id_entrega = logistica_item.id_entrega
                                    AND entregas_logs.situacao_anterior IN ('AB','EX')
                                    AND (entregas_logs.situacao_nova = 'PT' OR entregas_logs.situacao_nova = 'EN')
                                LIMIT 1),
                    'separado', (SELECT
                                    DATE_FORMAT(logistica_item_data_alteracao.data_criacao, '%d/%m/%Y %H:%i')
                                FROM logistica_item_data_alteracao
                                WHERE logistica_item_data_alteracao.uuid_produto = logistica_item.uuid_produto
                                    AND logistica_item_data_alteracao.situacao_nova = 'SE'
                                LIMIT 1),
                    'conferido', (SELECT
                                    DATE_FORMAT(logistica_item_data_alteracao.data_criacao, '%d/%m/%Y %H:%i')
                                FROM logistica_item_data_alteracao
                                WHERE logistica_item_data_alteracao.uuid_produto = logistica_item.uuid_produto
                                    AND logistica_item_data_alteracao.situacao_nova = 'CO'
                                LIMIT 1)
                )";
    }

    public static function buscaInfoTransacaoDetalhe(PDO $conexao, int $idTransacao): array
    {
        $caseSituacao = self::sqlCaseSituacao($conexao);

        $sqlValorEstornado = self::sqlValorEstornado();
        $consulta = DB::selectOne(
            "SELECT
            (
                SELECT JSON_OBJECT(
                    'id_colaborador', colaboradores.id,
                    'nome', TRIM(colaboradores.razao_social),
                    'telefone', colaboradores.telefone
                )
                FROM colaboradores
                WHERE colaboradores.id = transacao_financeiras.pagador
                LIMIT 1
            ) json_cliente,
            (
                SELECT
                    transacao_financeiras_metadados.valor
                FROM transacao_financeiras_metadados
                WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                    AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                LIMIT 1
            ) json_endereco_transacao,
            (
                SELECT transacao_financeiras_metadados.valor
                FROM transacao_financeiras_metadados
                WHERE transacao_financeiras_metadados.id_transacao = transacao_financeiras.id
                    AND transacao_financeiras_metadados.chave = 'PRODUTOS_JSON'
                LIMIT 1
            ) json_produtos_metadados,
            transacao_financeiras.metodo_pagamento,
            transacao_financeiras.status,
            transacao_financeiras.numero_parcelas,
            transacao_financeiras.cod_transacao,
            DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i:%s') data_criacao,
            DATE_FORMAT(transacao_financeiras.data_atualizacao, '%d/%m/%Y %H:%i:%s') data_atualizacao,
            transacao_financeiras.metodos_pagamentos_disponiveis,
            CASE
                WHEN transacao_financeiras.origem_transacao = 'MP' THEN 'Mobile Stock'
                WHEN transacao_financeiras.origem_transacao = 'MC' THEN 'Adição de crédito'
                WHEN transacao_financeiras.origem_transacao = 'ED' THEN 'Meu Estoque Digital'
                WHEN transacao_financeiras.origem_transacao = 'ML' THEN 'Meu Look'
                WHEN transacao_financeiras.origem_transacao = 'ZA' THEN 'Zagga'
                WHEN transacao_financeiras.origem_transacao = 'ET' THEN 'Esqueci Troca'
    			ELSE 'Erro! Entre em contato com o suporte.'
            END origem_transacao,
            transacao_financeiras.valor_itens,
            transacao_financeiras.valor_credito,
            transacao_financeiras.valor_credito_bloqueado,
            transacao_financeiras.valor_acrescimo,
            transacao_financeiras.valor_liquido,
            transacao_financeiras.valor_total,
            transacao_financeiras.valor_comissao_fornecedor,
            $sqlValorEstornado valor_estornado,
            transacao_financeiras.url_boleto,
            transacao_financeiras.qrcode_pix,
            transacao_financeiras.qrcode_text_pix,
            CONCAT('[', GROUP_CONCAT(DISTINCT IF(transacao_financeiras_produtos_itens.id, JSON_OBJECT(
                'id', transacao_financeiras_produtos_itens.id,
                'id_entrega', IF(transacao_financeiras_produtos_itens.tipo_item = 'PR', COALESCE((SELECT entregas_faturamento_item.id_entrega FROM entregas_faturamento_item WHERE entregas_faturamento_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto), 0), 0),
                'id_produto', transacao_financeiras_produtos_itens.id_produto,
                'id_responsavel_estoque', transacao_financeiras_produtos_itens.id_responsavel_estoque,
                'foto', (SELECT produtos_foto.caminho FROM produtos_foto WHERE produtos_foto.id = transacao_financeiras_produtos_itens.id_produto LIMIT 1),
                'infos_produtos', (
                    SELECT JSON_OBJECT(
                        'nome', COALESCE(produtos.nome_comercial, produtos.descricao, ''),
                        'localizacao', COALESCE(produtos.localizacao, '-')
                    )
                    FROM produtos
                    WHERE produtos.id = transacao_financeiras_produtos_itens.id_produto
                    LIMIT 1
                ),
                'tamanho', transacao_financeiras_produtos_itens.nome_tamanho,
                'tipo_item', transacao_financeiras_produtos_itens.tipo_item,
                'id_comissionado', transacao_financeiras_produtos_itens.id_fornecedor,
                'comissionado', (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor LIMIT 1),
                'preco', transacao_financeiras_produtos_itens.preco,
                'valor_comissao', transacao_financeiras_produtos_itens.comissao_fornecedor,
                'uuid_produto', transacao_financeiras_produtos_itens.uuid_produto,
                'negociacao_aceita', (
                    SELECT negociacoes_produto_log.mensagem
                    FROM negociacoes_produto_log
                    WHERE negociacoes_produto_log.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                        AND negociacoes_produto_log.situacao = 'ACEITA'
                    ORDER BY negociacoes_produto_log.id DESC
                    LIMIT 1
                ),
                'situacao_pagamento', IF (
                    transacao_financeiras.status IN ('CR', 'PE') OR transacao_financeiras_produtos_itens.momento_pagamento <> 'CARENCIA_ENTREGA',
                    NULL,
                    COALESCE(
                        (
                            SELECT 'PAGAMENTO_PENDENTE'
                            FROM lancamento_financeiro_pendente
                            WHERE lancamento_financeiro_pendente.origem = transacao_financeiras_produtos_itens.sigla_lancamento
                                AND lancamento_financeiro_pendente.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                AND lancamento_financeiro_pendente.numero_documento = transacao_financeiras_produtos_itens.uuid_produto
                        ), (
                            SELECT IF(lancamento_financeiro.origem = transacao_financeiras_produtos_itens.sigla_estorno, 'ESTORNADO', 'PAGO')
                            FROM lancamento_financeiro
                            WHERE lancamento_financeiro.origem IN (transacao_financeiras_produtos_itens.sigla_lancamento, transacao_financeiras_produtos_itens.sigla_estorno)
                                AND lancamento_financeiro.transacao_origem = transacao_financeiras_produtos_itens.id_transacao
                                AND lancamento_financeiro.numero_documento = transacao_financeiras_produtos_itens.uuid_produto
                            ORDER BY lancamento_financeiro.id DESC
                            LIMIT 1
                        ),
                        'CANCELADO'
                    )
                ),
                'situacao',
                $caseSituacao,
                'tipo_troca', (
                    SELECT entregas_devolucoes_item.tipo
                    FROM entregas_devolucoes_item
                    WHERE entregas_devolucoes_item.uuid_produto = transacao_financeiras_produtos_itens.uuid_produto
                )
            ), NULL)), ']') json_produtos,
           (SELECT COUNT(transacao_financeira_split.id) FROM transacao_financeira_split WHERE transacao_financeira_split.id_transacao = transacao_financeiras.id) qtd_splits,
            CONCAT('[',
                COALESCE((SELECT GROUP_CONCAT(JSON_OBJECT(
                        'situacao', colaboradores_suspeita_fraude.situacao,
                        'data_atualizacao', DATE_FORMAT(colaboradores_suspeita_fraude.data_atualizacao, '%d/%m/%Y %H:%i:%s'),
                        'origem', colaboradores_suspeita_fraude.origem))
                        FROM colaboradores_suspeita_fraude
                        WHERE colaboradores_suspeita_fraude.id_colaborador = transacao_financeiras.pagador
                ), ''),
            ']') json_fraudes_colaborador,
            transacao_financeiras.emissor_transacao
        FROM transacao_financeiras
        LEFT JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
                    WHERE transacao_financeiras.id = :id_transacao
                    GROUP BY transacao_financeiras.id",
            [
                'id_transacao' => $idTransacao,
            ]
        );

        if (!$consulta) {
            throw new NotFoundHttpException('Essa transação não existe.');
        }

        $consulta = array_merge($consulta, $consulta['cliente']);
        $consulta['qr_code'] = Globals::geraQRCODE('https://api.whatsapp.com/send/?phone=55' . $consulta['telefone']);
        $consulta['telefone'] = ConversorStrings::formataTelefone($consulta['telefone']);
        $consulta['cliente'] = "({$consulta['id_colaborador']}) {$consulta['nome']}";
        unset($consulta['nome']);

        $consulta['produtos'] ??= [];
        $consulta['id_cript'] = @Cript::criptInt($idTransacao);

        if ($consulta['status'] === 'PA' && $consulta['valor_estornado'] > 0) {
            $consulta['status'] = 'PX'; // Parcialmente estornado
        }
        $novosTipoItens = [];
        $consulta['produtos'] = array_map(function (array $item) use (&$novosTipoItens, $consulta): array {
            $item['situacao'] = json_decode($item['situacao'], true);
            if (!empty($item['infos_produtos'])) {
                $item = array_merge($item, json_decode($item['infos_produtos'], true));
            }
            if (is_null($item['situacao_pagamento'])) {
                switch (true) {
                    case in_array($consulta['status'], ['PX', 'ES']):
                        $item['situacao_pagamento'] = 'ESTORNADO';
                        break;
                    case $consulta['status'] === 'PA':
                        $item['situacao_pagamento'] = 'PAGO';
                        break;
                    default:
                        $item['situacao_pagamento'] = 'PAGAMENTO_PENDENTE';
                        break;
                }
            }
            if (!empty($item['negociacao_aceita'])) {
                $item['negociacao_aceita'] = json_decode($item['negociacao_aceita'], true);
                $item['negociacao_aceita']['produtos_oferecidos'] = json_decode(
                    $item['negociacao_aceita']['produtos_oferecidos'],
                    true
                );
            }
            $item['previsao'] = null;
            if (!empty($consulta['produtos_metadados'])) {
                $produtoMetadado = array_filter(
                    $consulta['produtos_metadados'],
                    fn(array $prodJson): bool => $prodJson['uuid_produto'] === $item['uuid_produto']
                );
                $produtoMetadado = reset($produtoMetadado);
                $item['previsao'] = $produtoMetadado['previsao'] ?? null;
            }
            if (
                !empty($item['uuid_produto']) &&
                in_array($item['tipo_item'], ['PR', 'RF']) &&
                !in_array($item['uuid_produto'], array_column($novosTipoItens, 'uuid_produto'))
            ) {
                $novoTipoItem = [
                    'foto' => $item['foto'],
                    'id_entrega' => $item['id_entrega'],
                    'id_produto' => $item['id_produto'],
                    'tamanho' => $item['tamanho'],
                    'tipo_item' => 'FOTO_PRODUTO',
                    'uuid_produto' => $item['uuid_produto'],
                    'nome' => $item['nome'] ?? '',
                    'qrcode_produto' => Globals::geraQRCODE(
                        'produto/' . $item['id_produto'] . '?w=' . $item['uuid_produto']
                    ),
                ];
                $novosTipoItens[] = $novoTipoItem;
            }

            if ($item['preco'] !== $item['valor_comissao']) {
                $situacaoNovaComissao = preg_replace('/CM_/', '', $item['tipo_item']);
                $novoTipoItem = [
                    'comissionado' => 'Marketplace',
                    'id' => $item['id'],
                    'id_comissionado' => 12,
                    'id_entrega' => $item['id_entrega'],
                    'id_produto' => $item['id_produto'],
                    'preco' => $item['preco'],
                    'situacao' => empty($item['situacao']) ? null : $item['situacao'],
                    'situacao_pagamento' => $item['situacao_pagamento'],
                    'valor_comissao' => $item['preco'] - $item['valor_comissao'],
                    'tamanho' => $item['tamanho'],
                    'tipo_item' => "TAXA_$situacaoNovaComissao",
                    'uuid_produto' => $item['uuid_produto'],
                ];
                if (!empty($item['foto'])) {
                    $novoTipoItem['foto'] = $item['foto'];
                }
                if (!empty($item['nome'])) {
                    $novoTipoItem['nome'] = $item['nome'];
                }
                if (!empty($item['localizacao'])) {
                    $novoTipoItem['localizacao'] = $item['localizacao'];
                }
                $novosTipoItens[] = $novoTipoItem;
            }
            unset($item['infos_produtos']);

            return $item;
        }, $consulta['produtos']);
        $consulta['produtos'] = array_merge($consulta['produtos'], $novosTipoItens);
        unset($consulta['produtos_metadados']);
        usort($consulta['produtos'], function (array $a, array $b): int {
            // Prioridade 1: TIPO_ITEM DIREITO_COLETA
            if ($a['tipo_item'] === 'DIREITO_COLETA') {
                return -1;
            } elseif ($b['tipo_item'] === 'DIREITO_COLETA') {
                return 1;
            }

            // Prioridade 2: TIPO_ITEM TAXA_DIREITO_COLETA
            if ($a['tipo_item'] === 'TAXA_DIREITO_COLETA') {
                return -1;
            } elseif ($b['tipo_item'] === 'TAXA_DIREITO_COLETA') {
                return 1;
            }

            // Prioridade 3: TIPO_ITEM FR
            if ($a['tipo_item'] === 'FR') {
                return -1;
            } elseif ($b['tipo_item'] === 'FR') {
                return 1;
            }

            // Agrupar por UUID_PRODUTO
            if ($a['uuid_produto'] !== $b['uuid_produto']) {
                return strcmp($a['uuid_produto'], $b['uuid_produto']);
            }

            // Prioridade 4: TIPO_ITEM FOTO_PRODUTO
            if ($a['tipo_item'] === 'FOTO_PRODUTO') {
                return -1;
            } elseif ($b['tipo_item'] === 'FOTO_PRODUTO') {
                return 1;
            }

            // Prioridade 5: TIPO_ITEM PR ou RF
            if (in_array($a['tipo_item'], ['PR', 'RF'])) {
                return -1;
            } elseif (in_array($b['tipo_item'], ['PR', 'RF'])) {
                return 1;
            }

            // Prioridade 6: TIPO_ITEM TAXA_PR
            if ($a['tipo_item'] === 'TAXA_PR') {
                return -1;
            } elseif ($b['tipo_item'] === 'TAXA_PR') {
                return 1;
            }

            // Prioridade 7: TIPO_ITEM CM_PONTO_COLETA
            if ($a['tipo_item'] == 'CM_PONTO_COLETA') {
                return -1;
            } elseif ($b['tipo_item'] == 'CM_PONTO_COLETA') {
                return 1;
            }

            // Prioridade 8: TIPO_ITEM TAXA_PONTO_COLETA
            if ($a['tipo_item'] == 'TAXA_PONTO_COLETA') {
                return -1;
            } elseif ($b['tipo_item'] == 'TAXA_PONTO_COLETA') {
                return 1;
            }

            // Caso contrário, TIPO_ITEM CE
            return $a['tipo_item'] == 'CE' ? -1 : 1;
        });

        return $consulta;
    }
    public static function buscaTentativaTransacao(PDO $conexao, int $idTransacao): array
    {
        $sql = $conexao->prepare("SELECT
                                    transacao_financeiras_tentativas_pagamento.id,
                                    transacao_financeiras_tentativas_pagamento.id_transacao,
                                    transacao_financeiras_tentativas_pagamento.emissor_transacao,
                                    transacao_financeiras_tentativas_pagamento.cod_transacao,
                                    COALESCE(transacao_financeiras_tentativas_pagamento.mensagem_erro, 'Sem erro!') mensagem_erro,
                                    transacao_financeiras_tentativas_pagamento.transacao_json,
                                    DATE_FORMAT(transacao_financeiras_tentativas_pagamento.data_criacao, '%d/%m/%Y %H:%i:%s') data_criacao
                                FROM
                                    transacao_financeiras_tentativas_pagamento
                                WHERE
                                    id_transacao = :id_transacao");
        $sql->bindParam(':id_transacao', $idTransacao);
        $sql->execute();
        $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
        $resultadoFormatado = array_map(function (array $item) {
            $item['transacao_json'] = json_decode($item['transacao_json'], true);
            return $item;
        }, $resultado);
        return $resultadoFormatado;
    }

    public static function buscaValorTransacao(PDO $conexao, int $idTransacao): int
    {
        $stmt = $conexao->prepare(
            "SELECT
                transacao_financeiras.valor_total
            FROM transacao_financeiras
            WHERE transacao_financeiras.id = :idTransacao"
        );
        $stmt->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['valor_total'] ?? 0;
    }

    public static function buscaValorItemTransacao(PDO $conexao, string $uuid): int
    {
        $stmt = $conexao->prepare(
            "SELECT
                transacao_financeiras_produtos_itens.preco
            FROM transacao_financeiras_produtos_itens
            WHERE transacao_financeiras_produtos_itens.uuid_produto = :uuid"
        );
        $stmt->bindValue(':uuid', $uuid);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['preco'] ?? 0;
    }
    /**
     * @param PDO $conexao
     * @param int $idDestinatario id_colaborador do ponto/cliente que receberá a entrega
     * @param int $idCidade id_cidade da cidade para onde essa entrega irá
     * @param string $categoria MS|ML|PE|ENVIO_TRANSPORTADORA
     */
    // public static function produtosEntregaPendentePorCategoria(
    //     PDO $conexao,
    //     int $idDestinatario,
    //     int $idCidade,
    //     string $categoria,
    //     int $idTipoFrete
    // ): array {
    //     $situacao = LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA;
    //     $join = '';
    //     switch (true) {
    //         case $categoria === "MS" && !!$idDestinatario:
    //             $join .= "
    //                     LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
    //                     INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //                     ";
    //             $where = " AND logistica_item.situacao <= :situacao
    //                 AND logistica_item.id_cliente = :id_colaborador
    //                 AND logistica_item.id_responsavel_estoque = 1
    //                 AND tipo_frete.id = :id_tipo_frete
    //                 AND pedido_item_meu_look.id IS NULL ";
    //             break;
    //         case in_array($categoria, array('ML', 'PE')) && !!$idDestinatario:
    //             $join .= " INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
    //                     AND transacao_financeiras.status = 'PA'
    //                 INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //                     AND tipo_frete.tipo_ponto = 'PP' ";
    //             $where = " AND logistica_item.situacao = :situacao
    //                 AND logistica_item.id_colaborador_tipo_frete = :id_colaborador ";
    //             break;
    //         case in_array($categoria, array('ML', 'PE')) && !!$idCidade:
    //             $join .= " INNER JOIN transacao_financeiras ON transacao_financeiras.id = logistica_item.id_transacao
    //                     AND transacao_financeiras.status = 'PA'
    //                 INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
    //                     AND colaboradores.id_cidade = :id_cidade
    //                 INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //                     AND tipo_frete.tipo_ponto = 'PM' ";
    //             $where = " AND logistica_item.situacao = :situacao ";
    //             break;
    //         case $categoria === 'ENVIO_TRANSPORTADORA' && $idDestinatario:
    //             $where = " AND logistica_item.situacao <= :situacao
    //                 AND logistica_item.id_cliente = :id_colaborador
    //                 AND logistica_item.id_colaborador_tipo_frete = 32257 ";
    //             break;
    //         default:
    //             throw new Exception('Não foi possível encontrar mais produtos');
    //     }

    //     $sql = $conexao->prepare(
    //         "SELECT
    //             logistica_item.id_transacao,
    //             logistica_item.id_produto,
    //             logistica_item.nome_tamanho,
    //             logistica_item.id_responsavel_estoque,
    //             COALESCE(produtos.localizacao, '-') localizacao,
    //             (
    //                 SELECT colaboradores.razao_social
    //                 FROM colaboradores
    //                 WHERE colaboradores.id = produtos.id_fornecedor
    //             ) nome_fornecedor,
    //             IF (
    //                 logistica_item.situacao = 'PE',
    //                 '-',
    //                 (
    //                     SELECT usuarios.nome
    //                     FROM usuarios
    //                     WHERE usuarios.id = logistica_item.id_usuario
    //                 )
    //             ) nome_usuario,
    //             (
    //                 SELECT produtos_foto.caminho
    //                 FROM produtos_foto
    //                 WHERE produtos_foto.id = logistica_item.id_produto
    //                 ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
    //                 LIMIT 1
    //             ) produto_foto,
    //             DATE_FORMAT(logistica_item.data_atualizacao, '%d/%m/%Y %H:%i:%s') data_atualizacao,
    //             logistica_item.situacao
    //         FROM logistica_item
    //         INNER JOIN produtos ON produtos.id = logistica_item.id_produto
    //         $join
    //         WHERE logistica_item.id_entrega IS NULL
    //             AND DATE(logistica_item.data_atualizacao) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    //             $where
    //         GROUP BY logistica_item.uuid_produto
    //         ORDER BY logistica_item.data_atualizacao DESC;"
    //     );
    //     $sql->bindValue(":situacao", $situacao, PDO::PARAM_INT);
    //     if ($categoria === "MS" && !!$idDestinatario) $sql->bindValue(":id_tipo_frete", $idTipoFrete, PDO::PARAM_INT);
    //     if (!!$idDestinatario) $sql->bindValue(":id_colaborador", $idDestinatario, PDO::PARAM_INT);
    //     if (!!$idCidade) $sql->bindValue(":id_cidade", $idCidade, PDO::PARAM_INT);
    //     $sql->execute();
    //     $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);

    //     $produtos = array_map(function (array $produto): array {
    //         $produto["id_transacao"] = (int) $produto["id_transacao"];
    //         $produto["id_produto"] = (int) $produto["id_produto"];
    //         $produto["responsavel_estoque"] = $produto["id_responsavel_estoque"] == 1 ? 'Fulfillment' : 'Externo';
    //         $produto["situacao"] = LogisticaItem::converteSituacao($produto["situacao"]);
    //         unset($produto["id_responsavel_estoque"]);

    //         return $produto;
    //     }, $produtos);

    //     return $produtos;
    // }

    public static function sqlValorEstornado(): string
    {
        $sqlValorEstornado = "COALESCE(
                (SELECT SUM(lancamento_financeiro.valor)
                 FROM lancamento_financeiro
                 WHERE lancamento_financeiro.transacao_origem = transacao_financeiras.id
                   AND lancamento_financeiro.origem = 'ES'
                   AND lancamento_financeiro.tipo = 'P'
                   AND lancamento_financeiro.id_colaborador = transacao_financeiras.pagador),
            0) +
            COALESCE(
                (SELECT SUM(lancamento_financeiro_pendente.valor)
                 FROM lancamento_financeiro_pendente
                 WHERE lancamento_financeiro_pendente.transacao_origem = transacao_financeiras.id
                   AND lancamento_financeiro_pendente.origem = 'ES'
                   AND lancamento_financeiro_pendente.tipo = 'P'
                   AND lancamento_financeiro_pendente.id_colaborador = transacao_financeiras.pagador),
            0)";

        return $sqlValorEstornado;
    }

    public static function insereEDeletaBackupLogTentativaTransacao(): void
    {
        $stmt = "INSERT INTO transacao_financeiras_tentativas_pagamento_bkp (
                transacao_financeiras_tentativas_pagamento_bkp.id,
                transacao_financeiras_tentativas_pagamento_bkp.id_transacao,
                transacao_financeiras_tentativas_pagamento_bkp.emissor_transacao,
                transacao_financeiras_tentativas_pagamento_bkp.cod_transacao,
                transacao_financeiras_tentativas_pagamento_bkp.mensagem_erro,
                transacao_financeiras_tentativas_pagamento_bkp.transacao_json,
                transacao_financeiras_tentativas_pagamento_bkp.data_criacao
            ) SELECT
                transacao_financeiras_tentativas_pagamento.id,
                transacao_financeiras_tentativas_pagamento.id_transacao,
                transacao_financeiras_tentativas_pagamento.emissor_transacao,
                transacao_financeiras_tentativas_pagamento.cod_transacao,
                transacao_financeiras_tentativas_pagamento.mensagem_erro,
                transacao_financeiras_tentativas_pagamento.transacao_json,
                transacao_financeiras_tentativas_pagamento.data_criacao
            FROM transacao_financeiras_tentativas_pagamento
            WHERE transacao_financeiras_tentativas_pagamento.data_criacao < NOW() - INTERVAL 30 DAY;";

        DB::insert($stmt);

        $stmt = "DELETE transacao_financeiras_tentativas_pagamento
            FROM transacao_financeiras_tentativas_pagamento
            INNER JOIN transacao_financeiras_tentativas_pagamento_bkp ON transacao_financeiras_tentativas_pagamento_bkp.id = transacao_financeiras_tentativas_pagamento.id;";

        DB::delete($stmt);
    }

    public static function buscaTransacoesASeremCanceladas(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                transacao_financeiras.id id_transacao,
                transacao_financeiras.cod_transacao,
                transacao_financeiras.pagador,
                transacao_financeiras.origem_transacao,
                transacao_financeiras.emissor_transacao,
                transacao_financeiras.metodo_pagamento
            FROM transacao_financeiras
            WHERE transacao_financeiras.status = 'PE'
            AND transacao_financeiras.data_criacao < NOW() - INTERVAL 1 HOUR
            AND transacao_financeiras.metodo_pagamento = 'PX'
            AND transacao_financeiras.emissor_transacao = 'Iugu';"
        );
        $sql->execute();
        $transacoes = $sql->fetchAll(PDO::FETCH_CLASS, TransacaoFinanceiraService::class);

        return $transacoes;
    }
}

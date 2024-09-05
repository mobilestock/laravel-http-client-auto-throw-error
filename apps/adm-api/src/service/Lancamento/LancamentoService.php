<?php

namespace MobileStock\service\Lancamento;

use Exception;
use MobileStock\helper\ConversorArray;
use MobileStock\model\Lancamento;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\Pagamento\LancamentoPendenteService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use PDO;

class LancamentoService
{
    public static function criaCreditoDebitoDevolucao(
        TrocaPendenteItem $troca,
        PDO $conexao = null,
        bool $forcarTroca = false
    ) {
        $faturamentoItem = LogisticaItemService::buscaProdutoLogisticaPorUuid($conexao, $troca->getUuid());

        if (is_null($faturamentoItem)) {
            throw new \InvalidArgumentException('Produto não existe');
        } elseif (!$faturamentoItem['id_fornecedor'] || !$faturamentoItem['comissao_fornecedor']) {
            throw new \InvalidArgumentException('Produto não tem comissão');
        }

        date_default_timezone_set('America/Sao_Paulo');
        $data = DATE('Y-m-d H:i:s');

        [
            'comissoes' => $comissoesProduto,
            'preco' => $precoCliente,
        ] = TransacaoFinanceiraItemProdutoService::buscaComissoesProduto($conexao, $troca->getUuid());

        $lancamentosProdutos = LancamentoPendenteService::buscaLancamentosPendentesProduto($conexao, $troca->getUuid());

        $comissoesProduto = array_filter($comissoesProduto, fn($comissao) => $comissao['sigla_estorno'] !== null);

        if (count($lancamentosProdutos) > 0) {
            $lancamentos = array_map(function (array $lancamento) {
                $lancamentoObj = new Lancamento(
                    $lancamento['tipo'],
                    1,
                    $lancamento['origem'],
                    $lancamento['id_colaborador'],
                    null,
                    $lancamento['valor'],
                    1,
                    7
                );

                $lancamentoObj->sequencia = $lancamento['sequencia'];
                $lancamentoObj->documento = $lancamento['documento'];
                $lancamentoObj->valor_total = $lancamento['valor_total'];
                $lancamentoObj->valor_pago = 0;
                $lancamentoObj->id_usuario_pag = $lancamento['id_usuario_pag'];
                $lancamentoObj->observacao = $lancamento['observacao'];
                $lancamentoObj->tabela = $lancamento['tabela'];
                $lancamentoObj->pares = $lancamento['pares'];
                $lancamentoObj->transacao_origem = $lancamento['transacao_origem'];
                $lancamentoObj->pedido_origem = $lancamento['pedido_origem'];
                $lancamentoObj->cod_transacao = $lancamento['cod_transacao'];
                $lancamentoObj->bloqueado = $lancamento['bloqueado'];
                $lancamentoObj->id_split = $lancamento['id_split'];
                $lancamentoObj->parcelamento = $lancamento['parcelamento'];
                $lancamentoObj->juros = $lancamento['juros'];
                $lancamentoObj->numero_documento = $lancamento['numero_documento'];

                return $lancamentoObj;
            }, $lancamentosProdutos);

            LancamentoService::insereVarios($conexao, $lancamentos);

            $qtdLancamentosRemovidos = LancamentoPendenteService::removeLancamentos(
                $conexao,
                array_column($lancamentos, 'sequencia')
            );

            if ($qtdLancamentosRemovidos !== count($lancamentos)) {
                throw new \RuntimeException('Quantidade inconsistente de lançamentos alterados.');
            }
        }

        foreach ($comissoesProduto as $comissao) {
            $estorno = new Lancamento(
                'R',
                1,
                $comissao['sigla_estorno'],
                $comissao['id_fornecedor'],
                null,
                $comissao['comissao_fornecedor'],
                $troca->getIdVendedor(),
                12
            );
            $estorno->numero_documento = $troca->getUuid();
            $estorno->transacao_origem = $comissao['id_transacao'];

            LancamentoCrud::salva($conexao, $estorno);
        }

        $lancCredito = new Lancamento(
            'P',
            1,
            'TR',
            $troca->getIdCliente(),
            $data,
            $precoCliente,
            $troca->getIdVendedor(),
            12
        );
        $lancCredito->transacao_origem = (int) $faturamentoItem['id_transacao'];
        $lancCredito->pares = 1;
        $lancCredito->numero_documento = $troca->getUuid() ?? 0;
        $lancCredito->observacao = $troca->geraObservacaoLancamento();

        LancamentoCrud::salva($conexao, $lancCredito);

        if ($forcarTroca === false) {
            $lancTaxa = new Lancamento(
                'R',
                1,
                'TX',
                $troca->getIdCliente(),
                $data,
                $troca->calculaTaxa(),
                $troca->getIdVendedor(),
                12
            );
            $lancTaxa->transacao_origem = (int) $faturamentoItem['id_transacao'];
            $lancTaxa->numero_documento = $troca->getUuid() ?? 0;
            $lancTaxa->observacao = $troca->geraObservacaoLancamentoTaxa();

            LancamentoCrud::salva($conexao, $lancTaxa);
        }
    }

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function buscaUltimoLancamentoAReceber(PDO $conexao, int $idFornecedor)
    //    {
    //        //        $query = "SELECT MAX(DATE_FORMAT(data_vencimento,'%d/%m/%Y'))data_vencimento, valor FROM lancamentos_financeiros_recebiveis
    //        //        WHERE id_recebedor={$idFornecedor} AND situacao='PE';";
    //        //        $resultado = $conexao->query($query);
    //        //        $recebivel = $resultado->fetch(PDO::FETCH_ASSOC);
    //        //        $query = "SELECT MAX(DATE_FORMAT(data_vencimento,'%d/%m/%Y'))data_vencimento, valor FROM lancamento_financeiro
    //        //        WHERE id_colaborador={$idFornecedor} AND situacao=1 AND origem='SP';";
    //        //        $resultado = $conexao->query($query);
    //        //        $lancamento = $resultado->fetch(PDO::FETCH_ASSOC);
    //        //        return strtotime($recebivel['data_vencimento'])>strtotime($lancamento['data_vencimento'])?$recebivel:$lancamento;
    //        $query = "SELECT
    //            DATE_FORMAT(MIN(lf.data_vencimento),'%d/%m/%Y')data_vencimento,
    //            sum(lf.valor) valor
    //            FROM lancamento_financeiro lf
    //            INNER JOIN faturamento ON faturamento.id=lf.pedido_origem
    //        WHERE lf.id_colaborador = $idFornecedor
    //        AND lf.situacao=1
    //        AND faturamento.tabela_preco = 2
    //        AND origem IN ('SP','SC')
    //        AND lf.data_vencimento
    //        GROUP BY DATE(lf.data_vencimento)
    //        ORDER BY data_vencimento LIMIT 1";
    //        return $conexao->query($query)->fetch(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function buscaUltimoLancamentoPago(PDO $conexao, int $idFornecedor)
    //    {
    //        //        $query = "SELECT MIN(DATE_FORMAT(data_vencimento,'%d/%m/%Y'))data_vencimento, valor FROM lancamentos_financeiros_recebiveis WHERE id_recebedor={$idFornecedor} AND situacao='PA';";
    //        //        $resultado = $conexao->query($query);
    //        //        $recebivel = $resultado->fetch(PDO::FETCH_ASSOC);
    //        //        $query = "SELECT MIN(DATE_FORMAT(data_vencimento,'%d/%m/%Y'))data_vencimento, valor FROM lancamento_financeiro WHERE id_colaborador={$idFornecedor} AND situacao=2 AND origem='SP';";
    //        //        $resultado = $conexao->query($query);
    //        //        $lancamento = $resultado->fetch(PDO::FETCH_ASSOC);
    //        //        return strtotime($recebivel['data_vencimento'])>strtotime($lancamento['data_vencimento'])?$recebivel:$lancamento;
    //
    //        return $conexao->query("SELECT
    //	DATE_FORMAT(data_vencimento,'%d/%m/%Y')data_vencimento,
    //    SUM(valor) valor
    //    FROM lancamentos_financeiros_recebiveis
    //    WHERE id_recebedor = $idFornecedor
    //    AND situacao='PA'
    //    GROUP BY DATE(lancamentos_financeiros_recebiveis.data_vencimento) ORDER BY DATE(lancamentos_financeiros_recebiveis.data_vencimento) DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // public static function buscaSomaLancamentosFuturos(PDO $conexao, int $idFornecedor, int $mes, int $ano)
    // {
    //     $query = "SELECT COALESCE(SUM(lf.valor), 0)valor FROM lancamento_financeiro lf
    //     INNER JOIN faturamento f ON f.id = lf.pedido_origem
    //     WHERE MONTH(f.data_emissao)={$mes}
    //     AND YEAR(f.data_emissao)={$ano}
    //     AND lf.id_colaborador={$idFornecedor}
    //     AND f.situacao=2
    //     AND f.tabela_preco<>3
    //     AND lf.situacao=1
    //     AND lf.origem IN ('SP','SC');";
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetch(PDO::FETCH_ASSOC);
    // }

    // public static function buscaSomaLancamentosPagos(PDO $conexao, int $idFornecedor, int $mes, int $ano)
    // {
    //     $query = "SELECT SUM(lf.valor)valor FROM lancamento_financeiro lf
    //     INNER JOIN faturamento f ON f.id = lf.pedido_origem
    //     WHERE MONTH(f.data_emissao)={$mes}
    //     AND YEAR(f.data_emissao)={$ano}
    //     AND lf.id_colaborador={$idFornecedor}
    //     AND f.situacao=2
    //     AND lf.situacao=2
    //     AND lf.origem IN ('SP','SC');";
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetch(PDO::FETCH_ASSOC);
    // }

    // public static function listaLancamentos(PDO $conexao, int $idFornecedor, int $mes, int $ano)
    // {
    //     $query = "SELECT
    //                     lancamento_financeiro.id,
    //                     lancamento_financeiro.valor,
    //                     1 num_parcela,
    //                     lancamento_financeiro.situacao status,
    //                     lancamento_financeiro.data_vencimento data_order,
    //                     DATE_FORMAT(lancamento_financeiro.data_vencimento,'%d/%m/%Y')data_vencimento,
    //                     'Deposito' parcelamento,
    //                     DATE_FORMAT(lancamento_financeiro.data_pagamento,'%d/%m/%Y')data_pagamento
    //                 FROM lancamento_financeiro
    //                     INNER JOIN faturamento faturamento ON faturamento.id=lancamento_financeiro.pedido_origem
    //                 WHERE lancamento_financeiro.id_colaborador = {$idFornecedor} AND
    //                     MONTH(faturamento.data_emissao)='{$mes}' AND
    //                     YEAR(faturamento.data_emissao)='{$ano}' AND
    //                     faturamento.situacao=2 AND
    //                     faturamento.tabela_preco IN (1,2,3) AND
    //                     (
    //                         (faturamento.tabela_preco IN (1,3) AND (NOT EXISTS(SELECT 1
    //                                                                             FROM lancamentos_financeiros_recebiveis
    //                                                                             WHERE lancamentos_financeiros_recebiveis.id_lancamento = lancamento_financeiro.id))
    //                             ) OR
    //                             (faturamento.tabela_preco IN (2))
    //                         )
    //                 GROUP BY lancamento_financeiro.id

    //                 UNION ALL

    //                 SELECT
    //                     lancamento_financeiro.id,
    //                     lancamento_financeiro.valor,
    //                     lancamentos_financeiros_recebiveis.num_parcela,
    //                     lancamentos_financeiros_recebiveis.situacao,
    //                     lancamentos_financeiros_recebiveis.data_vencimento data_order,
    //                     DATE_FORMAT(lancamentos_financeiros_recebiveis.data_vencimento,'%d/%m/%Y')data_vencimento,
    //                     IF(faturamento.tabela_preco=1,CONCAT('Cartão ',lancamentos_financeiros_recebiveis.num_parcela,'º Parc'),'Boleto') parcelamento,
    //                     DATE_FORMAT(lancamentos_financeiros_recebiveis.data_vencimento,'%d/%m/%Y')data_pagamento
    //                 FROM faturamento
    //                     INNER JOIN lancamento_financeiro ON lancamento_financeiro.pedido_origem = faturamento.id
    //                     INNER JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_lancamento = lancamento_financeiro.id
    //                 WHERE lancamento_financeiro.id_colaborador = {$idFornecedor} AND
    //                     MONTH(faturamento.data_emissao)='{$mes}' AND
    //                     YEAR(faturamento.data_emissao)='{$ano}' AND
    //                     faturamento.situacao=2 AND
    //                     faturamento.tabela_preco IN (1,3) AND
    //                     lancamento_financeiro.origem = 'SP'
    //                 GROUP BY lancamento_financeiro.id

    //                 UNION ALL

    //                  SELECT
    //                     lancamento_financeiro.id,
    //                     lancamento_financeiro.valor,
    //                     lancamentos_financeiros_recebiveis.num_parcela,
    //                     lancamentos_financeiros_recebiveis.situacao,
    //                     lancamentos_financeiros_recebiveis.data_vencimento data_order,
    //                     DATE_FORMAT(lancamentos_financeiros_recebiveis.data_vencimento,'%d/%m/%Y')data_vencimento,
    //                     'Deposito' parcelamento,
    //                     DATE_FORMAT(lancamentos_financeiros_recebiveis.data_vencimento,'%d/%m/%Y')data_pagamento
    //                 FROM faturamento
    //                     INNER JOIN lancamento_financeiro ON lancamento_financeiro.pedido_origem = faturamento.id
    //                     INNER JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_lancamento = lancamento_financeiro.id
    //                 WHERE lancamento_financeiro.id_colaborador = {$idFornecedor} AND
    //                     MONTH(faturamento.data_emissao)='{$mes}' AND
    //                     YEAR(faturamento.data_emissao)='{$ano}' AND
    //                     faturamento.situacao=2 AND
    //                     faturamento.tabela_preco IN (1,3) AND
    //                     lancamento_financeiro.origem = 'SC'
    //                 GROUP BY lancamento_financeiro.id

    //                 ORDER BY data_order DESC";
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll(PDO::FETCH_ASSOC);
    // }

    //	public static function buscaPagamentoSellerFaturamentoPendente(int $transacao): array
    //	{
    //		$query = "SELECT  lancamento_financeiro_pendente.id,
    //        lancamento_financeiro_pendente.pedido_origem,
    //        lancamento_financeiro_pendente.transacao_origem,
    //        lancamento_financeiro_pendente.situacao,
    //        lancamento_financeiro_pendente.valor,
    //        lancamento_financeiro_pendente.juros,
    //        lancamento_financeiro_pendente.valor_total,
    //        lancamento_financeiro_pendente.origem,
    //        lancamento_financeiro_pendente.observacao,
    //        colaboradores.razao_social,
    //        IF(lancamento_financeiro_pendente.origem = 'PF','Recebeu','Gerou Crédito') descricao
    //	    FROM lancamento_financeiro_pendente
    //	        INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro_pendente.id_colaborador
    //	    WHERE lancamento_financeiro_pendente.transacao_origem = :transacao AND lancamento_financeiro_pendente.origem IN ('PF','SC')";
    //		$resultado = Conexao::criarConexao()->prepare($query);
    //		$resultado->bindParam(':transacao',$transacao,PDO::PARAM_INT);
    //		$resultado->execute();
    //		$retorno = $resultado->fetchAll(PDO::FETCH_ASSOC);
    //		return $retorno;
    //	}

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function buscar(PDO $conexao, array $params)
    //    {
    //        $query = '';
    //
    //        $params = array_filter($params);
    //        $size = sizeof($params);
    //
    //        $count = 0;
    //        $query = "SELECT * FROM lancamento_financeiro WHERE ";
    //        foreach ($params as $key => $l) {
    //            $count++;
    //            $query .= $size > $count ? $key . " = " . $l . "  AND " : $key . " = " . $l;
    //        }
    //        if ($count == 0) {
    //            $query .= "1 = 1";
    //        }
    //        $query .= ";";
    //        $sth = $conexao->prepare($query);
    //        if (!$sth->execute()) {
    //            throw new Exception('Erro ao gerar lancamento financeiro', 1);
    //        }
    //
    //        return $sth->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function buscarLancamento(PDO $conexao, array $params)
    //    {
    //        $query = '';
    //
    //        $params = array_filter($params);
    //        $size = sizeof($params);
    //
    //        $count = 0;
    //        $query = "SELECT lancamento_financeiro.*,(SELECT razao_social FROM colaboradores WHERE colaboradores.id=lancamento_financeiro.id_colaborador)razao_social, CASE
    //        WHEN faturamento.tabela_preco = 1 THEN 'Cartão'
    //        WHEN faturamento.tabela_preco = 2 THEN 'Depósito'
    //        WHEN faturamento.tabela_preco = 3 THEN 'Boleto'
    //        WHEN faturamento.tabela_preco = 4 THEN 'Crédito'
    //        ELSE ''
    //      END as pagamento,
    //      (SELECT DATE_FORMAT(lf2.data_pagamento, '%d/%m/%Y') FROM lancamento_financeiro lf2 WHERE lf2.pedido_origem = lancamento_financeiro.pedido_origem AND lf2.origem = 'FA' AND lf2.situacao = 2) data_pagamento_faturamento
    //      FROM lancamento_financeiro
    //      LEFT OUTER JOIN faturamento ON (faturamento.id = lancamento_financeiro.pedido_origem) WHERE ";
    //        foreach ($params as $key => $l) {
    //            $count++;
    //            if ($key == 'id') {
    //                $query .= $size > $count ? "lancamento_financeiro." . $key . " = " . $l . "  AND " : "lancamento_financeiro." . $key . " = " . $l;
    //            } else {
    //                $query .= $size > $count ? $key . " = " . $l . "  AND " : $key . " = " . $l;
    //            }
    //        }
    //        if ($count == 0) {
    //            $query .= "1 = 1";
    //        }
    //        $query .= ";";
    //        $sth = $conexao->prepare($query);
    //        if (!$sth->execute()) {
    //            throw new Exception('Erro ao gerar lancamento financeiro', 1);
    //        }
    //
    //        return $sth->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    public static function __buscar(PDO $conexao, array $params)
    {
        $query = '';

        $params = array_filter($params);
        unset($params['action']);
        $size = sizeof($params);

        $count = 0;
        $query =
            'SELECT lancamento_financeiro.id,  lancamento_financeiro.numero_documento,  lancamento_financeiro.tipo,  lancamento_financeiro.situacao, colaboradores.razao_social, lancamento_financeiro.data_vencimento, lancamento_financeiro.valor FROM lancamento_financeiro INNER JOIN colaboradores ON(colaboradores.id= lancamento_financeiro.id_colaborador) WHERE ';

        foreach ($params as $key => $l) {
            $count++;
            $query .= $size > $count ? $key . ' = ' . $l . '  AND ' : $key . ' = ' . $l;
        }
        if ($count == 0) {
            $query .= '1 = 1 ORDER BY lancamento_financeiro.id DESC';
        }
        $query .= ';';
        $sth = $conexao->prepare($query);
        if (!$sth->execute()) {
            throw new Exception('Erro ao gerar lancamento financeiro', 1);
        }

        return $sth->fetchAll(PDO::FETCH_NUM);
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

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public function buscaLancamentosDevolucao(PDO $conexao, int $id_colaborador): array
    //    {
    //        $sth = $conexao->prepare("
    //            SELECT id,
    //            valor,
    //            id_colaborador
    //            FROM lancamento_financeiro
    //            WHERE valor>0 AND tipo='P' AND situacao=1 AND id_colaborador=:id_colaborador
    //            ");
    //        $sth->bindValue('id_colaborador', $id_colaborador, PDO::PARAM_INT);
    //        $sth->execute();
    //        return $sth->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public function buscaSomatorioLancamentos(PDO $conexao, array $params)
    //    {
    //        $params = array_filter($params);
    //        $size = sizeof($params);
    //        $count = 0;
    //        $query = "SELECT COALESCE(SUM(lf.valor),0) valor FROM lancamento_financeiro lf WHERE 1=1 ";
    //        foreach ($params as $key => $l) {
    //            $query .= $l;
    //        }
    //        $query .= ";";
    //        $stm = $conexao->prepare($query);
    //        $stm->execute();
    //        $linha = $stm->fetch(PDO::FETCH_ASSOC);
    //        return $linha['valor'];
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public function buscaVendasColaborador(PDO $conexao, int $id, array $params)
    //    {
    //        extract($params);
    //
    //        $query = "SELECT
    //        'Venda' tipo,
    //        DATE_FORMAT(fis.data_hora,'%d/%m/%Y %H:%m:%s') data_hora,
    //        (SELECT p.descricao FROM produtos p WHERE p.id=fis.id_produto) produto,
    //        fis.nome_tamanho tamanho,
    //        fis.id_faturamento pedido,
    //        fis.preco valor_bruto,
    //        (fis.preco - fis.comissao_fornecedor) comissao,
    //        fis.comissao_fornecedor valor_liquido
    //        FROM faturamento_item fis WHERE fis.id_fornecedor = :id ORDER BY fis.data_hora DESC LIMIT 1000;";
    //        $stm = $conexao->prepare($query);
    //        $stm->bindValue('id', $id, PDO::PARAM_INT);
    //        $stm->execute();
    //        return $stm->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public function buscaSaldoLancamentosColaborador(PDO $conexao, int $id, int $bloqueado)
    //    {
    //        $query = "SELECT COALESCE(SUM(CASE WHEN lf.tipo = 'P' AND lf.origem = 'SP' THEN lf.valor ELSE 0 END) -
    //        (SUM(CASE WHEN lf.tipo = 'R' AND lf.origem = 'FA' THEN lf.valor ELSE 0 END)),0) saldo
    //        FROM lancamento_financeiro lf
    //        WHERE lf.id_colaborador = :id AND lf.situacao=2 AND lf.bloqueado = :bloqueado;";
    //        $stm = $conexao->prepare($query);
    //        $stm->bindValue('id', $id, PDO::PARAM_INT);
    //        $stm->bindValue('bloqueado', $bloqueado, PDO::PARAM_INT);
    //        $stm->execute();
    //        $saldo = $stm->fetch(PDO::FETCH_ASSOC);
    //        return $saldo['saldo'];
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    //    public function buscaLancamentos(PDO $conexao, array $fields, array $params, array $group, array $order)
    //    {
    //        $query = "SELECT ";
    //
    //        if (sizeof($fields) > 0) {
    //            foreach ($fields as $key => $f) {
    //                $query .= " {$f},";
    //            }
    //        }
    //
    //        $query .= " 1 FROM lancamento_financeiro WHERE 1=1";
    //
    //        if (sizeof($params) > 0) {
    //            foreach ($params as $key => $p) {
    //                $query .= " {$p}";
    //            }
    //        }
    //
    //        if (sizeof($group) > 0) {
    //            foreach ($group as $key => $g) {
    //                $query .= " {$g}";
    //            }
    //        }
    //
    //        if (sizeof($order) > 0) {
    //            foreach ($order as $key => $o) {
    //                $query .= " {$o}";
    //            }
    //        }
    //
    //        $stm = $conexao->prepare($query);
    //        $stm->execute();
    //        $resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
    //        if (sizeof($resultado) > 0) {
    //            return $resultado;
    //        }
    //        return [];
    //    }

    public static function removeLancamentoTemporaria(PDO $conexao, int $idtransacao)
    {
        $remove = $conexao->prepare('DELETE FROM lancamento_financeiro_pendente
                                     WHERE lancamento_financeiro_pendente.transacao_origem = :idtransacao');
        $remove->bindParam(':idtransacao', $idtransacao, PDO::PARAM_INT);
        $remove->execute();
    }

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function removeLancamentoCancelamento(PDO $conexao, int $idFaturamento)
    //    {
    //        $conexao->exec('CALL atualiza_lancamento(0)');
    //        $remove = $conexao->prepare("DELETE FROM lancamento_financeiro
    //                                     WHERE  lancamento_financeiro.situacao = 1
    //                                            AND lancamento_financeiro.pedido_origem = :idFaturamento
    //                                            AND lancamento_financeiro.origem <> 'AU'");
    //        $remove->bindParam(':idFaturamento', $idFaturamento, PDO::PARAM_INT);
    //        $remove->execute();
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public function buscaDetalhesSplit(PDO $conexao, string $idSplit)
    //    {
    //        $query = "SELECT * FROM lancamentos_financeiros_recebiveis WHERE id_zoop_split = '{$idSplit}'";
    //        $stm = $conexao->prepare($query);
    //        $stm->execute();
    //        return $stm->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public function buscaLancamentosRecebiveis(PDO $conexao, array $fields, array $where, array $group, array $order)
    //    {
    //        $query = "SELECT 1";
    //        foreach ($fields as $key => $f) {
    //            $query .= " , {$f}";
    //        }
    //        $query .= " FROM lancamentos_financeiros_recebiveis WHERE 1=1";
    //        foreach ($where as $key => $w) {
    //            $query .= " {$w}";
    //        }
    //        $stm = $conexao->prepare($query);
    //        $stm->execute();
    //        return $stm->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // public static function ConsultaLancamentosFornecedor(PDO $conexao, int $idFornecedor, string $mes, string $ano, int $situacao)
    // {
    //     $query = "SELECT lf.pedido_origem faturamento,
    //     lf.id id_lancamento, lf.parcelamento,
    //     (SELECT DATE_FORMAT(lf.data_emissao,'%d/%m/%Y'))data_emissao,
    //     (SELECT DATE_FORMAT(lf.data_pagamento,'%d/%m/%Y'))data_pagamento,
    //     lf.valor FROM lancamento_financeiro lf
    //     INNER JOIN faturamento f ON f.id=lf.pedido_origem AND f.situacao=2
    //     WHERE lf.id_colaborador=:idFornecedor AND lf.tipo = 'P' AND lf.situacao=:situacao AND lf.origem IN ('SC','SP')
    //     AND MONTH(lf.data_pagamento) = '{$mes}' AND YEAR(lf.data_pagamento) = '{$ano}'
    //     ORDER BY lf.data_pagamento DESC;";
    //     $consulta = $conexao->prepare($query);
    //     $consulta->bindParam(':idFornecedor', $idFornecedor, PDO::PARAM_INT);
    //     $consulta->bindParam(':situacao', $situacao, PDO::PARAM_INT);
    //     $consulta->execute();
    //     $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //     return $resposta;
    // }

    // public static function ConsultaAbertosFornecedor(PDO $conexao, int $idFornecedor, string $mes, string $ano)
    // {
    //     $query = "SELECT lf.pedido_origem faturamento,
    //     lf.id id_lancamento, lf.tabela,
    //     (SELECT DATE_FORMAT(lf.data_emissao,'%d/%m/%Y'))data_emissao,
    //     (SELECT DATE_FORMAT(lf.data_vencimento,'%d/%m/%Y'))data_pagamento,
    //     lf.valor FROM lancamento_financeiro lf
    //     WHERE lf.id_colaborador=:idFornecedor AND lf.tipo = 'P' AND lf.situacao=1 AND lf.origem in ('SP','SC','AU')
    //     AND MONTH(lf.data_emissao) = {$mes} AND YEAR(lf.data_emissao) = {$ano}
    //     ORDER BY lf.data_emissao DESC;";
    //     $consulta = $conexao->prepare($query);
    //     $consulta->bindParam(':idFornecedor', $idFornecedor, PDO::PARAM_INT);
    //     $consulta->execute();
    //     $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC);
    //     return $resposta;
    // }

    // public static function buscaLancamentosPedido(PDO $conexao, int $pedido, int $idColaborador)
    // {
    //     $query = "SELECT lf.id, lf.situacao, lf.data_emissao, lf.valor, lf.tabela, lf.pedido_origem, lf.origem,
    //     lf.id, lf.parcelamento FROM lancamento_financeiro lf
    //     WHERE lf.pedido_origem = {$pedido} AND lf.id_colaborador = {$idColaborador} AND lf.origem in ('SP','SC');";
    //     $stm = $conexao->prepare($query);
    //     $stm->execute();
    //     return $stm->fetchAll(PDO::FETCH_ASSOC);
    // }

    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function buscaLancamentosDeposito(PDO $conexao, int $pedido, int $idColaborador)
    //    {
    //        $query = "SELECT valor, COALESCE(DATE_FORMAT(data_vencimento,'%d/%m/%Y'),' ') data_pagamento, situacao, 1 AS num_parcela, 1 AS qParcelas
    //        FROM lancamento_financeiro WHERE pedido_origem={$pedido} AND id_colaborador={$idColaborador} AND origem IN ('SP','SC');";
    //        $stm = $conexao->prepare($query);
    //        $stm->execute();
    //        return $stm->fetchAll(PDO::FETCH_ASSOC);
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    // public static function buscaLancamentosPorId(PDO $conexao, int $lancamento, int $idColaborador)
    // {
    //     $query = "SELECT lf.id, lf.situacao, lf.documento_pagamento, lf.data_emissao, lf.valor, lf.tabela, lf.pedido_origem, lf.origem,
    //     lf.id, lf.parcelamento FROM lancamento_financeiro lf
    //     WHERE lf.id = {$lancamento} AND lf.id_colaborador = {$idColaborador} AND lf.origem in ('SP','SC','AU');";
    //     $stm = $conexao->prepare($query);
    //     $stm->execute();
    //     return $stm->fetchAll(PDO::FETCH_ASSOC);
    // }

    public static function criaPagamentoLancamento(PDO $conexao, Lancamento $lancamento)
    {
        $lancamento->id = null;
        $lancamento->origem = 'LP';
        $lancamento->data_emissao = date('Y-m-d H:i:s');
        $lancamento->data_pagamento = date('Y-m-d H:i:s');
        $lancamento->observacao = 'Geração de Lançamento Contra Partida';

        $query = '';
        $dados = $lancamento->extrair();
        $dados = array_filter($dados, function ($i) {
            return $i !== null;
        });
        $size = sizeof($dados);

        $count = 0;
        $query = 'INSERT INTO lancamento_financeiro (';
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
            $sth->bindValue($key, $l, (new LancamentoService())->typeof($l));
        }

        if (!$sth->execute()) {
            throw new Exception('Erro ao gerar lancamento financeiro', 1);
        }

        $lancamento->id = $conexao->lastInsertId();
        return $lancamento;
    }
    // --Commented out by Inspection START (15/08/2022 11:16):
    //    public static function atualizaPagamentoLancamento(Lancamento $lancamento, PDO $conexao = null): Lancamento
    //    {
    //
    //        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();
    //
    //
    //        if ($conexao->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
    //            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //        }
    //
    //        $campos = $lancamento->extrair();
    //        unset($campos['id']);
    //        unset($campos['situacao']); //Campo situação não pode ser alterado
    //        unset($campos['valor_pago']);
    //        $sqlLoop = "";
    //        $bindValues = [];
    //        foreach ($campos as $campo => $value) {
    //
    //            $sqlLoop .= "$campo = :$campo,";
    //            $bindValues = array_merge($bindValues, [":$campo" => $value]);
    //        }
    //        $sqlLoopCorreto = substr($sqlLoop, 0, strlen($sqlLoop) - 1);
    //        $sql = "UPDATE lancamento_financeiro SET origem = origem, $sqlLoopCorreto WHERE id = {$lancamento->id} ;";
    //
    //        $execSql = $conexao->prepare($sql);
    //
    //        foreach ($bindValues as $key => $valor) :
    //            $execSql->bindValue($key, $valor);
    //        endforeach;
    //
    //        $Q1 = $execSql->execute();
    //        $Q2 = $conexao->query(' CALL atualiza_lancamento(0);')->execute();
    //        if ($Q1 && $Q2) :
    //            return $lancamento;
    //        else :
    //            throw new Exception("Erro ao atualizar lançamento", 500);
    //        endif;
    //    }
    // --Commented out by Inspection STOP (15/08/2022 11:16)

    //    public static function buscaPagamentoSellerFaturamento(PDO $conexao = null, int $transacao)
    //    {
    //        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();
    //        $query = "SELECT  lancamento_financeiro.id,
    //        lancamento_financeiro.pedido_origem,
    //        lancamento_financeiro.transacao_origem,
    //        lancamento_financeiro.situacao,
    //        lancamento_financeiro.valor,
    //        lancamento_financeiro.juros,
    //        lancamento_financeiro.valor_total,
    //        lancamento_financeiro.origem,
    //        lancamento_financeiro.observacao,
    //        colaboradores.razao_social,
    //        IF(lancamento_financeiro.origem = 'PF','Recebeu','Gerou Crédito') descricao
    //    FROM lancamento_financeiro
    //        INNER JOIN colaboradores ON colaboradores.id = lancamento_financeiro.id_colaborador
    //    WHERE lancamento_financeiro.transacao_origem = :transacao AND lancamento_financeiro.origem IN ('PF','SC')";
    //        $resultado = $conexao->prepare($query);
    //        $resultado->bindParam(':transacao',$transacao,PDO::PARAM_INT);
    //        $resultado->execute();
    //        $retorno = $resultado->fetchAll(PDO::FETCH_ASSOC);
    //        return $retorno;
    //    }

    // --Commented out by Inspection START (22/08/2022 17:47):
    //    public static function ListaProdutoAtendimento(\PDO $conexao, int $idCliente, int $idFaturamento, int $idProduto, string $nomeTamanho): array
    //    {
    //        $sql = $conexao->prepare(
    //            "SELECT
    //                faturamento_item.id_faturamento,
    //                faturamento_item.preco,
    //                faturamento_item.data_conferencia,
    //                faturamento_item.nome_tamanho,
    //                produtos.descricao,
    //                (
    //                    SELECT produtos_foto.caminho
    //                    FROM produtos_foto
    //                    WHERE produtos_foto.id = faturamento_item.id_produto
    //                    GROUP BY produtos_foto.id
    //                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
    //                    LIMIT 1
    //                )foto
    //            FROM faturamento_item
    //            INNER JOIN produtos ON produtos.id = faturamento_item.id_produto
    //            WHERE faturamento_item.id_faturamento = :id_faturamento
    //                AND faturamento_item.id_produto = :id_produto
    //                AND faturamento_item.nome_tamanho = :nome_tamanho
    //                AND faturamento_item.id_cliente = :id_cliente;"
    //        );
    //        $sql->bindValue(":id_faturamento", $idFaturamento, PDO::PARAM_INT);
    //        $sql->bindValue(":id_produto", $idProduto, PDO::PARAM_INT);
    //        $sql->bindValue(":nome_tamanho", $nomeTamanho, PDO::PARAM_STR);
    //        $sql->bindValue(":id_cliente", $idCliente, PDO::PARAM_INT);
    //        $sql->execute();
    //        $produtos = $sql->fetchAll(PDO::FETCH_ASSOC);
    //
    //        return $produtos;
    //    }
    // --Commented out by Inspection STOP (22/08/2022 17:47)

    public static function buscaExtratoFornecedor(
        PDO $conexao,
        int $idColaborador,
        ?string $dataInicial,
        ?string $dataFinal
    ): array {
        $stmt = $conexao->prepare(
            "SELECT
                DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%y') data,
                lancamento_financeiro.origem,
                IF(
                    lancamento_financeiro.tipo = 'P',
                    lancamento_financeiro.valor,
                    -lancamento_financeiro.valor
                ) valor,
                IF(
                    lancamento_financeiro.faturamento_criado_pago = 'T',
                    -lancamento_financeiro.valor_pago,
                    NULL
                ) valor_pago,
                lancamento_financeiro.faturamento_criado_pago,
                lancamento_financeiro.transacao_origem,
                DATE(lancamento_financeiro.data_emissao) dataAux,
                IF(lancamento_financeiro.origem = 'TF'
                    AND (SELECT 1
                        FROM entregas_devolucoes_item
                        WHERE entregas_devolucoes_item.uuid_produto = lancamento_financeiro.numero_documento
                        AND entregas_devolucoes_item.tipo = 'DE'
                        LIMIT 1
                    ), 'Defeito', 'Normal') motivo_cancelamento
            FROM lancamento_financeiro
            WHERE
                lancamento_financeiro.id_colaborador = :idColaborador AND
                lancamento_financeiro.origem <> 'AU'
            ORDER BY lancamento_financeiro.id DESC"
        );
        $stmt->execute([':idColaborador' => $idColaborador]);
        $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($lancamentos)) {
            return [];
        }

        $stmt = $conexao->prepare('SELECT saldo_cliente(:idColaborador) saldo');
        $stmt->execute([':idColaborador' => $idColaborador]);
        $saldo = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
        if ($saldo === false) {
            $saldo = 0;
        }

        $lancamentosRetorno = [];
        foreach ($lancamentos as $index => $lancamento) {
            if ($index === 0) {
                $lancamento['saldo'] = $saldo;
            } else {
                $lancamentoAnterior = $lancamentos[$index - 1];
                if ($lancamentoAnterior['faturamento_criado_pago'] === 'T') {
                    $lancamento['saldo'] = $lancamentoAnterior['saldo'];
                    unset($lancamentos[$index - 1]['faturamento_criado_pago']);
                } else {
                    $lancamento['saldo'] = $lancamentoAnterior['saldo'] - $lancamentoAnterior['valor'];
                }
            }

            $lancamento['origem'] = Lancamento::buscaTextoPelaOrigem($lancamento['origem'], true);

            $data = $lancamento['dataAux'];
            if (!$dataInicial && !$dataFinal) {
                $lancamentosRetorno[] = $lancamento;
            } elseif ($dataInicial && $dataFinal) {
                if ($dataInicial <= $data && $data <= $dataFinal) {
                    $lancamentosRetorno[] = $lancamento;
                }
            } else {
                if ($dataInicial && $data >= $dataInicial) {
                    $lancamentosRetorno[] = $lancamento;
                }
                if ($dataFinal && $data <= $dataFinal) {
                    $lancamentosRetorno[] = $lancamento;
                }
            }

            unset($lancamento['dataAux']);
            $lancamentos[$index] = $lancamento;
        }

        return $lancamentosRetorno;
    }

    /**
     * @param Lancamento[] $listaLancamentosFlip
     */
    public static function insereVarios(PDO $conexao, array $listaLancamentosFlip): void
    {
        if (empty($listaLancamentosFlip)) {
            return;
        }

        $camposLancamento = array_reduce(
            $listaLancamentosFlip,
            function (array $total, $lancamento) {
                return array_merge(
                    $total,
                    array_values(array_diff(array_keys(array_filter($lancamento->extrair())), $total))
                );
            },
            []
        );
        sort($camposLancamento);

        $camposLancamentoSql = implode(',', $camposLancamento);
        $bindValues = [];

        $values = implode(
            ',',
            array_map(
                function ($lancamento, int $key) use ($camposLancamento, &$bindValues) {
                    // Alterar array para sempre ter os indexes da var $camposLancamento
                    $listaItens = array_filter($lancamento->extrair());
                    $itensNaoAdicionados = array_diff($camposLancamento, array_keys($listaItens));
                    foreach ($itensNaoAdicionados as $campo) {
                        $listaItens[$campo] = ConversorArray::CAMPO_PADRAO_CRIA_BIND_VALUES;
                    }
                    ksort($listaItens);

                    [$dados, $bindTemp] = ConversorArray::criaBindValues($listaItens, "l{$key}");
                    $bindValues = array_merge($bindTemp, $bindValues);

                    return '(' . $dados . ')';
                },
                $listaLancamentosFlip,
                array_keys($listaLancamentosFlip)
            )
        );

        $stmt = $conexao->prepare(
            "INSERT INTO lancamento_financeiro ($camposLancamentoSql)
             VALUES $values"
        );
        $stmt->execute($bindValues);

        if ($stmt->rowCount() !== count($listaLancamentosFlip)) {
            throw new \DomainException('Quantidade de lançamentos inseridos está inconsistente.');
        }
    }

    public static function listaLancamentosPendentesTransacao(PDO $conexao, int $idTransacao): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                lancamento_financeiro_pendente.id AS sequencia,
                lancamento_financeiro_pendente.tipo,
                lancamento_financeiro_pendente.documento,
                lancamento_financeiro_pendente.origem,
                lancamento_financeiro_pendente.id_colaborador,
                lancamento_financeiro_pendente.valor,
                lancamento_financeiro_pendente.valor_total,
                IF(lancamento_financeiro_pendente.origem = 'FA', lancamento_financeiro_pendente.valor, 0) AS valor_pago,
                lancamento_financeiro_pendente.id_usuario,
                lancamento_financeiro_pendente.numero_documento,
                lancamento_financeiro_pendente.observacao,
                lancamento_financeiro_pendente.transacao_origem,
                lancamento_financeiro_pendente.cod_transacao
             FROM lancamento_financeiro_pendente
             WHERE lancamento_financeiro_pendente.transacao_origem = :idTransacao
               AND lancamento_financeiro_pendente.origem <> 'PC'
               AND NOT EXISTS(SELECT 1
                              FROM transacao_financeiras_produtos_itens
                              WHERE transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro_pendente.numero_documento
                                AND transacao_financeiras_produtos_itens.id_transacao = lancamento_financeiro_pendente.transacao_origem
                                AND transacao_financeiras_produtos_itens.momento_pagamento = 'CARENCIA_ENTREGA')"
        );

        $stmt->bindValue(':idTransacao', $idTransacao, PDO::PARAM_INT);
        $stmt->execute();
        $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $lancamentos;
    }
}

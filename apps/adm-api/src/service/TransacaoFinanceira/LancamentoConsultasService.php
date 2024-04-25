<?php

namespace MobileStock\service\TransacaoFinanceira;

use Database;
use Error;
use MobileStock\database\Conexao;
use PDO;
use phpDocumentor\Reflection\Types\Boolean;

class LancamentoConsultasService
{
    // public static function buscaLancamentoID(PDO $conexao, int $idFaturamento)
    // {
    //     $consulta = "SELECT lancamento_financeiro.id,
    //     CO_RE.razao_social receber, 
    //     CO_PA.razao_social pagar,
    //     lancamento_financeiro.observacao,
    //     lancamento_financeiro.transacao_origem,
    //     lancamento_financeiro.numero_documento,
    //     lancamento_financeiro.tipo,
    //     lancamento_financeiro.origem,
    //     lancamento_financeiro.situacao,
    //     lancamento_financeiro.valor_total,
    //     lancamento_financeiro.valor,
    //     lancamento_financeiro.juros,
    //     lancamento_financeiro.valor_pago,
    //     coalesce((SELECT transacao_financeiras.status FROM transacao_financeiras WHERE transacao_financeiras.id = lancamento_financeiro.id_colaborador),'ST') status,
    //     DATE_FORMAT(lancamento_financeiro.data_emissao,'%d/%m/%Y %H:%i:%s') data_atualizada,
    //     DATE_FORMAT(lancamento_financeiro.data_vencimento,'%d/%m/%Y %H:%i:%s') data_venc_atual,
    //     DATE_FORMAT(lancamento_financeiro.data_pagamento,'%d/%m/%Y %H:%i:%s') data_pag      
    // FROM lancamento_financeiro
    //     INNER JOIN colaboradores CO_PA ON CO_PA.id =  lancamento_financeiro.id_pagador
    //     INNER JOIN colaboradores CO_RE ON CO_RE.id = lancamento_financeiro.id_recebedor
    // WHERE lancamento_financeiro.id =  {$idFaturamento}";
    //     $stmt = $conexao->prepare($consulta);
    //     $stmt->execute();
    //     $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $retorno;
    // }
    
    // public static function buscaRecebiveisTransacao(PDO $conexao, int $idFaturamento)
    // {
    //     $consulta = "SELECT lancamentos_financeiros_recebiveis.id,
    //                         lancamentos_financeiros_recebiveis.id_lancamento,
    //                         lancamentos_financeiros_recebiveis.situacao,
    //                         lancamentos_financeiros_recebiveis.num_parcela,
    //                         lancamentos_financeiros_recebiveis.valor_pago,
    //                         lancamentos_financeiros_recebiveis.valor,
    //                         DATE_FORMAT( lancamentos_financeiros_recebiveis.data_vencimento,'%d/%m/%Y %H:%i:%s') data_pagamento
    //                         FROM lancamentos_financeiros_recebiveis 
    //                         WHERE lancamentos_financeiros_recebiveis.id_transacao = {$idFaturamento}";
    //     $stmt = $conexao->prepare($consulta);
    //     $stmt->execute();
    //     $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $retorno;
    // }

    // public static function buscaLancamentoTransacao(PDO $conexao, int $idFaturamento)
    // {
    //     $consulta = "SELECT lancamento_financeiro.id,
    //     lancamento_financeiro.tipo,
    //     lancamento_financeiro.origem,
    //     lancamento_financeiro.valor,
    //     lancamento_financeiro.valor_pago,
    //     lancamento_financeiro.observacao, 
    //     coalesce((lancamento_financeiro.lancamento_origem),'S/ Lançamento')lancamento_origem,
    //     if(lancamento_financeiro.id_lancamento_pag = $idFaturamento, 'Pago','Recebeu') acao,
    //     lancamento_financeiro.id_lancamento_pag,
    //     coalesce ((SELECT transacao_financeiras.status FROM transacao_financeiras WHERE transacao_financeiras.id = lancamento_financeiro.id_colaborador),'ST') status
    // FROM lancamento_financeiro
    // WHERE lancamento_financeiro.id_lancamento_pag IN (SELECT LF.id FROM lancamento_financeiro LF WHERE LF.lancamento_origem = $idFaturamento)
    //     OR lancamento_financeiro.id IN (SELECT LF.id FROM lancamento_financeiro LF WHERE LF.lancamento_origem = $idFaturamento)
    //     OR lancamento_financeiro.id_lancamento_pag = $idFaturamento
    //     OR lancamento_financeiro.id = (SELECT LR.id_lancamento_pag FROM lancamento_financeiro LR WHERE LR.id = $idFaturamento) 
    //     OR lancamento_financeiro.id = $idFaturamento";
    //     $stmt = $conexao->prepare($consulta);
    //     $stmt->execute();
    //     $retorno = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $retorno;
    // }

    public static function  buscaTransacao(PDO $conexao, int $idFaturamento)
    {
        $consulta = "SELECT transacao_financeiras.status,
                            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id =  transacao_financeiras.pagador) pagador,
                            transacao_financeiras.id,
                            transacao_financeiras.cod_transacao,
                            transacao_financeiras.valor_total,
                            transacao_financeiras.valor_credito,
                            transacao_financeiras.valor_acrescimo,
                            transacao_financeiras.valor_comissao_fornecedor,
                            transacao_financeiras.valor_liquido,
                            transacao_financeiras.valor_itens,
                            transacao_financeiras.valor_taxas,
                            transacao_financeiras.juros_pago_split,
                            transacao_financeiras.numero_transacao,
                            transacao_financeiras.metodos_pagamentos_disponiveis,
                            transacao_financeiras.valor_comissao_fornecedor,
                            transacao_financeiras.responsavel,
                            (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id =  transacao_financeiras.responsavel) responsavel,
                            transacao_financeiras.metodo_pagamento,
                            transacao_financeiras.numero_parcelas,
                            transacao_financeiras.url_boleto,
                            transacao_financeiras.origem_transacao, 
                            transacao_financeiras.qrcode_pix,
                            transacao_financeiras.qrcode_text_pix, 
                            transacao_financeiras.emissor_transacao,
                            transacao_financeiras.pagador as id_pagador,
                            DATE_FORMAT(transacao_financeiras.data_criacao,'%d/%m/%Y %H:%i:%s') data1,
                            DATE_FORMAT(transacao_financeiras.data_atualizacao,'%d/%m/%Y %H:%i:%s') data_atualizacao
                                FROM transacao_financeiras
                                    WHERE transacao_financeiras.id = $idFaturamento";
        $stmt = $conexao->prepare($consulta);
        $stmt->execute();
        $retorno = $stmt->fetch(PDO::FETCH_ASSOC);
        return $retorno;
    }

    public static function buscaLancamentosDaTransacao(PDO $conexao, int $id): array
    {
        $consulta = $conexao->query(
            "SELECT
                lancamentos.*,
                (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = lancamentos.id_colaborador LIMIT 1) colaborador
            FROM (SELECT
                        lancamento_financeiro.id,
                        'normal' tipo,
                        IF(lancamento_financeiro.tipo = 'P', lancamento_financeiro.valor, lancamento_financeiro.valor * -1) valor,
                        lancamento_financeiro.faturamento_criado_pago,
                        lancamento_financeiro.origem,
                        lancamento_financeiro.id_colaborador,
                        DATE_FORMAT(lancamento_financeiro.data_emissao, '%d/%m/%Y %H:%i:%s') data_emissao
                    FROM lancamento_financeiro
                    WHERE lancamento_financeiro.transacao_origem = $id
                    
                    UNION ALL
                    
                    SELECT 
                        lancamento_financeiro_pendente.id,
                        'pendente',
                        IF(lancamento_financeiro_pendente.tipo = 'P', lancamento_financeiro_pendente.valor, lancamento_financeiro_pendente.valor *-1) valor,
                        'F' faturamento_criado_pago,
                        lancamento_financeiro_pendente.origem,
                        lancamento_financeiro_pendente.id_colaborador,
                        DATE_FORMAT(lancamento_financeiro_pendente.data_emissao, '%d/%m/%Y %H:%i:%s') data_emissao
                    FROM lancamento_financeiro_pendente
                    WHERE lancamento_financeiro_pendente.transacao_origem = $id) lancamentos
            ORDER BY lancamentos.data_emissao ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $consulta;
    }
}
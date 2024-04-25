<?php /*

namespace MobileStock\service\Faturamento;

use MobileStock\database\Conexao;
use PDO;

class FaturamentoConsultasService{
//    public static function buscaFaturamento(PDO $conexao, int $idFaturamento){
//        $consulta = "  SELECT   faturamento.id,
//                                faturamento.id_cliente,
//                                faturamento.data_fechamento,
//                                faturamento.valor_total,
//                                faturamento.valor_liquido,
//                                faturamento.valor_frete,
//                                faturamento.pares,
//                                faturamento.separado,
//                                faturamento.conferido,
//                                faturamento.expedido,
//                                faturamento.entregue,
//                                (SELECT nome FROM usuarios WHERE usuarios.id = faturamento.id_separador)id_separador,
//                                (SELECT nome FROM usuarios WHERE usuarios.id = faturamento.id_conferidor)id_conferidor,
//                                (SELECT nome FROM usuarios WHERE usuarios.id =faturamento.id_expedidor)id_expedidor,
//                                (SELECT nome FROM usuarios WHERE usuarios.id = faturamento.id_entregador)id_entregador,
//                                faturamento.data_separacao,
//                                faturamento.data_conferencia,
//                                faturamento.data_expedicao,
//                                faturamento.data_entrega,
//                                faturamento.observacao,
//                                DATE_FORMAT(faturamento.data_emissao,'%d/%m/%Y  %H:%i:%s') data_emissao,
//                                (
//                                    CASE WHEN faturamento.situacao = 1
//                                        THEN 'Aberto'
//                                        ELSE 'Pago'
//                                    END
//                                )situacao,
//
//                                (
//                                    SELECT tipo_frete.nome
//                                        FROM tipo_frete
//                                            WHERE tipo_frete.id = faturamento.tipo_frete
//                                )entrega,
//                                (
//                                    SELECT colaboradores.razao_social
//                                        FROM colaboradores
//                                            WHERE faturamento.id_cliente = colaboradores.id
//                                )colaborador
//                                    FROM faturamento
//                                        WHERE faturamento.id = {$idFaturamento}";
//        $stmt = $conexao->prepare($consulta);
//        $stmt->execute();
//        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
//        return $resultado;
//    }

//
//    public static function faturamentoUltimoTransacao(PDO $conexao, int $idFaturamento){
//        $consulta = " (SELECT transacao_financeiras.metodo_pagamento
//						FROM transacao_financeiras
//						  	WHERE transacao_financeiras.id
//							  	IN (
//								  		SELECT MAX(transacao_financeiras_faturamento.id_transacao)
//								  			FROM transacao_financeiras_faturamento
//												WHERE transacao_financeiras_faturamento.id_faturamento = {$idFaturamento}
//									))meio_pagamento";
//        $stmt = $conexao->prepare($consulta);
//        $stmt->execute();
//        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
//        return $resultado['metodo_pagamento'];
//    }
//


}

?>*/
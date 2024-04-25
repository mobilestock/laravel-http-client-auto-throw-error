<?php
namespace MobileStock\service\Recebivel;

use Exception;
use PDO;

class RecebivelService {

    public static function existeRecebivel(PDO $conexao, string $idRecebivel)
    {
        $sql = "SELECT * FROM lancamentos_financeiros_recebiveis WHERE id_zoop_recebivel=:id_zoop_recebivel;";
        $stm = $conexao->prepare($sql);
        $stm->bindValue("id_zoop_recebivel",$idRecebivel, PDO::PARAM_STR);
        $stm->execute();
        return $stm->fetchAll();
    }

    public static function criaRecebivel(PDO $conexao, array $recebivel, int $lancamento, int $pedido, int $recebedor)
    {
        if(!RecebivelService::existeRecebivel($conexao,$recebivel['id'])) {

            $status = $recebivel['status']=='paid'?'PA':'PE';
            $paitAt = $recebivel['paid_at']!=null?date('Y-m-d H:i:s',strtotime($recebivel['paid_at'])):null;
            $expectedOn = $recebivel['expected_on']!=null?date('Y-m-d H:i:s',strtotime($recebivel['expected_on'])):null;
            $createdAt = $recebivel['created_at']!=null?date('Y-m-d H:i:s',strtotime($recebivel['created_at'])):null;

            $sql = "INSERT INTO lancamentos_financeiros_recebiveis (id_lancamento, 
            id_zoop_recebivel, 
            situacao, 
            id_zoop_split, 
            id_recebedor, 
            num_parcela, 
            valor_pago, 
            valor,
            data_pagamento,
            data_vencimento,
            data_gerado,
            id_faturamento) VALUES
            ({$lancamento},
            '{$recebivel['id']}',
            '{$status}',
            '{$recebivel['split_rule']}',
            {$recebedor},
            '{$recebivel['installment']}',
            {$recebivel['amount']},
            {$recebivel['gross_amount']},
            '{$paitAt}',
            '{$expectedOn}',
            '{$createdAt}',
            {$pedido});";
            $stm = $conexao->prepare($sql);
            if(!$stm->execute()){
                new Exception('Erro ao atualizar o recebivel', 400);
            }
            return true;
        }else{
            return RecebivelService::atualizaRecebivel($conexao, $recebivel, $lancamento, $pedido, $recebedor);
        }
    }

    public static function atualizaRecebivel(PDO $conexao, array $recebivel, int $lancamento, int $pedido, int $recebedor)
    {
        $status = $recebivel['status']=='paid' ? 'PA' : 'PE';
        $paitAt = $recebivel['paid_at']!=null ? date('Y-m-d H:i:s',strtotime($recebivel['paid_at'])) : null;

        $sql = "UPDATE lancamentos_financeiros_recebiveis 
                SET situacao = '{$status}',
                valor_pago = {$recebivel['amount']},
                data_pagamento = '{$paitAt}'
                WHERE id_zoop_recebivel = '{$recebivel['id']}'";

        $stm = $conexao->prepare($sql);
        if(!$stm->execute()){
            new Exception('Erro ao atualizar o recebivel', 400);
        }
        return true;
    }
}
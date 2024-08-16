DROP TRIGGER `transacao_financeiras_produtos_trocas_before_delete`;

CREATE TRIGGER `transacao_financeiras_produtos_trocas_before_delete` BEFORE DELETE ON `transacao_financeiras_produtos_trocas` FOR EACH ROW BEGIN
	IF((saldo_cliente(OLD.id_cliente) + COALESCE((
	   SELECT SUM(troca_pendente_agendamento.preco)
	      FROM troca_pendente_agendamento
	      INNER JOIN transacao_financeiras_produtos_trocas ON transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid
	      WHERE transacao_financeiras_produtos_trocas.id_cliente = OLD.id_cliente
	        AND transacao_financeiras_produtos_trocas.uuid <> OLD.uuid
	  ), 0) + COALESCE((SELECT SUM(IF(lancamento_financeiro_pendente.tipo = 'P', lancamento_financeiro_pendente.valor, lancamento_financeiro_pendente.valor * -1)) 
	  							FROM lancamento_financeiro_pendente 
								WHERE lancamento_financeiro_pendente.id_colaborador = OLD.id_cliente
									AND lancamento_financeiro_pendente.origem IN ('PC', 'ES')),0)
	) < 0) THEN
	 signal sqlstate '45000' set MESSAGE_TEXT = 'Para deletar uma troca de uma transação é necessário cancelar a transacao';
	END IF;
END;

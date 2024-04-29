DELIMITER //

DROP TRIGGER IF EXISTS colaboradores_after_update //

CREATE TRIGGER colaboradores_after_update AFTER UPDATE ON colaboradores FOR EACH ROW BEGIN

	IF(NEW.id <> 1 AND NEW.bloqueado_repor_estoque = 'T' AND OLD.bloqueado_repor_estoque = 'F') THEN
		UPDATE estoque_grade
		SET estoque_grade.estoque = 0,
			 estoque_grade.tipo_movimentacao = 'X',
			 estoque_grade.descricao = CONCAT('Estoque zerado porque Seller ', estoque_grade.id_responsavel, ' teve muitas correções')
		WHERE estoque_grade.id_responsavel = NEW.id;

	END IF;

	SET @ID_USUARIO = (
		SELECT usuarios.id
		FROM usuarios
		WHERE usuarios.id_colaborador = NEW.id
		LIMIT 1
	);

	IF (@ID_USUARIO IS NOT NULL) THEN
		IF (OLD.telefone <> NEW.telefone) THEN
			UPDATE usuarios SET usuarios.telefone = NEW.telefone WHERE usuarios.id = @ID_USUARIO;
		END IF;
		IF (OLD.cnpj <> NEW.cnpj) THEN
			UPDATE usuarios SET usuarios.cnpj = NEW.cnpj WHERE usuarios.id = @ID_USUARIO;
		END IF;
		IF (OLD.email <> NEW.email) THEN
			UPDATE usuarios SET usuarios.email = NEW.email WHERE usuarios.id = @ID_USUARIO;
		END IF;
	END IF;

	INSERT INTO colaboradores_log (
		colaboradores_log.id_colaborador,
		mensagem
	) VALUES (
		NEW.id,
		JSON_OBJECT(
			'OLD_id', OLD.id,
			'NEW_id', NEW.id,
			'OLD_regime', OLD.regime,
			'NEW_regime', NEW.regime,
			'OLD_cnpj', OLD.cnpj,
			'NEW_cnpj', NEW.cnpj,
			'OLD_cpf', OLD.cpf,
			'NEW_cpf', NEW.cpf,
			'OLD_razao_social', OLD.razao_social,
			'NEW_razao_social', NEW.razao_social,
			'OLD_rg', OLD.rg,
			'NEW_rg', NEW.rg,
			'OLD_telefone', OLD.telefone,
			'NEW_telefone', NEW.telefone,
			'OLD_telefone2', OLD.telefone2,
			'NEW_telefone2', NEW.telefone2,
			'OLD_email', OLD.email,
			'NEW_email', NEW.email,
			'OLD_bloqueado', OLD.bloqueado,
			'NEW_bloqueado', NEW.bloqueado,
			'OLD_tipo', OLD.tipo,
			'NEW_tipo', NEW.tipo,
			'OLD_usuario', OLD.usuario,
			'NEW_usuario', NEW.usuario,
			'OLD_em_uso', OLD.em_uso,
			'NEW_em_uso', NEW.em_uso,
			'OLD_conta_principal', OLD.conta_principal,
			'NEW_conta_principal', NEW.conta_principal,
			'OLD_emite_nota', OLD.emite_nota,
			'NEW_emite_nota', NEW.emite_nota,
			'OLD_foto_perfil', OLD.foto_perfil,
			'NEW_foto_perfil', NEW.foto_perfil,
			'OLD_pagamento_bloqueado', OLD.pagamento_bloqueado,
			'NEW_pagamento_bloqueado', NEW.pagamento_bloqueado,
			'OLD_data_botao_atualiza_produtos_entrada', OLD.data_botao_atualiza_produtos_entrada,
			'NEW_data_botao_atualiza_produtos_entrada', NEW.data_botao_atualiza_produtos_entrada,
			'OLD_id_tipo_entrega_padrao', OLD.id_tipo_entrega_padrao,
			'NEW_id_tipo_entrega_padrao', NEW.id_tipo_entrega_padrao,
			'OLD_usuario_meulook', OLD.usuario_meulook,
			'NEW_usuario_meulook', NEW.usuario_meulook,
			'OLD_bloqueado_criar_look', OLD.bloqueado_criar_look,
			'NEW_bloqueado_criar_look', NEW.bloqueado_criar_look,
			'OLD_bloqueado_repor_estoque', OLD.bloqueado_repor_estoque,
			'NEW_bloqueado_repor_estoque', NEW.bloqueado_repor_estoque,
			'OLD_nome_instagram', OLD.nome_instagram,
			'NEW_nome_instagram', NEW.nome_instagram,
			'OLD_adiantamento_bloqueado', OLD.adiantamento_bloqueado,
			'NEW_adiantamento_bloqueado', NEW.adiantamento_bloqueado,
			'OLD_url_webhook', OLD.url_webhook,
			'NEW_url_webhook', NEW.url_webhook,
			'OLD_tipo_embalagem', OLD.tipo_embalagem,
			'NEW_tipo_embalagem', NEW.tipo_embalagem,
			'OLD_observacoes', OLD.observacoes,
			'NEW_observacoes', NEW.observacoes,
			'OLD_data_criacao', OLD.data_criacao,
			'NEW_data_criacao', NEW.data_criacao,
			'OLD_data_atualizacao', OLD.data_atualizacao,
			'NEW_data_atualizacao', NEW.data_atualizacao
		)
	);

END //

DELIMITER ;

ALTER TABLE colaboradores DROP COLUMN IF EXISTS inscrito_receber_novidades;

DROP TABLE IF EXISTS mensagens_novidades;

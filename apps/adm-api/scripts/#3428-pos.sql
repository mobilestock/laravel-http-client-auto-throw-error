-- Active: 1706877031039@@127.0.0.1@3306@_banco_dia21mes2_084411_NORMAL
DELIMITER $$

CREATE TRIGGER colaboradores_enderecos_after_update AFTER UPDATE ON colaboradores_enderecos FOR EACH ROW BEGIN
	INSERT INTO colaboradores_enderecos_logs (
		colaboradores_enderecos_logs.id_endereco,
        colaboradores_enderecos_logs.id_colaborador,
        colaboradores_enderecos_logs.mensagem
    ) VALUES (
		NEW.id,
        NEW.id_colaborador,
		JSON_OBJECT(
            'id', NEW.id,
            'id_colaborador', NEW.id_colaborador,
            'id_cidade', NEW.id_cidade,
            'id_usuario', NEW.id_usuario,
            'apelido', NEW.apelido,
            'nome_destinatario', NEW.nome_destinatario,
            'telefone_destinatario', NEW.telefone_destinatario,
            'esta_verificado', NEW.esta_verificado,
            'eh_endereco_padrao', NEW.eh_endereco_padrao,
            'logradouro', NEW.logradouro,
            'numero', NEW.numero,
            'complemento', NEW.complemento,
            'ponto_de_referencia', NEW.ponto_de_referencia,
            'bairro', NEW.bairro,
            'cidade', NEW.cidade,
            'uf', NEW.uf,
            'cep', NEW.cep,
            'latitude', NEW.latitude,
            'longitude', NEW.longitude,
            'data_criacao', NEW.data_criacao,
            'data_atualizacao', NEW.data_atualizacao
        )
    );
END$$

DROP TRIGGER IF EXISTS colaboradores_enderecos_after_delete$$
CREATE TRIGGER colaboradores_enderecos_after_delete AFTER DELETE ON colaboradores_enderecos FOR EACH ROW BEGIN
	INSERT INTO colaboradores_enderecos_logs (
		colaboradores_enderecos_logs.id_endereco,
        colaboradores_enderecos_logs.id_colaborador,
        colaboradores_enderecos_logs.mensagem
    ) VALUES (
		OLD.id,
        OLD.id_colaborador,
        JSON_OBJECT(
			'id_usuario', OLD.id_usuario,
            'REGISTRO_APAGADO', TRUE
        )
    );
END$$

DELIMITER ;


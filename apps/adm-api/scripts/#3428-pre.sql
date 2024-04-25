-- Active: 1706877031039@@127.0.0.1@3306@_banco_dia21mes2_084411_NORMAL
ALTER TABLE colaboradores_enderecos
    CHANGE COLUMN apelido apelido VARCHAR(50) NULL,
	ADD COLUMN nome_destinatario VARCHAR(255) NULL AFTER apelido,
	ADD COLUMN telefone_destinatario CHAR(11) NULL AFTER nome_destinatario;

-- Essa trigger será criada novamente no script "pós". Ela foi apagada nesse momento porque o script em PHP irá atualizar
-- mais de 70mil linhas e isso isso causaria um insert adicional em cada linha atualizada, podendo causar lentidão no banco.
DROP TRIGGER IF EXISTS colaboradores_enderecos_after_update;

DROP TRIGGER IF EXISTS colaboradores_enderecos_after_insert;
TRUNCATE TABLE colaboradores_endereco_log;

RENAME TABLE colaboradores_endereco_log TO colaboradores_enderecos_logs;

ALTER TABLE colaboradores_enderecos_logs
CHANGE COLUMN endereco_novo mensagem LONGTEXT NOT NULL COLLATE 'utf8mb4_bin' AFTER id_colaborador,
CHANGE COLUMN data_alteracao data_criacao TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER mensagem;

DELIMITER $$
CREATE TRIGGER colaboradores_enderecos_after_insert AFTER INSERT ON colaboradores_enderecos FOR EACH ROW BEGIN
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

DELIMITER ;

-- Esse é o único colaborador sem razao_social no banco, então fiz esse update me baseando no email de cadastro
UPDATE colaboradores SET colaboradores.razao_social = 'Fabiane Rodrigues' WHERE colaboradores.id = 3135;

-- Este colaborador não tem telefone em seu cadastro, o que faz ele ser o único com endereço verificado sem telefone.
-- Portanto estou removendo a verificação do endereço deste colaborador para que, se ele vier a fazer uma compra nova,
-- precise atualizar o endereço novamente, e consequentemente, ter um telefone cadastrado
UPDATE colaboradores_enderecos SET colaboradores_enderecos.esta_verificado = 0 WHERE colaboradores_enderecos.id_colaborador = 6999;

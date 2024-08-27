ALTER TABLE `transacao_financeiras_metadados`
	CHANGE COLUMN `valor` `valor` LONGTEXT NOT NULL COLLATE 'utf8_bin' AFTER `chave`;

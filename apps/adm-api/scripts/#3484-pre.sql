ALTER TABLE `produtos_pontos`
	CHANGE COLUMN `criado_em` `data_criacao` TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER `id`,
	CHANGE COLUMN `atualizado_em` `data_atualizacao` TIMESTAMP NULL DEFAULT NULL AFTER `data_criacao`,
	CHANGE COLUMN `id_produto` `id_produto` INT(11) NOT NULL AFTER `data_atualizacao`,
	CHANGE COLUMN `cancelamento_automatico` `pontuacao_cancelamento` INT(11) NOT NULL DEFAULT '0' AFTER `pontuacao_devolucao_defeito`,
	CHANGE COLUMN `atraso_separacao` `pontuacao_atraso_separacao` INT(11) NOT NULL DEFAULT '0' AFTER `pontuacao_cancelamento`,
	DROP FOREIGN KEY `produtos_pontos_ibfk_1`;

RENAME TABLE `produtos_pontos` TO `produtos_pontuacoes`;

ALTER TABLE `configuracoes`
    ADD COLUMN `json_reputacao_fornecedor_pontuacoes` LONGTEXT NOT NULL DEFAULT '{}' AFTER `minutos_expiracao_cache_filtros`,
    ADD COLUMN `json_produto_pontuacoes` LONGTEXT NOT NULL DEFAULT '{}' AFTER `json_reputacao_fornecedor_pontuacoes`,
    DROP COLUMN `dias_para_cancelamento_automatico`;

ALTER TABLE `configuracoes`
	ADD CONSTRAINT `json_reputacao_fornecedor_pontuacoes` CHECK (json_valid(json_reputacao_fornecedor_pontuacoes)),
	ADD CONSTRAINT `json_produto_pontuacoes` CHECK (json_valid(json_produto_pontuacoes));

ALTER TABLE `produtos_pontos_metadados`
	DROP INDEX `chave`;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_AVALIACAO_5_ESTRELAS'
WHERE produtos_pontos_metadados.id = 1;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_AVALIACAO_4_ESTRELAS'
WHERE produtos_pontos_metadados.id = 2;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_REPUTACAO_MELHOR_FABRICANTE'
WHERE produtos_pontos_metadados.id = 3;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_REPUTACAO_EXCELENTE'
WHERE produtos_pontos_metadados.id = 4;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_REPUTACAO_REGULAR'
WHERE produtos_pontos_metadados.id = 5;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_REPUTACAO_RUIM'
WHERE produtos_pontos_metadados.id = 6;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_FULFILLMENT'
WHERE produtos_pontos_metadados.id = 7;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_DEVOLUCAO_NORMAL'
WHERE produtos_pontos_metadados.id = 8;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_DEVOLUCAO_DEFEITO'
WHERE produtos_pontos_metadados.id = 9;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_CANCELAMENTO'
WHERE  produtos_pontos_metadados.id = 10;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_ATRASO_SEPARACAO'
WHERE  produtos_pontos_metadados.id = 11;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'DIAS_MENSURAR_VENDAS'
WHERE produtos_pontos_metadados.id = 18;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'DIAS_MENSURAR_MEDIA_ENVIOS'
WHERE produtos_pontos_metadados.id = 19;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'DIAS_MENSURAR_CANCELAMENTO'
WHERE produtos_pontos_metadados.id = 20;

ALTER TABLE `produtos_pontos`
	CHANGE COLUMN `criado_em` `data_criacao` TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER `id`,
	CHANGE COLUMN `atualizado_em` `data_atualizacao` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `data_criacao`,
	CHANGE COLUMN `id_produto` `id_produto` INT(11) NOT NULL AFTER `data_atualizacao`,
	CHANGE COLUMN `cancelamento_automatico` `pontuacao_cancelamento` INT(11) NOT NULL DEFAULT '0' AFTER `pontuacao_devolucao_defeito`,
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
SET produtos_pontos_metadados.chave = 'PONTUACAO_FULFILLMENT'
WHERE produtos_pontos_metadados.id = 7;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_CANCELAMENTO'
WHERE  produtos_pontos_metadados.id = 10;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'DIAS_MENSURAR_VENDAS'
WHERE produtos_pontos_metadados.id = 18;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'DIAS_MENSURAR_MEDIA_ENVIOS'
WHERE produtos_pontos_metadados.id = 19;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'DIAS_MENSURAR_CANCELAMENTO'
WHERE produtos_pontos_metadados.id = 20;

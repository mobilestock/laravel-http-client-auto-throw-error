ALTER TABLE `produtos_pontos`
	CHANGE COLUMN `criado_em` `data_criacao` TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER `id`,
	CHANGE COLUMN `atualizado_em` `data_atualizacao` TIMESTAMP NULL DEFAULT NULL AFTER `data_criacao`,
	CHANGE COLUMN `id_produto` `id_produto` INT(11) NOT NULL AFTER `data_atualizacao`,
	CHANGE COLUMN `cancelamento_automatico` `pontuacao_cancelamento` INT(11) NOT NULL DEFAULT '0' COMMENT 'Cada um é -8 pontos' AFTER `pontuacao_devolucao_defeito`;

ALTER TABLE `configuracoes`
    ADD COLUMN `json_reputacao_fornecedor_pontuacoes` LONGTEXT NOT NULL AFTER `minutos_expiracao_cache_filtros`,
    ADD COLUMN `json_produto_pontuacoes` LONGTEXT NOT NULL AFTER `json_reputacao_fornecedor_pontuacoes`;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'PONTUACAO_CANCELAMENTO'
WHERE  produtos_pontos_metadados.id = 10;

UPDATE produtos_pontos_metadados
SET produtos_pontos_metadados.chave = 'POSSUI_FULFILLMENT'
WHERE produtos_pontos_metadados.id = 7;

ALTER TABLE `configuracoes`
    DROP COLUMN `horarios_separacao_fulfillment`,
	ADD COLUMN `json_logistica` LONGTEXT NOT NULL DEFAULT '{}',
	ADD CONSTRAINT `json_logistica` CHECK (json_valid(`json_logistica`));

UPDATE configuracoes
    SET configuracoes.json_logistica = '{"separacao_fulfillment": {"horarios": ["08:00", "14:00"], "horas_carencia_retirada": "02:30"}}';

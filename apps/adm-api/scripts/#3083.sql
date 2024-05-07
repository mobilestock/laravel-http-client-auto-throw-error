ALTER TABLE `municipios`
	ADD COLUMN `valor_frete` DECIMAL(5,2) NOT NULL,
	ADD COLUMN `valor_adicional` DECIMAL(10,2) NOT NULL;

UPDATE municipios
INNER JOIN frete_estado ON frete_estado.estado = municipios.uf
SET municipios.valor_frete = frete_estado.valor_frete,
	municipios.valor_adicional = frete_estado.valor_adicional;

DROP TABLE `frete_estado`;

ALTER TABLE `municipios`
	ADD COLUMN `data_criacao` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() AFTER `valor_adicional`,
	ADD COLUMN `data_atualizacao` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `data_criacao`;

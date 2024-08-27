 ALTER TABLE produtos
   ADD COLUMN em_liquidacao TINYINT(1) NOT NULL DEFAULT '0';

UPDATE produtos
INNER JOIN catalogo_fixo ON catalogo_fixo.id_produto = produtos.id
SET produtos.em_liquidacao = 1
WHERE catalogo_fixo.tipo = 'LIQUIDACAO';

DELETE FROM catalogo_fixo
WHERE catalogo_fixo.tipo = 'LIQUIDACAO';

ALTER TABLE catalogo_fixo
	MODIFY COLUMN tipo ENUM('IMPULSIONAR','MELHOR_FABRICANTE','PROMOCAO_TEMPORARIA','VENDA_RECENTE','MELHOR_PONTUACAO','MODA_GERAL','MODA_20','MODA_40','MODA_60','MODA_80','MODA_100') DEFAULT NULL;

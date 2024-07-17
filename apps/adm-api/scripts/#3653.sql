 ALTER TABLE produtos
   ADD COLUMN em_liquidacao TINYINT(1) NOT NULL DEFAULT '0';

UPDATE produtos
INNER JOIN catalogo_fixo ON catalogo_fixo.id_produto = produtos.id
SET produtos.em_liquidacao = 1
WHERE catalogo_fixo.tipo = 'LIQUIDACAO';

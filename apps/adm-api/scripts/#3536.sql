ALTER TABLE produtos ADD COLUMN eh_moda TINYINT NOT NULL DEFAULT 0;
ALTER TABLE catalogo_fixo MODIFY COLUMN tipo ENUM(
    'IMPULSIONAR', 
    'MELHOR_FABRICANTE', 
    'PROMOCAO_TEMPORARIA', 
    'VENDA_RECENTE', 
    'MELHOR_PONTUACAO', 
    'MODA_GERAL', 
    'MODA_20',
    'MODA_40',
    'MODA_60',
    'MODA_80',
    'MODA_100'
    );
ALTER TABLE colaboradores ADD COLUMN porcentagem_compras_moda TINYINT NOT NULL DEFAULT 0;
UPDATE produtos SET produtos.eh_moda = 1 WHERE produtos.id_fornecedor IN (12, 6984);

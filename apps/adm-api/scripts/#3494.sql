-- Adiciona uma coluna temporária com um identificador único para cada linha
ALTER TABLE compras ADD COLUMN temp_id INT AUTO_INCREMENT PRIMARY KEY;

-- Atualiza apenas a primeira linha com id 15173
UPDATE compras SET
    compras.id = (
        SELECT MAX(id) + 1 FROM compras
    )
WHERE compras.temp_id = (
    SELECT MIN(temp_id) FROM compras WHERE compras.id = 15173 AND compras.situacao = 1
);

-- Atualiza apenas a primeira linha com id 19571
UPDATE compras SET
    compras.id = (
        SELECT MAX(id) + 1 FROM compras
    )
WHERE compras.temp_id = (
    SELECT MIN(temp_id) FROM compras WHERE compras.id = 19571 AND compras.situacao = 1
);

-- Remove a coluna temporária
ALTER TABLE compras DROP COLUMN temp_id;

-- Altera o nome da tabela compras para reposicoes
RENAME TABLE compras TO reposicoes;

-- Alterando tabela de reposicoes
ALTER TABLE reposicoes
    MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY,
DROP COLUMN lote,
    DROP COLUMN edicao_fornecedor,
    ADD COLUMN valor_total DECIMAL(10,2) NOT NULL,
    ADD COLUMN data_criacao DATETIME,
    ADD COLUMN data_atualizacao DATETIME,
    ADD COLUMN id_usuario INT,
    ADD COLUMN situacao_enum ENUM(
        'EM_ABERTO',
        'ENTREGUE',
        'CANCELADO',
        'SEPARADO',
        'FATURADO',
        'NORMAL',
        'CONFERIDO',
        'DEVOLUCAO',
        'TROCA_DEFEITO',
        'TROCA_NUMERACAO',
        'TROCA_NORMAL',
        'TROCA',
        'COMPRA',
        'PARCIALMENTE_ENTREGUE',
        'RESERVADO',
        'TROCA_AUTORIZADA',
        'TROCA_ESPECIAL',
        'TROCA_APOS_60_DIAS',
        'CORRECAO',
        'PREMIO');

-- Fazendo casting da coluna situacao na tabela de reposicoes
UPDATE reposicoes
SET situacao_enum = CASE situacao
    WHEN 1 THEN 'EM_ABERTO'
    WHEN 2 THEN 'ENTREGUE'
    WHEN 3 THEN 'CANCELADO'
    WHEN 4 THEN 'SEPARADO'
    WHEN 5 THEN 'FATURADO'
    WHEN 6 THEN 'NORMAL'
    WHEN 7 THEN 'CONFERIDO'
    WHEN 8 THEN 'DEVOLUCAO'
    WHEN 9 THEN 'TROCA_DEFEITO'
    WHEN 10 THEN 'TROCA_NUMERACAO'
    WHEN 11 THEN 'TROCA_NORMAL'
    WHEN 12 THEN 'TROCA'
    WHEN 13 THEN 'COMPRA'
    WHEN 14 THEN 'RESERVADO'
    WHEN 15 THEN 'PARCIALMENTE_ENTREGUE'
    WHEN 16 THEN 'TROCA_AUTORIZADA'
    WHEN 17 THEN 'TROCA_ESPECIAL'
    WHEN 18 THEN 'TROCA_APOS_60_DIAS'
    WHEN 19 THEN 'CORRECAO'
    WHEN 20 THEN 'PREMIO'
    END
WHERE true;

ALTER TABLE reposicoes
DROP COLUMN situacao;

ALTER TABLE reposicoes
    CHANGE COLUMN situacao_enum situacao ENUM(
    'EM_ABERTO',
    'ENTREGUE',
    'CANCELADO',
    'SEPARADO',
    'FATURADO',
    'NORMAL',
    'CONFERIDO',
    'DEVOLUCAO',
    'TROCA_DEFEITO',
    'TROCA_NUMERACAO',
    'TROCA_NORMAL',
    'TROCA',
    'COMPRA',
    'PARCIALMENTE_ENTREGUE',
    'RESERVADO',
    'TROCA_AUTORIZADA',
    'TROCA_ESPECIAL',
    'TROCA_APOS_60_DIAS',
    'CORRECAO',
    'PREMIO');


-- Insere valor total e data criação a tabela de reposicoes
UPDATE
    reposicoes
SET valor_total = COALESCE((SELECT SUM(compras_itens.valor_total) FROM compras_itens WHERE compras_itens.id_compra = reposicoes.id), 0),
    data_criacao = data_emissao
WHERE true;

ALTER TABLE reposicoes DROP COLUMN data_emissao;

-- Cirando tabela de reposicoes_grades
CREATE TABLE reposicoes_grades (
   id INT AUTO_INCREMENT PRIMARY KEY,
   id_reposicao INT,
   id_produto INT,
   nome_tamanho VARCHAR(50),
   valor_produto DECIMAL(10,2),
   quantidade_entrada INT,
   quantidade_total INT,
   FOREIGN KEY (id_reposicao) REFERENCES reposicoes(id)
);

-- Inserindo dados da tabela de compras_itens_grade na tabela reposicoes_grades
INSERT INTO reposicoes_grades (id_reposicao, id_produto, nome_tamanho, valor_produto, quantidade_entrada, quantidade_total)
SELECT
    compras_itens_grade.id_compra,
    compras_itens_grade.id_produto,
    compras_itens_grade.nome_tamanho,
    (SELECT
         compras_itens.preco_unit
     FROM compras_itens
     WHERE compras_itens.id_compra = compras_itens_grade.id_compra
       AND compras_itens.id_produto = compras_itens_grade.id_produto
        LIMIT 1),
    SUM(CASE WHEN reposicoes.situacao = 'ENTREGUE' THEN compras_itens_grade.quantidade_total ELSE 0 END),
    SUM(compras_itens_grade.quantidade_total)
FROM compras_itens_grade
    JOIN reposicoes ON compras_itens_grade.id_compra = reposicoes.id
GROUP BY compras_itens_grade.id_compra, compras_itens_grade.id_produto, compras_itens_grade.nome_tamanho;

# TODO verificar a quantidade_entrada por produtos_aguarda_entrada_estoque em situações: tipo_entrada CO, id_produto, nome_tamanho e indetificao
    
-- Apaga tabelas que não seram mais utilizadas
DROP TABLE compras_itens_caixas;
DROP TABLE compras_itens_grade;
DROP TABLE compras_itens;
DROP TABLE compras_entrada_historico;
DROP TABLE compras_entrada_temp;
    
-- Atualização das tabelas concluida

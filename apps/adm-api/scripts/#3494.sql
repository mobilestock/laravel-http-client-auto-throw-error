-- Altera o nome da tabela compras para reposicoes
RENAME TABLE compras TO reposicoes;

-- Alterando tabela de reposicoes
ALTER TABLE reposicoes
MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY,
DROP COLUMN lote,
DROP COLUMN edicao_fornecedor,
ADD COLUMN data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
ADD COLUMN data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
ADD COLUMN id_usuario INT NOT NULL,
ADD COLUMN situacao_enum ENUM(
    'EM_ABERTO',
    'ENTREGUE',
    'CANCELADO',
    'PARCIALMENTE_ENTREGUE'
);

-- Fazendo casting da coluna situacao na tabela de reposicoes
UPDATE reposicoes
SET
    reposicoes.situacao_enum = CASE reposicoes.situacao
        WHEN 1 THEN 'EM_ABERTO'
        WHEN 2 THEN 'ENTREGUE'
        WHEN 3 THEN 'CANCELADO'
        WHEN 14 THEN 'PARCIALMENTE_ENTREGUE'
    END,
    reposicoes.id_usuario = 2
WHERE
    true;

ALTER TABLE reposicoes DROP COLUMN situacao;

ALTER TABLE reposicoes
CHANGE COLUMN situacao_enum situacao ENUM(
    'EM_ABERTO',
    'ENTREGUE',
    'CANCELADO',
    'PARCIALMENTE_ENTREGUE'
);

-- Insere e data criação a tabela de reposicoes
UPDATE reposicoes SET data_criacao = data_emissao WHERE true;

ALTER TABLE reposicoes DROP COLUMN data_emissao;

-- Criando tabela de reposicoes_grades
CREATE TABLE reposicoes_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reposicao INT NOT NULL,
    id_usuario INT NOT NULL,
    id_produto INT NOT NULL,
    nome_tamanho VARCHAR(50) NOT NULL COLLATE 'utf8_general_ci',
    preco_custo_produto DECIMAL(10, 2) NOT NULL,
    quantidade_entrada INT UNSIGNED NOT NULL DEFAULT 0,
    quantidade_total INT UNSIGNED NOT NULL,
    data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    FOREIGN KEY (id_reposicao) REFERENCES reposicoes (id)
);

ALTER TABLE reposicoes_grades
ADD UNIQUE INDEX idx_unique_reposicao_produto_tamanho (
    id_reposicao,
    id_produto,
    nome_tamanho
);

-- Inserindo dados da tabela de compras_itens_grade na tabela reposicoes_grades
INSERT INTO
    reposicoes_grades (
        reposicoes_grades.id_reposicao,
        reposicoes_grades.id_produto,
        reposicoes_grades.nome_tamanho,
        reposicoes_grades.preco_custo_produto,
        reposicoes_grades.quantidade_entrada,
        reposicoes_grades.quantidade_total,
        reposicoes_grades.id_usuario
    )
SELECT compras_itens_grade.id_compra, compras_itens_grade.id_produto, compras_itens_grade.nome_tamanho, (
        SELECT compras_itens.preco_unit
        FROM compras_itens
        WHERE
            compras_itens.id_compra = compras_itens_grade.id_compra
            AND compras_itens.id_produto = compras_itens_grade.id_produto
        LIMIT 1
    ), SUM(
        IF(
            reposicoes.situacao = 'ENTREGUE', compras_itens_grade.quantidade_total, 0
        )
    ), SUM(
        compras_itens_grade.quantidade_total
    ), 2
FROM
    compras_itens_grade
    JOIN reposicoes ON compras_itens_grade.id_compra = reposicoes.id
GROUP BY
    compras_itens_grade.id_compra,
    compras_itens_grade.id_produto,
    compras_itens_grade.nome_tamanho;

-- Apaga tabelas, colunas e procedures que não serão mais utilizadas
DROP TABLE compras_itens_caixas;

DROP TABLE compras_itens_grade;

DROP TABLE compras_itens;

DROP TABLE compras_entrada_historico;

DROP TABLE compras_entrada_temp;

ALTER TABLE configuracoes
DROP COLUMN verificacao_expirar_pares,
DROP COLUMN entrada_compra_temp;

DROP TABLE paginas_acessadas;

DROP TABLE situacao;

DROP PROCEDURE notifica_clientes_produto_chegou;

DROP PROCEDURE processo_cria_foguinho;

DROP EVENT evento_hora_em_hora;
-- Atualização das tabelas concluída
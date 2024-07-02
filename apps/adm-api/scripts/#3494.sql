-- Adiciona uma coluna temporária com um identificador único para cada linha
ALTER TABLE compras
ADD COLUMN temp_id INT AUTO_INCREMENT PRIMARY KEY;

-- Atualiza apenas a primeira linha com id 15173
UPDATE compras
SET
    compras.id = (
        SELECT MAX(id) + 1
        FROM compras
    )
WHERE
    compras.temp_id = (
        SELECT MIN(temp_id)
        FROM compras
        WHERE
            compras.id = 15173
            AND compras.situacao = 1
    );

-- Atualiza apenas a primeira linha com id 19571
UPDATE compras
SET
    compras.id = (
        SELECT MAX(id) + 1
        FROM compras
    )
WHERE
    compras.temp_id = (
        SELECT MIN(temp_id)
        FROM compras
        WHERE
            compras.id = 19571
            AND compras.situacao = 1
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
ADD COLUMN valor_total DECIMAL(10, 2) NOT NULL,
ADD COLUMN data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
ADD COLUMN data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
ADD COLUMN id_usuario INT NOT NULL DEFAULT 2,
ADD COLUMN situacao_enum ENUM(
    'EM_ABERTO',
    'ENTREGUE',
    'CANCELADO',
    'PARCIALMENTE_ENTREGUE'
);

-- Fazendo casting da coluna situacao na tabela de reposicoes
UPDATE reposicoes
SET
    situacao_enum = CASE situacao
        WHEN 1 THEN 'EM_ABERTO'
        WHEN 2 THEN 'ENTREGUE'
        WHEN 3 THEN 'CANCELADO'
        WHEN 14 THEN 'PARCIALMENTE_ENTREGUE'
    END
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

-- Insere valor total e data criação a tabela de reposicoes
UPDATE reposicoes
SET
    valor_total = COALESCE(
        (
            SELECT SUM(compras_itens.valor_total)
            FROM compras_itens
            WHERE
                compras_itens.id_compra = reposicoes.id
        ),
        0
    ),
    data_criacao = data_emissao
WHERE
    true;

ALTER TABLE reposicoes DROP COLUMN data_emissao;

-- Criando tabela de reposicoes_grades
CREATE TABLE reposicoes_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reposicao INT NOT NULL,
    id_produto INT NOT NULL,
    nome_tamanho VARCHAR(50) NOT NULL COLLATE 'utf8_general_ci',
    valor_produto DECIMAL(10, 2) NOT NULL,
    quantidade_entrada INT NOT NULL,
    quantidade_total INT NOT NULL,
    FOREIGN KEY (id_reposicao) REFERENCES reposicoes (id)
);

-- Inserindo dados da tabela de compras_itens_grade na tabela reposicoes_grades
INSERT INTO
    reposicoes_grades (
        id_reposicao,
        id_produto,
        nome_tamanho,
        valor_produto,
        quantidade_entrada,
        quantidade_total
    )
SELECT compras_itens_grade.id_compra, compras_itens_grade.id_produto, compras_itens_grade.nome_tamanho, (
        SELECT compras_itens.preco_unit
        FROM compras_itens
        WHERE
            compras_itens.id_compra = compras_itens_grade.id_compra
            AND compras_itens.id_produto = compras_itens_grade.id_produto
        LIMIT 1
    ), SUM(
        CASE
            WHEN reposicoes.situacao = 'ENTREGUE' THEN compras_itens_grade.quantidade_total
            ELSE 0
        END
    ), SUM(
        compras_itens_grade.quantidade_total
    )
FROM
    compras_itens_grade
    JOIN reposicoes ON compras_itens_grade.id_compra = reposicoes.id
GROUP BY
    compras_itens_grade.id_compra,
    compras_itens_grade.id_produto,
    compras_itens_grade.nome_tamanho;

-- Apaga tabelas que não serão mais utilizadas
DROP TABLE compras_itens_caixas;

DROP TABLE compras_itens_grade;

DROP TABLE compras_itens;

DROP TABLE compras_entrada_historico;

DROP TABLE compras_entrada_temp;

ALTER TABLE configuracoes DROP COLUMN verificacao_expirar_pares;

DROP TABLE paginas_acessadas;
-- Atualização das tabelas concluída
DROP TABLE IF EXISTS produtos_logistica;

CREATE TABLE produtos_logistica (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    nome_tamanho VARCHAR(50) NOT NULL COLLATE 'utf8_general_ci',
    sku CHAR(12) NOT NULL,
    situacao ENUM(
        'AGUARDANDO_ENTRADA',
        'EM_ESTOQUE',
        'CONFERIDO'
    ) NOT NULL DEFAULT 'AGUARDANDO_ENTRADA',
    origem ENUM('REPOSICAO', 'DEVOLUCAO') DEFAULT 'REPOSICAO',
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    INDEX idx_id_produto (id_produto),
    CONSTRAINT fk_produtos_id FOREIGN KEY (id_produto) REFERENCES produtos (id) ON DELETE CASCADE, -- analisar
    UNIQUE INDEX unique_sku_produto (sku)
);

ALTER TABLE logistica_item
    ADD sku CHAR(12) NULL AFTER uuid_produto;

CREATE INDEX idx_sku
    ON logistica_item (sku);

DROP TABLE IF EXISTS produtos_logistica;

CREATE TABLE produtos_logistica (
    sku CHAR(12) NOT NULL,
    id_produto INT NOT NULL,
    nome_tamanho VARCHAR(50) NOT NULL COLLATE 'utf8_general_ci',
    situacao ENUM(
        'AGUARDANDO_ENTRADA',
        'EM_ESTOQUE',
        'CONFERIDO'
    ) NOT NULL DEFAULT 'AGUARDANDO_ENTRADA',
    id_usuario INT(11) NOT NULL,
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

CREATE TABLE IF NOT EXISTS `produtos_logistica_logs` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `uuid_produto` varchar(100),
    `sku` varchar(100) NOT NULL,
    `mensagem` longtext NOT NULL,
    `id_usuario` int(11) NOT NULL,
    `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2162912 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

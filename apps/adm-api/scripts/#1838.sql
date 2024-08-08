DROP TABLE IF EXISTS reposicoes_grades;
DROP TABLE IF EXISTS reposicoes;

UPDATE configuracoes
SET configuracoes.json_logistica = '{"separacao_fulfillment": {"horarios": ["08:00", "11:00", "15:00"], "horas_carencia_retirada": "02:00"}, "periodo_retencao_sku": {"anos_apos_entregue": 2, "dias_aguardando_entrada": 120}}'
WHERE TRUE;

DROP TABLE IF EXISTS produtos_logistica;

CREATE TABLE produtos_logistica (
    sku CHAR(12) NOT NULL PRIMARY KEY COLLATE 'utf8_bin',
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
    CONSTRAINT fk_produtos_id FOREIGN KEY (id_produto) REFERENCES produtos (id) ON DELETE CASCADE,
    UNIQUE INDEX unique_sku_produto (sku)
);

ALTER TABLE logistica_item
    ADD sku CHAR(12) NULL COLLATE 'utf8_bin' AFTER uuid_produto;

CREATE INDEX idx_sku
    ON logistica_item (sku);

CREATE TABLE IF NOT EXISTS `produtos_logistica_logs` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `sku` CHAR(12) NOT NULL COLLATE 'utf8_bin',
    `mensagem` longtext NOT NULL,
    `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2162912 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DELIMITER $$
DROP TRIGGER IF EXISTS produtos_logistica_after_update$$
CREATE TRIGGER `produtos_logistica_after_update` AFTER UPDATE ON `produtos_logistica` FOR EACH ROW
BEGIN
    INSERT INTO produtos_logistica_logs (sku, mensagem)
    VALUES (
                NEW.sku,
                JSON_OBJECT(
                    'sku', NEW.sku,
                    'id_produto', NEW.id_produto,
                    'nome_tamanho', NEW.nome_tamanho,
                    'situacao', NEW.situacao,
                    'id_usuario', NEW.id_usuario,
                    'data_criacao', NEW.data_criacao,
                    'data_atualizacao', NEW.data_atualizacao
                )
           );
END$$

DROP TRIGGER IF EXISTS produtos_logistica_after_insert$$
CREATE TRIGGER `produtos_logistica_after_insert` AFTER INSERT ON `produtos_logistica` FOR EACH ROW
BEGIN
    INSERT INTO produtos_logistica_logs (sku, mensagem)
    VALUES (
                NEW.sku,
                JSON_OBJECT(
                    'sku', NEW.sku,
                    'id_produto', NEW.id_produto,
                    'nome_tamanho', NEW.nome_tamanho,
                    'situacao', NEW.situacao,
                    'id_usuario', NEW.id_usuario,
                    'data_criacao', NEW.data_criacao,
                    'data_atualizacao', NEW.data_atualizacao
                )
           );
END$$

DROP TRIGGER IF EXISTS produtos_logistica_after_delete$$
CREATE TRIGGER `produtos_logistica_after_delete` AFTER DELETE ON `produtos_logistica` FOR EACH ROW
BEGIN
    INSERT INTO produtos_logistica_logs (sku, mensagem)
    VALUES (
                OLD.sku,
                JSON_OBJECT(
                    'REGISTRO_APAGADO', true,
                    'sku', OLD.sku,
                    'id_produto', OLD.id_produto,
                    'nome_tamanho', OLD.nome_tamanho,
                    'situacao', OLD.situacao,
                    'id_usuario', OLD.id_usuario,
                    'data_criacao', OLD.data_criacao,
                    'data_atualizacao', OLD.data_atualizacao
                )
           );
END$$

DELIMITER ;

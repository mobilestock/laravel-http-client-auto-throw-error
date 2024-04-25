TRUNCATE TABLE produtos_video;

RENAME TABLE produtos_video TO produtos_videos;

ALTER TABLE produtos_videos
    MODIFY COLUMN id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    DROP COLUMN sequencia,
    ADD id_produto INT NOT NULL,
    ADD id_usuario INT NOT NULL,
    ADD data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    ADD data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP();

ALTER TABLE `produtos_videos`
	ADD CONSTRAINT `FK_id_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE produtos_categorias
    ADD id_usuario INT NOT NULL,
    ADD data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP();

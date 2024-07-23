DELIMITER //
DROP TRIGGER IF EXISTS produtos_foto_after_insert//

CREATE TRIGGER `produtos_foto_after_insert` AFTER INSERT ON `produtos_foto` FOR EACH ROW BEGIN
	DECLARE ID_FOTOGRAFO_ INT DEFAULT 0;
	UPDATE produtos SET produtos.data_entrada = NOW() WHERE produtos.id = NEW.id;

	IF(NEW.tipo_foto <> 'SM') THEN
		SELECT produtos.id_fornecedor FROM produtos WHERE produtos.id = NEW.id
		INTO ID_FOTOGRAFO_;

		INSERT INTO publicacoes (publicacoes.id_colaborador, publicacoes.foto, publicacoes.tipo_publicacao)
		SELECT ID_FOTOGRAFO_, NEW.caminho, 'AU' FROM DUAL
		WHERE NOT EXISTS(SELECT 1 FROM publicacoes WHERE publicacoes.id_colaborador = ID_FOTOGRAFO_ AND publicacoes.foto = NEW.caminho AND publicacoes.tipo_publicacao = 'AU');

		IF(ROW_COUNT() > 0) THEN
			INSERT INTO publicacoes_produtos (publicacoes_produtos.id_publicacao, publicacoes_produtos.id_produto, publicacoes_produtos.foto_publicacao)
			SELECT LAST_INSERT_ID(), NEW.id, NEW.caminho;
		END IF;
	END IF;
END//
DELIMITER ;

CREATE INDEX `idx_foto` USING BTREE ON publicacoes (`foto`);

INSERT INTO publicacoes (publicacoes.id_colaborador, publicacoes.foto, publicacoes.tipo_publicacao)
SELECT produtos.id_fornecedor, produtos_foto.caminho, 'AU'
FROM produtos_foto
LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos_foto.id
LEFT JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
JOIN produtos ON produtos.id = produtos_foto.id
WHERE publicacoes.id IS NULL
    AND publicacoes_produtos.id_produto IS NULL
    AND produtos_foto.id > 100000
GROUP BY produtos_foto.caminho;

INSERT INTO publicacoes_produtos (publicacoes_produtos.id_publicacao, publicacoes_produtos.id_produto, publicacoes_produtos.foto_publicacao)
SELECT
  publicacoes.id,
  produtos_foto.id id_produto,
  produtos_foto.caminho
FROM produtos_foto
JOIN publicacoes ON publicacoes.foto = produtos_foto.caminho
LEFT JOIN publicacoes_produtos ON publicacoes_produtos.id_produto = produtos_foto.id AND publicacoes_produtos.id_publicacao = publicacoes.id
WHERE publicacoes_produtos.id_produto IS NULL
  AND produtos_foto.id > 100000
GROUP BY produtos_foto.caminho;

DROP INDEX `idx_foto` ON publicacoes;

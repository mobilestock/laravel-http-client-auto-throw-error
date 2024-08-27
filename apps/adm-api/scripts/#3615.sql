DELETE FROM produtos_pontuacoes WHERE produtos_pontuacoes.id_produto NOT IN (SELECT produtos.id FROM produtos);

ALTER TABLE produtos_pontuacoes
ADD FOREIGN KEY (id_produto) REFERENCES produtos(id)
ON DELETE CASCADE;

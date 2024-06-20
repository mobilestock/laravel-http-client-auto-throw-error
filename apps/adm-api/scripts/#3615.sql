ALTER TABLE produtos_pontuacoes
ADD FOREIGN KEY (id_produto) REFERENCES produtos(id)
ON DELETE CASCADE;

UPDATE produtos_aguarda_entrada_estoque
    INNER JOIN produtos ON produtos_aguarda_entrada_estoque.id_produto = produtos.id 
        AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
    SET produtos_aguarda_entrada_estoque.localizacao = produtos.localizacao
WHERE produtos_aguarda_entrada_estoque.localizacao IS NULL
  AND produtos.localizacao IS NOT NULL;

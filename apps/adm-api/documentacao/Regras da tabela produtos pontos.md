# Regras dos Pontos de Produtos

## Colunas

- id: Inteiro que representa o id do registro
- criado_em: Timestamp representando quando o registro foi criado
- id_produto: Foreign key que liga o registro à tabela produtos

## Colunas pontos

- pontuacao_avaliacoes:
  - Representa a soma das avaliações dos produtos com 4 e 5 estrelas, sendo:
    - 5 = +10 pontos
    - 4 = +3 pontos
    - demais estrelas não dão pontos
- pontuacao_seller:
  - Representa a pontuação conforme a reputação do seller, sendo:
    - Melhor Fabricante = +10 pontos
    - Excelente = +2 pontos
    - Regular = 0 pontos
    - Ruim = -20 pontos
- pontuacao_fullfillment:
  - Se houver houver nossa permissão para repor no fulfillment = +10 pontos
- quantidade_vendas:
  - 1 venda = +1 ponto
  - A venda deve ser válida (Não fraude, corrigida, trocada ou devolvida)
- pontuacao_devolucao_normal:
  - 1 devolução = -2 pontos
- pontuacao_devolucao_defeito:
  - 1 devolução = -5 pontos
- cancelamento_automatico:
  - 1 cancelamento = -8 pontos

## Coluna final

- total:
  - Representa a soma dos itens das colunas acima

## Como funciona?

A tabela é atualizada 1 vez por dia, com o Job `jobGerarPontuacaoProdutos.php`, do qual:

- Limpa a tabela
- Reeinsere produtos atualizados e não bloqueados com os pontos de cada coluna
- Soma todas as colunas implicitamente através do campo virtual `total` da tabela
- Atualiza a coluna data_qualquer_alteracao, todos os produtos não bloqueados
- Em algum momento o OpenSearch vai rodar e buscar essas alterações nos pontos dos produtos, alterando o resultado na pesquisa conforme a pontuação dos mesmos.

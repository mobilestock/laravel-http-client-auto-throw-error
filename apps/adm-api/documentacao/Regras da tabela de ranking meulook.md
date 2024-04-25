# Regras do Ranking
## Tabelas
### ranking
Contém as modalidades de ranking ao qual os usuários participarão
* id: Chave primária `int(11)` **(não é autoincrementada)**
* nome: Descrição da modalidade `varchar(128)`
* chave: Chave em `varchar(128)` para busca da modalidade
* url_endpoint: O endpoint de requisição no WEB para obter os participantes da modalidade em ordem crescente `varchar(128)` **(do 1° lugar ao último)**
* ativo: Booleano para definir se a modalidade está ativa e será mostrada no frontend e premiada, default `true`
* data_criacao: Datetime com default `current_timestamp()`
* recontar_premios: Booleano que define se o ranking buscará prêmios atualizados **(TRUE)** ou se dará desconto pelos itens devolvidos **(FALSE)**

### ranking_premios
Contém os prêmios individuais de cada colocação em cada modalidade do ranking
* id: Chave primária `int(11)` auto incrementada
* id_ranking: Chave estrangeira que liga esta tabela à tabela `ranking`
* posicao: Posicionamento na modalidade `int(11)` referente à tabela `ranking`
* valor: Valor da premiação `float` referente a coluna `posicao` desta tabela
* ativo: Boolean para definir se o posicionamento será premiado, default `true`
* data_criacao: Datetime com default `current_timestamp()`

### ranking_vencedores_itens
Contém os produtos que fizeram o participante estar em sua posição
* id: Chave primário `int(11)` auto incrementada
* uuid_produto: `varchar(80)`
* id_lancamento_pendente: Chave estrangeira que liga esta tabela à tabela `lancamento_financeiros_pendente`
* id_lancamento: Chave estrangeira que liga esta tabela à tabela `lancamento_financeiros`
* data_criacao: Datetime com default `current_timestamp`

### configuracoes
A tabela da empresa para definir preferências de valores para consultas
* ...
* horario_final_dia_ranking_meulook: O horário que o ranking é encerrado e feito a premiação (preenchido com o horário normal do Brasil. **Ex: 23:59:59**)

# Regras de negócio
1. As posições são dadas por:
   1. Valor vendido DESCRESCENTE
   2. Quantidade vendida CRESCENTE
2. Todas as colunas são essenciais nas tabelas são necessárias para o funcionamento, **não deixar valores `NULL`**
3. A coluna `ranking.chave` deverá ser um texto curto com palavras separadas por **" `-` "** **(sinal de menos)** que descreva a modalidade
4. A coluna `ranking.url_endpoint` deverá se referir à um endpoint do `WEB` sem o domínio e iniciando com `/`. **Ex: `/api_meulook/ranking/influencers`**
5. A coluna `ranking_premios.ativo` deverá SOMENTE ser alterada baseando-se na ordem da coluna `ranking_premios.posicao` DESCRESCENTE
   * Caso queira desativar uma ou mais posições, deverá iniciar pela última, antepenúltima e assim por diante.
   * Não deixar posição ativas com intervalos maiores que um.
        * ✅ Certo: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        * ❌ Errado: 1, 3, 4, 6, 9, 10
6. Os valores da coluna `ranking.valor` deverão ser **PROPORCIONALMENTE INVERSOS** à posição da coluna `ranking.posicao`
   * ✅ Certo:
     * posicao 1: valor 100
     * posicao 2: valor 50
     * posicao 3: valor 25
   * ❌ Errado:
        * posicao 1: valor 50
        * posicao 2: valor 75
        * posicao 3: valor 100

# Fechamento
O fechamento ocorre no final do período do ranking para salvar os itens que fizeram o participante estar em sua posição. Primeiramente é criado um `lancamento_financeiros_pendente` e para cada produto um registro em `ranking_vencedores_itens` com o `UUID` do produto e o `lancamento_financeiros_pendente.id`

# Pagamento
O pagamento ocorre quando todos os produtos de algum ranking foram **finalizados** podendo estar:
* Entregue: Terem se passado 8 dias da coleta do produto no ponto pelo consumidor final
* Corrigido: Compra do produto cancelada por não haver estoque no momento da separação
* Devolvido: Consumidor final devolveu produto/trocou produto antes do 8º dia após a coleta no ponto

As posições serão reajustadas considerando os produtos que continuam com os consumidores finais.

# Observações importantes
Os resultados da premiação do ranking são salvas na tabela `lancamento_financeiros` e `lancamento_financeiros_pendente`, prenchendo:
* tipo: 'P' (O mobile paga o influencer)
* situacao: 1
* origem: 'MR' (Meulook Ranking)
* id_colaborador: (id do colaborador)
* data_vencimento:
    * Datetime do momento da premiação
    * Deve ser o mesmo para todos os participantes do dia
* valor: (valor do prêmio)
* id_usuario: (id_usuario do colaboradorador)
* documento: '7' (Campo antigo)
* observacao: (Texto tipo '1º lugar em tal ranking')
* numero_movimento: (Posição no ranking)
* numero_documento: (Chave do ranking, a mesma da tabela ranking)

# AWS EventBridge e Lambda
O Lambda contém um código em node que realiza a requisição para o WEB que realiza a premiação dos rankings **(o código em questão está num repositório github, consultar os membros)**

## EventBridge
O `EventBridge` é responsável por executar a função `lambda` num horário definido em `CRON` no fuso horário `UTC`
### Fuso horário UTC
O fuso horário `UTC` em relação ao Brasil está atrasado 3h, então se quiser executar um processo às **15h30** deverá preencher **18h30**

# Consultas no Banco
Para consultar os itens serão utilizadas as seguintes tabelas:
* ranking_vencedores_itens
  * Produto: Uuid do produto
  * Ranking Pendente: Id do lançamento financeiro pendente
  * Rankings Concluídos: Id do lançamento financeiro
* lancamento_financeiros_pendente e lancamento_financeiros
  * Participante: Id do colaborador
  * Ranking: Chave String do Ranking
  * Prêmio
  * Posição
  * Data Criação
  * Observação
* pedido_item_meulook
  * Ponto: Id do ponto selecionado na compra
  * Valor do produto
* produtos
  * Nome comercial ou descrição
  * Fornecedor
* transacao_financeiras
  * Data da compra
* faturamento_item
  * Algumas situações do produto
    * 6 ou NULL: Normal
    * 12: Devolvido ou Trocado
    * 19: Corrigido
* faturamento
  * Algumas situações do produto
    * Separado `boolean`
    * Conferido `boolean`
    * Expedido `boolean`
    * Entregue no Ponto `boolean`
* entregas_faturamento_item
  * Algumas situações do produto
    * EN: Entregue ao consumidor final
    * AR: Aguardando no Ponto
    * PB: Ponto bipou
  * Data das situações citadas

```
# Nem todos os joins são obrigatórios, citei todos para especificar o motivo junto

SELECT ...

# Recomendo começar por essa tabela por já ter os produtos
FROM ranking_vencedores_itens

# Os dados do ranking se encontram nessa tabela
# id_colaborador, valor_total, data_emissao, numero_documento, numero_movimento, observacao.
INNER JOIN lancamento_financeiros_pendente ON lancamento_financeiros_pendente.id = ranking_vencedores_itens.id_lancamento_pendente

# Produtos da venda
# id_produto, tamanho, id_ponto, preco, id_transacao, situacao
INNER JOIN pedido_item_meulook ON
    pedido_item_meulook.uuid = ranking_vencedores_itens.uuid_produto AND
    pedido_item_meulook.situacao = 'PA'

# Dados do produto
# nome_comercial, descricao
INNER JOIN produtos ON produtos.id = pedido_item_meulook.id_produto

# Dados da venda
# data_atualizacao, situacao
INNER JOIN transacao_financeiras ON transacao_financeiras.id = pedido_item_meulook.id_transacao

# Situações de produto (1)
# situacao (6 = normal, 12 = devolvido, 19 = corrigido)
LEFT JOIN faturamento_item ON faturamento_item.uuid = ranking_vencedores_itens.uuid_produto

# Situações de produto (2)
# separado, conferido, expedido, entregue
INNER JOIN faturamento ON faturamento.id = faturamento_item.id_faturamento

# Situações de produto (3)
# situacao (AR, PB, EN), data_atualizacao
INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.uuid_produto = ranking_vencedores_itens.uuid_produto

# O group pode variar dependendo do caso
GROUP BY lancamentos_financeiros_pendentes.id
# ...
#  lancamentos_financeiros_pendentes.numero_documento,
#  lancamentos_financeiros_pendentes.data_emissao
```








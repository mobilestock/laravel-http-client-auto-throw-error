# Instalação OpenSearch

Baixar arquivos: https://opensearch.org/downloads.html

### OpenSearch Local

Você pode iniciar o opensearch e o dashboard localmente vindo até esse diretório na linha de comando e rodando:
`docker-compose up -d`
Após instalar aponte a váriavel 'OPENSEARCH.ENDPOINT' para o seu host local.

### 1 - Criar indexes no console:

```js
PUT meulook_produtos
{
  "mappings": {
    "properties": {
      "id_produto": {
        "type": "integer"
      },
      "id_fornecedor": {
        "type": "integer"
      },
      "valor_venda_ml": {
        "type": "float"
      },
      "valor_venda_ms": {
        "type": "float"
      },
      "grade_produto": {
        "type": "text"
      },
      "grade_fullfillment": {
        "type": "text"
      },
      "linha_produto": {
        "type": "keyword"
      },
      "sexo_produto": {
        "type": "keyword"
      },
      "cor_produto": {
        "type": "text"
      },
      "categoria_produto": {
        "type": "text"
      },
      "tem_estoque": {
        "type": "boolean"
      },
      "tem_estoque_fullfillment": {
        "type": "boolean"
      },
      "reputacao_fornecedor": {
        "type": "keyword"
      },
      "pontuacao_produto": {
        "type": "float"
      },
      "concatenado": {
        "type": "text"
      },
      "5_estrelas": {
        "type": "integer"
      },
      "4_estrelas": {
        "type": "integer"
      },
      "3_estrelas": {
        "type": "integer"
      },
      "2_estrelas": {
        "type": "integer"
      },
      "1_estrelas": {
        "type": "integer"
      },
      "timestamp": {
        "type": "date"
      }
    }
  }
}

PUT meulook_autocomplete
{
  "mappings": {
    "properties": {
      "id_colaborador": {
        "type": "keyword"
      },
      "nome": {
        "type": "keyword"
      },
      "data_criacao": {
        "type": "date"
      }
    }
  }
}

PUT logs
{
    "mappings": {
    "properties": {
        "origem": {
            "type": "keyword"
        },
        "data_criacao": {
            "type": "date"
        },
        "dados": {
            "dynamic": false,
            "properties": {
                "id_fila": {
                    "type": "keyword"
                }
            }
        }
    }
}
}
```

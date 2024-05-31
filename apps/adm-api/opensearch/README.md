# Opensearch

1. Você pode iniciar o opensearch e o dashboard localmente vindo até o diretório `apps/adm-api/opensearch` e rodando:
`docker-compose up -d`

2. Configure em seu .env a variável 'OPENSEARCH' da seguinte forma:
```
$_ENV['OPENSEARCH'] = [
    'ENDPOINT' => 'URL_OPENSEARCH:9200',
    'INDEXES' => [
        'PESQUISA' => 'meulook_produtos',
        'AUTOCOMPLETE' => 'meulook_autocomplete',
    ],
];
```

3. Para criar o indice vá até o dashboard `http://localhost:5601`.

4. Faça as configurações iniciais.

5. Vá ao dev tools do opensearch por meio da url `http://localhost:5601/app/dev_tools` e execute os comandos:

### 1 - Criar indexes no console:

```js
PUT meulook_produtos
{
  "mappings": {
    "properties": {
      "id": {
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
      "grade_fulfillment": {
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
      "tem_estoque_fulfillment": {
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

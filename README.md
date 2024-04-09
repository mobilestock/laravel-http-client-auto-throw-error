# LP - Look Pay

<details>
  <summary>Ligar projeto para desenvolvimento.</summary>

1. É necessário rodar o comando ```bash yarn ``` no terminal, para que o prettier seja baixado
2. Abra o arquivo `docker-compose.development.yml` e comente os serviços que você não vai precisar
3. ```bash
   docker build -t lookpay-api:latest -f apps/lookpay-api/Dockerfile.development apps/lookpay-api/
   docker compose -f docker-compose.development.yml up --build
   ```
</details>

<details>
    <summary>Configurar .ENV.</summary>

Para fazer a requisição de criar transação, será necessário preencher as seguintes variáveis do .ENV

- IUGU_ACCOUNT_ID= esse dado está presente no web como: $DADOS_PAGAMENTO_IUGUCONTAMOBILE; Caso não encontre, fale com um dos responsáveis pelo backend da tarefa;
- MOBILE_STOCK_API_TOKEN=api:lookpay
- MOBILE_STOCK_API_URL=${seu_backend (web)};
</details>

<details>
  <summary>Rodar testes automatizados.</summary>

```bash
    docker compose -f docker-compose.test.yml up --build
```

</details>

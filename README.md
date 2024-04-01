# LP - Look Pay

<details>
  <summary>Ligar projeto para desenvolvimento.</summary>

1. Abra o arquivo `docker-compose.development.yml` e comente os serviços que você não vai precisar,
2. ````bash
        docker build -t lookpay-api:latest -f apps/lookpay-api/Dockerfile.development apps/lookpay-api/
        docker compose -f docker-compose.development.yml up --build
        ```
   </details>
   ````

<details>
  <summary>Rodar testes automatizados.</summary>

```bash
docker compose -f docker-compose.test.yml up --build
```

</details>

# LP - Look Pay

<details>
  <summary>Ligar projeto para desenvolvimento.</summary>

  1. (WINDOWS) Entrar em Docker->configurações->resources->File Sharing e adicionar o caminho do projeto (Backend)
  2. É necessário rodar o comando `yarn` no terminal, para que o prettier seja baixado
  3. Abra o arquivo `docker-compose.development.yml` e comente os serviços que você não vai precisar
  4. ```bash
     docker build -t lookpay-api:latest -f apps/lookpay-api/Dockerfile.development apps/lookpay-api/
     docker build -t med-api:latest -f apps/med-api/Dockerfile.development apps/med-api/
     docker compose -f docker-compose.development.yml up --build
     ```
</details>

### Configurar .ENV do projeto
<details>
    <summary>.ENV central:</summary>

    Para fazer todos os projetos funcionarem corretamente, é necessário criar um arquivo `.env` na raiz do projeto.
    - ADM_API_URL= a rota pra administração.
    - AWS_PREFIX= esse é o prefixo comum pra acessar a AWS.
    - AWS_ACCESS_KEY_ID= A chave única da conta de usuário da AWS.
    - AWS_DEFAULT_REGION= Especifica a região da AWS.
    - AWS_SECRET_ACCESS_KEY= A chave secreta usada pra assinar digitalmente as solicitações feitas à AWS.
</details>

<details>
    <summary>Configurar .ENV do Lookpay-Api:</summary>

    Para fazer a requisição de criar transação, será necessário preencher as seguintes variáveis do .ENV

    - IUGU_ACCOUNT_ID= esse dado está presente no web como: $DADOS_PAGAMENTO_IUGUCONTAMOBILE; Caso não encontre, fale com um dos responsáveis pelo backend da tarefa;
    - SECRET_MOBILE_STOCK_API_TOKEN=dummy
</details>

<details>
    <summary>Configurar .ENV do Med-Api:</summary>

    - APP_AUTH_TOKEN=dummy
</details>

<details>
    <summary>Comando pra preencher o APP_KEY</summary>

```bash
    php artisan key:generate
```
</details>

<details>
  <summary>Rodar testes automatizados.</summary>

  > Esse comando deve ser rodado em bash

  ```bash
      docker build -t backend-shared:latest shared;
      find . -name '*.dockerignore' -type f -delete;
      docker compose -f docker-compose.test.yml up --build;
      git checkout -- '*.dockerignore';
  ```

</details>

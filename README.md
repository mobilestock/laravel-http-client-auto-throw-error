# Backend

Este repositório contém o backend dos serviços da aplicação. Abaixo estão as instruções para configurar o ambiente, rodar o projeto localmente e executar os testes automatizados.

## Configuração de ambiente

<details>
<summary>Passos para configuração inicial</summary>

1. Crie um arquivo `.env` na raiz do repositório, utilizando como base o arquivo `.env.example` fornecido;
2. Crie um arquivo `.env` em cada serviço individual (diretório), utilizando como base o respectivo `.env.example`;
3. Para preencher a chave `APP_KEY`, execute o seguinte comando no terminal do serviço:

```bash
php artisan key:generate
```

</details>

## Executando o Projeto Localmente

<details>
<summary>Passos para rodar o projeto</summary>

### Pré-requisitos

- Certifique-se de que o Docker está instalado e em execução na sua máquina;
- (_Somente para Windows_): Acesse as configurações do Docker → Configurações → Resources → File Sharing e adicione o caminho do diretório do projeto backend.

### Instruções

1. No terminal, na raiz do repositório, execute o comando abaixo para instalar o Prettier:

```bash
yarn
```

2. Abra o arquivo `docker-compose.development.yml` e comente os serviços que você não deseja executar no momento;
3. Execute os seguintes comandos para construir as imagens Docker e iniciar os serviços:

```bash
docker build -t lookpay-api:latest -f apps/lookpay-api/Dockerfile.development apps/lookpay-api/
docker build -t med-api:latest -f apps/med-api/Dockerfile.development apps/med-api/
docker compose -f docker-compose.development.yml up --build -d
```

Esses comandos irão construir as imagens necessárias e iniciar os contêineres em segundo plano.

</details>

## Executando Testes Automatizados

<details>
<summary>Passos para rodar os testes</summary>

1. Construa a imagem compartilhada de backend:

```bash
docker build -t backend-shared:latest shared
```

2. Remova arquivos `.dockerignore`:

```bash
find . -name '*.dockerignore' -type f -delete
```

3. Execute os testes com o comando abaixo:

```bash
docker compose -f docker-compose.test.yml up --build
```

4. Após a execução dos testes, restaure os arquivos `.dockerignore`:

```bash
git checkout -- '*.dockerignore'
```

**Observação:** Esse procedimento deve ser executado em um terminal Bash para funcionar corretamente.

</details>

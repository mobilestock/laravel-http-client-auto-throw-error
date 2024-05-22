# Para essa tarefa precisaremos de um usuário novo só com permissão de SELECT no banco de dados.

# Antes de criá-lo busque online um gerador de senhas seguro e crie uma senha com:
#   Caracteres especiais,
#   Números,
#   Letras maiúsculas e minúsculas,
#   Mínimo 16 caracteres;

# Quando o fizer substitua na query o termo senha-segura pela senha gerada e rode o script.

CREATE USER 'select_only'@'%' IDENTIFIED BY 'senha-segura';
GRANT SELECT ON mobile_stock.* TO 'select_only'@'%';
FLUSH PRIVILEGES;

# O script criará um usuário com o nome 'select_only' e senha que precisarão serem serem adicionado ao .env do projeto na produção,
# verificar .env.example para saber as chaves.

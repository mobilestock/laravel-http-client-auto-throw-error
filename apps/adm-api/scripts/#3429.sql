# Para essa tarefa precisaremos de um usuário novo só com permissão de SELECT em tabelas específicas do banco de dados.

# Antes de criá-lo busque online um gerador de senhas seguro e crie uma senha com:
#   Caracteres especiais,
#   Números,
#   Letras maiúsculas e minúsculas,
#   Mínimo 16 caracteres;

# Quando o fizer substitua na query o termo senha-segura pela senha gerada e rode o script.

CREATE USER 'user_read_only'@'%' IDENTIFIED BY 'senha-segura';
GRANT SELECT ON mobile_stock.colaboradores_enderecos_logs TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.colaboradores_log TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.entregas_log_devolucoes_item TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.entregas_log_faturamento_item TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.entregas_logs TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.logistica_item_logs TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.negociacoes_produto_log TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.pedido_item_logs TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.pontos_coleta_calculo_percentual_frete_logs TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.tipo_frete_log TO 'user_read_only'@'%';
GRANT SELECT ON mobile_stock.transacao_financeiras_logs TO 'user_read_only'@'%';
FLUSH PRIVILEGES;

# O script criará um usuário com o nome 'user_read_only' e senha que precisarão serem serem adicionado ao .env do projeto na produção,
# verificar .env.example para saber as chaves.

-- Active: 1713881408892@@127.0.0.1@3306@MOBILE_ENTREGAS
ALTER TABLE transportadores_raios
ADD COLUMN valor_coleta DECIMAL(10, 2) NOT NULL DEFAULT 2.50 AFTER prazo_forcar_entrega,
CHANGE valor valor_entrega DECIMAL(10, 2) NOT NULL DEFAULT 3.00;

ALTER TABLE transacao_financeiras_produtos_itens CHANGE COLUMN tipo_item tipo_item ENUM ('AC', 'AP', 'CC', 'CE', 'CL', 'CO', 'FR', 'PR', 'RF', 'CM_LOGISTICA', 'CM_PONTO_COLETA', 'CM_ENTREGA', 'DIREITO_COLETA') CHARACTER
SET
    'utf8' COLLATE 'utf8_swedish_ci' NOT NULL COMMENT 'PR- Produto FR-Frete AC-Adição de credito RF-Retorno Fornecedor AP-Acréscimo CNPJ CC-Comissão criador publicação CE-Comissão entregador CL-Comissão link CO-Comissão MED CM_LOGISTICA-Comissão logistica CM_PONTO_COLETA-Comissão ponto coleta CM_ENTREGA- Comissão tarifa de entrega DIREITO_COLETA-Comissão referênte à coleta do Mobile Entregas';

ALTER TABLE configuracoes
ADD COLUMN porcentagem_comissao_coleta DECIMAL(4, 2) NULL DEFAULT '10' AFTER porcentagem_comissao;

ALTER TABLE transacao_financeiras_metadados CHANGE COLUMN chave chave ENUM (
    'ID_COLABORADOR_TIPO_FRETE',
    'ENDERECO_CLIENTE_JSON',
    'PRODUTOS_JSON',
    'VALOR_FRETE',
    'ID_PEDIDO',
    'ID_UNICO',
    'PRODUTOS_TROCA',
    'ENDERECO_COLETA_JSON'
) NOT NULL COLLATE 'utf8_bin' AFTER id_transacao;
ALTER TABLE transportadores_raios
    ADD COLUMN valor_coleta DECIMAL(10, 2) NOT NULL DEFAULT 3.99 AFTER prazo_forcar_entrega;

ALTER TABLE transacao_financeiras_produtos_itens
    CHANGE COLUMN tipo_item tipo_item ENUM ('AC', 'AP', 'CC', 'CE', 'CL', 'CO', 'FR', 'PR', 'RF', 'CM_LOGISTICA', 'CM_PONTO_COLETA', 'CM_ENTREGA', 'DIREITO_COLETA') CHARACTER SET 'utf8' COLLATE 'utf8_swedish_ci' NOT NULL COMMENT 'PR- Produto FR-Frete AC-Adição de credito RF-Retorno Fornecedor AP-Acréscimo CNPJ CC-Comissão criador publicação CE-Comissão entregador CL-Comissão link CO-Comissão MED CM_LOGISTICA-Comissão logistica CM_PONTO_COLETA-Comissão ponto coleta CM_ENTREGA- Comissão tarifa de entrega DIREITO_COLETA-Comissão referênte à coleta do Mobile Entregas';

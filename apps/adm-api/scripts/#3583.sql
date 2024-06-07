ALTER TABLE transportadores_raios
    ADD COLUMN valor_coleta DECIMAL(10, 2) NOT NULL DEFAULT 3.99 AFTER prazo_forcar_entrega;

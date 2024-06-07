ALTER TABLE transportadores_raios
    ADD COLUMN pode_fazer_coleta TINYINT(1)     NOT NULL DEFAULT 0 AFTER prazo_forcar_entrega,
    ADD COLUMN valor_coleta      DECIMAL(10, 2) NOT NULL DEFAULT 3.99 AFTER pode_fazer_coleta;

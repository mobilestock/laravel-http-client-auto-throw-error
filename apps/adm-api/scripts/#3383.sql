-- Active: 1715943724727@@127.0.0.1@3306@banco_normal
ALTER TABLE configuracoes
ADD COLUMN dias_pagamento_transferencia_antecipacao TINYINT DEFAULT 0 AFTER dias_pagamento_transferencia_ENTREGADOR;
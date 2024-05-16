ALTER TABLE acompanhamento_temp
    ADD COLUMN id_raio INT(11) NULL COMMENT 'ID do raio que está em transportadores_raios.\n\nApenas para ponto móvel.' AFTER id_cidade,
    CHANGE COLUMN id_destinatario id_destinatario INT(11) NOT NULL COMMENT 'ID do destino final.\n\nSe for retiradada ou transportadora, refere-se ao id_colaborador do cliente que fez a compra;\nSe for ponto parado, refere-se ao id_colaborador do responsável pelo ponto;\nSe for ponto movel, refere-se ao id_colaborador do entregador.',
    CHANGE COLUMN id_tipo_frete id_tipo_frete INT(11) NOT NULL COMMENT 'ID do tipo_frete de destino, que está em tipo_frete.\n\n2 = transportadora\n3 = Vou buscar na MobileStock',
    DROP INDEX UNIQUE_destino,
    ADD UNIQUE INDEX UNIQUE_destino (id_destinatario ASC, id_tipo_frete ASC, id_cidade ASC, id_raio ASC);

ALTER TABLE acompanhamento_item_temp
    DROP COLUMN data_atualizacao;

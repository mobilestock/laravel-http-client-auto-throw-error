-- --------------------------------------------------------
-- Host:                         mobilestock-prod.cwlisnj4go4t.sa-east-1.rds.amazonaws.com
-- Server version:               10.4.30-MariaDB-log - Source distribution
-- Server OS:                    Linux
-- HeidiSQL Version:             11.3.0.6295
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table mobile_stock.acertos
CREATE TABLE IF NOT EXISTS `acertos` (
  `id` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT NULL,
  `origem` varchar(100) DEFAULT NULL,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `data_acerto` timestamp NULL DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `numero_documento` int(11) NOT NULL DEFAULT 0,
  `desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observacao_acerto` varchar(1000) DEFAULT NULL,
  KEY `idx_id_colab_usuario_doc` (`id`,`id_colaborador`,`usuario`,`numero_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.acertos_documentos
CREATE TABLE IF NOT EXISTS `acertos_documentos` (
  `id_acerto` int(11) NOT NULL DEFAULT 0,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT NULL,
  `documento` int(11) NOT NULL DEFAULT 0,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `motivo` varchar(500) DEFAULT NULL,
  `conta_bancaria` int(11) NOT NULL DEFAULT 0,
  `numero` int(11) NOT NULL DEFAULT 0,
  `tesouraria` int(11) NOT NULL DEFAULT 0,
  `responsavel` int(11) NOT NULL DEFAULT 0,
  `caixa` int(11) NOT NULL DEFAULT 1,
  KEY `idx_acertos_documentos` (`id_acerto`,`sequencia`,`documento`,`usuario`,`conta_bancaria`,`numero`,`tesouraria`,`responsavel`,`caixa`,`tipo`,`valor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.acompanhamento_item_temp
CREATE TABLE IF NOT EXISTS `acompanhamento_item_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_acompanhamento` int(11) NOT NULL,
  `uuid_produto` varchar(100) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_produto_UNIQUE` (`uuid_produto`),
  KEY `FK_id_acompanhamento_idx` (`id_acompanhamento`),
  CONSTRAINT `FK_id_acompanhamento` FOREIGN KEY (`id_acompanhamento`) REFERENCES `acompanhamento_temp` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_uuid_produto` FOREIGN KEY (`uuid_produto`) REFERENCES `logistica_item` (`uuid_produto`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=169606 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.acompanhamento_temp
CREATE TABLE IF NOT EXISTS `acompanhamento_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_destinatario` int(11) NOT NULL,
  `id_tipo_frete` int(11) NOT NULL,
  `id_cidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `situacao` enum('PENDENTE','AGUARDANDO_SEPARAR','AGUARDANDO_ADICIONAR_ENTREGA','ENTREGA_EM_ABERTO','PAUSADO') NOT NULL DEFAULT 'PENDENTE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_destino` (`id_destinatario`,`id_tipo_frete`,`id_cidade`)
) ENGINE=InnoDB AUTO_INCREMENT=8096 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.alerta_responsavel_estoque_cancelamento
CREATE TABLE IF NOT EXISTS `alerta_responsavel_estoque_cancelamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `id_responsavel_estoque` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4498 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.analise_estoque
CREATE TABLE IF NOT EXISTS `analise_estoque` (
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `codigo` varchar(30) DEFAULT NULL,
  `tipo` varchar(2) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL DEFAULT 0,
  `cod_barras` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.analise_estoque_header
CREATE TABLE IF NOT EXISTS `analise_estoque_header` (
  `localizacao` varchar(5) DEFAULT NULL,
  `pares` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.api_colaboradores
CREATE TABLE IF NOT EXISTS `api_colaboradores` (
  `id_colaborador` int(11) NOT NULL,
  `id_zoop` varchar(200) DEFAULT NULL,
  `id_iugu` varchar(80) NOT NULL DEFAULT '',
  `id_pagarme` varchar(80) NOT NULL DEFAULT '',
  `iugu_token_user` varchar(80) DEFAULT NULL,
  `iugu_token_teste` varchar(80) DEFAULT NULL,
  `iugu_token_live` varchar(80) DEFAULT NULL,
  `conta_iugu_verificada` char(2) NOT NULL DEFAULT 'F',
  `status` varchar(100) DEFAULT NULL,
  `resource` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `account_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fiscal_responsibility` varchar(200) DEFAULT NULL,
  `first_name` varchar(200) DEFAULT NULL,
  `last_name` varchar(200) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `taxpayer_id` varchar(50) DEFAULT NULL,
  `birthdate` timestamp NULL DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `business_name` varchar(500) DEFAULT NULL,
  `business_phone` varchar(500) DEFAULT NULL,
  `business_email` varchar(200) DEFAULT NULL,
  `business_website` varchar(200) DEFAULT NULL,
  `business_description` varchar(200) DEFAULT NULL,
  `business_facebook` varchar(200) DEFAULT NULL,
  `business_twitter` varchar(200) DEFAULT NULL,
  `statement_descriptor` varchar(200) DEFAULT NULL,
  `ein` varchar(50) DEFAULT NULL,
  `b_line1` varchar(200) DEFAULT NULL,
  `b_line2` varchar(200) DEFAULT NULL,
  `b_line3` varchar(200) DEFAULT NULL,
  `b_neighborhood` varchar(200) DEFAULT NULL,
  `b_city` varchar(200) DEFAULT NULL,
  `b_state` varchar(2) DEFAULT NULL,
  `b_postal_code` varchar(20) DEFAULT NULL,
  `b_country_code` varchar(2) DEFAULT NULL,
  `business_opening_date` timestamp NULL DEFAULT NULL,
  `line1` varchar(200) DEFAULT NULL,
  `line2` varchar(200) DEFAULT NULL,
  `line3` varchar(200) DEFAULT NULL,
  `neighborhood` varchar(200) DEFAULT NULL,
  `city` varchar(200) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `delinquent` tinyint(1) DEFAULT 0,
  `default_debit` varchar(200) DEFAULT NULL,
  `default_credit` varchar(200) DEFAULT NULL,
  `mcc` int(11) NOT NULL DEFAULT 10,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_colaborador`),
  KEY `idx_id_zoop` (`id_zoop`) USING BTREE,
  KEY `idx_ein` (`ein`) USING BTREE,
  KEY `idx_taxpayer_id` (`taxpayer_id`) USING BTREE,
  KEY `idx_id_colaborador` (`id_colaborador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.api_colaboradores_inativos
CREATE TABLE IF NOT EXISTS `api_colaboradores_inativos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_zoop` varchar(200) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `taxpayer_id` varchar(50) DEFAULT NULL,
  `ein` varchar(50) DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(200) DEFAULT NULL,
  `id_iugu` varchar(200) DEFAULT NULL,
  `iugu_token_user` varchar(200) DEFAULT NULL,
  `iugu_token_teste` varchar(200) DEFAULT NULL,
  `iugu_token_live` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=312 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.api_transacao
CREATE TABLE IF NOT EXISTS `api_transacao` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_transacao` varchar(80) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL,
  `url_boleto` varchar(1000) DEFAULT NULL,
  `original_amount` int(11) NOT NULL DEFAULT 0,
  `barcode` varchar(1000) DEFAULT NULL,
  `json_split` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json_split`)),
  `fees` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_vencimento` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_transacao` (`id_transacao`,`status`,`data_criacao`,`data_hora`)
) ENGINE=InnoDB AUTO_INCREMENT=4824 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.atendimento_cliente
CREATE TABLE IF NOT EXISTS `atendimento_cliente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `id_tipo_atendimento` int(11) NOT NULL DEFAULT 0,
  `controle` int(11) NOT NULL DEFAULT 0 COMMENT '1 - Erro no PAC Endereco. 2 - Erro PAC informações incompletas.',
  `mensagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `anexo` varchar(1000) DEFAULT NULL,
  `id_transacao` int(11) DEFAULT NULL,
  `id_faturamento` int(11) DEFAULT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `id_colaborador` varchar(10) DEFAULT NULL,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_final` datetime DEFAULT NULL,
  `numero_par` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sugestao` text DEFAULT NULL COMMENT 'Sugestao para melhorar atendimento',
  `score` int(11) DEFAULT -1 COMMENT '-1 - AINDA N FINALIZADO 0 - AGUARDANDO AVALIACAO 1 A 5 - AVALIACAO',
  `uuid` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_tipo_atendimento` (`id_tipo_atendimento`),
  KEY `id_tipo_defeito` (`controle`),
  KEY `id_colaborador` (`id_colaborador`),
  KEY `atendimento_cliente_id_transacao_IDX` (`id_transacao`),
  KEY `atendimento_cliente_uuid_IDX` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=4308 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.atualiza_cliente_pedido_item
DELIMITER //
CREATE PROCEDURE `atualiza_cliente_pedido_item`(
	IN `_UUID` VARCHAR(100),
	IN `_NOME_CONSUMIDOR_FINAL` VARCHAR(100),
	IN `_ID_CONSUMIDOR_FINAL` INT
)
BEGIN
	DECLARE _ID_CLIENTE INT DEFAULT 0;
	DECLARE _ID_PRODUTO INT DEFAULT 0;
	DECLARE _NOME_TAMANHO VARCHAR(50);
	DECLARE _PRECO DECIMAL(10, 2) DEFAULT 0;
	DECLARE _SITUACAO VARCHAR(1) DEFAULT 'A';

	SELECT pedido_item.id_cliente,
		pedido_item.id_produto,
		pedido_item.nome_tamanho,
		pedido_item.preco
	INTO _ID_CLIENTE, _ID_PRODUTO, _NOME_TAMANHO, _PRECO
	FROM pedido_item
	WHERE pedido_item.uuid = _UUID
	AND COALESCE(pedido_item.id_cliente_final, 0) <> _ID_CONSUMIDOR_FINAL;

	IF(_ID_CLIENTE = 0) THEN
		SELECT
			(SELECT transacao_financeiras.pagador FROM transacao_financeiras WHERE transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao),
			transacao_financeiras_produtos_itens.id_produto,
			transacao_financeiras_produtos_itens.nome_tamanho,
			transacao_financeiras_produtos_itens.preco,
			'C'
		INTO _ID_CLIENTE, _ID_PRODUTO, _NOME_TAMANHO, _PRECO, _SITUACAO
		FROM transacao_financeiras_produtos_itens
		WHERE transacao_financeiras_produtos_itens.uuid_produto = _UUID
		AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
		AND COALESCE(transacao_financeiras_produtos_itens.id_consumidor_final, 0) <> _ID_CONSUMIDOR_FINAL;
	END IF;

#	IF(_ID_CLIENTE > 0) THEN
#		DELETE FROM med_venda_produtos_consumidor_final WHERE med_venda_produtos_consumidor_final.uuid_pedido_item = _UUID;
#
#		IF(_ID_CONSUMIDOR_FINAL > 0) THEN
#			INSERT INTO med_venda_produtos_consumidor_final (
#				med_venda_produtos_consumidor_final.id_cliente,
#				med_venda_produtos_consumidor_final.id_consumidor_final,
#				med_venda_produtos_consumidor_final.id_produto,
#				med_venda_produtos_consumidor_final.nome_tamanho,
#				med_venda_produtos_consumidor_final.valor,
#				med_venda_produtos_consumidor_final.uuid_pedido_item,
#				med_venda_produtos_consumidor_final.situacao
#			)
#	      SELECT _ID_CLIENTE,
#				_ID_CONSUMIDOR_FINAL,
#				_ID_PRODUTO,
#				_NOME_TAMANHO,
#				CAST(med_calcula_valor_revenda(_PRECO,_ID_CLIENTE) AS DECIMAL(10, 2)),
#				_UUID,
#				_SITUACAO;
#		END IF;
#	END IF;

	UPDATE pedido_item SET pedido_item.cliente = _NOME_CONSUMIDOR_FINAL, pedido_item.id_cliente_final = _ID_CONSUMIDOR_FINAL
	WHERE pedido_item.uuid = _UUID;

	UPDATE transacao_financeiras_produtos_itens
	SET transacao_financeiras_produtos_itens.observacao = _NOME_CONSUMIDOR_FINAL,
	transacao_financeiras_produtos_itens.id_consumidor_final = _ID_CONSUMIDOR_FINAL
	WHERE transacao_financeiras_produtos_itens.uuid_produto = _UUID
		AND transacao_financeiras_produtos_itens.tipo_item = 'PR';
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.atualiza_faturamento_separacao
DELIMITER //
CREATE PROCEDURE `atualiza_faturamento_separacao`()
BEGIN

END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.atualiza_lancamento
DELIMITER //
CREATE PROCEDURE `atualiza_lancamento`(
	IN `ID_LANCAMENTO` INT
)
BEGIN
	IF(ID_LANCAMENTO>0)THEN
		INSERT INTO lancamento_financeiro(tipo,
												 documento,
												 situacao,
												 origem,
												 id_colaborador,
												 data_emissao,
												 valor,
												 valor_total,
												 numero_documento,
												 id_usuario,
												 observacao,
												 nota_fiscal,
												 pedido_origem,
												 lancamento_origem)
		SELECT tipo,
				 documento,
				 situacao,
				 origem,
				 id_colaborador,
				 data_emissao,
				 valor,
				 valor_total,
				 numero_documento,
				 id_usuario,
				 observacao,
				 nota_fiscal,
				 pedido_origem,
				 lancamento_origem
		FROM lancamento_financeiro_temp
		WHERE lancamento_financeiro_temp.id_lancamento = ID_LANCAMENTO;

		DELETE FROM lancamento_financeiro_temp
		WHERE lancamento_financeiro_temp.id_lancamento = ID_LANCAMENTO;
	ELSE
		INSERT INTO lancamento_financeiro(tipo,
												 documento,
												 situacao,
												 origem,
												 id_colaborador,
												 data_emissao,
												 valor,
												 valor_total,
												 numero_documento,
												 id_usuario,
												 observacao,
												 nota_fiscal,
												 pedido_origem,
												 lancamento_origem)
		SELECT tipo,
				 documento,
				 situacao,
				 origem,
				 id_colaborador,
				 data_emissao,
				 valor,
				 valor_total,
				 numero_documento,
				 id_usuario,
				 observacao,
				 nota_fiscal,
				 pedido_origem,
				 lancamento_origem
		FROM lancamento_financeiro_temp;

		DELETE FROM lancamento_financeiro_temp;
	END IF;


END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.atualiza_metas_cliente
DELIMITER //
CREATE PROCEDURE `atualiza_metas_cliente`(
	IN `_IDCLIENTE` int,
	IN `_VALOR` decimal(10,2),
	IN `_TIPO` char
)
BEGIN
	DECLARE _VALORMETA INT DEFAULT 0;
    DECLARE _IDTEMP INT DEFAULT 0;
    DECLARE _METADEFAULT DECIMAL(10,2) DEFAULT 0;


	SET _METADEFAULT = (SELECT meta_mensal_valor FROM configuracoes LIMIT 1);


	SELECT id INTO _IDTEMP FROM metas WHERE id_cliente=_IDCLIENTE AND VALOR < _METADEFAULT AND GERADO = 0 AND MONTH(NOW()) = MONTH(data_meta) ORDER BY id DESC LIMIT 1;


    IF (_TIPO = 'E') THEN


        SELECT valor INTO _VALORMETA FROM metas WHERE id_cliente=_IDCLIENTE AND VALOR < _METADEFAULT AND GERADO = 0 AND MONTH(NOW()) = MONTH(data_meta) ORDER BY id DESC LIMIT 1;


        if(_IDTEMP > 0 && _VALOR <= (_METADEFAULT - _VALORMETA))THEN


			IF _VALOR + _VALORMETA = _METADEFAULT THEN
				UPDATE metas SET valor = _VALOR + _VALORMETA, data_meta= NOW(), gerado = 1 WHERE id = _IDTEMP;
			ELSE
				UPDATE metas SET valor = _VALOR + _VALORMETA, data_meta= NOW() WHERE id = _IDTEMP;
			END IF;


        ELSE
			SET @RESTO := _VALOR;
            WHILE(@RESTO > 0) DO
				IF(_VALORMETA > 0) THEN
					UPDATE metas SET valor = _METADEFAULT, data_meta= NOW(), gerado = 1 WHERE id = _IDTEMP;
                    SET @RESTO := @RESTO - (_METADEFAULT - _VALORMETA);
                    SET _VALORMETA := 0;
				ELSE
					if @RESTO >= _METADEFAULT THEN
						INSERT INTO metas (id_cliente, valor, data_meta, gerado) VALUES (_IDCLIENTE, _METADEFAULT, NOW(), 1);
						SET @RESTO := @RESTO - _METADEFAULT;
                    else
						INSERT INTO metas (id_cliente, valor, data_meta) VALUES (_IDCLIENTE, @RESTO, NOW());
                        SET @RESTO := 0;
					END IF;
				END IF;
            END WHILE;
        END IF;
    END IF;
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.atualiza_saque_antigo
DELIMITER //
CREATE PROCEDURE `atualiza_saque_antigo`()
BEGIN
	DECLARE done boolean DEFAULT 0;
    DECLARE _IDLANCAMENTO int;
	DECLARE _SEQUENCIA int;
    DECLARE _TIPO char(1);
    DECLARE _DOCUMENTO int;
    DECLARE _SITUACAO int;
    DECLARE _ORIGEM varchar(10);
    DECLARE _IDCOLABORADOR int;
    DECLARE _VALOR decimal(10,2);
    DECLARE _VALORTOTAL decimal(10,2);
    DECLARE _IDUSUARIO int;
    DECLARE _IDUSUARIOPAG int;
    DECLARE _OBSERVACAO varchar(1000);
    DECLARE _TABELA int;
	DECLARE _PARES int;
    DECLARE _TRANSACAOORIGEM int;
    DECLARE _CODTRANSACAO varchar(100);
    DECLARE _BLOQUEADO int;
    DECLARE _IDSPLIT varchar(1000);
    DECLARE _PARCELAMENTO varchar(1000);
    DECLARE _JUROS decimal(10,2);
    DECLARE _IDCOLABPRIORIDADE int;
    DECLARE _DATAVENCIMENTO varchar(100);

		DECLARE cur1 cursor for SELECT lancamento_financeiro_pendente.sequencia,
				lancamento_financeiro_pendente.tipo,
				lancamento_financeiro_pendente.documento,
				lancamento_financeiro_pendente.situacao,
				lancamento_financeiro_pendente.origem,
				lancamento_financeiro_pendente.id_colaborador,
				lancamento_financeiro_pendente.valor,
				lancamento_financeiro_pendente.valor_total,
				lancamento_financeiro_pendente.id_usuario,
				lancamento_financeiro_pendente.id_usuario_pag,
				lancamento_financeiro_pendente.observacao,
				lancamento_financeiro_pendente.tabela,
				lancamento_financeiro_pendente.pares,
				lancamento_financeiro_pendente.transacao_origem,
				lancamento_financeiro_pendente.cod_transacao,
				lancamento_financeiro_pendente.bloqueado,
				lancamento_financeiro_pendente.id_split,
				lancamento_financeiro_pendente.parcelamento,
				lancamento_financeiro_pendente.juros,
                lancamento_financeiro_pendente.data_vencimento
                FROM lancamento_financeiro_pendente WHERE lancamento_financeiro_pendente.origem = "PF"
                AND EXISTS(SELECT 1 FROM transacao_financeiras WHERE transacao_financeiras.status = 'PE' AND transacao_financeiras.id = lancamento_financeiro_pendente.transacao_origem);
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

		OPEN cur1;
		readLoop: LOOP
		FETCH cur1 INTO _SEQUENCIA, _TIPO, _DOCUMENTO, _SITUACAO, _ORIGEM, _IDCOLABORADOR, _VALOR, _VALORTOTAL, _IDUSUARIO, _IDUSUARIOPAG, _OBSERVACAO, _TABELA,
        _PARES, _TRANSACAOORIGEM, _CODTRANSACAO, _BLOQUEADO, _IDSPLIT, _PARCELAMENTO, _JUROS, _DATAVENCIMENTO;
			IF done=1 THEN
			  LEAVE readLoop;
			END IF;
			INSERT INTO colaboradores_prioridade_pagamento (
				id_colaborador,
                valor_pagamento,
                valor_pago,
                data_criacao,
                usuario,
                pago
            )VALUES(
				_IDCOLABORADOR,
                _VALORTOTAL,
                _VALORTOTAL,
                NOW(),
                _IDUSUARIO,
                'T'
            );

            SET _IDCOLABPRIORIDADE = last_insert_id();

            SELECT lancamento_financeiro.id id_lancamento INTO _IDLANCAMENTO
            FROM lancamento_financeiro
            WHERE lancamento_financeiro.id_prioridade_saque = _IDCOLABPRIORIDADE;

            INSERT INTO lancamentos_financeiros_recebiveis
            (id_transacao,
            id_lancamento,
            situacao,
            id_zoop_split,
            id_recebedor,
            num_parcela,
            valor_pago,
            valor,
            data_vencimento,
            data_gerado,
            cod_transacao)
            VALUES (_TRANSACAOORIGEM,
            _IDLANCAMENTO,
            'PA',
            _IDSPLIT,
            _IDCOLABORADOR,
            1,
            _VALOR,
            _VALOR,
            _DATAVENCIMENTO,
            NOW(),
            _CODTRANSACAO);

		END LOOP readLoop;
	CLOSE cur1;

    DELETE FROM lancamento_financeiro_pendente WHERE lancamento_financeiro_pendente.origem = "PF"
                AND EXISTS(SELECT 1 FROM transacao_financeiras WHERE transacao_financeiras.status = 'PE' AND transacao_financeiras.id = lancamento_financeiro_pendente.transacao_origem);
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.avaliacao_produtos
CREATE TABLE IF NOT EXISTS `avaliacao_produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `qualidade` int(11) DEFAULT 0,
  `custo_beneficio` int(11) DEFAULT 0,
  `comentario` varchar(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_avaliacao` timestamp NULL DEFAULT current_timestamp(),
  `foto_upload` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_faturamento` int(11) DEFAULT 0,
  `origem` enum('MS','ML') DEFAULT 'MS' COMMENT 'MS-MobileStock, ML-Meulook',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_produto` (`id_produto`),
  KEY `idx_avaliacao` (`qualidade`,`custo_beneficio`),
  KEY `idx_qualidade` (`qualidade`)
) ENGINE=InnoDB AUTO_INCREMENT=387620 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.avaliacao_tipo_frete
CREATE TABLE IF NOT EXISTS `avaliacao_tipo_frete` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_tipo_frete` int(11) NOT NULL,
  `nota_atendimento` tinyint(1) unsigned DEFAULT 0,
  `nota_localizacao` tinyint(1) unsigned DEFAULT 0,
  `comentario` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `visualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_colaborador` (`id_colaborador`),
  KEY `id_tipo_frete` (`id_tipo_frete`),
  CONSTRAINT `avaliacao_tipo_frete_ibfk_1` FOREIGN KEY (`id_colaborador`) REFERENCES `colaboradores` (`id`),
  CONSTRAINT `avaliacao_tipo_frete_ibfk_2` FOREIGN KEY (`id_tipo_frete`) REFERENCES `tipo_frete` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25606 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.bancos
CREATE TABLE IF NOT EXISTS `bancos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(500) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL,
  `cod_banco` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=283 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for function mobile_stock.calcula_percentual_por_km
DELIMITER //
CREATE FUNCTION `calcula_percentual_por_km`(`ID_TIPO_FRETE_` INT
) RETURNS int(11)
BEGIN
	DECLARE JSON_PERCENTUAL JSON DEFAULT (SELECT porcentagem_comissao_freteiros_por_km FROM configuracoes);
	DECLARE JSON_ITEMS_ INT DEFAULT JSON_LENGTH(JSON_PERCENTUAL);
	DECLARE INDEX_ INT DEFAULT 0;

	DECLARE LAT_CENTRAL_ DECIMAL DEFAULT (SELECT latitude_central FROM configuracoes);
	DECLARE LNG_CENTRAL_ DECIMAL DEFAULT (SELECT longitude_central FROM configuracoes);
	DECLARE DISTANCIA_ INT DEFAULT (
		SELECT CEIL(distancia_geolocalizacao(LAT_CENTRAL_, LNG_CENTRAL_, tipo_frete.latitude, tipo_frete.longitude)) FROM tipo_frete WHERE tipo_frete.id = ID_TIPO_FRETE_ LIMIT 1
	);

	WHILE INDEX_ < JSON_ITEMS_ DO
		IF (DISTANCIA_ >= JSON_EXTRACT(JSON_PERCENTUAL, CONCAT('$[', INDEX_, '].de')) && DISTANCIA_ <= JSON_EXTRACT(JSON_PERCENTUAL, CONCAT('$[', INDEX_, '].ate'))) THEN
			RETURN JSON_EXTRACT(JSON_PERCENTUAL, CONCAT('$[', INDEX_, '].porcentagem'));
		END IF;
	 	SET INDEX_ := INDEX_ + 1;
	END WHILE;

	RETURN 0;
END//
DELIMITER ;

-- Dumping structure for function mobile_stock.calcula_valor_venda
DELIMITER //
CREATE FUNCTION `calcula_valor_venda`(`_IDCLIENTE` int,
	`_IDPRODUTO` int
) RETURNS decimal(10,2)
BEGIN
	DECLARE _VALORVENDA DECIMAL(10,2);
    DECLARE _CUSTO DECIMAL(10,2);
    DECLARE _IDFORNECEDOR INT;
    DECLARE _VALORESTOQUE DECIMAL(10,2);
    DECLARE _VALORPEDIDO DECIMAL(10,2);
    DECLARE _SALDOMOBILE DECIMAL(10,2);
    -- https://github.com/mobilestock/web/issues/2618
	SELECT
		produtos.valor_venda_ms,
		produtos.valor_custo_produto,
		produtos.id_fornecedor
	INTO
		_VALORVENDA,
		_CUSTO,
		_IDFORNECEDOR
	FROM produtos
	WHERE produtos.id = _IDPRODUTO;

    IF (_IDCLIENTE = _IDFORNECEDOR) THEN
		SELECT COALESCE(estoque_grade_valor_dia.valor_estoque, 0) * (configuracoes.porcentagem_antecipacao / 100) AS `valor_estoque`
		INTO _VALORESTOQUE
		FROM estoque_grade_valor_dia
		INNER JOIN configuracoes ON TRUE
		WHERE estoque_grade_valor_dia.id_fornecedor = _IDFORNECEDOR;

		SELECT COUNT(pedido_item.id_produto) * _CUSTO
		INTO _VALORPEDIDO
		FROM pedido_item
		WHERE pedido_item.situacao = 1
			AND pedido_item.id_cliente = _IDFORNECEDOR
			AND pedido_item.id_produto = _IDPRODUTO
		GROUP BY pedido_item.id_produto;

		SET _SALDOMOBILE = (SELECT saldo_cliente(_IDFORNECEDOR) saldoCliente);

		SET _VALORVENDA := 1;
		IF (_VALORESTOQUE + _SALDOMOBILE - _VALORPEDIDO <= 0) THEN
			SET _VALORVENDA := _VALORVENDA + _CUSTO;
		END IF;
	END IF;
RETURN _VALORVENDA;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.campanhas
CREATE TABLE IF NOT EXISTS `campanhas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url_pagina` varchar(1000) NOT NULL,
  `url_imagem` varchar(1000) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.cartoes_senhas
CREATE TABLE IF NOT EXISTS `cartoes_senhas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave_publica` varchar(1000) NOT NULL,
  `chave_privada` varchar(1000) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.catalogo_fixo
CREATE TABLE IF NOT EXISTS `catalogo_fixo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_publicacao` int(11) DEFAULT NULL,
  `tipo` enum('IMPULSIONAR','MELHOR_FABRICANTE','PROMOCAO_TEMPORARIA','VENDA_RECENTE','MELHOR_PONTUACAO') DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `expira_em` datetime NOT NULL,
  `id_publicacao_produto` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `nome_produto` varchar(100) NOT NULL,
  `valor_venda_ml` decimal(10,2) DEFAULT 0.00,
  `valor_venda_ms` decimal(10,2) DEFAULT 0.00,
  `valor_venda_ml_historico` decimal(10,2) DEFAULT 0.00,
  `valor_venda_ms_historico` decimal(10,2) DEFAULT 0.00,
  `foto_produto` varchar(1000) NOT NULL,
  `quantidade_acessos` int(11) DEFAULT 0,
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantidade_vendida` int(11) DEFAULT 0,
  `vendas_recentes` int(11) NOT NULL DEFAULT 0,
  `pontuacao` int(11) NOT NULL DEFAULT 0,
  `possui_fulfillment` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `id_publicacao` (`id_publicacao`),
  CONSTRAINT `catalogo_fixo_ibfk_1` FOREIGN KEY (`id_publicacao`) REFERENCES `publicacoes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12077847 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.catalogo_personalizado
CREATE TABLE IF NOT EXISTS `catalogo_personalizado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) DEFAULT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('PRIVADO','PUBLICO') NOT NULL DEFAULT 'PRIVADO',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `produtos` text NOT NULL DEFAULT '[]',
  `plataformas_filtros` varchar(20) DEFAULT '["MS","ML","MED"]',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_colaborador` (`id_colaborador`),
  CONSTRAINT `catalogo_personalizado_ibfk_1` FOREIGN KEY (`id_colaborador`) REFERENCES `colaboradores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1009 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `mostrar_altura_salto` int(11) NOT NULL DEFAULT 0,
  `icone_imagem` varchar(30) DEFAULT NULL,
  `subcategoria` varchar(10) DEFAULT NULL COMMENT 'CA - Calçados\nRO - Roupas',
  `id_categoria_pai` int(11) DEFAULT NULL,
  `tags` varchar(100) DEFAULT NULL,
  `ordem` tinyint(2) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.cheques
CREATE TABLE IF NOT EXISTS `cheques` (
  `id` int(11) NOT NULL DEFAULT 0,
  `banco` int(11) NOT NULL DEFAULT 0,
  `agencia` varchar(10) DEFAULT NULL,
  `conta` varchar(20) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `data_vencimento` timestamp NULL DEFAULT NULL,
  `data_situacao` timestamp NULL DEFAULT NULL,
  `cpf` varchar(11) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `recebido_de` int(11) NOT NULL DEFAULT 0,
  `passado_para` int(11) NOT NULL DEFAULT 0,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `acerto_pagar` int(11) NOT NULL DEFAULT 0,
  `acerto_receber` int(11) NOT NULL DEFAULT 0,
  `guardou` int(11) NOT NULL DEFAULT 0,
  `data_guardou` timestamp NULL DEFAULT NULL,
  `passado_para_manual` varchar(1000) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores
CREATE TABLE IF NOT EXISTS `colaboradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regime` int(11) NOT NULL DEFAULT 0,
  `cnpj` varchar(18) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `razao_social` varchar(1000) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `telefone` varchar(16) DEFAULT NULL,
  `telefone2` varchar(16) DEFAULT NULL,
  `email` varchar(500) DEFAULT NULL,
  `bloqueado` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT 'C',
  `usuario` varchar(255) DEFAULT NULL,
  `em_uso` int(11) NOT NULL DEFAULT 0,
  `conta_principal` tinyint(1) NOT NULL DEFAULT 0,
  `emite_nota` int(11) NOT NULL DEFAULT 0,
  `foto_perfil` varchar(1000) DEFAULT NULL,
  `pagamento_bloqueado` char(2) DEFAULT 'T',
  `data_botao_atualiza_produtos_entrada` date DEFAULT NULL COMMENT 'Data da utilizaca do botao para reposicionar produtos no catalogo ',
  `id_tipo_entrega_padrao` int(11) NOT NULL DEFAULT 0 COMMENT 'Id ponto de retirada',
  `usuario_meulook` varchar(100) DEFAULT NULL,
  `bloqueado_criar_look` char(2) NOT NULL DEFAULT 'F' COMMENT 'F - Colaborador pode postar foto; T - Colaborador não pode mais postar foto',
  `bloqueado_repor_estoque` enum('T','F') NOT NULL DEFAULT 'T' COMMENT 'F - Colaborador pode repor estoque; T - Colaborador não pode repor estoque;',
  `nome_instagram` varchar(50) DEFAULT NULL,
  `inscrito_receber_novidades` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0: Não receberá mensagens sobre novidades | 1: Receberá mensagens sobre novidades',
  `adiantamento_bloqueado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1: Colaboraborador com saque bloqueado. | 0: colaborador não está com saque bloqueado ',
  `url_webhook` varchar(1000) DEFAULT NULL,
  `tipo_embalagem` enum('SA','CA') CHARACTER SET utf8 COLLATE utf8_bin DEFAULT 'SA' COMMENT 'CA - Caixa, SA - Sacola',
  `observacoes` varchar(1000) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usuario_meulook` (`usuario_meulook`),
  KEY `idx_colaboradores` (`id`,`regime`,`em_uso`,`bloqueado`,`razao_social`(255)) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=77137 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_documentos
CREATE TABLE IF NOT EXISTS `colaboradores_documentos` (
  `id_colaborador` int(11) NOT NULL,
  `tipo_documento` enum('CARTEIRA_HABILITACAO','CEDULA_IDENTIDADE','REGISTRO_VEICULO','COMPROVANTE_ENDERECO') NOT NULL,
  `url_documento` varchar(255) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_enderecos
CREATE TABLE IF NOT EXISTS `colaboradores_enderecos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_cidade` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `apelido` varchar(255) DEFAULT NULL,
  `esta_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `eh_endereco_padrao` tinyint(1) NOT NULL DEFAULT 0,
  `logradouro` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `complemento` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `ponto_de_referencia` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `bairro` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `cidade` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `cep` char(8) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `IDX_id_colaborador` (`id_colaborador`),
  KEY `FK_id_cidade` (`id_cidade`),
  CONSTRAINT `FK_id_cidade` FOREIGN KEY (`id_cidade`) REFERENCES `municipios` (`id`),
  CONSTRAINT `FK_id_colaborador` FOREIGN KEY (`id_colaborador`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=133885 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_endereco_log
CREATE TABLE IF NOT EXISTS `colaboradores_endereco_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_endereco` int(11) DEFAULT NULL,
  `id_colaborador` int(11) NOT NULL,
  `endereco_novo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '' CHECK (json_valid(`endereco_novo`)),
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38841 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_log
CREATE TABLE IF NOT EXISTS `colaboradores_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `mensagem` longtext DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `IDX_id_colaborador` (`id_colaborador`)
) ENGINE=InnoDB AUTO_INCREMENT=5122 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_prioridade_pagamento
CREATE TABLE IF NOT EXISTS `colaboradores_prioridade_pagamento` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL COMMENT 'Solicitante do Saque',
  `id_conta_bancaria` int(11) NOT NULL COMMENT 'Conta Bancaria Favorecida',
  `valor_pagamento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario` varchar(20) DEFAULT NULL,
  `situacao` char(2) NOT NULL DEFAULT 'CR' COMMENT 'CR - criado EP - em processamento PA - pago RE - rejeitado RV - rejeitado visualizado',
  `id_transferencia` varchar(80) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_prioridade_pgto_situacao` (`situacao`)
) ENGINE=InnoDB AUTO_INCREMENT=30801 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_seguidores
CREATE TABLE IF NOT EXISTS `colaboradores_seguidores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `id_colaborador_seguindo` int(11) NOT NULL DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22175 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.colaboradores_suspeita_fraude
CREATE TABLE IF NOT EXISTS `colaboradores_suspeita_fraude` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `situacao` enum('PE','FR','LG','LT') NOT NULL DEFAULT 'PE' COMMENT 'PE - Pendente FR - Fraude LG - Legitimo LT - Liberado temporariamente',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `origem_transacao` enum('ML','MS','LP') DEFAULT NULL COMMENT 'ML - MeuLook, MS - Mobile Stock, LP - Look Pay',
  `origem` enum('CARTAO','DEVOLUCAO') NOT NULL,
  `valor_minimo_fraude` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_colaborador` (`id_colaborador`,`origem`) USING BTREE,
  KEY `idx_colaborador` (`id_colaborador`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13946 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.comissao
CREATE TABLE IF NOT EXISTS `comissao` (
  `usuario` int(11) NOT NULL DEFAULT 0,
  `pares` int(11) NOT NULL DEFAULT 0,
  `mes` int(11) NOT NULL DEFAULT 0,
  `ano` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) NOT NULL DEFAULT '0',
  KEY `idx_comissao` (`usuario`,`pares`,`mes`,`ano`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.compras
CREATE TABLE IF NOT EXISTS `compras` (
  `id` int(11) NOT NULL DEFAULT 0,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `data_previsao` timestamp NULL DEFAULT NULL,
  `edicao_fornecedor` int(11) NOT NULL DEFAULT 0,
  `lote` varchar(10) DEFAULT NULL,
  KEY `idx_compra` (`id`,`id_fornecedor`,`situacao`,`data_previsao`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.compras_entrada_historico
CREATE TABLE IF NOT EXISTS `compras_entrada_historico` (
  `codigo_barras` varchar(24) DEFAULT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `status` int(11) DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.compras_entrada_temp
CREATE TABLE IF NOT EXISTS `compras_entrada_temp` (
  `codigo_barras` varchar(24) DEFAULT NULL,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `id_situacao` int(11) NOT NULL DEFAULT 0,
  `pares` int(11) NOT NULL DEFAULT 0,
  `preco_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compra` int(11) NOT NULL DEFAULT 0,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `volume` int(11) NOT NULL DEFAULT 0,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `baixado` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `idx_entrada` (`codigo_barras`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.compras_itens
CREATE TABLE IF NOT EXISTS `compras_itens` (
  `id_compra` int(11) NOT NULL DEFAULT 0,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `preco_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `caixas` int(11) NOT NULL DEFAULT 0,
  `quantidade_total` int(11) NOT NULL DEFAULT 0,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_situacao` int(11) NOT NULL DEFAULT 0,
  KEY `idx_compra_item` (`id_compra`,`sequencia`,`id_produto`,`caixas`,`quantidade_total`,`id_situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.compras_itens_caixas
CREATE TABLE IF NOT EXISTS `compras_itens_caixas` (
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `id_compra` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `id_sequencia` int(11) NOT NULL DEFAULT 0,
  `volume` int(11) NOT NULL DEFAULT 0,
  `codigo_barras` varchar(30) DEFAULT NULL,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `numero_mov` int(11) NOT NULL DEFAULT 0,
  `data_baixa` timestamp NULL DEFAULT NULL,
  `id_lancamento` int(11) NOT NULL DEFAULT 0,
  `situacao_pagamento` int(11) NOT NULL DEFAULT 1,
  `usuario_pagamento` int(11) NOT NULL DEFAULT 0,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  `acerto_pagamento` int(11) NOT NULL DEFAULT 0,
  KEY `idx_compras_itens_cx` (`id_compra`,`id_sequencia`,`id_produto`,`volume`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.compras_itens_grade
CREATE TABLE IF NOT EXISTS `compras_itens_grade` (
  `id_compra` int(11) NOT NULL DEFAULT 0,
  `id_sequencia` int(11) NOT NULL DEFAULT 0,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `quantidade_total` int(11) NOT NULL DEFAULT 0,
  `quantidade_devolvida` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  KEY `idx_compras_itens_grade` (`id_compra`,`id_sequencia`,`id_produto`,`nome_tamanho`,`quantidade`,`quantidade_total`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.condicao_pagamento
CREATE TABLE IF NOT EXISTS `condicao_pagamento` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(30) NOT NULL DEFAULT '0',
  `parcelas` int(11) NOT NULL DEFAULT 0,
  `dias` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.configuracoes
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `prazo_troca` int(11) NOT NULL DEFAULT 0,
  `pares_troca` int(11) NOT NULL DEFAULT 0,
  `pares_saldo` int(11) NOT NULL DEFAULT 0,
  `colaborador_caixa` int(11) NOT NULL DEFAULT 1,
  `estantes` int(11) NOT NULL DEFAULT 0,
  `verificacao_expirar_pares` date DEFAULT NULL,
  `horas_backup` int(11) NOT NULL DEFAULT 0,
  `entrada_compra_temp` int(11) NOT NULL DEFAULT 0,
  `nome_destaque` varchar(30) DEFAULT NULL,
  `tema_destaque` varchar(50) DEFAULT NULL,
  `pagina` int(11) NOT NULL DEFAULT 0,
  `limite_de_compra` int(11) NOT NULL,
  `emissao_nota_fiscal` int(11) NOT NULL DEFAULT 0,
  `metas_do_mobile` int(11) NOT NULL,
  `tipo_comissao` varchar(1) NOT NULL DEFAULT 'G' COMMENT 'G- gereal,F-fornecedor,P-produto',
  `zoop_seller` int(11) DEFAULT 0,
  `fornecedor_mobile_fisico` int(11) DEFAULT 0,
  `fornecedor_mobile_juridico` int(11) DEFAULT 0,
  `id_zoop_mobile` varchar(100) DEFAULT NULL,
  `data_auto_exclusao` datetime DEFAULT '2020-11-29 23:59:00' COMMENT 'Data/Hora que o ultimo cancelamento automático foi feito.',
  `num_dias_remover_produto_pago` char(2) NOT NULL DEFAULT '15' COMMENT 'Numero de dias para remover item pago do painal e não cobrar taxa ',
  `valor_taxa_remove_produto_pago` decimal(10,2) NOT NULL DEFAULT 2.00 COMMENT 'Valor da taxa que será cobrada quando passa o numero de dias limite para remover do painel',
  `num_dias_venc_boleto` int(11) NOT NULL DEFAULT 1,
  `valor_min_cobra_taxa_boleto` decimal(10,2) NOT NULL DEFAULT 200.00,
  `id_seller_recebe_padrao_cartao` int(5) NOT NULL DEFAULT 12 COMMENT 'Seller que vai receber pagamento integral no cartão',
  `porcetagem_pagamento_seller_regra` int(2) NOT NULL DEFAULT 50 COMMENT 'Regra para recebimento de seller no split, valorSaldo * 50%  < valor pendente, sai da fila',
  `valor_minino_estoque_receber_seller` decimal(10,2) NOT NULL DEFAULT 5000.00 COMMENT 'valor mínimo para soma valor_estoque + credito(negativo) + pendente > 5000.00',
  `num_parcela_limit_mobile` int(2) NOT NULL DEFAULT 12,
  `num_parcela_limit_meuestoque` int(2) NOT NULL DEFAULT 3,
  `valor_limit_recebe_cartao_dia` decimal(10,2) NOT NULL DEFAULT 8000.00 COMMENT 'valor limite para cair na cielo dia ',
  `segundos_expirar_expirar_link_pagamento` int(5) DEFAULT NULL,
  `dados_pagamento_padrao` varchar(250) DEFAULT '{"parcelas":"5","metodo_pagamento":"CA","desconto_vista":"10"}' COMMENT 'Numero parcel,modo_pagamento,porcentagem desconto',
  `meta_mensal_valor` decimal(10,2) DEFAULT NULL,
  `valor_max_saque` decimal(10,2) NOT NULL DEFAULT 5000.00 COMMENT 'Valor Maximo Saque por Dia',
  `valor_min_saque` decimal(10,2) NOT NULL DEFAULT 5.00 COMMENT 'Valor Mínimo Saque',
  `permite_transferencia` char(1) DEFAULT 'F' COMMENT 'Trava as transferencias do sistema',
  `taxa_adiantamento` decimal(10,2) DEFAULT 2.00,
  `valor_max_mobile_inteira_transferencia` decimal(10,2) DEFAULT 0.05,
  `qtd_webhook_processa_fila` int(11) DEFAULT 100 COMMENT 'Quantidade de webhooks retormados na fila de webhooks a serem processados',
  `id_iugu_conta_mobile_inteira` varchar(500) DEFAULT 'F21185850DA9466BA1B74755656AC1C7' COMMENT 'ID IUGU conta utilizada para inteirar transferencias',
  `qtd_vezes_produto_pode_ser_adicionado_publicacao` int(11) NOT NULL DEFAULT 3,
  `qtd_dias_disponiveis_troca_normal` int(11) NOT NULL DEFAULT 7,
  `qtd_dias_disponiveis_troca_normal_ms` int(11) NOT NULL DEFAULT 365,
  `qtd_dias_disponiveis_troca_defeito` int(11) NOT NULL DEFAULT 90,
  `qtd_dias_disponiveis_troca_defeito_ms` int(11) NOT NULL DEFAULT 180,
  `qtd_dias_aprovacao_automatica_solicitacao_troca` int(11) NOT NULL DEFAULT 7,
  `qtd_dias_necessarios_destaque_melhores_fabricantes` int(11) NOT NULL DEFAULT 60,
  `qtd_dias_impulsionar_produtos_melhores_fabricantes` int(11) NOT NULL DEFAULT 3,
  `qtd_dias_impulsionar_produtos_normal` int(11) NOT NULL DEFAULT 7 COMMENT 'Botao para atualizar data_entrada dos produtos',
  `permite_criar_look_com_qualquer_produto` char(1) NOT NULL DEFAULT 'T' COMMENT 'Permite looker criar publicacao com qualquer produto',
  `quantidade_publicacoes_looks_momento` int(11) DEFAULT 10,
  `horario_final_dia_ranking_meulook` varchar(15) DEFAULT '23:59:59',
  `quantidade_influencers_recomendados_iniciantes_meulook` int(11) DEFAULT 50,
  `quantidade_maxima_produtos_publicacoes_meu_look` int(11) DEFAULT 7,
  `id_conta_publicacoes_produtos_especiais` int(11) DEFAULT 10347,
  `id_colaborador_padrao_link` int(11) DEFAULT NULL,
  `informacoes_metodos_pagamento` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL DEFAULT '[{"prefixo": "PX","nome": "Pix","meios_pagamento": [{ "local_pagamento": "Iugu", "situacao": "ativo" },{ "local_pagamento": "Zoop", "situacao": "ativo" }]},{"prefixo": "CA","nome": "Cartão","meios_pagamento": [{ "local_pagamento": "Cielo", "situacao": "ativo" },{ "local_pagamento": "Zoop", "situacao": "ativo" },{ "local_pagamento": "Iugu", "situacao": "ativo" }]},{"prefixo": "BL","nome": "Boleto","meios_pagamento": [{ "local_pagamento": "Iugu", "situacao": "ativo" }]}]',
  `email_padrao_colaboradores` varchar(50) DEFAULT 'pagamentosdigitalize@gmail.com.br',
  `atraso_padrao_mensagem_coleta_transportadora` int(2) DEFAULT 2 COMMENT 'Apenas inteiros em horas.',
  `porcentagem_comissao_ml` decimal(4,2) DEFAULT 0.00,
  `porcentagem_comissao_ms` decimal(4,2) DEFAULT 15.00,
  `porcentagem_comissao` decimal(4,2) DEFAULT 25.00,
  `porcentagem_comissao_ponto_coleta` decimal(4,2) DEFAULT 3.00,
  `dias_para_cancelamento_automatico` int(4) NOT NULL DEFAULT 7,
  `dias_atraso_para_chegar_no_ponto` tinyint(4) NOT NULL DEFAULT 7,
  `dias_atraso_para_separacao` tinyint(4) NOT NULL DEFAULT 4,
  `dias_atraso_para_conferencia` tinyint(4) NOT NULL DEFAULT 2,
  `dias_atraso_para_entrega_ao_cliente` tinyint(4) NOT NULL DEFAULT 7,
  `dias_atraso_para_trocas_ponto` tinyint(4) NOT NULL DEFAULT 30,
  `porcentagem_comissao_freteiros_por_km` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[{"de":0,"ate":10,"porcentagem":2},{"de":11,"ate":59,"porcentagem":3},{"de":60,"ate":150,"porcentagem":4},{"de":151,"ate":999999999,"porcentagem":5}]' CHECK (json_valid(`porcentagem_comissao_freteiros_por_km`)),
  `latitude_central` decimal(20,6) NOT NULL DEFAULT -19.881038,
  `longitude_central` decimal(20,6) NOT NULL DEFAULT -44.989417,
  `valor_minimo_vendas_ponto_frete_gratis` float NOT NULL DEFAULT 10000,
  `token_rodonaves` varchar(1000) NOT NULL,
  `qtd_dias_repostar_promocao_temporaria` int(11) NOT NULL DEFAULT 3,
  `porcentagem_minima_desconto_promocao_temporaria` int(11) NOT NULL DEFAULT 30,
  `id_colaborador_tipo_frete_transportadora_meulook` int(11) NOT NULL DEFAULT 32257,
  `permite_pagamento_automatico_transferencias` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'jobInteirarPagamentoAuto.php - 0: Job não executa | 1: Job executa normalmente',
  `alerta_chat_atendimento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  `dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE` tinyint(4) NOT NULL DEFAULT 0,
  `dias_pagamento_transferencia_fornecedor_EXCELENTE` tinyint(4) NOT NULL DEFAULT 0,
  `dias_pagamento_transferencia_fornecedor_REGULAR` tinyint(4) NOT NULL DEFAULT 0,
  `dias_pagamento_transferencia_fornecedor_RUIM` tinyint(4) NOT NULL DEFAULT 0,
  `dias_pagamento_transferencia_CLIENTE` tinyint(4) NOT NULL DEFAULT 0,
  `dias_pagamento_transferencia_fornecedor_NOVATO` tinyint(4) NOT NULL DEFAULT 0,
  `dias_pagamento_transferencia_ENTREGADOR` tinyint(4) NOT NULL DEFAULT 0,
  `horarios_separacao_fulfillment` text NOT NULL DEFAULT '[]',
  `qtd_dias_aprovacao_automatica` int(11) NOT NULL DEFAULT 7,
  `porcentagem_antecipacao` decimal(4,2) DEFAULT 50.00,
  `tamanho_raio_padrao_ponto_parado` int(11) NOT NULL DEFAULT 1000,
  `percentual_para_cortar_pontos` int(11) NOT NULL DEFAULT 30,
  `minimo_entregas_para_cortar_pontos` int(11) NOT NULL DEFAULT 4,
  `valor_minimo_fraude` decimal(10,2) NOT NULL DEFAULT 300.00,
  `filtros_pesquisa_padrao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[{"id":"LANCAMENTO","nome":"Lançamentos"},{"id":"PROMOCAO","nome":"Promoções"},{"id":"MELHOR_FABRICANTE","nome":"Melhores Fabricantes"},{"id":"MENOR_PRECO","nome":"Menor Preço"}]' COMMENT 'Os filtros de ordenamento de catálogo comuns em todos os sites da plataforma' CHECK (json_valid(`filtros_pesquisa_padrao`)),
  `filtros_pesquisa_ordenados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT 'Os filtros de ordenamento ordenados de acordo com a necessidade da plataforma' CHECK (json_valid(`filtros_pesquisa_ordenados`)),
  `minutos_expiracao_cache_filtros` int(11) NOT NULL DEFAULT 10 COMMENT 'Tempo de expiração do cache em MINUTOS',
  `produtos_promocoes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"HORAS_DURACAO_PROMOCAO_TEMPORARIA":24,"HORAS_ESPERA_REATIVAR_PROMOCAO":72,"PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA":30}' COMMENT 'HORAS_DURACAO_PROMOCAO_TEMPORARIA: Tempo em horas para a promoção temporária expirar, HORAS_ESPERA_REATIVAR_PROMOCAO: Tempo em horas para reativar uma promoção que foi desativada, PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA: Porcentagem minima de desconto para entrar em promoção temporária' CHECK (json_valid(`produtos_promocoes`)),
  `logistica_reversa` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"cancelamento":{"taxa_minima_bloqueio_fornecedor":30},"devolucao":{"taxa_produto_errado":10}}' COMMENT 'cancelamento:\r\n  taxa_minima_bloqueio_fornecedor: taxa com porcentagem minima para bloqueio do fornecedor\r\ndevolucao:\r\n  taxa_produto_errado: Taxa com valor fixo para cobrar do fornecedor para cada devolução de produto que foi enviado errado',
  `permite_monitoramento_sentry` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'permite o monitoramento com o sentry'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.contas_bancarias
CREATE TABLE IF NOT EXISTS `contas_bancarias` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.conta_bancaria_colaboradores
CREATE TABLE IF NOT EXISTS `conta_bancaria_colaboradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `conta` varchar(50) NOT NULL,
  `agencia` varchar(20) NOT NULL,
  `id_banco` int(11) NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `cpf_titular` varchar(15) NOT NULL,
  `nome_titular` varchar(100) NOT NULL,
  `prioridade` varchar(2) NOT NULL DEFAULT 'S' COMMENT 'S-SECUNDARIA P-PRINCIPAL',
  `token_zoop` varchar(45) DEFAULT NULL,
  `tipo` varchar(45) DEFAULT NULL,
  `id_iugu` varchar(500) NOT NULL,
  `iugu_token_live` varchar(500) NOT NULL,
  `iugu_token_user` varchar(500) NOT NULL,
  `iugu_token_teste` varchar(500) NOT NULL,
  `phone` varchar(18) DEFAULT NULL,
  `conta_iugu_verificada` char(1) NOT NULL DEFAULT 'F',
  `pagamento_bloqueado` char(1) NOT NULL DEFAULT 'F',
  PRIMARY KEY (`id`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_banco` (`id_banco`)
) ENGINE=InnoDB AUTO_INCREMENT=4816 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.correios_atendimento
CREATE TABLE IF NOT EXISTS `correios_atendimento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `id_atendimento` int(11) NOT NULL DEFAULT 0,
  `numeroColeta` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT ' PAC Reverso',
  `prazo` date DEFAULT NULL,
  `idObjeto` varchar(60) DEFAULT NULL,
  `statusObjeto` varchar(45) DEFAULT '55' COMMENT '55 - Aguardando Postagem na Agencia;\r\n6 - Postado;\r\n57 - Prazo Expirado;\r\n68 - Cancelado;',
  `data_verificacao` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` char(2) DEFAULT 'A' COMMENT 'A - Aguardando Objeto na Agencia;\r\nP - Postado;\r\nC - Cancelado;\r\nE - Expirado;',
  PRIMARY KEY (`id`),
  KEY `idx_correio` (`id_cliente`,`numeroColeta`,`id_atendimento`,`prazo`)
) ENGINE=InnoDB AUTO_INCREMENT=1038 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.credito
CREATE TABLE IF NOT EXISTS `credito` (
  `id_pedido` int(11) NOT NULL DEFAULT 0,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `data_pagamento` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_lancamento` int(11) NOT NULL DEFAULT 0,
  `tipo_tabela` int(11) NOT NULL DEFAULT 0,
  `id_acerto` int(11) NOT NULL DEFAULT 0,
  `pedido_destino` int(11) NOT NULL DEFAULT 0,
  KEY `idx_credito` (`id_pedido`,`id_cliente`,`data_emissao`,`situacao`,`data_pagamento`,`valor`,`id_lancamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.cria_localizacao
DELIMITER //
CREATE PROCEDURE `cria_localizacao`(IN `valor` SMALLINT(255))
    NO SQL
BEGIN
	set valor = 1000;
	WHILE (valor<=4000)
    DO
    INSERT INTO localizacao_estoque (tipo,local) VALUES ('O',CONCAT('O',valor));
SET valor = valor+1;
            END WHILE;
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.cria_publicacao_para_cada_foto
DELIMITER //
CREATE PROCEDURE `cria_publicacao_para_cada_foto`()
BEGIN
	DECLARE ID_COLABORADOR_ INT DEFAULT 0;
	DECLARE ID_PRODUTO_ INT DEFAULT 0;
	DECLARE CAMINHO_ VARCHAR(500) DEFAULT '';
	DECLARE DATA_CRIACAO_ TIMESTAMP DEFAULT NOW();

	DECLARE FIM_ INTEGER DEFAULT FALSE;
	DEClARE CURSOR_FOTOS_ CURSOR FOR SELECT
													colaboradores.id,
													produtos_foto.id id_produto,
													produtos_foto.caminho,
													produtos_foto.data_hora
												FROM produtos_foto
												INNER JOIN usuarios ON usuarios.id = produtos_foto.id_usuario
												INNER JOIN colaboradores ON colaboradores.id = usuarios.id_colaborador
												INNER JOIN estoque_grade ON estoque_grade.id_produto = produtos_foto.id
												WHERE produtos_foto.tipo_foto <> 'SM'
												GROUP BY produtos_foto.id
												ORDER BY estoque_grade.estoque > 0 ASC, produtos_foto.data_hora ASC;

	DECLARE CONTINUE HANDLER FOR NOT FOUND SET FIM_ = 1;

	OPEN CURSOR_FOTOS_;

	CARREGA_: LOOP
	FETCH CURSOR_FOTOS_ INTO ID_COLABORADOR_,ID_PRODUTO_,CAMINHO_,DATA_CRIACAO_;
	IF FIM_ THEN
		LEAVE CARREGA_;
	END IF;

		INSERT INTO publicacoes (publicacoes.id_colaborador, publicacoes.foto, publicacoes.tipo_publicacao, publicacoes.data_criacao)
		SELECT ID_COLABORADOR_, CAMINHO_, 'AU', DATA_CRIACAO_ FROM DUAL
		WHERE NOT EXISTS(SELECT 1 FROM publicacoes WHERE publicacoes.id_colaborador = ID_COLABORADOR_ AND publicacoes.foto = CAMINHO_ AND publicacoes.tipo_publicacao = 'AU');

		IF(ROW_COUNT() > 0) THEN
			INSERT INTO publicacoes_produtos (publicacoes_produtos.id_publicacao, publicacoes_produtos.id_produto)
			SELECT LAST_INSERT_ID(), ID_PRODUTO_;
		END IF;

	END LOOP;
	CLOSE CURSOR_FOTOS_;
END//
DELIMITER ;

-- Dumping structure for function mobile_stock.DATEADD_DIAS_UTEIS
DELIMITER //
CREATE FUNCTION `DATEADD_DIAS_UTEIS`(DIAS_ADICIONAIS INT,
                                    DATA_INICIAL DATE
								) RETURNS date
BEGIN
    DECLARE _DIAS_UTEIS_ADICIONADOS INT DEFAULT 0;
    DECLARE _PROXIMA_DATA DATE;

    SET _PROXIMA_DATA = DATA_INICIAL;

	WHILE _DIAS_UTEIS_ADICIONADOS < DIAS_ADICIONAIS DO
        SET _PROXIMA_DATA = DATE_ADD(_PROXIMA_DATA, INTERVAL 1 DAY);
        IF VERIFICA_DIA_UTIL(_PROXIMA_DATA) THEN
            SET _DIAS_UTEIS_ADICIONADOS = _DIAS_UTEIS_ADICIONADOS + 1;
        END IF;
    END WHILE;

    RETURN DATE(_PROXIMA_DATA);
END//
DELIMITER ;

-- Dumping structure for function mobile_stock.DATEDIFF_DIAS_UTEIS
DELIMITER //
CREATE FUNCTION `DATEDIFF_DIAS_UTEIS`(DATA_FINAL DATE, DATA_INICIAL DATE) RETURNS int(11)
    DETERMINISTIC
    COMMENT 'Retorna os dias úteis entre duas datas.'
BEGIN
	DECLARE _DIAS_REAIS INT DEFAULT DATEDIFF(DATA_FINAL, DATA_INICIAL);
	DECLARE _DIA INT DEFAULT 0;
	DECLARE _DATA_ATUAL_LOOP DATE DEFAULT DATA_INICIAL;
	DECLARE _ITERACOES INT DEFAULT _DIAS_REAIS;

	WHILE _DIA <= _ITERACOES DO
		IF (NOT VERIFICA_DIA_UTIL(_DATA_ATUAL_LOOP)) THEN
			IF(_DATA_ATUAL_LOOP <> DATA_INICIAL) THEN
				SET _DIAS_REAIS = _DIAS_REAIS - 1;
			END IF;
		END IF;
		SET _DATA_ATUAL_LOOP = DATE_ADD(_DATA_ATUAL_LOOP, INTERVAL 1 DAY);
		SET _DIA = _DIA + 1;
	END WHILE;

	IF (_DIAS_REAIS < 0) THEN
		RETURN 0;
	END IF;

	RETURN _DIAS_REAIS;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.dias_nao_trabalhados
CREATE TABLE IF NOT EXISTS `dias_nao_trabalhados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for function mobile_stock.distancia_geolocalizacao
DELIMITER //
CREATE FUNCTION `distancia_geolocalizacao`(`lat1` DOUBLE,
	`lon1` DOUBLE,
	`lat2` DOUBLE,
	`lon2` DOUBLE
) RETURNS double
    COMMENT 'Retorna distância em metro entre duas coordenadas geográficas.'
BEGIN
  DECLARE dist    DOUBLE;
  DECLARE latDist DOUBLE;
  DECLARE lonDist DOUBLE;
  DECLARE a,c,r   DOUBLE;

  # Raio da terra
  SET r = 6371;

  # Fórmula Haversine <http://en.wikipedia.org/wiki/Haversine_formula>
  SET latDist = RADIANS( lat2 - lat1 );
  SET lonDist = RADIANS( lon2 - lon1 );
  SET a = POW( SIN( latDist/2 ), 2 ) + COS( RADIANS( lat1 ) ) * COS( RADIANS( lat2 ) ) * POW( SIN( lonDist / 2 ), 2 );
  SET c = 2 * ATAN2( SQRT( a ), SQRT( 1 - a ) );
  SET dist = r * c;

  RETURN dist;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.documentos
CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0',
  `desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `exibir` int(11) NOT NULL DEFAULT 1,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.emprestimo
CREATE TABLE IF NOT EXISTS `emprestimo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_favorecido` int(11) NOT NULL DEFAULT 0,
  `id_lancamento` int(11) DEFAULT NULL,
  `id_conta_bancaria_favorecida` int(11) NOT NULL,
  `valor_capital` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `taxa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `situacao` char(2) NOT NULL DEFAULT 'PE',
  `data_inicio` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `emprestimo_atualiza_lancamento` (`id_favorecido`,`situacao`)
) ENGINE=InnoDB AUTO_INCREMENT=1186 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.emprestimo_taxa
DELIMITER //
CREATE PROCEDURE `emprestimo_taxa`()
BEGIN
	DECLARE done boolean DEFAULT 0;
	DECLARE ID_COLABORADOR_ INT DEFAULT 0;
	DECLARE ID_ INT DEFAULT 0;
	DECLARE TOTAL_ decimal(10,2) DEFAULT 0.00;
	DECLARE CAPITAL_ decimal(10,2) DEFAULT 0.00;
	DECLARE TAXA_MENSAL_ decimal(10,2) DEFAULT 0.00;
	DECLARE ID_LANCAMENTO_ INT DEFAULT 0;

	DECLARE cur1 cursor FOR SELECT emprestimo.id,emprestimo.id_favorecido,emprestimo.id_lancamento,emprestimo.valor_capital,emprestimo.taxa FROM emprestimo WHERE emprestimo.situacao = "PE" ;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
		DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem)
		VALUES(1,NOW(),'Ocorreu um erro',CONCAT('Erro ao executar procedure emprestimo_taxa',ID_COLABORADOR_),'Z');
	END;

   OPEN cur1;
   readLoop: LOOP
   FETCH cur1 INTO ID_, ID_COLABORADOR_, ID_LANCAMENTO_, CAPITAL_, TAXA_MENSAL_;
      IF done=1 THEN
        LEAVE readLoop;
      END IF;
 	SELECT ROUND(((TAXA_MENSAL_/30)/100) *CAPITAL_,2) INTO	TOTAL_;
	INSERT INTO lancamento_financeiro (sequencia,tipo,documento,situacao,origem,id_colaborador,valor,valor_total,id_usuario,observacao,id_lancamento_adiantamento)
	VALUES (1,'R',15,1,'JA',ID_COLABORADOR_,TOTAL_,TOTAL_,1,'Juros cobrados na antecipaçÃo', ID_LANCAMENTO_);
	 END LOOP readLoop;
  CLOSE cur1;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.entregas
CREATE TABLE IF NOT EXISTS `entregas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_tipo_frete` int(11) NOT NULL,
  `id_transporte` int(11) NOT NULL DEFAULT 0,
  `id_cidade` int(11) NOT NULL DEFAULT 0,
  `situacao` enum('AB','EX','PT','EN') NOT NULL DEFAULT 'AB' COMMENT 'AB-Aberta, EX-Expedicao, PT-Ponto Transporte, EN-entregue',
  `volumes` int(2) NOT NULL DEFAULT 1,
  `uuid_entrega` varchar(100) NOT NULL DEFAULT uuid(),
  `data_entrega` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `FK_entrega_cliente` (`id_cliente`),
  KEY `index_tipo_frete` (`id_tipo_frete`),
  CONSTRAINT `FK_entrega_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `colaboradores` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49605 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_devolucoes_item
CREATE TABLE IF NOT EXISTS `entregas_devolucoes_item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_entrega` int(11) unsigned NOT NULL,
  `id_transacao` int(11) unsigned NOT NULL DEFAULT 0,
  `id_ponto_responsavel` int(11) NOT NULL DEFAULT 0 COMMENT 'id do tipo de frete',
  `id_usuario` int(11) NOT NULL DEFAULT 0 COMMENT 'ID do usuário que bipou a devolução',
  `id_cliente` int(11) GENERATED ALWAYS AS (cast(substring_index(`uuid_produto`,'_',1) as signed)) VIRTUAL,
  `id_produto` int(11) unsigned NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `uuid_produto` varchar(50) NOT NULL DEFAULT '0' COMMENT 'uuid faturamento item',
  `situacao` enum('PE','CO','RE','VE','PR') NOT NULL DEFAULT 'PE' COMMENT 'PE = Pendente, CO = Confirmado, RE = Rejeitado, VE = Vendido, PR = Produto Retirado',
  `tipo` enum('NO','DE') NOT NULL DEFAULT 'NO' COMMENT 'NO = Normal, DE = Defeito',
  `situacao_envio` enum('NO','AU') NOT NULL DEFAULT 'NO' COMMENT 'NO = Normal, AU = Ausente',
  `origem` enum('ML','MS') NOT NULL DEFAULT 'ML' COMMENT 'ML = Meu Look, MS = Mobile Stock',
  `pac_reverso` varchar(50) DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_responsavel_estoque` int(11) NOT NULL DEFAULT 1,
  UNIQUE KEY `trava_uuid` (`uuid_produto`),
  KEY `ID` (`id`),
  KEY `ponto_responsavel` (`id_ponto_responsavel`),
  KEY `IDX_produto` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=65880 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_etiquetas
CREATE TABLE IF NOT EXISTS `entregas_etiquetas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_entrega` int(11) unsigned NOT NULL,
  `volume` int(11) NOT NULL DEFAULT 0,
  `uuid_volume` varchar(100) NOT NULL DEFAULT uuid(),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_ee_entregas` (`id_entrega`),
  CONSTRAINT `FK_ee_entregas` FOREIGN KEY (`id_entrega`) REFERENCES `entregas` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74024 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_faturamento_item
CREATE TABLE IF NOT EXISTS `entregas_faturamento_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_entrega` int(10) unsigned NOT NULL,
  `id_transacao` int(11) unsigned NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `situacao` enum('PE','AR','EN') NOT NULL DEFAULT 'PE' COMMENT 'PE - Pendente, AR - Aguardando Retirada, EN - Entregue',
  `origem` enum('MS','ML') NOT NULL,
  `uuid_produto` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `id_produto` int(11) unsigned NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `nome_recebedor` varchar(150) DEFAULT '',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_entrega` timestamp NULL DEFAULT NULL,
  `data_base_troca` timestamp NULL DEFAULT NULL,
  `id_responsavel_estoque` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `FK_e_efi` (`id_entrega`),
  KEY `FK_tf_efi` (`id_transacao`),
  KEY `cliente` (`id_cliente`),
  KEY `idx_uuid_produto` (`uuid_produto`) USING BTREE,
  KEY `IDX_responsavel_estoque` (`id_responsavel_estoque`),
  KEY `idx_situcao` (`situacao`),
  CONSTRAINT `FK_e_efi` FOREIGN KEY (`id_entrega`) REFERENCES `entregas` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `FK_tf_efi` FOREIGN KEY (`id_transacao`) REFERENCES `transacao_financeiras` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=1207492 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_fechadas_temp
CREATE TABLE IF NOT EXISTS `entregas_fechadas_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_entrega` int(10) unsigned NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_tipo_frete_grupos` int(11) DEFAULT NULL COMMENT 'Este campo referencia o ID da tabela tipo_frete_grupos, para que seja exibido dentro do aplicativo as entregas de maneira agrupada',
  `entrega_manipulada` tinyint(1) NOT NULL DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id_entrega` (`id_entrega`),
  CONSTRAINT `fk_entregas_fechadas_temp_entregas` FOREIGN KEY (`id_entrega`) REFERENCES `entregas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4719 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_fila_processo_alterar_entregador
CREATE TABLE IF NOT EXISTS `entregas_fila_processo_alterar_entregador` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(100) NOT NULL,
  `id_colaborador_tipo_frete` int(11) NOT NULL,
  `situacao` enum('PE','PR') NOT NULL DEFAULT 'PE' COMMENT 'PE - Pendente PR - Processado',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=20883 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_logs
CREATE TABLE IF NOT EXISTS `entregas_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_entrega` int(11) unsigned NOT NULL,
  `mensagem` text NOT NULL,
  `situacao_anterior` char(2) DEFAULT NULL,
  `situacao_nova` char(2) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `IDX_entregas_logs_id_entrega` (`id_entrega`)
) ENGINE=InnoDB AUTO_INCREMENT=475641 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_log_devolucoes_item
CREATE TABLE IF NOT EXISTS `entregas_log_devolucoes_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `mensagem` longtext NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38490 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_log_faturamento_item
CREATE TABLE IF NOT EXISTS `entregas_log_faturamento_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `id_entregas_fi` int(11) NOT NULL,
  `mensagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mensagem`)),
  `situacao_anterior` varchar(2) DEFAULT NULL,
  `situacao_nova` varchar(2) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  KEY `id` (`id`),
  KEY `log_entregas_FI` (`id_entregas_fi`),
  CONSTRAINT `log_entregas_FI` FOREIGN KEY (`id_entregas_fi`) REFERENCES `entregas_faturamento_item` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3554282 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='tabela de log entregas faturamento item';

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.entregas_transportadoras
CREATE TABLE IF NOT EXISTS `entregas_transportadoras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_entrega` int(10) unsigned NOT NULL,
  `id_transportadora` int(11) DEFAULT NULL,
  `cnpj` varchar(50) DEFAULT NULL,
  `nota_fiscal` int(11) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `FK_entregas_transportadoras_entregas` (`id_entrega`),
  CONSTRAINT `FK_entregas_transportadoras_entregas` FOREIGN KEY (`id_entrega`) REFERENCES `entregas` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.estados
CREATE TABLE IF NOT EXISTS `estados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigouf` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `uf` char(2) NOT NULL,
  `regiao` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.estados_ddd
CREATE TABLE IF NOT EXISTS `estados_ddd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ddd` int(11) NOT NULL,
  `uf` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.estatistica_indicacao
CREATE TABLE IF NOT EXISTS `estatistica_indicacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `dado_coletado` varchar(100) NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=855 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.estoque_grade
CREATE TABLE IF NOT EXISTS `estoque_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `id_responsavel` int(11) NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 13,
  `estoque` int(11) NOT NULL DEFAULT 0,
  `vendido` int(11) NOT NULL DEFAULT 0,
  `tipo_movimentacao` varchar(1) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT 'S-Saida E-Entrada X-Exclusao M-Movimentacao N-Entrada Vendido C-Correção Manual I-Inserindo Grade R-Removendo Grade',
  `descricao` longtext CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uidx_produto` (`id_produto`,`id_responsavel`,`nome_tamanho`),
  KEY `idx_produto_responsavel_tamanho` (`id_produto`,`id_responsavel`,`nome_tamanho`),
  KEY `idx_produto_tamanho_estoque` (`id_produto`,`nome_tamanho`,`estoque`)
) ENGINE=InnoDB AUTO_INCREMENT=427391 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.estoque_grade_valor_dia
CREATE TABLE IF NOT EXISTS `estoque_grade_valor_dia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `valor_estoque` float(12,2) NOT NULL DEFAULT 0.00,
  `qtd_estoque` int(11) NOT NULL DEFAULT 0,
  `qtd_vendido` int(11) NOT NULL DEFAULT 0,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4105661 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for event mobile_stock.evento_atualizacao_pedido_item
DELIMITER //
CREATE EVENT `evento_atualizacao_pedido_item` ON SCHEDULE EVERY 60 SECOND STARTS '2020-06-04 16:15:00' ON COMPLETION PRESERVE DISABLE DO BEGIN

	CALL atualiza_pedido_item();
END//
DELIMITER ;

-- Dumping structure for event mobile_stock.evento_diario_noite
DELIMITER //
CREATE EVENT `evento_diario_noite` ON SCHEDULE EVERY 1 DAY STARTS '2021-04-20 00:10:00' ON COMPLETION PRESERVE ENABLE DO BEGIN
  CALL emprestimo_taxa();
  CALL limpa_senhas_temporarias();
END//
DELIMITER ;

-- Dumping structure for event mobile_stock.evento_dispara_alerta_meta_mensal
DELIMITER //
CREATE EVENT `evento_dispara_alerta_meta_mensal` ON SCHEDULE EVERY '0-1' YEAR_MONTH STARTS '2021-06-25 03:00:00' ON COMPLETION PRESERVE ENABLE DO BEGIN
	DECLARE done boolean DEFAULT 0;
	DECLARE IDCLIENTE int default 0;
	DECLARE _VALOR DECIMAL(10,2) default 0;
	DECLARE cur1 cursor for SELECT id_cliente, SUM(valor)valor FROM metas WHERE YEAR(data_meta) = YEAR(NOW()) AND MONTH(data_meta) = MONTH(NOW()) AND gerado=0 AND valor < (SELECT meta_mensal_valor FROM configuracoes) GROUP BY id_cliente order by id desc;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN cur1;
	readLoop: LOOP
	FETCH cur1 INTO IDCLIENTE, _VALOR;
		IF done=1 THEN
		  LEAVE readLoop;
		END IF;
		SET @RESTO = (SELECT meta_mensal_valor FROM configuracoes) - _VALOR;
		IF (@RESTO < 1000) THEN
			INSERT INTO notificacoes (id_cliente, data_evento, mensagem, tipo_mensagem, icon)
			VALUES (IDCLIENTE, NOW(), CONCAT("Olá. Falta R$ ",@RESTO," para você completar sua meta mensal. Compre prdutos e atinja a meta mensal para receber um cashback de R$ 20,00."),"C",10);
		END IF;
	END LOOP readLoop;
	CLOSE cur1;
END//
DELIMITER ;

-- Dumping structure for event mobile_stock.evento_hora_em_hora
DELIMITER //
CREATE EVENT `evento_hora_em_hora` ON SCHEDULE EVERY 1 HOUR STARTS '2022-11-04 18:00:00' ON COMPLETION PRESERVE ENABLE DO BEGIN
	CALL processo_cria_foguinho();
END//
DELIMITER ;

-- Dumping structure for event mobile_stock.evento_limpar_produtos_fora_de_linha
DELIMITER //
CREATE EVENT `evento_limpar_produtos_fora_de_linha` ON SCHEDULE EVERY 1 DAY STARTS '2021-07-14 04:00:00' ON COMPLETION NOT PRESERVE ENABLE DO call limpar_produtos_fora_de_linha()//
DELIMITER ;

-- Dumping structure for event mobile_stock.evento_pagamento_lancamento
DELIMITER //
CREATE EVENT `evento_pagamento_lancamento` ON SCHEDULE EVERY 10 MINUTE STARTS '2021-01-28 18:47:50' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
	CALL lancamento_financeiro_abate();
	CALL atualiza_lancamento('0');

	DELETE FROM transacao_financeiras
	WHERE
		transacao_financeiras.status IN ('CR', 'LK') AND
		TIMESTAMPDIFF(MINUTE, transacao_financeiras.data_criacao, NOW()) >= 57;
END//
DELIMITER ;

-- Dumping structure for event mobile_stock.evento_valor_estoque
DELIMITER //
CREATE EVENT `evento_valor_estoque` ON SCHEDULE EVERY 3 HOUR STARTS '2021-04-12 14:30:10' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;
		INSERT INTO notificacoes (id_cliente, data_evento, mensagem, tipo_mensagem)
		VALUES(1,NOW(),CONCAT('Erro no processo automatico de gerar valor e quantidade estoque por dia'),'Z');
	END;

	START TRANSACTION;
	DELETE FROM estoque_grade_valor_dia;

	INSERT INTO estoque_grade_valor_dia(id_fornecedor,valor_estoque,qtd_estoque,qtd_vendido)
	SELECT produtos.id_fornecedor,
        SUM((estoque_grade.estoque) * produtos.valor_custo_produto),
        SUM(estoque_grade.estoque),
        SUM(estoque_grade.vendido)
	FROM estoque_grade
        INNER JOIN produtos ON produtos.id =  estoque_grade.id_produto
	WHERE estoque_grade.id_responsavel = 1
	GROUP BY produtos.id_fornecedor;
	COMMIT;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.extrato_saldo_dia
CREATE TABLE IF NOT EXISTS `extrato_saldo_dia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `extrato` varchar(1000) NOT NULL DEFAULT '',
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2200 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.faq
CREATE TABLE IF NOT EXISTS `faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario_responde` int(11) DEFAULT NULL,
  `pergunta` varchar(1000) DEFAULT NULL,
  `resposta` varchar(1000) DEFAULT NULL,
  `data_pergunta` timestamp NULL DEFAULT current_timestamp(),
  `data_resposta` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `tipo` varchar(3) DEFAULT 'MP' COMMENT 'MP - Mobile Pay',
  `frequencia` int(11) DEFAULT 1,
  `id_produto` int(11) DEFAULT 0,
  `id_fornecedor` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5477 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.faturamento_item
CREATE TABLE IF NOT EXISTS `faturamento_item` (
  `id_faturamento` int(11) NOT NULL DEFAULT 0,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `tipo_cobranca` int(11) NOT NULL DEFAULT 0,
  `id_tabela` int(11) NOT NULL DEFAULT 0,
  `id_vendedor` int(11) NOT NULL DEFAULT 0,
  `baixa_comissao_vendedor` int(11) NOT NULL DEFAULT 0,
  `id_separador` int(11) NOT NULL DEFAULT 0,
  `baixa_comissao_separador` int(11) NOT NULL DEFAULT 0,
  `id_conferidor` int(11) NOT NULL DEFAULT 0,
  `baixa_comissao_conferidor` int(11) NOT NULL DEFAULT 0,
  `preco` decimal(7,2) NOT NULL DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `acrescimo` decimal(10,2) DEFAULT 0.00,
  `valor_total` decimal(10,2) DEFAULT 0.00,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `defeito` int(11) NOT NULL DEFAULT 0,
  `data_hora` timestamp NULL DEFAULT NULL,
  `data_separacao` timestamp NULL DEFAULT NULL,
  `data_conferencia` timestamp NULL DEFAULT NULL,
  `cod_barras` varchar(30) DEFAULT NULL,
  `uuid` varchar(80) DEFAULT NULL,
  `troca_pendente` int(11) NOT NULL DEFAULT 0,
  `separado` int(11) NOT NULL DEFAULT 0,
  `conferido` int(11) NOT NULL DEFAULT 0,
  `expedido` int(11) NOT NULL DEFAULT 0,
  `entregue` int(11) NOT NULL DEFAULT 0,
  `id_repositor` int(11) NOT NULL DEFAULT 0,
  `pedido_cliente` int(11) NOT NULL DEFAULT 0,
  `cliente` varchar(100) DEFAULT '',
  `venda_balcao` int(11) NOT NULL DEFAULT 0,
  `data_garantido` timestamp NULL DEFAULT NULL,
  `id_garantido` int(11) NOT NULL DEFAULT 0,
  `garantido_pago` int(11) NOT NULL DEFAULT 0,
  `premio` int(11) NOT NULL DEFAULT 0,
  `id_fornecedor` int(11) DEFAULT 0,
  `comissao_fornecedor` decimal(10,2) DEFAULT 0.00,
  `nota_comissao_fornec` varchar(1) NOT NULL DEFAULT 'P' COMMENT 'P-pendente,E-emitida',
  `data_nota_comissao` timestamp NULL DEFAULT NULL,
  `valor_custo_produto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `lote` varchar(10) DEFAULT NULL,
  `lote_compras_produto` int(11) NOT NULL DEFAULT 0 COMMENT 'Id da compra que pertence o produto',
  `id_responsavel_estoque` int(11) NOT NULL DEFAULT 1,
  KEY `idx_id_produto` (`id_produto`),
  KEY `idx_faturamento_item01` (`uuid`),
  KEY `idx_id_cliente` (`id_cliente`,`id_faturamento`) USING BTREE,
  KEY `idx_faturamento_item` (`id_faturamento`,`id_cliente`,`id_produto`,`sequencia`,`nome_tamanho`,`tipo_cobranca`,`id_tabela`,`id_vendedor`,`id_separador`,`id_conferidor`,`situacao`,`separado`,`conferido`,`expedido`,`entregue`,`uuid`,`data_hora`,`id_fornecedor`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.faturamento_item_split
CREATE TABLE IF NOT EXISTS `faturamento_item_split` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_faturamento` int(11) NOT NULL DEFAULT 0,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `fornecedor_original` int(11) DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `tamanho` int(11) NOT NULL DEFAULT 0,
  `custo_fornecedor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_venda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `comissao` decimal(10,2) NOT NULL DEFAULT 0.00,
  `uuid` varchar(36) DEFAULT NULL,
  `consumo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `consumido` tinyint(1) NOT NULL DEFAULT 0,
  `data_venda` timestamp NULL DEFAULT NULL,
  `taxa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_split` varchar(100) DEFAULT NULL,
  `data_emissao_nota` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_faturamento_item_split` (`id_faturamento`,`id_fornecedor`,`tamanho`,`custo_fornecedor`,`valor_venda`,`comissao`,`uuid`,`consumo`,`consumido`,`data_venda`,`id_produto`,`taxa`,`fornecedor_original`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.faturamento_split
CREATE TABLE IF NOT EXISTS `faturamento_split` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `fornecedor_original` int(11) NOT NULL DEFAULT 0,
  `id_zoop` varchar(100) DEFAULT NULL,
  `pares` int(11) NOT NULL DEFAULT 0,
  `custo` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_faturamento` int(11) NOT NULL DEFAULT 0,
  `id_split` varchar(100) DEFAULT NULL,
  `valor_acrescimo` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_faturamento_split` (`id_fornecedor`,`fornecedor_original`,`id_zoop`,`pares`,`custo`,`valor_total`,`id_faturamento`)
) ENGINE=InnoDB AUTO_INCREMENT=32417 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.fila_processo_webhook
CREATE TABLE IF NOT EXISTS `fila_processo_webhook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cod_transacao` varchar(1000) NOT NULL,
  `processado` char(2) NOT NULL DEFAULT 'F',
  `situacao` varchar(50) NOT NULL,
  `requisicao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unico_id_transacao_situacao` (`cod_transacao`,`situacao`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=486218 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.fila_respostas
CREATE TABLE IF NOT EXISTS `fila_respostas` (
  `id_fila` char(36) NOT NULL,
  `resposta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`resposta`)),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  KEY `idx_id_fila` (`id_fila`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.fila_transferencia_automatica
CREATE TABLE IF NOT EXISTS `fila_transferencia_automatica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_transferencia` int(11) NOT NULL,
  `valor_pagamento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `origem` char(20) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UIDX_id_transferencia` (`id_transferencia`)
) ENGINE=InnoDB AUTO_INCREMENT=12312 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.frete
CREATE TABLE IF NOT EXISTS `frete` (
  `fk_tabela` int(11) NOT NULL DEFAULT 0,
  `uf` varchar(2) NOT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`fk_tabela`,`uf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.freteiro
CREATE TABLE IF NOT EXISTS `freteiro` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.frete_estado
CREATE TABLE IF NOT EXISTS `frete_estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estado` varchar(2) NOT NULL,
  `valor_frete` decimal(5,2) NOT NULL,
  `valor_adicional` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.geolocalizacao_bipagem
CREATE TABLE IF NOT EXISTS `geolocalizacao_bipagem` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL DEFAULT 0,
  `latitude` double NOT NULL DEFAULT 0,
  `longitude` double NOT NULL DEFAULT 0,
  `motivo` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2187 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.gera_lancamento_cashback
DELIMITER //
CREATE PROCEDURE `gera_lancamento_cashback`(
	IN `_TIPO` varchar(2),
	IN `_IDCLIENTE` int
)
BEGIN
	IF _TIPO='MT' THEN
		INSERT INTO lancamento_financeiro (tipo, situacao, origem, id_colaborador, data_vencimento, valor, id_usuario, documento, documento_pagamento, observacao)
        VALUES ('P', 1, 'CH', _IDCLIENTE, NOW(), 20.00, 1, 12, 12, 'Crédito gerado por ter atingido a meta mensal');
	END IF;
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.gera_tabela_notas_comissao
DELIMITER //
CREATE PROCEDURE `gera_tabela_notas_comissao`(
	IN `DATA_` DATE
)
BEGIN
DECLARE VALOR_BOLETO_ FLOAT(10,2) DEFAULT COALESCE((SELECT
																			SUM(lancamentos_financeiros_recebiveis.valor_pago)
																		FROM transacao_financeiras
																			INNER JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_transacao = transacao_financeiras.id
																		WHERE transacao_financeiras.status = 'PA'
																			AND DATE(transacao_financeiras.data_atualizacao) = DATA_
																			AND transacao_financeiras.metodo_pagamento IN ('BL','PX')
																			AND lancamentos_financeiros_recebiveis.id_recebedor = 1
																		),0);

DECLARE VALOR_TOTAL_ FLOAT(10,2) DEFAULT COALESCE((SELECT SUM(transacao_financeiras_produtos_itens.comissao_fornecedor)
																					FROM transacao_financeiras
																						INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
																																							AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
																						INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
																					WHERE transacao_financeiras.status = 'PA'
																						AND date(transacao_financeiras.data_atualizacao) = DATA_
																						AND transacao_financeiras.metodo_pagamento IN ('BL','PX')),0);

DELETE FROM tabela_notas_comissao;


	IF(VALOR_BOLETO_ > 0)THEN

		INSERT INTO tabela_notas_comissao (nome, id_colaborador, qtd_pares, valor_fornecedor, porcentagem, valor_nota, tipo, data)
			SELECT
			COALESCE((SELECT CONCAT(api_colaboradores.first_name,' ',api_colaboradores.last_name)
						 FROM api_colaboradores
						 WHERE api_colaboradores.id_colaborador = colaboradores.id LIMIT 1),colaboradores.razao_social),
			colaboradores.id,
			0 qtd,
			SUM(lancamentos_financeiros_recebiveis.valor_pago) valor_fornecedor,
			ROUND((SUM(lancamentos_financeiros_recebiveis.valor_pago) / VALOR_TOTAL_)*100,4),
			ROUND(VALOR_BOLETO_  * (SUM(lancamentos_financeiros_recebiveis.valor_pago) / VALOR_TOTAL_),2),
			'Boleto',
			DATA_
		FROM transacao_financeiras
				INNER JOIN lancamento_financeiro ON lancamento_financeiro.transacao_origem = transacao_financeiras.id
				INNER JOIN lancamentos_financeiros_recebiveis ON lancamentos_financeiros_recebiveis.id_lancamento = lancamento_financeiro.id
				INNER JOIN colaboradores ON colaboradores.id = lancamentos_financeiros_recebiveis.id_recebedor
				INNER JOIN api_colaboradores ON api_colaboradores.id_colaborador = colaboradores.id
		WHERE transacao_financeiras.status = 'PA'
			AND DATE(transacao_financeiras.data_atualizacao) = DATA_
			AND transacao_financeiras.metodo_pagamento IN ('BL','PX')
			AND colaboradores.tipo = 'F'
			AND colaboradores.id <> 12
		GROUP BY colaboradores.id;
	END IF;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.historico_pedido
CREATE TABLE IF NOT EXISTS `historico_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `faturamento` int(11) NOT NULL DEFAULT 0,
  `descricao` varchar(1000) DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `data_hora` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_historico` (`id`,`id_cliente`,`faturamento`,`descricao`(255),`usuario`,`data_hora`)
) ENGINE=InnoDB AUTO_INCREMENT=323911 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.historico_pedido_item
CREATE TABLE IF NOT EXISTS `historico_pedido_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `status` varchar(32) NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `observacao` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=174457 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.historico_pontos
CREATE TABLE IF NOT EXISTS `historico_pontos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `origem` int(11) NOT NULL COMMENT '0 - faturamento 1- Desempenho 3 - lancamento 4- compra (SAIDA) 5-Avaliacao',
  `operacao` varchar(1) NOT NULL,
  `observacao` varchar(280) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70673 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.historico_usuario
CREATE TABLE IF NOT EXISTS `historico_usuario` (
  `data` timestamp NULL DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `tela` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.influencers_oficiais_links
CREATE TABLE IF NOT EXISTS `influencers_oficiais_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `situacao` varchar(2) DEFAULT 'CR' COMMENT 'CR - Criado, RE - Desativado',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `influencers_oficiais_links_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=501 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.insere_grade_responsavel
DELIMITER //
CREATE PROCEDURE `insere_grade_responsavel`(
	IN `_ID_PRODUTO` INT,
	IN `_NOME_TAMANHO` VARCHAR(50),
	IN `_ID_RESPONSAVEL` INT,
	IN `_ID_USUARIO` INT,
	IN `_SQL` VARCHAR(5000)
)
BEGIN
	IF (NOT EXISTS(
		SELECT 1
		FROM estoque_grade
		WHERE estoque_grade.id_produto = _ID_PRODUTO
			AND estoque_grade.id_responsavel = _ID_RESPONSAVEL
			AND estoque_grade.nome_tamanho = _NOME_TAMANHO
	)) THEN
		INSERT INTO estoque_grade (
			estoque_grade.id_produto,
			estoque_grade.nome_tamanho,
			estoque_grade.id_responsavel,
			estoque_grade.sequencia,
			estoque_grade.tipo_movimentacao,
			estoque_grade.descricao
		) VALUES (
			_ID_PRODUTO,
			_NOME_TAMANHO,
			_ID_RESPONSAVEL,
			(
				SELECT produtos_grade.sequencia
				FROM produtos_grade
				WHERE produtos_grade.id_produto = _ID_PRODUTO
					AND produtos_grade.nome_tamanho = _NOME_TAMANHO
			),
			'I',
			CONCAT('O tamanho: ', _NOME_TAMANHO, ', do produto: ', _ID_PRODUTO, ', do responsável: ', _ID_RESPONSAVEL, ' foi criado pelo usuário: ', _ID_USUARIO,'!')
		);
	END IF;

	IF (_SQL <> '') THEN
		PREPARE myquery FROM _SQL;
		EXECUTE myquery;

		IF (ROW_COUNT() = 0) THEN
			SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Erro ao fazer entrada de estoque, reporte a equipe de T.I.';
		END IF;
	END IF;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.iugu_mensagens_erro_cartao
CREATE TABLE IF NOT EXISTS `iugu_mensagens_erro_cartao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_lr` varchar(10) NOT NULL,
  `mensagem` varchar(200) DEFAULT NULL,
  `acao_recomendada` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_codigo_lr` (`codigo_lr`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamentos_financeiros_recebiveis
CREATE TABLE IF NOT EXISTS `lancamentos_financeiros_recebiveis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_lancamento` int(11) NOT NULL DEFAULT 0,
  `id_zoop_recebivel` varchar(80) DEFAULT NULL,
  `situacao` varchar(2) NOT NULL DEFAULT 'PE' COMMENT 'PE-Pendente PA-Pago, CA-Cancelado RE-Renbolso',
  `id_zoop_split` varchar(80) DEFAULT NULL,
  `id_recebedor` int(11) NOT NULL COMMENT 'id_colaborador',
  `num_parcela` varchar(2) NOT NULL DEFAULT '1',
  `valor_pago` decimal(10,2) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` datetime DEFAULT NULL,
  `data_gerado` datetime NOT NULL DEFAULT current_timestamp(),
  `cod_transacao` varchar(100) DEFAULT NULL,
  `id_transacao` int(11) DEFAULT 0,
  `data_transferencia` timestamp NULL DEFAULT NULL,
  `tipo` char(2) DEFAULT 'TR' COMMENT 'TR - Transacao MI - Mobile inteira',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_lancamento_financeiro_id` (`id_lancamento`),
  KEY `FK_lancamento_financeiro_1` (`id_zoop_recebivel`,`id`),
  KEY `idx_transferencia` (`id_transacao`,`id_recebedor`)
) ENGINE=InnoDB AUTO_INCREMENT=505848 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro
CREATE TABLE IF NOT EXISTS `lancamento_financeiro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT NULL,
  `documento` int(11) NOT NULL DEFAULT 0,
  `documento_pagamento` int(11) NOT NULL DEFAULT 0,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `origem` varchar(255) NOT NULL COMMENT 'Ver documentacao',
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT current_timestamp(),
  `data_vencimento` timestamp NULL DEFAULT current_timestamp(),
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `juros` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `numero_documento` varchar(100) NOT NULL DEFAULT '0',
  `numero_movimento` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL DEFAULT 0,
  `id_usuario_pag` int(11) NOT NULL DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  `tabela` int(11) NOT NULL DEFAULT 0,
  `pares` int(11) NOT NULL DEFAULT 0,
  `nota_fiscal` int(11) NOT NULL DEFAULT 0,
  `pedido_origem` int(11) NOT NULL DEFAULT 0,
  `transacao_origem` int(11) NOT NULL DEFAULT 0,
  `pedido_destino` int(11) NOT NULL DEFAULT 0,
  `id_usuario_edicao` int(11) DEFAULT NULL,
  `status_estorno` varchar(3) NOT NULL DEFAULT 'A' COMMENT 'A-Aberto C-Credito R-Reembolso P-Pago ',
  `atendimento` varchar(2) DEFAULT 'N' COMMENT 'Lançamento gerado por atendimentos.\r\nN:Não - S:SIM',
  `taxa_pagamento` float(10,2) DEFAULT 0.00,
  `cod_transacao` varchar(100) DEFAULT NULL,
  `notificacao` int(1) DEFAULT 1 COMMENT 'habilitar notificação',
  `bloqueado` tinyint(1) NOT NULL DEFAULT 1,
  `id_split` varchar(100) DEFAULT NULL,
  `data_liquidacao` timestamp NULL DEFAULT NULL,
  `lancamento_origem` int(11) DEFAULT NULL COMMENT 'Compo de uso exclusivos de lançamentos gerados de forma automatica',
  `parcelamento` varchar(30) DEFAULT NULL,
  `id_pagador` int(11) DEFAULT NULL,
  `id_recebedor` int(11) DEFAULT NULL,
  `id_lancamento_pag` int(11) NOT NULL DEFAULT 0,
  `faturamento_criado_pago` char(1) NOT NULL DEFAULT 'F' COMMENT 'T-true F-false ',
  `id_prioridade_saque` int(11) NOT NULL DEFAULT 0,
  `id_lancamento_adiantamento` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_id_cliente` (`id_colaborador`),
  KEY `idx_sequencia` (`sequencia`),
  KEY `idx_prioridade_saque` (`id_prioridade_saque`),
  KEY `lancamento_financeiro_ix_001` (`transacao_origem`,`id_colaborador`,`origem`) USING BTREE,
  KEY `idx_lf` (`situacao`,`tipo`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3703684 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.lancamento_financeiro_abate
DELIMITER //
CREATE PROCEDURE `lancamento_financeiro_abate`()
BEGIN

   DECLARE ID_COLABORADOR_ INT DEFAULT 0;
   DECLARE VALOR_PAGAR_ DECIMAL(10,2) DEFAULT 0;
   DECLARE VALOR_RECEBER_ DECIMAL(10,2) DEFAULT 0;
   DECLARE TIPO_PARAMETRO_ CHAR(1) DEFAULT NULL;
   DECLARE VALOR_SALDO_ DECIMAL(10,2) DEFAULT 0;
   DECLARE ID_LANCAMENTO_SALDO INT(11) DEFAULT 0;
   DECLARE VALOR_LANCAMENTO_SALDO_ DECIMAL(10,2) DEFAULT 0;
   DECLARE TEMP_LOOP_PAGAMENTO_ CHAR(1) DEFAULT 'F';
	DECLARE ID_LANCAMENTO INT(11) DEFAULT 0;
	DECLARE VALOR_LANCAMENTO DECIMAL(10,2) DEFAULT 0;
	DECLARE FIM_ INT(1) DEFAULT 0;


	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;

		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem)
		VALUES(1,NOW(),'Ocorreu um erro',CONCAT('Erro no processo automatico de pagamento de lancamentos ID colaborador',ID_COLABORADOR_),'Z');
	END;


	CARREGA_: LOOP
		SELECT COALESCE(colaboradores.id,0),
			SUM(if(lancamento_financeiro.tipo='R',lancamento_financeiro.valor,0)) valor_pagar,
			SUM(if(lancamento_financeiro.tipo='P',lancamento_financeiro.valor,0)) valor_receber
			INTO ID_COLABORADOR_,VALOR_PAGAR_,VALOR_RECEBER_
		FROM colaboradores
			INNER JOIN lancamento_financeiro ON lancamento_financeiro.id_colaborador =  colaboradores.id
		WHERE lancamento_financeiro.situacao = 1
			AND lancamento_financeiro.valor > 0
			AND lancamento_financeiro.data_emissao <= DATE_SUB(NOW(), INTERVAL 90 MINUTE)
		GROUP BY colaboradores.id
		HAVING valor_pagar > 0 AND valor_receber > 0
		ORDER BY colaboradores.id DESC
		LIMIT 1;


		IF ((FIM_ >=4) OR (ID_COLABORADOR_<=0) OR (ID_COLABORADOR_ IS NULL)) THEN
			LEAVE CARREGA_;
		END IF;

		SET FIM_ = FIM_ + 1;

		SET TEMP_LOOP_PAGAMENTO_ = 'T';
		START TRANSACTION;
			PAGAEMNTO_LOOP : LOOP
			IF TEMP_LOOP_PAGAMENTO_ = 'F' THEN
				LEAVE PAGAEMNTO_LOOP;
			END IF;
				SET ID_LANCAMENTO_SALDO = 0;
				SET VALOR_LANCAMENTO_SALDO_ = 0;
				SET TIPO_PARAMETRO_ = 0;
				SELECT COALESCE(lancamento_financeiro.id,0),
					lancamento_financeiro.valor,
					lancamento_financeiro.tipo
					INTO ID_LANCAMENTO_SALDO,VALOR_LANCAMENTO_SALDO_,TIPO_PARAMETRO_
				FROM lancamento_financeiro
				WHERE lancamento_financeiro.id_colaborador = ID_COLABORADOR_
					AND lancamento_financeiro.situacao = 1
						AND lancamento_financeiro.valor > 0
				ORDER BY lancamento_financeiro.lancamento_origem DESC, lancamento_financeiro.id LIMIT 1;

				IF(ID_LANCAMENTO_SALDO = 0 OR ID_LANCAMENTO_SALDO IS NULL)THEN
					LEAVE PAGAEMNTO_LOOP;
				END IF;

				PAGAMENTO_LOOP_LANC : LOOP
					IF (VALOR_LANCAMENTO_SALDO_ <= 0) THEN
						LEAVE PAGAMENTO_LOOP_LANC;
					END IF;
					SET ID_LANCAMENTO = 0;
					SET VALOR_LANCAMENTO = 0;
					SELECT COALESCE(lancamento_financeiro.id,0),
						lancamento_financeiro.valor
						INTO ID_LANCAMENTO, VALOR_LANCAMENTO
					FROM lancamento_financeiro
					WHERE lancamento_financeiro.tipo = IF(TIPO_PARAMETRO_ = 'R','P','R')
						AND lancamento_financeiro.id_colaborador = ID_COLABORADOR_
						AND lancamento_financeiro.situacao = 1
						AND lancamento_financeiro.valor > 0
						AND lancamento_financeiro.id <> ID_LANCAMENTO_SALDO
					ORDER BY lancamento_financeiro.lancamento_origem DESC, lancamento_financeiro.id LIMIT 1;

					IF(ID_LANCAMENTO > 0 ) THEN
						UPDATE lancamento_financeiro
							SET lancamento_financeiro.valor_pago = IF(VALOR_LANCAMENTO>VALOR_LANCAMENTO_SALDO_,VALOR_LANCAMENTO_SALDO_,VALOR_LANCAMENTO),
								 lancamento_financeiro.id_lancamento_pag = ID_LANCAMENTO_SALDO,
								 lancamento_financeiro.documento_pagamento = '15'
						WHERE lancamento_financeiro.id = ID_LANCAMENTO;

						SET VALOR_LANCAMENTO_SALDO_ = IF(VALOR_LANCAMENTO>VALOR_LANCAMENTO_SALDO_,0,VALOR_LANCAMENTO_SALDO_ - VALOR_LANCAMENTO);
						CALL atualiza_lancamento('0');
					ELSE
						SET TEMP_LOOP_PAGAMENTO_ = 'F';
						LEAVE PAGAMENTO_LOOP_LANC;
					END IF;

				END LOOP;
				IF(VALOR_LANCAMENTO_SALDO_ > 0)THEN
					UPDATE lancamento_financeiro
						SET lancamento_financeiro.valor_pago =  lancamento_financeiro.valor - VALOR_LANCAMENTO_SALDO_,
							 lancamento_financeiro.documento_pagamento = '15'
					WHERE lancamento_financeiro.id = ID_LANCAMENTO_SALDO
				AND lancamento_financeiro.valor <> VALOR_LANCAMENTO_SALDO_;
				ELSE
					UPDATE lancamento_financeiro
						SET lancamento_financeiro.valor_pago = lancamento_financeiro.valor,
							 lancamento_financeiro.documento_pagamento = '15'
					WHERE lancamento_financeiro.id = ID_LANCAMENTO_SALDO;
					CALL atualiza_lancamento('0');
				END IF;
			END LOOP;
		COMMIT;
	END LOOP;

END//
DELIMITER ;

-- Dumping structure for table mobile_stock.lancamento_financeiro_abates
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_abates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_lancamento` enum('NORMAL','PENDENTE') NOT NULL,
  `id_lancamento_credito` int(10) unsigned NOT NULL,
  `id_lancamento_debito` int(10) unsigned NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_lancamento_credito` (`id_lancamento_credito`,`id_lancamento_debito`)
) ENGINE=InnoDB AUTO_INCREMENT=15414 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_abates_grupo
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_abates_grupo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_ultimo_lancamento` int(11) NOT NULL,
  `modelo_serializado` text NOT NULL,
  `tipo_lancamento` enum('NORMAL','PENDENTE') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_colaborador_tipo_lancamento_unique` (`id_colaborador`,`tipo_lancamento`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_documento
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_documento` (
  `id` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT NULL,
  `fk_lancamento` int(11) NOT NULL DEFAULT 0,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `documento` int(11) NOT NULL DEFAULT 0,
  `motivo` varchar(255) DEFAULT NULL,
  `guardar` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_historico
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_historico` (
  `id_lancamento` int(11) NOT NULL DEFAULT 0,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `acao` varchar(50) DEFAULT NULL,
  `data_registro` timestamp NULL DEFAULT NULL,
  `id_usuario` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_pendente
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_pendente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT NULL,
  `documento` int(11) NOT NULL DEFAULT 0,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `origem` varchar(255) NOT NULL COMMENT 'Ver documentacao',
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT current_timestamp(),
  `data_vencimento` timestamp NULL DEFAULT current_timestamp(),
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_usuario` int(11) NOT NULL DEFAULT 0,
  `id_usuario_pag` int(11) NOT NULL DEFAULT 0,
  `numero_documento` varchar(100) DEFAULT NULL,
  `numero_movimento` int(11) DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  `tabela` int(11) NOT NULL DEFAULT 0,
  `pares` int(11) NOT NULL DEFAULT 0,
  `pedido_origem` int(11) NOT NULL DEFAULT 0,
  `transacao_origem` int(11) NOT NULL DEFAULT 0,
  `cod_transacao` varchar(100) DEFAULT NULL,
  `bloqueado` tinyint(1) NOT NULL DEFAULT 1,
  `id_split` varchar(100) DEFAULT NULL,
  `parcelamento` varchar(30) DEFAULT NULL,
  `id_pagador` int(11) DEFAULT NULL,
  `id_recebedor` int(11) DEFAULT NULL,
  `juros` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_lancamento_pendente` (`id_colaborador`,`origem`),
  KEY `ix_lancamento_financeiro_pendente_001` (`transacao_origem`,`origem`,`numero_documento`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3251520 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_seller
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_seller` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) DEFAULT NULL,
  `documento` int(11) NOT NULL DEFAULT 0,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `origem` varchar(20) DEFAULT NULL,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT current_timestamp(),
  `data_vencimento` timestamp NULL DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `juros` decimal(10,2) NOT NULL DEFAULT 0.00,
  `desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `numero_movimento` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL DEFAULT 0,
  `id_usuario_pag` int(11) NOT NULL DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  `pares` int(11) NOT NULL DEFAULT 0,
  `acerto` int(11) NOT NULL DEFAULT 0,
  `conta_bancaria` int(11) NOT NULL DEFAULT 0,
  `compras` varchar(1000) DEFAULT NULL,
  `devolucao` int(11) NOT NULL DEFAULT 0,
  `conta_deposito` varchar(20) DEFAULT NULL,
  `id_usuario_edicao` int(11) DEFAULT NULL,
  `numero_documento` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3087 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_temp
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(1) DEFAULT NULL,
  `documento` int(11) NOT NULL DEFAULT 0,
  `situacao` int(11) NOT NULL DEFAULT 0,
  `origem` varchar(20) DEFAULT NULL,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `numero_documento` varchar(100) NOT NULL DEFAULT '0',
  `id_usuario` int(11) NOT NULL DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `nota_fiscal` int(11) NOT NULL DEFAULT 0,
  `pedido_origem` int(11) NOT NULL DEFAULT 0,
  `cod_transacao` varchar(100) DEFAULT NULL,
  `lancamento_origem` int(11) DEFAULT NULL COMMENT 'Compo de uso exclusivos de lançamentos gerados de forma automatica',
  `id_lancamento` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=556655 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.lancamento_financeiro_transferencias
CREATE TABLE IF NOT EXISTS `lancamento_financeiro_transferencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) DEFAULT NULL,
  `id_zoop_recipient` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_transferencia` timestamp NULL DEFAULT NULL,
  `id_zoop_transferencia` varchar(100) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_zoop_transf` (`id_zoop_transferencia`),
  KEY `idx_transferencia` (`status`,`id_zoop_recipient`,`amount`,`data_transferencia`,`id_zoop_transferencia`,`id_colaborador`,`bank_name`,`account_number`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=366 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.limpar_produtos_fora_de_linha
DELIMITER //
CREATE PROCEDURE `limpar_produtos_fora_de_linha`()
BEGIN
	DECLARE done boolean DEFAULT 0;
    DECLARE _IDPRODUTO int default 0;
    DECLARE _ESTOQUE int default 0;
	DECLARE cur1 cursor for SELECT p.id, SUM(eg.estoque) estoque FROM produtos p
    INNER JOIN estoque_grade eg ON p.id = eg.id_produto
    WHERE p.fora_de_linha = 1 GROUP BY p.id HAVING estoque = 0;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN cur1;
	readLoop: LOOP
	FETCH cur1 INTO _IDPRODUTO, _ESTOQUE;
		IF done=1 THEN
		  LEAVE readLoop;
		END IF;
        DELETE FROM pedido_item WHERE situacao = 1 AND id_produto = _IDPRODUTO;
	END LOOP readLoop;
	CLOSE cur1;
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.limpa_senhas_temporarias
DELIMITER //
CREATE PROCEDURE `limpa_senhas_temporarias`()
BEGIN
	UPDATE usuarios SET
		usuarios.senha_temporaria = NULL,
		usuarios.data_senha_temporaria = NULL
	WHERE usuarios.senha_temporaria IS NOT NULL
		OR usuarios.data_senha_temporaria IS NOT NULL;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.linha
CREATE TABLE IF NOT EXISTS `linha` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(30) DEFAULT NULL,
  `icone_imagem` varchar(30) NOT NULL,
  `tamanho_padrao_foto` varchar(2) NOT NULL DEFAULT '0' COMMENT 'Tamnho padrão para foto',
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.localizacao
CREATE TABLE IF NOT EXISTS `localizacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.localizacao_estoque
CREATE TABLE IF NOT EXISTS `localizacao_estoque` (
  `tipo` varchar(1) DEFAULT NULL,
  `local` int(6) NOT NULL,
  `num_caixa` int(3) NOT NULL DEFAULT 0,
  UNIQUE KEY `idx_local` (`tipo`,`local`) USING BTREE,
  KEY `idx_localizacao_estoque` (`num_caixa`,`local`),
  KEY `idx_localizacao_estoque1` (`local`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.logistica_item
CREATE TABLE IF NOT EXISTS `logistica_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_transacao` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_responsavel_estoque` int(11) NOT NULL,
  `id_colaborador_tipo_frete` int(11) NOT NULL,
  `id_entrega` int(11) DEFAULT NULL,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `situacao` enum('PE','SE','CO','RE','DE','DF','ES') NOT NULL DEFAULT 'PE' COMMENT 'PE - Pendente, SE - Separado, CO - Conferido, RE - Rejeitado, DE - Devolucao, DF - Defeito, ES - Estorno',
  `preco` decimal(10,2) NOT NULL,
  `uuid_produto` varchar(100) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_produto` (`uuid_produto`),
  KEY `logistica_item_situacao_IDX` (`situacao`) USING BTREE,
  KEY `logistica_item_id_cliente_IDX` (`id_cliente`) USING BTREE,
  KEY `id_responsavel_estoque_IDX` (`id_responsavel_estoque`),
  KEY `id_colaborador_tipo_frete_IDX` (`id_colaborador_tipo_frete`),
  KEY `IDX_produto` (`id_produto`,`id_responsavel_estoque`,`nome_tamanho`)
) ENGINE=InnoDB AUTO_INCREMENT=1339762 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.logistica_item_data_alteracao
CREATE TABLE IF NOT EXISTS `logistica_item_data_alteracao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `situacao_anterior` char(2) NOT NULL DEFAULT 'PE',
  `situacao_nova` char(2) NOT NULL DEFAULT 'PE',
  `id_linha` int(11) NOT NULL DEFAULT 0,
  `id_usuario` int(11) NOT NULL DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uuid_produto` (`uuid_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=2484719 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.logistica_item_impressos_temp
CREATE TABLE IF NOT EXISTS `logistica_item_impressos_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(100) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23401 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.logistica_item_logs
CREATE TABLE IF NOT EXISTS `logistica_item_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(100) NOT NULL,
  `mensagem` longtext NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1786834 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.logs_requisicoes_meulook
CREATE TABLE IF NOT EXISTS `logs_requisicoes_meulook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `log` text DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `data_IDX` (`data_criacao`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1958721 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_alteracao_produto
CREATE TABLE IF NOT EXISTS `log_alteracao_produto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `data` timestamp NULL DEFAULT current_timestamp(),
  `nome_coluna` varchar(50) NOT NULL,
  `linha_tabela` varchar(10) NOT NULL,
  `valor_anterior` varchar(200) NOT NULL,
  `valor_novo` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9982722 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_erros
CREATE TABLE IF NOT EXISTS `log_erros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(1000) DEFAULT NULL,
  `data_hora` timestamp NULL DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_estoque_grade
CREATE TABLE IF NOT EXISTS `log_estoque_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `id_responsavel_estoque` int(11) NOT NULL,
  `tipo_movimentacao` enum('I','R') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'I-Inserindo Grade R-Removendo Grade',
  `descricao` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_produto` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=254276 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_estoque_movimentacao
CREATE TABLE IF NOT EXISTS `log_estoque_movimentacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `id_responsavel_estoque` int(11) NOT NULL,
  `oldEstoque` int(10) NOT NULL DEFAULT 0,
  `newEstoque` int(10) NOT NULL DEFAULT 0,
  `oldVendido` int(10) NOT NULL DEFAULT 0,
  `newVendido` int(10) NOT NULL DEFAULT 0,
  `tipo_movimentacao` enum('S','E','X','M','N','C') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'S-Saida E-Entrada X-Exclusao M-Movimentacao N-Entrada Vendido C-Correção Manual',
  `descricao` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6926281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_faturamento_item
CREATE TABLE IF NOT EXISTS `log_faturamento_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_faturamento` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `mensagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`mensagem`)),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  KEY `Index 1` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=512751 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_pesquisa
CREATE TABLE IF NOT EXISTS `log_pesquisa` (
  `pesquisa` tinytext NOT NULL DEFAULT '',
  `id_colaborador` int(11) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.log_produtos_localizacao
CREATE TABLE IF NOT EXISTS `log_produtos_localizacao` (
  `id_produto` int(11) NOT NULL,
  `old_localizacao` varchar(7) NOT NULL,
  `new_localizacao` varchar(7) NOT NULL,
  `data_hora` timestamp NULL DEFAULT current_timestamp(),
  `usuario` varchar(20) DEFAULT NULL,
  `qtd_entrada` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.mensagens_novidades
CREATE TABLE IF NOT EXISTS `mensagens_novidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `json_texto` longtext NOT NULL,
  `situacao` enum('PE','EV') NOT NULL DEFAULT 'PE' COMMENT 'PE = Pendente, EV = Enviado',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `categoria` enum('PR','NO') NOT NULL COMMENT 'PR = Promoção, NO = Novidade',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12013 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.metas
CREATE TABLE IF NOT EXISTS `metas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) DEFAULT 0,
  `valor` decimal(10,2) DEFAULT 0.00,
  `data_meta` timestamp NULL DEFAULT NULL,
  `gerado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idex_metas` (`id_cliente`,`valor`,`data_meta`,`gerado`)
) ENGINE=InnoDB AUTO_INCREMENT=11182 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.movimentacao_estoque
CREATE TABLE IF NOT EXISTS `movimentacao_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(1) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `data` timestamp NULL DEFAULT NULL,
  `origem` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  KEY `idx_id_usuario_tipo_data_origem` (`id`,`usuario`,`tipo`,`data`,`origem`)
) ENGINE=InnoDB AUTO_INCREMENT=236441 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.movimentacao_estoque_item
CREATE TABLE IF NOT EXISTS `movimentacao_estoque_item` (
  `id_mov` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `compra` int(11) NOT NULL DEFAULT 0,
  `sequencia_compra` int(11) NOT NULL DEFAULT 0,
  `volume` int(11) NOT NULL DEFAULT 0,
  `preco_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_responsavel_estoque` int(11) NOT NULL,
  KEY `idx_mov_item` (`id_mov`,`id_produto`,`nome_tamanho`,`sequencia`,`quantidade`,`compra`,`sequencia_compra`,`volume`,`preco_unit`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.movimentacoes_financeiras
CREATE TABLE IF NOT EXISTS `movimentacoes_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fornecedor` int(11) NOT NULL,
  `data` datetime NOT NULL,
  `valor_lancamento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total_produtos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_anterior` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_defeitos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo` int(1) DEFAULT 1 COMMENT 'Tipo 1: crédito / Tipo 2: débito',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.movimentacoes_manuais_caixa
CREATE TABLE IF NOT EXISTS `movimentacoes_manuais_caixa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(1) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `motivo` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `responsavel` int(5) NOT NULL,
  `id_faturamento` int(11) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  `conferido_por` int(11) DEFAULT 0,
  `conferido_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5010 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.movimento
CREATE TABLE IF NOT EXISTS `movimento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(1) DEFAULT NULL,
  `data` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `valor` decimal(10,0) DEFAULT 0,
  `motivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.municipios
CREATE TABLE IF NOT EXISTS `municipios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `uf` char(2) NOT NULL,
  `logradouro` varchar(255) NOT NULL,
  `bairro` varchar(255) NOT NULL,
  `cep` char(8) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `valor_comissao_bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `IDX_cidade_uf` (`uf`,`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=22281 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.negociacoes_produto_log
CREATE TABLE IF NOT EXISTS `negociacoes_produto_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(100) NOT NULL,
  `mensagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`mensagem`)),
  `situacao` enum('CRIADA','ACEITA','RECUSADA','CANCELADA') NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1643 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.negociacoes_produto_temp
CREATE TABLE IF NOT EXISTS `negociacoes_produto_temp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(100) NOT NULL,
  `itens_oferecidos` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UIDX_uuid_produto` (`uuid_produto`),
  CONSTRAINT `FK_uuid_produto_negociacoes_logistica_item` FOREIGN KEY (`uuid_produto`) REFERENCES `logistica_item` (`uuid_produto`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=827 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.nivel_permissao
CREATE TABLE IF NOT EXISTS `nivel_permissao` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL COMMENT 'Nome do Acesso em si\r\nEx: Estoquista,Vendendor, Cliente Premium',
  `nivel_value` varchar(30) NOT NULL,
  `categoria` varchar(30) NOT NULL COMMENT 'CLIENTE, TRANSPORTADOR, SELLER, MOBILE PAY, INTERNO',
  `subacesso` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 - SUBACESSO\r\n2 - ACESSO PADRAO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.notificacoes
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `data_evento` timestamp NULL DEFAULT current_timestamp(),
  `imagem` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `titulo` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `mensagem` text CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `recebida` int(11) DEFAULT 0,
  `tipo_frete` int(11) DEFAULT NULL COMMENT 'Se tipo CORRECAO = tipo_frete = pedido_origem;',
  `tipo_mensagem` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'A' COMMENT 'A - PADRAO\r\nZ - ZOOP',
  `icon` tinyint(1) DEFAULT 0 COMMENT '1 - Mensagem do atendimento (Headset)\r\n2 - Troca Inseria (Exchange)\r\n3 - Adicionado par no meu estoque digital (HoldingUsd)\r\n4 - Pedido fechado (Checkcircle)\r\n5 - Pagamento aprovado (DollarSign)\r\n6 - Pedido Separado (BoxOpen)\r\n7 - Pedido Conferido (ClipboardList)\r\n8 - Pedido está pronto (Truck)\r\n9 - Produto nao encontrado na separação (Bug)\r\n10 - Metas e seu produto chegou (EventIcon)\r\n11 - Resposda de dúvidas sobre o produto (EventIcon)\r\n12 - Retirada no ponto de entrega (QrCode)\r\n',
  `prioridade` char(2) DEFAULT 'NA' COMMENT 'NA - Normal UR - Urgente',
  `destino` char(2) DEFAULT 'MS',
  PRIMARY KEY (`id`),
  KEY `id_cliente` (`id_cliente`),
  KEY `idx_notificacoes` (`id_cliente`,`data_evento`)
) ENGINE=InnoDB AUTO_INCREMENT=4348208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.notifica_clientes_produto_chegou
DELIMITER //
CREATE PROCEDURE `notifica_clientes_produto_chegou`(
	IN `IDPRODUTO` INT,
	IN `NOME_TAMANHO_` VARCHAR(50)
)
BEGIN
 DECLARE done boolean DEFAULT 0;
 DECLARE IDCLIENTE int default 0;
 DECLARE TAM VARCHAR(50) default NULL;
 DECLARE cur1 cursor for SELECT
			pedido_item.id_cliente,
			pedido_item.nome_tamanho
		FROM pedido_item
		WHERE pedido_item.id_produto = IDPRODUTO
			AND pedido_item.nome_tamanho = NOME_TAMANHO_
			AND pedido_item.situacao = 1
		GROUP BY pedido_item.id_cliente;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN cur1;
	readLoop: LOOP
   FETCH cur1 INTO IDCLIENTE, TAM;
	 IF done=1 THEN
	  LEAVE readLoop;
	END IF;
	IF(TAM = NOME_TAMANHO_) THEN
        SET @FOTO = (
            SELECT produtos_foto.caminho
            FROM produtos_foto
            WHERE produtos_foto.id = IDPRODUTO
            ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
            LIMIT 1
        );
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, imagem, mensagem, tipo_mensagem, icon) VALUES (IDCLIENTE, NOW(), 'O seu produto chegou!', @FOTO, CONCAT(
		 "O seu produto ",
		 IDPRODUTO,
		 " tamanho ",
		 NOME_TAMANHO_,
		 " chegou. Acesse <a href='/pedido' style='color:red'><strong>AQUI</strong></a> para garantir o seu produto."), "C", 10);
	 END IF;
	END LOOP readLoop;
	CLOSE cur1;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.num_acessos
CREATE TABLE IF NOT EXISTS `num_acessos` (
  `pagina` varchar(100) DEFAULT NULL,
  `requisicoes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ordem_correcao_estoque
CREATE TABLE IF NOT EXISTS `ordem_correcao_estoque` (
  `id` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `data_emissao` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ordem_localizacao
CREATE TABLE IF NOT EXISTS `ordem_localizacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_produto` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=4245 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ordem_separacao
CREATE TABLE IF NOT EXISTS `ordem_separacao` (
  `id` int(11) NOT NULL DEFAULT 0,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `concluido` int(11) NOT NULL DEFAULT 0,
  `id_separador` int(11) NOT NULL DEFAULT 0,
  `data_separacao` timestamp NULL DEFAULT NULL,
  `prioridade` int(11) NOT NULL DEFAULT 0,
  `presencial` int(11) NOT NULL DEFAULT 0,
  `cliente_aguardando` int(11) NOT NULL DEFAULT 0,
  `id_faturamento` int(11) NOT NULL DEFAULT 0,
  `bloqueado` int(11) NOT NULL DEFAULT 0,
  KEY `ordem_separacao` (`id`,`id_cliente`,`concluido`,`id_separador`,`prioridade`,`data_emissao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.paginas_acessadas
CREATE TABLE IF NOT EXISTS `paginas_acessadas` (
  `mes` int(11) NOT NULL DEFAULT 0,
  `ano` int(11) NOT NULL DEFAULT 0,
  `acessos` int(11) NOT NULL DEFAULT 0,
  `adicionados` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `data_registro` date NOT NULL,
  KEY `idx_clique` (`mes`,`ano`,`acessos`,`id_produto`,`adicionados`) USING BTREE,
  KEY `idx_id_produto` (`id_produto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pedido
CREATE TABLE IF NOT EXISTS `pedido` (
  `id_cliente` int(11) NOT NULL,
  `tabela_preco` int(11) NOT NULL DEFAULT 1,
  `finalizar` int(11) NOT NULL DEFAULT 0,
  `separador_temp` int(11) NOT NULL DEFAULT 0,
  `exclusao_temp` int(11) NOT NULL DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `data_confirmar` timestamp NULL DEFAULT NULL,
  `tipo_frete` int(11) NOT NULL DEFAULT 0,
  `frete` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ordem_separacao_situacao` int(11) NOT NULL DEFAULT 0,
  `usuario_ordem_separacao` int(11) NOT NULL DEFAULT 0,
  `sinalizado` int(11) NOT NULL DEFAULT 0,
  `data_sinalizado` timestamp NULL DEFAULT NULL,
  `usuario_contato` int(11) DEFAULT 0,
  `data_contato` timestamp NULL DEFAULT NULL,
  `observacao2` text DEFAULT NULL,
  `ultimo_vendedor` int(11) NOT NULL DEFAULT 0,
  `cliente_pagando` varchar(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `idx_pedido` (`id_cliente`,`tabela_preco`,`finalizar`,`separador_temp`,`exclusao_temp`,`data_confirmar`,`tipo_frete`,`frete`,`ordem_separacao_situacao`,`usuario_ordem_separacao`,`sinalizado`,`data_sinalizado`,`usuario_contato`,`data_contato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pedidos_separados
CREATE TABLE IF NOT EXISTS `pedidos_separados` (
  `id_separador` int(11) NOT NULL DEFAULT 0,
  `data` timestamp NULL DEFAULT NULL,
  `pares` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pedido_estante
CREATE TABLE IF NOT EXISTS `pedido_estante` (
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `estante` int(11) NOT NULL DEFAULT 0,
  `cheio` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `estante` (`estante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pedido_item
CREATE TABLE IF NOT EXISTS `pedido_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0 COMMENT 'Depreciado não usar',
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `situacao` enum('1','2','3','DI','FR') NOT NULL DEFAULT '1' COMMENT '1 - Carrinho 2 - Reservado DI - Direito de item FR - Fraude',
  `tipo_adicao` char(2) DEFAULT 'PR' COMMENT '(Coluna exclusiva meulook) PR - produto adicionado normalmente no carrinho, FL - adicionado na fila',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_vencimento` timestamp NULL DEFAULT NULL COMMENT 'Depreciado não usar',
  `separado` int(11) NOT NULL DEFAULT 0 COMMENT 'Depreciado não usar',
  `uuid` varchar(80) DEFAULT NULL,
  `cliente` varchar(100) DEFAULT '' COMMENT 'Depreciado não usar',
  `premio` int(11) NOT NULL DEFAULT 0 COMMENT 'Depreciado não usar',
  `id_cliente_final` int(11) DEFAULT 0 COMMENT 'Depreciado não usar',
  `id_responsavel_estoque` int(11) NOT NULL,
  `id_transacao` int(11) DEFAULT NULL,
  `observacao` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ped_item` (`uuid`),
  KEY `idx_id_produto` (`id_produto`),
  KEY `idx_pedido_item` (`id_cliente`,`id_produto`,`sequencia`,`nome_tamanho`,`situacao`,`separado`) USING BTREE,
  KEY `IDX_produto` (`id_produto`,`id_responsavel_estoque`,`nome_tamanho`)
) ENGINE=InnoDB AUTO_INCREMENT=629792 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pedido_item_logs
CREATE TABLE IF NOT EXISTS `pedido_item_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(100) NOT NULL,
  `mensagem` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5733937 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pedido_item_meu_look
CREATE TABLE IF NOT EXISTS `pedido_item_meu_look` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `id_ponto` int(11) DEFAULT NULL COMMENT 'campo depreciado - somente utilizar no ultimo caso',
  `id_transacao` int(11) DEFAULT NULL,
  `uuid` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `situacao` char(2) NOT NULL DEFAULT 'CR' COMMENT 'CR - criado, RE - removida, DM - desmonetizada, PA - pago',
  `id_responsavel_estoque` int(11) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `idx_uuid_unique` (`uuid`) USING BTREE,
  KEY `idx_produto_publicacao` (`id_produto`),
  KEY `responsavel` (`id_responsavel_estoque`),
  KEY `idx_id_ponto` (`id_ponto`)
) ENGINE=InnoDB AUTO_INCREMENT=1408374 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pontos_coleta
CREATE TABLE IF NOT EXISTS `pontos_coleta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `dias_pedido_chegar` tinyint(4) NOT NULL DEFAULT 0,
  `deve_recalcular_percentual` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'FALSE: O percentual só pode ser modificado "manualmente" | TRUE: Evento pode modificar percentual',
  `valor_custo_frete` decimal(10,2) NOT NULL DEFAULT 0.00,
  `porcentagem_frete` decimal(10,2) NOT NULL DEFAULT 10.00,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UIDX_id_colaborador` (`id_colaborador`)
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pontos_coleta_agenda_acompanhamento
CREATE TABLE IF NOT EXISTS `pontos_coleta_agenda_acompanhamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `dia` enum('SEGUNDA','TERCA','QUARTA','QUINTA','SEXTA') NOT NULL,
  `horario` time NOT NULL,
  `frequencia` enum('RECORRENTE','PONTUAL') NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UIDX_horario` (`id_colaborador`,`dia`,`horario`),
  CONSTRAINT `FK_id_colaborador_ponto_coleta` FOREIGN KEY (`id_colaborador`) REFERENCES `pontos_coleta` (`id_colaborador`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=375 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.pontos_coleta_calculo_percentual_frete_logs
CREATE TABLE IF NOT EXISTS `pontos_coleta_calculo_percentual_frete_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador_ponto_coleta` int(11) NOT NULL,
  `lista_id_entrega` text NOT NULL,
  `valor_custo_frete` decimal(10,2) NOT NULL,
  `porcentagem_frete` decimal(10,2) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4837 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.processo_cria_foguinho
DELIMITER //
CREATE PROCEDURE `processo_cria_foguinho`()
BEGIN

   DECLARE ID_PRODUTO_ INT DEFAULT 0;
	DECLARE QTD_ INT DEFAULT 0;
	DECLARE FIM_ INTEGER DEFAULT FALSE;
	DEClARE CURSOR_ CURSOR FOR SELECT produtos_acessos.id_produto,
														COUNT(produtos_acessos.id_produto) qtd
													FROM produtos_acessos
													WHERE produtos_acessos.origem <> 'ML'
													GROUP BY produtos_acessos.id_produto
												ORDER BY qtd DESC LIMIT 50;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET FIM_ = 1;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		ROLLBACK;

		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem)
		VALUES(1,NOW(),'Ocorreu um erro',CONCAT('GRAVE: Erro no processo de gerar foguinho, ',NOW()),'Z');
	END;

	START TRANSACTION;
	INSERT INTO paginas_acessadas (mes,ano,acessos,id_produto,data_registro)
	SELECT YEAR(produtos_acessos.data),
		MONTH(produtos_acessos.data),
		COUNT(produtos_acessos.id_produto),
		produtos_acessos.id_produto,
		DATE(produtos_acessos.data)
	FROM produtos_acessos
		WHERE produtos_acessos.origem <> 'ML' AND produtos_acessos.id_produto IN (SELECT pr.id from produtos pr WHERE pr.posicao_acessado >= 1)
	GROUP BY produtos_acessos.id_produto;



	DELETE FROM produtos_acessos
	WHERE produtos_acessos.origem <> 'ML' AND produtos_acessos.id_produto IN (SELECT pr.id from produtos pr WHERE pr.posicao_acessado >= 1);

	UPDATE produtos
		SET produtos.posicao_acessado = 0
	WHERE produtos.posicao_acessado > 0;


	OPEN CURSOR_;
	CARREGA_: LOOP
		FETCH CURSOR_ INTO ID_PRODUTO_,QTD_;
		IF FIM_ THEN
			LEAVE CARREGA_;
		END IF;
		UPDATE produtos
			SET produtos.posicao_acessado = QTD_
		WHERE produtos.id = ID_PRODUTO_;
	END LOOP;
	CLOSE CURSOR_;
	COMMIT;

END//
DELIMITER ;

-- Dumping structure for table mobile_stock.produtos
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `id_fornecedor` int(11) NOT NULL DEFAULT 0,
  `bloqueado` int(11) NOT NULL DEFAULT 0,
  `id_tabela` int(11) NOT NULL DEFAULT 0,
  `id_linha` int(11) NOT NULL DEFAULT 0,
  `grade` int(11) NOT NULL DEFAULT 0,
  `data_entrada` timestamp NULL DEFAULT NULL,
  `promocao` int(11) NOT NULL DEFAULT 0,
  `localizacao` varchar(5) DEFAULT NULL,
  `destaque` int(11) NOT NULL DEFAULT 0,
  `outras_informacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `altura_solado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grade_min` int(11) NOT NULL DEFAULT 0,
  `grade_max` int(11) NOT NULL DEFAULT 0,
  `id_tabela_promocao` int(11) NOT NULL DEFAULT 0,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_primeira_entrada` timestamp NULL DEFAULT NULL,
  `premio` int(11) NOT NULL DEFAULT 0,
  `premio_pontos` int(11) NOT NULL DEFAULT 0,
  `thumbnails` varchar(1000) DEFAULT NULL,
  `promocao_temp` int(11) NOT NULL DEFAULT 0,
  `id_tabela_promocao_temp` varchar(255) DEFAULT '0',
  `proporcao_caixa` decimal(10,2) NOT NULL DEFAULT 1.00,
  `forma` enum('PEQUENA','NORMAL','GRANDE') NOT NULL DEFAULT 'NORMAL',
  `embalagem` enum('SACOLA','CAIXA') DEFAULT NULL COMMENT 'SACOLA: sacola | CAIXA: caixa | NULL: irrelevante',
  `nome_comercial` varchar(100) NOT NULL,
  `especial` int(11) DEFAULT 0,
  `preco_promocao` int(3) DEFAULT 0,
  `porcentagem_comissao_ms` decimal(4,2) DEFAULT NULL COMMENT 'Esse campo é preenchido automaticamente com a tabela configurações',
  `porcentagem_comissao` decimal(4,2) DEFAULT NULL COMMENT 'Esse campo é preenchido automaticamente com a tabela configurações',
  `porcentagem_comissao_ml` decimal(4,2) DEFAULT NULL COMMENT 'Esse campo é preenchido automaticamente com a tabela configurações',
  `porcentagem_comissao_ponto_coleta` decimal(4,2) NOT NULL COMMENT 'Esse campo é preenchido automaticamente com a tabela configurações',
  `valor_custo_produto` decimal(10,2) NOT NULL,
  `valor_venda_ms` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_venda_ml` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_venda_sem_comissao` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_custo_produto_fornecedor` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `usuario` int(11) DEFAULT 0,
  `valor_custo_produto_historico` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'historico do valor sem a promocao',
  `data_ultima_entrega` timestamp NULL DEFAULT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_grade` int(11) NOT NULL DEFAULT 1,
  `sexo` varchar(45) NOT NULL COMMENT 'MA - masculino FE - feminino UN - unissex',
  `cores` varchar(100) CHARACTER SET utf8 COLLATE utf8_swedish_ci DEFAULT NULL,
  `valor_venda_ms_historico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_venda_ml_historico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `posicao_acessado` smallint(6) NOT NULL DEFAULT 0 COMMENT 'poiscao de acessado no dia anterior ',
  `posicao_acessado_meulook` smallint(6) NOT NULL DEFAULT 0 COMMENT 'Registra o número de acessos, reseta todo dia.',
  `fora_de_linha` tinyint(1) DEFAULT 0,
  `data_up` timestamp NULL DEFAULT NULL,
  `data_atualizou_valor_custo` timestamp NULL DEFAULT NULL,
  `data_qualquer_alteracao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Campo usado somente para revalidação do logstash.',
  `permitido_reposicao` int(11) DEFAULT 0,
  `id_colaborador_publicador_padrao` int(11) GENERATED ALWAYS AS (if(`especial` = 1,10347,`id_fornecedor`)) VIRTUAL,
  `quantidade_vendida` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_produtos` (`id_linha`,`id_fornecedor`,`especial`,`descricao`,`nome_comercial`,`bloqueado`,`preco_promocao`) USING BTREE,
  KEY `ix_produtos_001` (`premio`,`bloqueado`),
  KEY `IDX_id_fornecedor` (`id_fornecedor`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=89598 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_acessos
CREATE TABLE IF NOT EXISTS `produtos_acessos` (
  `id_produto` int(11) NOT NULL,
  `data` timestamp NULL DEFAULT current_timestamp(),
  `origem` tinytext NOT NULL DEFAULT 'MS' COMMENT 'MS: MobileStock / ML: MeuLook',
  `id_colaborador` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_aguarda_entrada_estoque
CREATE TABLE IF NOT EXISTS `produtos_aguarda_entrada_estoque` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `nome_tamanho` varchar(50) NOT NULL,
  `localizacao` varchar(7) DEFAULT NULL,
  `tipo_entrada` enum('FT','TR','CO','PC','SP') NOT NULL COMMENT 'FT-Foto TR-Troca CO-Compra PC-PedidoCancelado SP-separado',
  `em_estoque` enum('F','T') NOT NULL DEFAULT 'F' COMMENT 'F-false T-true',
  `identificao` varchar(56) NOT NULL DEFAULT '0' COMMENT 'FT-null TR-uuid CO-id_compra PC-uuid SP-Null',
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario` int(5) DEFAULT NULL,
  `qtd` int(5) NOT NULL DEFAULT 1,
  `usuario_resp` int(5) NOT NULL,
  `tamanho_foto` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_produtos_aguarda_entrada_estoque` (`id`,`id_produto`,`nome_tamanho`,`localizacao`,`tipo_entrada`,`em_estoque`,`data_hora`,`usuario`) USING BTREE,
  KEY `idx_produto` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=761259 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_categorias
CREATE TABLE IF NOT EXISTS `produtos_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_produto_id_categoria_unique` (`id_produto`,`id_categoria`),
  KEY `id_produto` (`id_produto`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `id_categoria_fk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `id_produto_fk_1` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=455600 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_foto
CREATE TABLE IF NOT EXISTS `produtos_foto` (
  `id` int(11) NOT NULL DEFAULT 0,
  `caminho` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `nome_foto` varchar(500) DEFAULT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `foto_calcada` int(11) DEFAULT 0,
  `id_usuario` int(11) DEFAULT NULL,
  `data_hora` timestamp NULL DEFAULT current_timestamp(),
  `tipo_foto` char(2) DEFAULT 'MD' COMMENT 'SM - foto minuatura MD - foto normal LG - foto calçada XL - banner',
  KEY `idx_produtos_foto` (`id`,`sequencia`,`caminho`(255),`nome_foto`(255),`data_hora`,`tipo_foto`),
  KEY `IDX_produto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_grade
CREATE TABLE IF NOT EXISTS `produtos_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 13,
  `nome_tamanho` varchar(50) NOT NULL,
  `cod_barras` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uidx_produto_sequencia` (`id_produto`,`sequencia`),
  UNIQUE KEY `uidx_cod_barras` (`cod_barras`),
  UNIQUE KEY `uidx_produto_tamanho` (`id_produto`,`nome_tamanho`),
  KEY `idx_produto_tamanho` (`id_produto`,`nome_tamanho`)
) ENGINE=InnoDB AUTO_INCREMENT=759920 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_lista_desejos
CREATE TABLE IF NOT EXISTS `produtos_lista_desejos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_colaborador` (`id_colaborador`),
  KEY `produtos_lista_desejos_ibfk_2` (`id_produto`),
  CONSTRAINT `produtos_lista_desejos_ibfk_1` FOREIGN KEY (`id_colaborador`) REFERENCES `colaboradores` (`id`),
  CONSTRAINT `produtos_lista_desejos_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=122020 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_ordem_catalogo
CREATE TABLE IF NOT EXISTS `produtos_ordem_catalogo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idxs_produto_id` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=63290 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_pontos
CREATE TABLE IF NOT EXISTS `produtos_pontos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `pontuacao_avaliacoes` int(11) NOT NULL DEFAULT 0 COMMENT '5 estrelas = 10 pontos, 4 estrelas = 3 pontos',
  `pontuacao_seller` int(11) NOT NULL DEFAULT 0 COMMENT 'Melhor fabricante = 10 pontos, Excelente = 2 pontos, Regular = 0, Ruim = -20',
  `pontuacao_fullfillment` int(11) NOT NULL DEFAULT 0 COMMENT 'Tem qualquer tamanho fullfillment = 10 pontos (não soma)',
  `quantidade_vendas` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantidade de vendas válidas (Não fraude, corrigida, trocada ou devolvida) 1 venda = 1 ponto',
  `pontuacao_devolucao_normal` int(11) NOT NULL DEFAULT 0 COMMENT 'Cada uma é -2 pontos',
  `pontuacao_devolucao_defeito` int(11) NOT NULL DEFAULT 0 COMMENT 'Cada uma é -5 pontos',
  `cancelamento_automatico` int(11) NOT NULL DEFAULT 0 COMMENT 'Cada um é -8 pontos',
  `atraso_separacao` int(11) NOT NULL DEFAULT 0 COMMENT 'Se houver atraso na separação -50 pontos',
  `total` int(11) GENERATED ALWAYS AS (`pontuacao_avaliacoes` + `pontuacao_seller` + `pontuacao_fullfillment` + `quantidade_vendas` + `pontuacao_devolucao_normal` + `pontuacao_devolucao_defeito` + `cancelamento_automatico` + `atraso_separacao`) VIRTUAL,
  `total_normalizado` double(15,14) NOT NULL DEFAULT 0.00000000000000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_produto` (`id_produto`),
  CONSTRAINT `produtos_pontos_ibfk_1` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107639 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_pontos_metadados
CREATE TABLE IF NOT EXISTS `produtos_pontos_metadados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grupo` enum('PRODUTOS_PONTOS','REPUTACAO_FORNECEDORES') NOT NULL,
  `chave` varchar(255) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `observacao` text NOT NULL DEFAULT '',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_separacao_fotos
CREATE TABLE IF NOT EXISTS `produtos_separacao_fotos` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `nome_tamanho` varchar(50) NOT NULL,
  `separado` char(1) NOT NULL DEFAULT 'F' COMMENT 'F-false T-true',
  `data_emissao` timestamp NULL DEFAULT NULL,
  `data_separado` timestamp NULL DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_solicita` int(5) DEFAULT NULL,
  `usuario_separado` int(5) DEFAULT NULL,
  `tipo_separacao` char(1) DEFAULT NULL COMMENT 'E-saida do estoque   P-saida aguarda estoque',
  `id_produto_agu_estoque` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_produtos_aguarda_entrada_estoque` (`id`,`id_produto`,`nome_tamanho`,`separado`,`data_emissao`,`data_separado`,`data_hora`,`usuario_solicita`,`usuario_separado`) USING BTREE,
  KEY `IDX_produto` (`id_produto`,`nome_tamanho`)
) ENGINE=InnoDB AUTO_INCREMENT=18467 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_sugestao
CREATE TABLE IF NOT EXISTS `produtos_sugestao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `foto_produto` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=762 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_tipos_grades
CREATE TABLE IF NOT EXISTS `produtos_tipos_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(45) NOT NULL,
  `grade_json` varchar(1000) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.produtos_video
CREATE TABLE IF NOT EXISTS `produtos_video` (
  `id` int(11) NOT NULL DEFAULT 0,
  `link` varchar(1000) NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.promocao_temporaria
CREATE TABLE IF NOT EXISTS `promocao_temporaria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `duracao` int(11) NOT NULL DEFAULT 24,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.promocoes
CREATE TABLE IF NOT EXISTS `promocoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `status` int(1) DEFAULT 0 COMMENT ' [0] false, [1] true',
  `data_inicio` timestamp NULL DEFAULT current_timestamp(),
  `data_fim` timestamp NULL DEFAULT current_timestamp(),
  `usuario` int(11) DEFAULT NULL,
  `ultima_alteracao` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19747 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.publicacoes
CREATE TABLE IF NOT EXISTS `publicacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `descricao` varchar(240) DEFAULT NULL,
  `foto` varchar(1000) NOT NULL,
  `situacao` char(2) DEFAULT 'CR' COMMENT 'CR - Criado RE - Removido',
  `tipo_publicacao` enum('ML','AU','ST') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ML' COMMENT 'ML - Publicacao Meu Look AU - Publicacao Automatica Fotografo ST - Stories',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vendas` int(11) NOT NULL DEFAULT 0 COMMENT 'Número de vendas incrementado em cada compra',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=266712 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.publicacoes_cards
CREATE TABLE IF NOT EXISTS `publicacoes_cards` (
  `id_publicacao` int(11) NOT NULL,
  `storie_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `likes` int(11) NOT NULL DEFAULT 0,
  KEY `idx_publicacao` (`id_publicacao`) USING BTREE,
  CONSTRAINT `storie_json` CHECK (json_valid(`storie_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.publicacoes_produtos
CREATE TABLE IF NOT EXISTS `publicacoes_produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_publicacao` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `uuid` varchar(100) DEFAULT NULL,
  `situacao` char(2) NOT NULL DEFAULT 'CR',
  `foto_publicacao` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_id_produto` (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=301683 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.publicacoes_story_detalhes
CREATE TABLE IF NOT EXISTS `publicacoes_story_detalhes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_publicacao` int(11) NOT NULL,
  `id_colaborador` int(11) NOT NULL,
  `story_curtido` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11021 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ranking
CREATE TABLE IF NOT EXISTS `ranking` (
  `id` int(11) NOT NULL,
  `nome` varchar(128) DEFAULT NULL,
  `chave` varchar(128) DEFAULT NULL,
  `url_endpoint` varchar(128) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `recontar_premios` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ranking_premios
CREATE TABLE IF NOT EXISTS `ranking_premios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ranking` int(11) DEFAULT NULL,
  `posicao` int(11) DEFAULT NULL,
  `valor` float DEFAULT NULL,
  `porcentagem` float NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_ranking` (`id_ranking`),
  CONSTRAINT `ranking_premios_ibfk_1` FOREIGN KEY (`id_ranking`) REFERENCES `ranking` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ranking_produtos_meulook
CREATE TABLE IF NOT EXISTS `ranking_produtos_meulook` (
  `id_produto` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.ranking_vencedores_itens
CREATE TABLE IF NOT EXISTS `ranking_vencedores_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid_produto` varchar(80) DEFAULT NULL,
  `id_lancamento_pendente` int(11) DEFAULT NULL,
  `id_lancamento` int(11) DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_lancamento_pendente` (`id_lancamento_pendente`),
  KEY `id_lancamento` (`id_lancamento`),
  CONSTRAINT `ranking_vencedores_itens_ibfk_1` FOREIGN KEY (`id_lancamento_pendente`) REFERENCES `lancamento_financeiro_pendente` (`id`),
  CONSTRAINT `ranking_vencedores_itens_ibfk_2` FOREIGN KEY (`id_lancamento`) REFERENCES `lancamento_financeiro` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=141502 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.reembolso
CREATE TABLE IF NOT EXISTS `reembolso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_recebedor` int(11) NOT NULL,
  `id_pagador` int(11) NOT NULL,
  `conta` int(11) DEFAULT NULL,
  `id_lancamento_origem` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `situacao` varchar(3) NOT NULL DEFAULT 'A' COMMENT 'A - ABERTO; P-PAGO',
  `log_conta` text DEFAULT NULL,
  `data_emissao` datetime DEFAULT NULL,
  `data_pagamento` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_atendimento` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.representantes
CREATE TABLE IF NOT EXISTS `representantes` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(500) DEFAULT NULL,
  `id_colaborador` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.reputacao_fornecedores
CREATE TABLE IF NOT EXISTS `reputacao_fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) DEFAULT NULL,
  `vendas_totais` int(11) NOT NULL DEFAULT 0,
  `vendas_entregues` int(11) NOT NULL DEFAULT 0,
  `vendas_canceladas_totais` int(11) NOT NULL DEFAULT 0,
  `taxa_cancelamento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `media_envio` int(11) DEFAULT NULL,
  `vendas_canceladas_recentes` int(11) DEFAULT 0,
  `valor_vendido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reputacao` enum('RUIM','REGULAR','EXCELENTE','MELHOR_FABRICANTE') DEFAULT 'RUIM' COMMENT 'ATENÇÃO: SE FOR EDITAR MANTER ENUM NA ORDEM DOS PIORES PROS MELHORES!!!!!',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_colaborador` (`id_colaborador`),
  KEY `idx_id_colaborador` (`id_colaborador`)
) ENGINE=InnoDB AUTO_INCREMENT=266421 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for function mobile_stock.retornar_idfornecedor_produto
DELIMITER //
CREATE FUNCTION `retornar_idfornecedor_produto`(`ID_PRODUTO_` INT,
	`REGIME_` INT
) RETURNS int(11)
BEGIN
	DECLARE ID_FORNECEDOR_PRODUTO_ INT(10) DEFAULT 0;
	 DECLARE RETORNO_ INT(10) DEFAULT NULL;

	SELECT COALESCE(produtos.id_fornecedor,0) INTO ID_FORNECEDOR_PRODUTO_
	FROM produtos
	WHERE produtos.id = ID_PRODUTO_;


	SET RETORNO_ = ID_FORNECEDOR_PRODUTO_;
RETURN RETORNO_ ;
END//
DELIMITER ;

-- Dumping structure for function mobile_stock.retorna_dia_util
DELIMITER //
CREATE FUNCTION `retorna_dia_util`(`_DATA` DATE
) RETURNS date
    COMMENT 'Caso a data recebida por essa função não for um dia útil ela retornará o próximo dia útil.'
BEGIN
	DECLARE _DATA_ATUAL_LOOP DATE DEFAULT _DATA;

	WHILE (NOT VERIFICA_DIA_UTIL(_DATA_ATUAL_LOOP)) DO
		SET _DATA_ATUAL_LOOP = DATE_ADD(_DATA_ATUAL_LOOP, INTERVAL 1 DAY);
	END WHILE;

	RETURN _DATA_ATUAL_LOOP;
END//
DELIMITER ;

-- Dumping structure for function mobile_stock.saldo_cliente
DELIMITER //
CREATE FUNCTION `saldo_cliente`(`ID_CLIENTE_` INT(11)
) RETURNS float(10,2)
BEGIN

	DECLARE VALOR_SALDO_ DECIMAL(10,2) DEFAULT 0;
	DECLARE VALOR_PAGA_ DECIMAL(10,2) DEFAULT 0;
	DECLARE VALOR_RECEBER_ DECIMAL(10,2) DEFAULT 0;
	DECLARE VALOR_PRIORIDADE_ DECIMAL(10,2) DEFAULT 0;
	DECLARE VALOR_PENDENTE_ DECIMAL(10,2) DEFAULT 0;

   	SELECT
			COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0),
			COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0)

		INTO VALOR_PAGA_, VALOR_RECEBER_
		FROM lancamento_financeiro
		WHERE lancamento_financeiro.situacao = 1
			AND lancamento_financeiro.id_colaborador = ID_CLIENTE_;

	SET VALOR_SALDO_ = VALOR_RECEBER_ - VALOR_PAGA_ ;

	RETURN VALOR_SALDO_;

END//
DELIMITER ;

-- Dumping structure for function mobile_stock.saldo_cliente_bloqueado
DELIMITER //
CREATE FUNCTION `saldo_cliente_bloqueado`(`ID_CLIENTE_` INT
) RETURNS float
BEGIN
	DECLARE VALOR_PAGA_ DECIMAL(10,2) DEFAULT 0;
	DECLARE VALOR_RECEBER_ DECIMAL(10,2) DEFAULT 0;

	SELECT
		COALESCE(SUM(IF(lancamento_financeiro_pendente.tipo = 'R',lancamento_financeiro_pendente.valor,0)),0),
		COALESCE(SUM(IF(lancamento_financeiro_pendente.tipo = 'P',lancamento_financeiro_pendente.valor,0)),0)
	INTO VALOR_PAGA_, VALOR_RECEBER_
	FROM lancamento_financeiro_pendente
	WHERE lancamento_financeiro_pendente.situacao = 1
		AND lancamento_financeiro_pendente.id_colaborador = ID_CLIENTE_
		AND lancamento_financeiro_pendente.origem IN ('TR', 'PC', 'ES');

	RETURN VALOR_RECEBER_ - VALOR_PAGA_ ;
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.saldo_cliente_detalhe
DELIMITER //
CREATE PROCEDURE `saldo_cliente_detalhe`(
	IN `ID_CLIENTE_` INT
)
BEGIN

END//
DELIMITER ;

-- Dumping structure for function mobile_stock.saldo_cliente_emprestimo
DELIMITER //
CREATE FUNCTION `saldo_cliente_emprestimo`(`ID_FORNECEDOR_` INT
) RETURNS decimal(10,2)
    DETERMINISTIC
BEGIN
	DECLARE SALDO_ DECIMAL(10,3) DEFAULT 0.00;
	DECLARE ESTOQUE_ DECIMAL(10,3) DEFAULT 0.00;
	SELECT (estoque_grade_valor_dia.valor_estoque * (
		SELECT configuracoes.porcentagem_antecipacao
		FROM configuracoes
	) / 100) INTO ESTOQUE_ FROM estoque_grade_valor_dia where estoque_grade_valor_dia.id_fornecedor = ID_FORNECEDOR_;
	SELECT saldo_cliente(ID_FORNECEDOR_) INTO SALDO_;
	IF(ESTOQUE_ >= -SALDO_) THEN
		IF (SALDO_ < 0) THEN
			RETURN ESTOQUE_ + SALDO_;
		ELSE
			RETURN ESTOQUE_;
		END IF;
	ELSE
		RETURN 0.00;
	END IF;
END//
DELIMITER ;

-- Dumping structure for function mobile_stock.saldo_emprestimo
DELIMITER //
CREATE FUNCTION `saldo_emprestimo`(`ID_LANCAMENTO_` INT,
	`ID_CLIENTE_` INT
) RETURNS decimal(10,2)
    DETERMINISTIC
BEGIN
DECLARE VALOR_SALDO_ DECIMAL(10,2) DEFAULT 0.00;
  DECLARE VALOR_PAGA_ DECIMAL(10,2) DEFAULT 0.00;
  DECLARE VALOR_RECEBER_ DECIMAL(10,2) DEFAULT 0.00;
  DECLARE VALOR_R_ DECIMAL(10,2) DEFAULT 0.00;
  DECLARE VALOR_P_ DECIMAL(10,2) DEFAULT 0.00;
  DECLARE VALOR_A_ DECIMAL(10,2) DEFAULT 0.00;

  IF(NOT EXISTS(SELECT 1 FROM colaboradores WHERE colaboradores.id = 12))THEN
        signal sqlstate '45000' set MESSAGE_TEXT = 'CLiente invalido';
   END IF;

   IF(SELECT emprestimo.id_lancamento FROM emprestimo WHERE emprestimo.id_favorecido = ID_CLIENTE_ AND emprestimo.situacao='PA' AND emprestimo.id_lancamento = ID_LANCAMENTO_ ORDER BY id ASC LIMIT 1)THEN
		RETURN 0;
   END IF;

     IF(ID_LANCAMENTO_ = (SELECT emprestimo.id_lancamento FROM emprestimo WHERE emprestimo.id_favorecido = ID_CLIENTE_ AND emprestimo.situacao='PE' AND emprestimo.id_lancamento <= ID_LANCAMENTO_ ORDER BY id ASC LIMIT 1)) THEN
      SELECT
        saldo_cliente(ID_CLIENTE_) +
        (SELECT
          COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0) - COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0)
        FROM lancamento_financeiro
        WHERE lancamento_financeiro.id >= ID_LANCAMENTO_
        AND lancamento_financeiro.origem <> 'AU'
        AND lancamento_financeiro.faturamento_criado_pago = 'F'
        AND lancamento_financeiro.id_colaborador = ID_CLIENTE_)
      INTO VALOR_A_;
        IF(VALOR_A_ < 0) THEN
          SET VALOR_A_=0;
        END IF;
     ELSE
      SET VALOR_A_ = 0;
    END IF;


     SELECT
      COALESCE(SUM(IF(lancamento_financeiro.tipo = 'R',lancamento_financeiro.valor,0)),0),
      COALESCE(SUM(IF(lancamento_financeiro.tipo = 'P',lancamento_financeiro.valor,0)),0)

    INTO VALOR_PAGA_, VALOR_RECEBER_
    FROM lancamento_financeiro
    WHERE (lancamento_financeiro.id_lancamento_adiantamento = ID_LANCAMENTO_ OR lancamento_financeiro.id = ID_LANCAMENTO_)
      AND lancamento_financeiro.origem <> 'AU'
      AND lancamento_financeiro.id_colaborador = ID_CLIENTE_;

  SET VALOR_SALDO_ = VALOR_A_ + (VALOR_RECEBER_ - VALOR_PAGA_) ;

  IF(VALOR_SALDO_ >= 0)THEN
    RETURN VALOR_SALDO_;
  ELSE
    RETURN VALOR_SALDO_;
  END IF;




END//
DELIMITER ;

-- Dumping structure for table mobile_stock.saldo_troca
CREATE TABLE IF NOT EXISTS `saldo_troca` (
  `tipo` varchar(1) DEFAULT NULL,
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `data_emissao` date DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `pares` int(11) DEFAULT 0,
  `saldo_compra` decimal(10,2) DEFAULT 0.00,
  `troca` int(11) NOT NULL DEFAULT 0,
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `obs_troca` text DEFAULT NULL,
  `faturado` int(11) NOT NULL DEFAULT 0,
  `num_fatura` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.salva_log_alteracao_produtos
DELIMITER //
CREATE PROCEDURE `salva_log_alteracao_produtos`(
  IN _USUARIO_ INT,
  IN _ID_ INT,
  IN _NOME_CULUNA_ VARCHAR(50),
  IN _VALOR_ANTERIOR_ VARCHAR(50),
  IN _VALOR_NOVO_ VARCHAR(50) )
BEGIN

	IF ( _VALOR_NOVO_ <> _VALOR_ANTERIOR_ )THEN

    	INSERT INTO log_alteracao_produto
        ( id_colaborador, nome_coluna,linha_tabela,valor_anterior,valor_novo ) VALUES
        (
            _USUARIO_,
            _NOME_CULUNA_,
            _ID_,
            _VALOR_ANTERIOR_,
            _VALOR_NOVO_
        );
    END IF;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.situacao
CREATE TABLE IF NOT EXISTS `situacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.situacao_cheque
CREATE TABLE IF NOT EXISTS `situacao_cheque` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(30) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.situacao_lancamento
CREATE TABLE IF NOT EXISTS `situacao_lancamento` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.situacao_pedido
CREATE TABLE IF NOT EXISTS `situacao_pedido` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.status_pedido_item_corrigido_log
CREATE TABLE IF NOT EXISTS `status_pedido_item_corrigido_log` (
  `id_cliente` int(11) DEFAULT 0,
  `id_produto` int(11) DEFAULT NULL,
  `preco` double(22,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.status_pedido_item_log
CREATE TABLE IF NOT EXISTS `status_pedido_item_log` (
  `id_cliente` int(11) DEFAULT 0,
  `id_produto` int(11) DEFAULT NULL,
  `situacao` int(11) DEFAULT NULL,
  `situacao_new` varchar(32) DEFAULT NULL,
  `uuid` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.status_separacao
CREATE TABLE IF NOT EXISTS `status_separacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tabela_notas_comissao
CREATE TABLE IF NOT EXISTS `tabela_notas_comissao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `id_colaborador` int(11) DEFAULT NULL,
  `qtd_pares` varchar(5) DEFAULT NULL,
  `valor_fornecedor` float(10,2) NOT NULL DEFAULT 0.00,
  `porcentagem` float(10,4) NOT NULL DEFAULT 0.0000,
  `valor_nota` float(10,2) NOT NULL DEFAULT 0.00,
  `tipo` varchar(30) NOT NULL COMMENT 'cartao 1,2..., boleto ',
  `data` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=248725 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tags
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_unique` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=79166 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tags_tipos
CREATE TABLE IF NOT EXISTS `tags_tipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tag` int(11) NOT NULL,
  `tipo` varchar(45) NOT NULL COMMENT 'MA - material CO - cor',
  `ordem` tinyint(2) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `id_tag` (`id_tag`),
  CONSTRAINT `id_tag_fk_tags_tipos` FOREIGN KEY (`id_tag`) REFERENCES `tags` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tarefas
CREATE TABLE IF NOT EXISTS `tarefas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date DEFAULT NULL,
  `data_solucao` date DEFAULT NULL,
  `sistema` int(11) DEFAULT NULL,
  `modulo` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `solucao` text DEFAULT NULL,
  `prioridade` int(11) DEFAULT NULL,
  `resolvido` int(11) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.taxas
CREATE TABLE IF NOT EXISTS `taxas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_de_parcelas` int(11) NOT NULL DEFAULT 1,
  `mastercard` decimal(4,2) DEFAULT 0.00 COMMENT 'Depreciado não usar',
  `visa` decimal(4,2) DEFAULT 0.00 COMMENT 'Depreciado não usar',
  `elo` decimal(4,2) DEFAULT 0.00 COMMENT 'Depreciado não usar',
  `american_express` decimal(4,2) DEFAULT 0.00 COMMENT 'Depreciado não usar',
  `hiper` decimal(4,2) DEFAULT 0.00 COMMENT 'Depreciado não usar',
  `boleto` decimal(4,2) DEFAULT 0.00 COMMENT 'Depreciado não usar',
  `juros` decimal(4,2) NOT NULL DEFAULT 0.00 COMMENT 'Taxa Mobile',
  `pix` decimal(4,2) DEFAULT 0.00,
  `Juros_para_fornecedor` decimal(4,2) DEFAULT 60.00 COMMENT 'porcentagem repartida com fornecedor ',
  `Juros_fixo_mobile` decimal(4,2) DEFAULT 3.00 COMMENT 'Juros que não sera repartido com fornecedor',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tesouraria
CREATE TABLE IF NOT EXISTS `tesouraria` (
  `id` int(11) NOT NULL,
  `tipo` varchar(1) DEFAULT NULL,
  `documento` int(11) NOT NULL DEFAULT 0,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `usuario` int(11) NOT NULL DEFAULT 0,
  `responsavel` int(11) NOT NULL DEFAULT 0,
  `motivo` varchar(1000) DEFAULT NULL,
  `acerto` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_atendimento
CREATE TABLE IF NOT EXISTS `tipo_atendimento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) DEFAULT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_defeito
CREATE TABLE IF NOT EXISTS `tipo_defeito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_envio
CREATE TABLE IF NOT EXISTS `tipo_envio` (
  `id` int(11) NOT NULL DEFAULT 0,
  `nome` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_frete
CREATE TABLE IF NOT EXISTS `tipo_frete` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `mensagem` varchar(1000) DEFAULT NULL,
  `tipo_ponto` enum('PP','PM') NOT NULL DEFAULT 'PP' COMMENT 'PP - Ponto Parado, PM - Ponto Movel',
  `mensagem_cliente` varchar(500) CHARACTER SET utf8 COLLATE utf8_swedish_ci DEFAULT NULL,
  `mapa` text DEFAULT NULL,
  `foto` varchar(1000) DEFAULT NULL,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `previsao_entrega` varchar(50) DEFAULT NULL,
  `categoria` char(2) DEFAULT 'MS' COMMENT 'DEPRECIADO | MS - mobile stock ML - meu look',
  `percentual_comissao` decimal(10,2) NOT NULL DEFAULT 5.00 COMMENT 'depreciado - nao utilizar',
  `horario_de_funcionamento` varchar(300) NOT NULL DEFAULT '',
  `emitir_nota_fiscal` tinyint(1) NOT NULL DEFAULT 0,
  `id_usuario` int(11) DEFAULT NULL,
  `id_colaborador_ponto_coleta` int(11) DEFAULT NULL COMMENT 'ID do ponto de coleta responsável por esse tipo_frete ',
  PRIMARY KEY (`id`),
  KEY `responsavel_ponto` (`id_colaborador`)
) ENGINE=InnoDB AUTO_INCREMENT=2890 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_frete_grupos
CREATE TABLE IF NOT EXISTS `tipo_frete_grupos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_grupo` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `ativado` tinyint(1) NOT NULL DEFAULT 0,
  `dia_fechamento` enum('SEGUNDA','TERCA','QUARTA','QUINTA','SEXTA') NOT NULL DEFAULT 'SEGUNDA',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_frete_grupos_item
CREATE TABLE IF NOT EXISTS `tipo_frete_grupos_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_frete_grupos` int(11) NOT NULL,
  `id_tipo_frete` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_id_tipo_frete_grupos` (`id_tipo_frete_grupos`),
  CONSTRAINT `fk_tipo_frete_grupos_item_id` FOREIGN KEY (`id_tipo_frete_grupos`) REFERENCES `tipo_frete_grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4026 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_frete_log
CREATE TABLE IF NOT EXISTS `tipo_frete_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensagem` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1933 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_frete_rejeitados
CREATE TABLE IF NOT EXISTS `tipo_frete_rejeitados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.tipo_mensagem
CREATE TABLE IF NOT EXISTS `tipo_mensagem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(45) DEFAULT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras
CREATE TABLE IF NOT EXISTS `transacao_financeiras` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cod_transacao` varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('CR','LK','PE','CA','PA','ES') NOT NULL DEFAULT 'CR' COMMENT 'CR - Criado, LK - Link, PE - Pendente, CA - Cancelado, PA - Pago, ES - Estornado',
  `url_boleto` varchar(1000) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_credito` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor total utilizado em crédito',
  `valor_credito_bloqueado` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'valor utilizado credito bloqueado',
  `valor_acrescimo` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'valor de juros ou boleto',
  `valor_desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_comissao_fornecedor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_liquido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_itens` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_taxas` decimal(10,2) NOT NULL DEFAULT 0.00,
  `juros_pago_split` decimal(10,2) NOT NULL DEFAULT 0.00,
  `numero_transacao` varchar(100) DEFAULT NULL,
  `responsavel` varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '@deprecated',
  `pagador` int(11) NOT NULL DEFAULT 0,
  `metodo_pagamento` char(2) DEFAULT NULL COMMENT 'BL-Boleto CA-Cartao DE-dinheiro PX-pix',
  `metodos_pagamentos_disponiveis` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'CA,BL,CR,PX' COMMENT 'BL-Boleto CA-Cartao DE-dinheiro PX-pix CR- crédito',
  `numero_parcelas` int(2) NOT NULL DEFAULT 1,
  `id_usuario` char(10) NOT NULL DEFAULT '0',
  `id_usuario_pagamento` char(10) DEFAULT '0',
  `barcode` varchar(1000) DEFAULT NULL,
  `origem_transacao` char(2) NOT NULL DEFAULT 'MP' COMMENT 'MP-Mobile produto, MC-mobile credito, ED-Meuestoquedigial, ML-Meulook',
  `qrcode_pix` varchar(500) DEFAULT NULL,
  `qrcode_text_pix` varchar(1024) DEFAULT NULL,
  `emissor_transacao` varchar(25) DEFAULT NULL,
  `url_fatura` varchar(500) NOT NULL DEFAULT '',
  `uuid_requisicao_pagamento` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `IDX_REMOVE_TRANSACOES` (`pagador`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=741453 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_links
CREATE TABLE IF NOT EXISTS `transacao_financeiras_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `nome_consumidor_final` varchar(45) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_transacao` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_transacao_fk` (`id_transacao`),
  CONSTRAINT `id_transacao_fk_1` FOREIGN KEY (`id_transacao`) REFERENCES `transacao_financeiras` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=17173 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_logs
CREATE TABLE IF NOT EXISTS `transacao_financeiras_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_transacao` int(11) NOT NULL,
  `status` enum('CR','LK','PE','CA','PA','ES') NOT NULL DEFAULT 'CR' COMMENT 'CR - Criado, LK - Link, PE - Pendente, CA - Cancelado, PA - Pago, ES - Estornado',
  `metodo_pagamento` enum('BL','CA','CR','DE','PX') NOT NULL COMMENT 'BL-Boleto CA-Cartao DE-dinheiro PX-pix CR- crédito',
  `numero_parcelas` int(11) NOT NULL DEFAULT 1,
  `transacao_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`transacao_json`)),
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `IDX_transacao` (`id_transacao`)
) ENGINE=InnoDB AUTO_INCREMENT=1240635 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_logs_criacao
CREATE TABLE IF NOT EXISTS `transacao_financeiras_logs_criacao` (
  `id_transacao` int(11) NOT NULL,
  `id_colaborador` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  KEY `idx_fraudes` (`id_colaborador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_metadados
CREATE TABLE IF NOT EXISTS `transacao_financeiras_metadados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_transacao` int(10) unsigned NOT NULL,
  `chave` enum('ID_COLABORADOR_TIPO_FRETE','ENDERECO_CLIENTE_JSON','PRODUTOS_JSON','VALOR_FRETE','ID_PEDIDO','ID_UNICO','PRODUTOS_TROCA') NOT NULL,
  `valor` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transacao_chave` (`id_transacao`,`chave`),
  CONSTRAINT `transacao_financeiras_metadados_ibfk_1` FOREIGN KEY (`id_transacao`) REFERENCES `transacao_financeiras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1606192 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_produtos_itens
CREATE TABLE IF NOT EXISTS `transacao_financeiras_produtos_itens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_transacao` int(11) unsigned NOT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `id_fornecedor` int(11) DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `valor_custo_produto` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'depreciado, não utilizar.',
  `comissao_fornecedor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `uuid_produto` varchar(80) DEFAULT NULL,
  `tipo_item` enum('AC','AP','CC','CE','CL','CO','FR','PR','RF','CM_LOGISTICA','CM_PONTO_COLETA','CM_ENTREGA') CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL COMMENT 'PR- Produto FR-Frete AC-Adição de credito RF-Retorno Fornecedor AP-Acréscimo CNPJ CC-Comissão criador publicação CE-Comissão entregador CL-Comissão link CO-Comissão MED CM_LOGISTICA-Comissão logistica CM_PONTO_COLETA-Comissão ponto coleta CM_ENTREGA- Comissão tarifa de entrega',
  `observacao` varchar(150) CHARACTER SET utf8 COLLATE utf8_swedish_ci DEFAULT NULL,
  `id_responsavel_estoque` int(11) DEFAULT NULL,
  `uuid` varchar(80) GENERATED ALWAYS AS (if(`tipo_item` = 'PR',`uuid_produto`,NULL)) VIRTUAL COMMENT 'depreciado, usar uuid_produto',
  `momento_pagamento` enum('CARENCIA_ENTREGA','PAGAMENTO') DEFAULT NULL,
  `sigla_lancamento` varchar(255) DEFAULT NULL,
  `sigla_estorno` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `FK_transacao_financeiras_produtos_itens_transacao_financeiras` (`id_transacao`),
  KEY `idx_pesquisa_uuid` (`uuid_produto`,`tipo_item`) USING BTREE,
  CONSTRAINT `FK_transacao_financeiras_produtos_itens_transacao_financeiras` FOREIGN KEY (`id_transacao`) REFERENCES `transacao_financeiras` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5111864 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_produtos_trocas
CREATE TABLE IF NOT EXISTS `transacao_financeiras_produtos_trocas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_transacao` int(11) DEFAULT NULL,
  `uuid` varchar(100) DEFAULT NULL,
  `situacao` char(2) DEFAULT 'PE' COMMENT 'PE - PENDENTE - PA - PAGA',
  `id_nova_transacao` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuid_uniq` (`uuid`),
  KEY `IDX_id_nova_transacao` (`id_nova_transacao`)
) ENGINE=InnoDB AUTO_INCREMENT=62438 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_tentativas_pagamento
CREATE TABLE IF NOT EXISTS `transacao_financeiras_tentativas_pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_transacao` int(11) NOT NULL,
  `emissor_transacao` varchar(25) NOT NULL,
  `cod_transacao` varchar(80) DEFAULT NULL,
  `mensagem_erro` text DEFAULT NULL,
  `transacao_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  CONSTRAINT `transacao_json` CHECK (json_valid(`transacao_json`))
) ENGINE=InnoDB AUTO_INCREMENT=335919 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transacao_financeiras_tentativas_pagamento_bkp
CREATE TABLE IF NOT EXISTS `transacao_financeiras_tentativas_pagamento_bkp` (
  `id` int(11) NOT NULL,
  `id_transacao` int(11) NOT NULL,
  `emissor_transacao` varchar(25) NOT NULL,
  `cod_transacao` varchar(80) DEFAULT NULL,
  `mensagem_erro` text DEFAULT NULL,
  `transacao_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for procedure mobile_stock.transacao_financeira_calcula
DELIMITER //
CREATE PROCEDURE `transacao_financeira_calcula`(
	IN `ID_TRANSACAO_` INT,
	IN `MODO_PAGAMENTO_` CHAR(2),
	IN `PARCELAS_` INT(2),
	IN `USA_CREDITO_` INT(1)
)
BEGIN
  DECLARE VALOR_ACRESCIMO_ FLOAT(10,2) DEFAULT 0;
  DECLARE METODO_PAGAMENTO_ CHAR(2) DEFAULT 'DE';
  DECLARE ID_PAGADOR_ INT(5) DEFAULT 0;
  DECLARE VALOR_CREDITO_ FLOAT(10,2) DEFAULT 0;
  DECLARE VALOR_CREDITO_BLOQUEADO_ FLOAT(10,2) DEFAULT 0;
  DECLARE VALOR_ITENS_ FLOAT(10,2) DEFAULT 0;
  DECLARE VALOR_COMISSAO_FORNECEDOR_ FLOAT(10,2) DEFAULT 0;
  DECLARE JUROS_FORNECEDOR_ FLOAT(5,2) DEFAULT 0;
  DECLARE VALOR_DESCONTO_ FLOAT(10,2) DEFAULT 0;
  DECLARE TAXA_JUROS FLOAT(5,2) DEFAULT 0;
  DECLARE VALOR_BOLETO FLOAT(5,2) DEFAULT 0;
  DECLARE VALOR_MINIMO_TAXA_ DECIMAL(10,2) DEFAULT COALESCE((SELECT configuracoes.valor_min_cobra_taxa_boleto FROM configuracoes LIMIT 1),200);
  DECLARE ORIGEM_TRANSACAO_ CHAR(2) DEFAULT NULL;
  DECLARE NUMERO_MAX_PARCELA_ INT(2) DEFAULT 0;
  DECLARE _METODOS_PAGAMENTO_DEFAULT VARCHAR(30) DEFAULT NULL;

  IF(MODO_PAGAMENTO_ NOT IN('BL','CA','DE','PX','CR'))THEN
    signal sqlstate '45000' set MESSAGE_TEXT = 'Modo de pagamento incorreto';
  ELSEIF(USA_CREDITO_ NOT IN(1,0))THEN
    signal sqlstate '45000' set MESSAGE_TEXT = 'Modo de pagamento incorreto';
  ELSEIF(NOT EXISTS(SELECT 1 FROM transacao_financeiras WHERE transacao_financeiras.id = ID_TRANSACAO_ AND transacao_financeiras.status IN ('LK', 'CR')))THEN
    signal sqlstate '45040' set MESSAGE_TEXT = 'Transacao nao existe ou nao pode ser mais calculada';
  END IF;

  SELECT
    COALESCE(SUM(transacao_financeiras_produtos_itens.preco),0),
    COALESCE(SUM(IF(transacao_financeiras_produtos_itens.tipo_item = 'PR', transacao_financeiras_produtos_itens.comissao_fornecedor, 0)),0),
    transacao_financeiras.pagador,
    transacao_financeiras.origem_transacao,
    COALESCE((SELECT IF(transacao_financeiras.origem_transacao = 'ED',configuracoes.num_parcela_limit_meuestoque,configuracoes.num_parcela_limit_mobile) FROM configuracoes LIMIT 1),3) numero_max_parcela,
	transacao_financeiras.metodos_pagamentos_disponiveis
    INTO VALOR_ITENS_ ,VALOR_COMISSAO_FORNECEDOR_,ID_PAGADOR_,ORIGEM_TRANSACAO_,NUMERO_MAX_PARCELA_,_METODOS_PAGAMENTO_DEFAULT
  FROM transacao_financeiras
    INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.id_transacao = transacao_financeiras.id
  WHERE transacao_financeiras.id = ID_TRANSACAO_;

  IF(PARCELAS_ > NUMERO_MAX_PARCELA_ )THEN
	 SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Numero de parcelas incorreto';
  END IF;

  SELECT COALESCE(saldo_cliente(ID_PAGADOR_),0) INTO VALOR_CREDITO_;
  IF(USA_CREDITO_ = 1)THEN

  	 SELECT IF(ORIGEM_TRANSACAO_ = 'ML', COALESCE(saldo_cliente_bloqueado(ID_PAGADOR_), 0), 0) INTO VALOR_CREDITO_BLOQUEADO_;

  	 IF(VALOR_CREDITO_BLOQUEADO_ < 0) THEN
  	 	SET VALOR_CREDITO_BLOQUEADO_ = 0;
  	 END IF;

  	 IF(VALOR_CREDITO_BLOQUEADO_ > VALOR_ITENS_) THEN
  	 	SET VALOR_CREDITO_BLOQUEADO_ = VALOR_ITENS_;
  	 END IF;

  	 IF(VALOR_CREDITO_ >= 0) THEN
	  	SET VALOR_CREDITO_ = VALOR_CREDITO_ + VALOR_CREDITO_BLOQUEADO_;
	 ELSE
	 	SET VALOR_CREDITO_BLOQUEADO_ = 0;
  	 END IF;

	 IF(VALOR_CREDITO_ > VALOR_ITENS_)THEN
	 	SET VALOR_CREDITO_  = VALOR_ITENS_;
    END IF;
  ELSEIF(VALOR_CREDITO_ >= 0) THEN
    SET VALOR_CREDITO_ = 0;
  ELSE
  	 SET VALOR_CREDITO_ = VALOR_CREDITO_;
  END IF;

  IF(VALOR_CREDITO_ = VALOR_ITENS_)THEN
    UPDATE transacao_financeiras
       SET transacao_financeiras.valor_credito = VALOR_CREDITO_,
         transacao_financeiras.valor_credito_bloqueado = VALOR_CREDITO_BLOQUEADO_,
         transacao_financeiras.valor_acrescimo = 0,
         transacao_financeiras.valor_comissao_fornecedor = VALOR_COMISSAO_FORNECEDOR_,
         transacao_financeiras.valor_itens = VALOR_ITENS_,
         transacao_financeiras.valor_taxas = 0,
         transacao_financeiras.juros_pago_split = 0,
         transacao_financeiras.metodo_pagamento = 'DE',
         transacao_financeiras.numero_parcelas = 1
     WHERE transacao_financeiras.id = ID_TRANSACAO_;
  ELSE
  	 SET MODO_PAGAMENTO_ = IF(INSTR(_METODOS_PAGAMENTO_DEFAULT, MODO_PAGAMENTO_), MODO_PAGAMENTO_, SUBSTR(_METODOS_PAGAMENTO_DEFAULT, 1, 2));

    SELECT ROUND(if(taxas.juros > taxas.Juros_fixo_mobile,taxas.juros - taxas.Juros_fixo_mobile,0) * (taxas.Juros_para_fornecedor / 100),2),
      taxas.juros,
      taxas.boleto
       INTO JUROS_FORNECEDOR_, TAXA_JUROS, VALOR_BOLETO
    FROM taxas WHERE taxas.numero_de_parcelas = PARCELAS_;

    SET VALOR_ACRESCIMO_ = CASE
                    WHEN MODO_PAGAMENTO_ = 'BL' THEN VALOR_BOLETO
                    WHEN MODO_PAGAMENTO_ ='CA' THEN (VALOR_ITENS_ - VALOR_CREDITO_)*(TAXA_JUROS/100)
                    ELSE 0
                    END;

		IF(MODO_PAGAMENTO_ = 'BL' AND (VALOR_ACRESCIMO_ + VALOR_ITENS_ - VALOR_ACRESCIMO_)>VALOR_MINIMO_TAXA_)THEN

			SET VALOR_DESCONTO_ = VALOR_ACRESCIMO_;
		END IF;

    UPDATE transacao_financeiras
       SET transacao_financeiras.valor_credito = VALOR_CREDITO_,
		 transacao_financeiras.valor_credito_bloqueado = VALOR_CREDITO_BLOQUEADO_,
         transacao_financeiras.valor_acrescimo = VALOR_ACRESCIMO_,
         transacao_financeiras.valor_comissao_fornecedor = VALOR_COMISSAO_FORNECEDOR_,
			transacao_financeiras.valor_itens = VALOR_ITENS_,
			transacao_financeiras.valor_desconto = VALOR_DESCONTO_,
         transacao_financeiras.valor_taxas = 0,
         transacao_financeiras.juros_pago_split = JUROS_FORNECEDOR_,
         transacao_financeiras.metodo_pagamento = MODO_PAGAMENTO_,
         transacao_financeiras.numero_parcelas = IF(MODO_PAGAMENTO_ = 'CA',PARCELAS_,1)
     WHERE transacao_financeiras.id = ID_TRANSACAO_;
  END IF;
END//
DELIMITER ;

-- Dumping structure for procedure mobile_stock.transacao_financeira_pagamento_credito_debito
DELIMITER //
CREATE PROCEDURE `transacao_financeira_pagamento_credito_debito`(
	IN ID_TRANSACAO_ INT
)
BEGIN
	DECLARE ID_CLIENTE_ INT(5) DEFAULT 0;
	DECLARE VALOR_CREDITO_ FLOAT(10,2) DEFAULT 0;
	DECLARE VALOR_CREDITO_BLOQUEADO FLOAT(10,2) DEFAULT 0;
	DECLARE ORIGEM_TRANSACAO_ VARCHAR(2) DEFAULT NULL;

	SELECT
		transacao_financeiras.valor_credito,
		transacao_financeiras.valor_credito_bloqueado,
		transacao_financeiras.pagador,
		transacao_financeiras.origem_transacao
		INTO VALOR_CREDITO_,VALOR_CREDITO_BLOQUEADO,ID_CLIENTE_,ORIGEM_TRANSACAO_
	FROM transacao_financeiras
	WHERE transacao_financeiras.id = ID_TRANSACAO_;

	DELETE FROM lancamento_financeiro_pendente
	WHERE lancamento_financeiro_pendente.tipo = 'P'
		AND lancamento_financeiro_pendente.origem IN ('SC','CM','CC','CE','CL')
		AND lancamento_financeiro_pendente.transacao_origem = ID_TRANSACAO_;

	INSERT INTO lancamento_financeiro_pendente(
		sequencia,
		tipo,
		documento,
		situacao,
		origem,
		id_colaborador,
		data_emissao,
		valor,
		valor_total,
		id_usuario,
		observacao,
		transacao_origem,
		id_pagador,
		id_recebedor,
		data_vencimento,
		numero_documento
	)
	SELECT
		'1',
		'P',
		'14',
		'1',
        transacao_financeiras_produtos_itens.sigla_lancamento,
		transacao_financeiras_produtos_itens.id_fornecedor,
		NOW(),
		SUM(transacao_financeiras_produtos_itens.comissao_fornecedor),
		SUM(transacao_financeiras_produtos_itens.comissao_fornecedor),
		ID_CLIENTE_,
		CASE
			WHEN transacao_financeiras_produtos_itens.momento_pagamento = 'CARENCIA_ENTREGA'
				THEN COALESCE((SELECT CONCAT(colaboradores.razao_social, ' / ', COALESCE(colaboradores_enderecos.cidade, ''), ' - ', COALESCE(colaboradores_enderecos.uf, ''))
							   FROM colaboradores
                               LEFT JOIN colaboradores_enderecos ON
									colaboradores_enderecos.id_colaborador = colaboradores.id AND
									colaboradores_enderecos.eh_endereco_padrao = 1
							   WHERE colaboradores.id = ID_CLIENTE_),
							CONCAT('Comissão de influencer pedido #', ID_TRANSACAO_))
			ELSE 'Gerado como credito'
		END,
	  	ID_TRANSACAO_,
	  	'1',
	  	transacao_financeiras_produtos_itens.id_fornecedor,
	  	IF(transacao_financeiras_produtos_itens.momento_pagamento = 'CARENCIA_ENTREGA', NOW() + INTERVAL 15 DAY, NOW() + INTERVAL 1 DAY),
	  	IF(transacao_financeiras_produtos_itens.momento_pagamento = 'CARENCIA_ENTREGA', transacao_financeiras_produtos_itens.uuid_produto, '')
	FROM transacao_financeiras_produtos_itens
	WHERE
		transacao_financeiras_produtos_itens.id_transacao = ID_TRANSACAO_ AND
		transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL
	GROUP BY CASE
		WHEN transacao_financeiras_produtos_itens.momento_pagamento = 'CARENCIA_ENTREGA' THEN transacao_financeiras_produtos_itens.id
	END, transacao_financeiras_produtos_itens.id_fornecedor;

	IF((VALOR_CREDITO_ - VALOR_CREDITO_BLOQUEADO) > 0) THEN
		INSERT INTO lancamento_financeiro(sequencia,tipo,documento,situacao,origem,id_colaborador,data_emissao,valor,valor_total,id_usuario,observacao,
													 transacao_origem,id_pagador,id_recebedor, data_vencimento)
		SELECT '1', 'R', '15', '1', 'PC', ID_CLIENTE_, NOW(), VALOR_CREDITO_ - VALOR_CREDITO_BLOQUEADO, VALOR_CREDITO_ - VALOR_CREDITO_BLOQUEADO, '1', 'Pagamento de crédito',
				   ID_TRANSACAO_, ID_CLIENTE_, '1', NOW() + INTERVAL 1 DAY FROM DUAL
		WHERE NOT EXISTS(SELECT 1 FROM lancamento_financeiro
								WHERE lancamento_financeiro.transacao_origem = ID_TRANSACAO_
									AND lancamento_financeiro.id_colaborador = ID_CLIENTE_
									AND lancamento_financeiro.origem = 'PC'
									AND lancamento_financeiro.tipo = 'R');
	ELSEIF(VALOR_CREDITO_ < 0) THEN
		INSERT INTO lancamento_financeiro_pendente(sequencia,tipo,documento,situacao,origem,id_colaborador,data_emissao,valor,valor_total,id_usuario,observacao,transacao_origem,
																	id_pagador,id_recebedor,data_vencimento)
		SELECT '1', 'P', '15', 1, 'PD', ID_CLIENTE_, NOW(), VALOR_CREDITO_ * -1, VALOR_CREDITO_ * -1, ID_CLIENTE_, 'Gerado para pagar dívidas',
							ID_TRANSACAO_, 1, ID_CLIENTE_, NOW() + INTERVAL 1 DAY FROM DUAL
		WHERE NOT EXISTS(SELECT 1 FROM lancamento_financeiro_pendente
								WHERE lancamento_financeiro_pendente.transacao_origem = ID_TRANSACAO_
									AND lancamento_financeiro_pendente.id_colaborador = ID_CLIENTE_
									AND lancamento_financeiro_pendente.origem = 'PD'
									AND lancamento_financeiro_pendente.tipo = 'P');
	END IF;

	IF(VALOR_CREDITO_BLOQUEADO > 0) THEN
		INSERT INTO lancamento_financeiro_pendente(sequencia,tipo,documento,situacao,origem,id_colaborador,data_emissao,valor,valor_total,id_usuario,observacao,
														 transacao_origem,id_pagador,id_recebedor, data_vencimento)
			SELECT '1', 'R', '15', '1', 'PC', ID_CLIENTE_, NOW(), VALOR_CREDITO_BLOQUEADO, VALOR_CREDITO_BLOQUEADO, '1', 'Pagamento de crédito',
					   ID_TRANSACAO_, ID_CLIENTE_, '1', NOW() + INTERVAL 1 DAY FROM DUAL
			WHERE NOT EXISTS(SELECT 1 FROM lancamento_financeiro_pendente
									WHERE lancamento_financeiro_pendente.transacao_origem = ID_TRANSACAO_
										AND lancamento_financeiro_pendente.id_colaborador = ID_CLIENTE_
										AND lancamento_financeiro_pendente.origem = 'PC'
										AND lancamento_financeiro_pendente.tipo = 'R');

		INSERT INTO transacao_financeiras_produtos_trocas (id_cliente, id_transacao, uuid)
		SELECT ID_CLIENTE_, ID_TRANSACAO_, troca_pendente_agendamento.uuid
		FROM troca_pendente_agendamento
		WHERE troca_pendente_agendamento.tipo_agendamento = 'ML'
		AND troca_pendente_agendamento.id_cliente = ID_CLIENTE_
		AND NOT EXISTS(SELECT 1 FROM transacao_financeiras_produtos_trocas WHERE transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid);


	END IF;
END//
DELIMITER ;

-- Dumping structure for table mobile_stock.transacao_financeira_split
CREATE TABLE IF NOT EXISTS `transacao_financeira_split` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL DEFAULT 0 COMMENT 'ID Conta Bancaria Favorecida',
  `id_zoop` varchar(100) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_transacao` int(11) NOT NULL DEFAULT 0,
  `id_transferencia` int(11) DEFAULT NULL,
  `valor_acrescimo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `situacao` enum('NA','EX','CA') NOT NULL COMMENT 'NA - normal EX - estornado CA - cancelado',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_transferencia_01` (`id_transacao`,`id_colaborador`) USING BTREE,
  KEY `idx_transferencia_02` (`id_transferencia`)
) ENGINE=InnoDB AUTO_INCREMENT=340715 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transportadoras_horarios
CREATE TABLE IF NOT EXISTS `transportadoras_horarios` (
  `id_transportadora` int(11) NOT NULL AUTO_INCREMENT,
  `segunda` time DEFAULT '00:00:00',
  `terca` time DEFAULT '00:00:00',
  `quarta` time DEFAULT '00:00:00',
  `quinta` time DEFAULT '00:00:00',
  `sexta` time DEFAULT '00:00:00',
  `sabado` time DEFAULT '00:00:00',
  `domingo` time DEFAULT '00:00:00',
  PRIMARY KEY (`id_transportadora`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7828 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transportes
CREATE TABLE IF NOT EXISTS `transportes` (
  `id_colaborador` int(11) NOT NULL,
  `situacao` enum('PE','PR') DEFAULT 'PE' COMMENT 'PE - Pendente, PR - Aprovado',
  `link_rastreio` varchar(250) DEFAULT NULL,
  `tipo_transporte` enum('TRANSPORTADORA','ENTREGADOR') NOT NULL DEFAULT 'TRANSPORTADORA',
  `tipo_envio` tinyint(4) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.transportes_cidades
CREATE TABLE IF NOT EXISTS `transportes_cidades` (
  `id_raio` int(11) NOT NULL AUTO_INCREMENT,
  `id_colaborador` int(11) NOT NULL,
  `id_cidade` int(11) NOT NULL DEFAULT 0,
  `latitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `longitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `raio` float NOT NULL DEFAULT 0 COMMENT 'Em Metros',
  `valor` decimal(10,2) NOT NULL DEFAULT 3.00,
  `ativo` tinyint(1) NOT NULL DEFAULT 0 COMMENT '''0: Inativo | 1: Ativo''',
  `apelido` varchar(50) DEFAULT NULL,
  `dias_entregar_cliente` tinyint(4) DEFAULT NULL,
  `dias_margem_erro` tinyint(4) NOT NULL DEFAULT 5,
  `prazo_forcar_entrega` tinyint(4) NOT NULL DEFAULT 30,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_raio`),
  KEY `idx_cidade_responsavel` (`id_cidade`,`id_colaborador`)
) ENGINE=InnoDB AUTO_INCREMENT=1972 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.troca_fila_solicitacoes
CREATE TABLE IF NOT EXISTS `troca_fila_solicitacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `nome_tamanho` varchar(50) NOT NULL,
  `uuid_produto` varchar(50) NOT NULL,
  `situacao` enum('APROVADO','CANCELADO_PELO_CLIENTE','EM_DISPUTA','SOLICITACAO_PENDENTE','PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO','REPROVADO','REPROVADA_NA_DISPUTA','ITEM_TROCADO','REPROVADA_POR_FOTO','PENDENTE_FOTO') DEFAULT 'SOLICITACAO_PENDENTE',
  `descricao_defeito` varchar(300) NOT NULL,
  `motivo_reprovacao_seller` varchar(300) DEFAULT NULL,
  `motivo_reprovacao_disputa` varchar(300) DEFAULT NULL,
  `motivo_reprovacao_foto` varchar(300) DEFAULT NULL,
  `foto1` varchar(256) NOT NULL,
  `foto2` varchar(256) DEFAULT NULL,
  `foto3` varchar(256) DEFAULT NULL,
  `foto4` varchar(256) DEFAULT NULL,
  `foto5` varchar(256) DEFAULT NULL,
  `foto6` varchar(256) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_produto` (`uuid_produto`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_produto` (`id_produto`),
  CONSTRAINT `troca_fila_solicitacoes_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `colaboradores` (`id`),
  CONSTRAINT `troca_fila_solicitacoes_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4460 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.troca_pendente
CREATE TABLE IF NOT EXISTS `troca_pendente` (
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `tabela_preco` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.troca_pendente_agendamento
CREATE TABLE IF NOT EXISTS `troca_pendente_agendamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) DEFAULT NULL,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `preco` decimal(7,2) NOT NULL,
  `taxa` decimal(7,2) NOT NULL,
  `uuid` varchar(50) NOT NULL,
  `data_hora` timestamp NULL DEFAULT current_timestamp(),
  `data_vencimento` timestamp NOT NULL,
  `defeito` char(1) DEFAULT 'F',
  `tipo_agendamento` char(2) DEFAULT 'MS' COMMENT 'MS - Mobile Stock - ML - Meu Look',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_id_cliente` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=93542 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.troca_pendente_item
CREATE TABLE IF NOT EXISTS `troca_pendente_item` (
  `id_cliente` int(11) NOT NULL DEFAULT 0,
  `id_produto` int(11) NOT NULL DEFAULT 0,
  `nome_tamanho` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sequencia` int(11) NOT NULL DEFAULT 0,
  `tipo_cobranca` int(11) NOT NULL DEFAULT 0,
  `id_tabela` int(11) NOT NULL DEFAULT 0,
  `id_vendedor` int(11) NOT NULL DEFAULT 0,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_hora` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `uuid` varchar(80) DEFAULT NULL,
  `cod_barras` varchar(30) DEFAULT NULL,
  `defeito` int(11) NOT NULL DEFAULT 0,
  `confirmado` int(11) NOT NULL DEFAULT 0,
  `troca_pendente` int(11) NOT NULL DEFAULT 0,
  `descricao_defeito` text DEFAULT NULL,
  `autorizado` int(11) NOT NULL DEFAULT 0,
  KEY `idx_troca_pendente_item` (`id_cliente`,`data_hora`,`id_produto`),
  KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nivel_acesso` int(11) NOT NULL DEFAULT 0,
  `id_colaborador` int(11) NOT NULL DEFAULT 0,
  `bloqueado` int(11) NOT NULL DEFAULT 0,
  `online` int(11) NOT NULL DEFAULT 0,
  `acesso_nome` varchar(200) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `telefone` varchar(16) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `tipos` varchar(15) CHARACTER SET utf8 COLLATE utf8_swedish_ci DEFAULT 'U' COMMENT 'O - fotografo E - estoquista S - separador C - conferente V - vendedor F - fornecedor U - Cliente T - Transportador',
  `permissao` varchar(100) NOT NULL DEFAULT '10',
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dados_zoop` int(11) NOT NULL DEFAULT 0 COMMENT ' 0 - Nenhum dado Zoop Pendente 1 - Dados Zoop Pendentes',
  `password_pay` varchar(50) DEFAULT NULL,
  `token_temporario` varchar(50) DEFAULT NULL,
  `data_token_temporario` timestamp NULL DEFAULT current_timestamp(),
  `senha_temporaria` char(97) DEFAULT NULL,
  `data_senha_temporaria` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_colaborador` (`id_colaborador`),
  FULLTEXT KEY `idx_token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=77076 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.usuarios_tokens_maquinas
CREATE TABLE IF NOT EXISTS `usuarios_tokens_maquinas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) NOT NULL,
  `token` varchar(100) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `FK_usuarios` (`id_usuario`),
  FULLTEXT KEY `idx_token` (`token`),
  CONSTRAINT `FK_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=282 DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table mobile_stock.vales
CREATE TABLE IF NOT EXISTS `vales` (
  `id` int(11) NOT NULL DEFAULT 0,
  `id_representante` int(11) NOT NULL DEFAULT 0,
  `data_emissao` timestamp NULL DEFAULT NULL,
  `data_vencimento` timestamp NULL DEFAULT NULL,
  `pares` int(11) NOT NULL DEFAULT 0,
  `valor` int(11) NOT NULL DEFAULT 0,
  `id_faturamento` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Data exporting was unselected.

-- Dumping structure for function mobile_stock.VERIFICA_DIA_UTIL
DELIMITER //
CREATE FUNCTION `VERIFICA_DIA_UTIL`(DATA_ATUAL DATE) RETURNS tinyint(1)
    COMMENT 'Verifica se o dia é útil.'
BEGIN

    IF(
        DAYOFWEEK(DATA_ATUAL) IN (1,7) # Sábado e Domingo
        OR DATE_FORMAT(DATA_ATUAL, '%m-%d') IN (
            '01-01', # Confraternização Universal
            '04-21', # Tiradentes
            '05-01', # Dia do Trabalho
            '09-07', # Independência do Brasil
            '10-12', # Nossa Sr.a Aparecida - Padroeira do Brasil
            '11-02', # Finados
            '11-15', # Proclamação da República
            '12-25' # Natal
            )
        OR EXISTS(SELECT 1 FROM dias_nao_trabalhados WHERE dias_nao_trabalhados.data = DATA_ATUAL) # Dias não trabalhados cadastrados
    ) THEN
        RETURN FALSE;
    ELSE
        RETURN TRUE;
    END IF;
END//
DELIMITER ;

-- Dumping structure for trigger mobile_stock.acerto_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `acerto_delete` AFTER DELETE ON `acertos` FOR EACH ROW BEGIN

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.api_colaboradores_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `api_colaboradores_after_delete` AFTER DELETE ON `api_colaboradores` FOR EACH ROW BEGIN
            INSERT INTO api_colaboradores_inativos (id_colaborador,
                                                    id_zoop,
                                                    type,
                                                    taxpayer_id,
                                                    ein,
                                                    first_name,
                                                    id_iugu,
                                                    iugu_token_user,
                                                    iugu_token_teste,
                                                    iugu_token_live
                                                    )
                                                SELECT OLD.id_colaborador,
                                                    OLD.id_zoop,
                                                    OLD.type,
                                                    OLD.taxpayer_id,
                                                    OLD.ein,
                                                    OLD.first_name,
																	 OLD.id_iugu,
																	 OLD.iugu_token_user,
																	 OLD.iugu_token_teste,
																	 OLD.iugu_token_live
                                                        FROM DUAL
                                                            WHERE
                                                                NOT EXISTS(
                                                                            SELECT 1 FROM api_colaboradores_inativos
                                                                                WHERE api_colaboradores_inativos.id_zoop = OLD.id_zoop OR api_colaboradores_inativos.id_iugu = OLD.id_iugu
                                                                            );
        END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.api_colaboradores_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `api_colaboradores_before_insert` BEFORE INSERT ON `api_colaboradores` FOR EACH ROW BEGIN
	DELETE FROM api_colaboradores_inativos WHERE api_colaboradores_inativos.id_colaborador = NEW.id_colaborador AND api_colaboradores_inativos.id_zoop = NEW.id_zoop;
	IF(LENGTH(NEW.id_iugu) < 1 AND LENGTH(NEW.id_zoop) < 1)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Erro id zoop não pode ser null';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.api_colaboradores_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `api_colaboradores_before_update` BEFORE UPDATE ON `api_colaboradores` FOR EACH ROW BEGIN
	IF(EXISTS(SELECT 1 FROM api_colaboradores_inativos WHERE api_colaboradores_inativos.id_colaborador <> NEW.id_colaborador AND api_colaboradores_inativos.id_zoop = NEW.id_zoop ))THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Conta Zoop ja esta vinculada a outra pessoa';
	ELSE
		DELETE FROM api_colaboradores_inativos WHERE api_colaboradores_inativos.id_colaborador = NEW.id_colaborador AND api_colaboradores_inativos.id_zoop = NEW.id_zoop;
	END IF;

	IF(LENGTH(NEW.id_iugu) < 1 AND LENGTH(NEW.id_zoop) < 1)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Erro id zoop não pode ser null';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.atendimento_cliente_after_update_notificacao
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `atendimento_cliente_after_update_notificacao` AFTER UPDATE ON `atendimento_cliente` FOR EACH ROW BEGIN
	IF (NEW.situacao=2 AND OLD.situacao<>2) THEN
			INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem, icon) VALUES (NEW.id_cliente, NOW(), 'Atendimento!', "Atendimento respondido, clique <a style='color:red' href='/atendimento'><strong>aqui</strong></a> para ver a mensagem.",'C',1);
	ELSEIF (NEW.situacao=4 AND OLD.situacao<>4) THEN
			INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem, icon) VALUES (NEW.id_cliente, NOW(), 'Atendimento!', 'Existem clientes aguardando resposta na central de atendimentos', 'I',1);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_after_insert` AFTER INSERT ON `colaboradores` FOR EACH ROW BEGIN
	IF (NEW.tipo="T") THEN
		INSERT INTO transportadoras_horarios (id_transportadora) VALUES (NEW.id);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER colaboradores_after_update AFTER UPDATE ON colaboradores FOR EACH ROW BEGIN

	IF(NEW.id <> 1 AND NEW.bloqueado_repor_estoque = 'T' AND OLD.bloqueado_repor_estoque = 'F') THEN
		UPDATE estoque_grade
		SET estoque_grade.estoque = 0,
			 estoque_grade.tipo_movimentacao = 'X',
			 estoque_grade.descricao = CONCAT('Estoque zerado porque Seller ', estoque_grade.id_responsavel, ' teve muitas correções')
		WHERE estoque_grade.id_responsavel = NEW.id;

	END IF;

	SET @ID_USUARIO = (
		SELECT usuarios.id
		FROM usuarios
		WHERE usuarios.id_colaborador = NEW.id
		LIMIT 1
	);

	IF (@ID_USUARIO IS NOT NULL) THEN
		IF (OLD.telefone <> NEW.telefone) THEN
			UPDATE usuarios SET usuarios.telefone = NEW.telefone WHERE usuarios.id = @ID_USUARIO;
		END IF;
		IF (OLD.cnpj <> NEW.cnpj) THEN
			UPDATE usuarios SET usuarios.cnpj = NEW.cnpj WHERE usuarios.id = @ID_USUARIO;
		END IF;
		IF (OLD.email <> NEW.email) THEN
			UPDATE usuarios SET usuarios.email = NEW.email WHERE usuarios.id = @ID_USUARIO;
		END IF;
	END IF;

	INSERT INTO colaboradores_log (
		colaboradores_log.id_colaborador,
		mensagem
	) VALUES (
		NEW.id,
		JSON_OBJECT(
			'OLD_id', OLD.id,
			'NEW_id', NEW.id,
			'OLD_regime', OLD.regime,
			'NEW_regime', NEW.regime,
			'OLD_cnpj', OLD.cnpj,
			'NEW_cnpj', NEW.cnpj,
			'OLD_cpf', OLD.cpf,
			'NEW_cpf', NEW.cpf,
			'OLD_razao_social', OLD.razao_social,
			'NEW_razao_social', NEW.razao_social,
			'OLD_rg', OLD.rg,
			'NEW_rg', NEW.rg,
			'OLD_telefone', OLD.telefone,
			'NEW_telefone', NEW.telefone,
			'OLD_telefone2', OLD.telefone2,
			'NEW_telefone2', NEW.telefone2,
			'OLD_email', OLD.email,
			'NEW_email', NEW.email,
			'OLD_bloqueado', OLD.bloqueado,
			'NEW_bloqueado', NEW.bloqueado,
			'OLD_tipo', OLD.tipo,
			'NEW_tipo', NEW.tipo,
			'OLD_usuario', OLD.usuario,
			'NEW_usuario', NEW.usuario,
			'OLD_em_uso', OLD.em_uso,
			'NEW_em_uso', NEW.em_uso,
			'OLD_conta_principal', OLD.conta_principal,
			'NEW_conta_principal', NEW.conta_principal,
			'OLD_emite_nota', OLD.emite_nota,
			'NEW_emite_nota', NEW.emite_nota,
			'OLD_foto_perfil', OLD.foto_perfil,
			'NEW_foto_perfil', NEW.foto_perfil,
			'OLD_pagamento_bloqueado', OLD.pagamento_bloqueado,
			'NEW_pagamento_bloqueado', NEW.pagamento_bloqueado,
			'OLD_data_botao_atualiza_produtos_entrada', OLD.data_botao_atualiza_produtos_entrada,
			'NEW_data_botao_atualiza_produtos_entrada', NEW.data_botao_atualiza_produtos_entrada,
			'OLD_id_tipo_entrega_padrao', OLD.id_tipo_entrega_padrao,
			'NEW_id_tipo_entrega_padrao', NEW.id_tipo_entrega_padrao,
			'OLD_usuario_meulook', OLD.usuario_meulook,
			'NEW_usuario_meulook', NEW.usuario_meulook,
			'OLD_bloqueado_criar_look', OLD.bloqueado_criar_look,
			'NEW_bloqueado_criar_look', NEW.bloqueado_criar_look,
			'OLD_bloqueado_repor_estoque', OLD.bloqueado_repor_estoque,
			'NEW_bloqueado_repor_estoque', NEW.bloqueado_repor_estoque,
			'OLD_nome_instagram', OLD.nome_instagram,
			'NEW_nome_instagram', NEW.nome_instagram,
			'OLD_inscrito_receber_novidades', OLD.inscrito_receber_novidades,
			'NEW_inscrito_receber_novidades', NEW.inscrito_receber_novidades,
			'OLD_adiantamento_bloqueado', OLD.adiantamento_bloqueado,
			'NEW_adiantamento_bloqueado', NEW.adiantamento_bloqueado,
			'OLD_url_webhook', OLD.url_webhook,
			'NEW_url_webhook', NEW.url_webhook,
			'OLD_tipo_embalagem', OLD.tipo_embalagem,
			'NEW_tipo_embalagem', NEW.tipo_embalagem,
			'OLD_observacoes', OLD.observacoes,
			'NEW_observacoes', NEW.observacoes,
			'OLD_data_criacao', OLD.data_criacao,
			'NEW_data_criacao', NEW.data_criacao,
			'OLD_data_atualizacao', OLD.data_atualizacao,
			'NEW_data_atualizacao', NEW.data_atualizacao
		)
	);

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_before_insert` BEFORE INSERT ON `colaboradores` FOR EACH ROW BEGIN
	IF((NEW.regime = 2) AND (NEW.cpf IS NULL OR LENGTH(NEW.cpf) < 11)) then
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Para pessoa física, o campo CPF é obigatório';
	ELSEIF((NEW.regime = 1) AND (NEW.cnpj IS NULL OR LENGTH(NEW.cnpj) < 14)) then
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Para pessoa jurídica, o campo CNPJ é obrigatório';
	ELSEIF(NEW.regime <> 3 AND NEW.regime <> 1 AND NEW.regime <> 2) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'regime não identificado';
	END if;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER colaboradores_before_update BEFORE UPDATE ON colaboradores FOR EACH ROW BEGIN

	DECLARE m_nova_cidade VARCHAR(255);
	DECLARE m_novo_uf CHAR(2);

	IF(OLD.bloqueado_repor_estoque <> NEW.bloqueado_repor_estoque
		AND NEW.bloqueado_repor_estoque = 'F'
		AND NOT EXISTS(
			SELECT 1
			FROM usuarios
			WHERE usuarios.id_colaborador = NEW.id
				AND usuarios.permissao REGEXP '30'
		)
	)THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuário não é fornecedor para poder repor estoque';
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_enderecos_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER colaboradores_enderecos_after_delete AFTER DELETE ON colaboradores_enderecos FOR EACH ROW BEGIN
	INSERT INTO colaboradores_endereco_log (
		colaboradores_endereco_log.id_endereco,
        colaboradores_endereco_log.id_colaborador,
        colaboradores_endereco_log.endereco_novo
    ) VALUES (
		OLD.id,
        OLD.id_colaborador,
        JSON_OBJECT(
			'id_usuario', OLD.id_usuario,
			'ENDERECO_APAGADO', TRUE
        )
    );
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_enderecos_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER colaboradores_enderecos_after_insert AFTER INSERT ON colaboradores_enderecos FOR EACH ROW BEGIN
	INSERT INTO colaboradores_endereco_log (
		colaboradores_endereco_log.id_endereco,
        colaboradores_endereco_log.id_colaborador,
        colaboradores_endereco_log.endereco_novo
    ) VALUES (
		NEW.id,
        NEW.id_colaborador,
		JSON_OBJECT(
			'id_usuario', NEW.id_usuario,
            'apelido', NEW.apelido,
			'endereco', NEW.logradouro,
            'numero', NEW.numero,
            'complemento', NEW.complemento,
            'ponto_de_referencia', NEW.ponto_de_referencia,
			'bairro', NEW.bairro,
			'cidade', NEW.cidade,
			'uf', NEW.uf,
            'latitude', NEW.latitude,
            'longitude', NEW.longitude
        )
    );
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_enderecos_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER colaboradores_enderecos_after_update AFTER UPDATE ON colaboradores_enderecos FOR EACH ROW BEGIN
	INSERT INTO colaboradores_endereco_log (
		colaboradores_endereco_log.id_endereco,
        colaboradores_endereco_log.id_colaborador,
        colaboradores_endereco_log.endereco_novo
    ) VALUES (
		NEW.id,
        NEW.id_colaborador,
		JSON_OBJECT(
			'id_usuario', NEW.id_usuario,
            'apelido', NEW.apelido,
			'endereco', NEW.logradouro,
            'numero', NEW.numero,
            'complemento', NEW.complemento,
            'ponto_de_referencia', NEW.ponto_de_referencia,
			'bairro', NEW.bairro,
			'cidade', NEW.cidade,
			'uf', NEW.uf,
            'latitude', NEW.latitude,
            'longitude', NEW.longitude
        )
    );
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_prioridade_pagamento_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_prioridade_pagamento_after_insert` AFTER INSERT ON `colaboradores_prioridade_pagamento` FOR EACH ROW BEGIN
	IF(NEW.situacao <> 'EM') THEN
		INSERT INTO lancamento_financeiro (sequencia,tipo,documento,situacao,origem,numero_documento,id_colaborador,valor,valor_total,id_usuario,observacao,id_prioridade_saque)
		VALUES (1,'R',15,1,'PF',NEW.id_conta_bancaria,NEW.id_colaborador,NEW.valor_pagamento,NEW.valor_pagamento,1,(SELECT conta_bancaria_colaboradores.nome_titular FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = NEW.id_conta_bancaria), NEW.id);
	END IF;
	IF NEW.situacao ='EM' THEN
		INSERT INTO lancamento_financeiro (sequencia,tipo,documento,situacao,numero_documento,origem,id_colaborador,valor,valor_total,id_usuario,observacao,id_prioridade_saque)
		VALUES (1,'R',15,1,round(saldo_cliente(NEW.id_colaborador),2),'EM',NEW.id_colaborador,NEW.valor_pagamento,NEW.valor_pagamento,1,(SELECT conta_bancaria_colaboradores.nome_titular FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = NEW.id_conta_bancaria), NEW.id);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_prioridade_pagamento_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_prioridade_pagamento_after_update` AFTER UPDATE ON `colaboradores_prioridade_pagamento` FOR EACH ROW BEGIN
	IF (
		OLD.valor_pago <> NEW.valor_pago
		AND NEW.valor_pagamento = NEW.valor_pago
		AND EXISTS(
			SELECT 1
			FROM fila_transferencia_automatica
			WHERE fila_transferencia_automatica.id_transferencia = NEW.id
		)
	) THEN
		DELETE FROM fila_transferencia_automatica
		WHERE fila_transferencia_automatica.id_transferencia = NEW.id;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_prioridade_pagamento_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_prioridade_pagamento_before_delete` BEFORE DELETE ON `colaboradores_prioridade_pagamento` FOR EACH ROW BEGIN
	IF(
		(EXISTS(SELECT 1 FROM conta_bancaria_colaboradores WHERE OLD.id_conta_bancaria = conta_bancaria_colaboradores.id AND conta_bancaria_colaboradores.pagamento_bloqueado = 'F'))
			OR
		(EXISTS(SELECT 1 FROM transacao_financeira_split WHERE transacao_financeira_split.id_transferencia = OLD.id))
		)
		THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Item nao pode ser removido do sistema';
		ELSE
			INSERT INTO lancamento_financeiro (sequencia,tipo,documento,situacao,origem,id_colaborador,valor,valor_total,id_usuario,observacao,id_prioridade_saque)
				VALUES (1,'P',15,1,'EP',OLD.id_colaborador,OLD.valor_pagamento,OLD.valor_pagamento,1,"Saque deletado manualmente no sistema",
						OLD.id);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_prioridade_pagamento_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_prioridade_pagamento_before_insert` BEFORE INSERT ON `colaboradores_prioridade_pagamento` FOR EACH ROW BEGIN
	IF(EXISTS(SELECT 1 FROM configuracoes WHERE configuracoes.permite_transferencia = 'F')) THEN
	  signal sqlstate '45000' set MESSAGE_TEXT = 'Recurso bloqueado temporariamente';
  	END IF;


	IF(NEW.situacao <> 'EM' AND
		(
			SELECT round(SUM(colaboradores_prioridade_pagamento.valor_pagamento)-(SELECT configuracoes.valor_max_saque FROM configuracoes),2)
				FROM colaboradores_prioridade_pagamento
				WHERE colaboradores_prioridade_pagamento.id_colaborador = NEW.id_colaborador
				AND DATE(colaboradores_prioridade_pagamento.data_criacao) = DATE(NOW()) GROUP BY colaboradores_prioridade_pagamento.id_colaborador
		)
		> 0.00
	)THEN
		 signal sqlstate '45001' set MESSAGE_TEXT = 'Máximo de Saque atingido no dia';
  END IF;

	IF (NEW.situacao <> 'EM' AND (SELECT round(NEW.valor_pagamento - configuracoes.valor_min_saque,2) FROM configuracoes) < 0.00)
		THEN
		 	signal sqlstate '45000' set MESSAGE_TEXT = 'Saque Mínimo permitido é de R$5,00';
		ELSE
			IF (NEW.situacao = 'EM' AND NEW.valor_pagamento < 100) THEN
				signal sqlstate '45000' set MESSAGE_TEXT = 'Saque Mínimo permitido para emprestimos é de R$100,00';
			END IF;
  END IF;

  IF(EXISTS (SELECT 1 FROM colaboradores_prioridade_pagamento
      WHERE colaboradores_prioridade_pagamento.id_colaborador = NEW.id_colaborador
      AND round(abs(colaboradores_prioridade_pagamento.valor_pagamento - NEW.valor_pagamento),2) <= 0.05
      AND colaboradores_prioridade_pagamento.data_criacao >= DATE_SUB(NOW(), INTERVAL 5 MINUTE))
    ) THEN
    signal sqlstate '45000' set MESSAGE_TEXT = 'Solicitação de transferência/Saque duplicado';
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_prioridade_pagamento_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_prioridade_pagamento_before_update` BEFORE UPDATE ON `colaboradores_prioridade_pagamento` FOR EACH ROW BEGIN
	IF(OLD.valor_pagamento <> NEW.valor_pagamento) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Valor pagamento do saque não pode ser alterado';
	END IF;

	IF(NEW.valor_pago > NEW.valor_pagamento) THEN
		signal sqlstate '45020' set MESSAGE_TEXT = 'Valor pago do saque foi maior que o valor do saque';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.colaboradores_seguidores_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `colaboradores_seguidores_after_insert` AFTER INSERT ON `colaboradores_seguidores` FOR EACH ROW BEGIN
	INSERT INTO notificacoes (
			notificacoes.id_cliente,
			notificacoes.destino,
			notificacoes.titulo,
			notificacoes.imagem,
			notificacoes.mensagem,
			notificacoes.tipo_mensagem,
			notificacoes.data_evento
		)SELECT
			NEW.id_colaborador_seguindo,
			'ML',
			'Novo seguidor!',
			COALESCE(colaboradores.foto_perfil, 'http://adm.mobilestock.com.br/images/avatar-padrao-mobile.jpg'),
			CONCAT(
				'<b><a style=''text-decoration:underline;'' href=''/', COALESCE(colaboradores.usuario_meulook, ''), '''>', COALESCE(colaboradores.usuario_meulook, ''), '</a></b>',
				' Começou a te seguir.'),
				'C',
				NOW()
			FROM colaboradores
			WHERE colaboradores.id = NEW.id_colaborador;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.compras_itens_grade_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `compras_itens_grade_before_insert` BEFORE INSERT ON `compras_itens_grade` FOR EACH ROW BEGIN
	IF(NOT EXISTS(
		SELECT 1
		FROM produtos
		WHERE produtos.id = NEW.id_produto
			AND produtos.permitido_reposicao = 1
	)) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Esse produto não tem permissão para repor no Mobile Stock';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.emprestimo_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `emprestimo_after_insert` AFTER INSERT ON `emprestimo` FOR EACH ROW BEGIN
	INSERT INTO colaboradores_prioridade_pagamento(id_colaborador,id_conta_bancaria,valor_pagamento,situacao,usuario)
	VALUES(NEW.id_favorecido,NEW.id_conta_bancaria_favorecida ,NEW.valor_capital,"EM",1);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.emprestimo_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `emprestimo_before_update` BEFORE UPDATE ON `emprestimo` FOR EACH ROW BEGIN
		IF(NEW.situacao = 'PE' AND OLD.situacao = 'PA') THEN
			SET NEW.situacao = 'PA';
		END IF;
		IF(NEW.valor_atual >=0 AND OLD.situacao = 'PE')THEN
			INSERT INTO notificacoes(id_cliente, data_evento, titulo, mensagem, tipo_mensagem)
				VALUES(1,NOW(),'Adiantamento',CONCAT(OLD.id_favorecido,'-',OLD.id_lancamento, 'Este Adiantamento foi pago.'),'Z');
			SET NEW.situacao = 'PA';
		END IF;
		IF(NEW.valor_atual < 0 AND OLD.situacao = 'PA')THEN
			SET NEW.situacao = 'PE';
		END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER entregas_after_update AFTER UPDATE ON entregas FOR EACH ROW BEGIN
	INSERT INTO entregas_logs (
		entregas_logs.id_entrega,
		entregas_logs.id_usuario,
		entregas_logs.situacao_anterior,
		entregas_logs.situacao_nova,
		entregas_logs.mensagem
	) VALUES (
		OLD.id,
		OLD.id_usuario,
		OLD.situacao,
		NEW.situacao,
		JSON_OBJECT(
			'OLD_id', OLD.id,
			'NEW_id', NEW.id,
			'OLD_id_usuario', OLD.id_usuario,
			'NEW_id_usuario', NEW.id_usuario,
			'OLD_id_cliente', OLD.id_cliente,
			'NEW_id_cliente', NEW.id_cliente,
			'OLD_id_tipo_frete', OLD.id_tipo_frete,
			'NEW_id_tipo_frete', NEW.id_tipo_frete,
			'OLD_id_transporte', OLD.id_transporte,
			'NEW_id_transporte', NEW.id_transporte,
			'OLD_id_cidade', OLD.id_cidade,
			'NEW_id_cidade', NEW.id_cidade,
			'OLD_situacao', OLD.situacao,
			'NEW_situacao', NEW.situacao,
			'OLD_volumes', OLD.volumes,
			'NEW_volumes', NEW.volumes,
			'OLD_uuid_entrega', OLD.uuid_entrega,
			'NEW_uuid_entrega', NEW.uuid_entrega,
			'OLD_data_entrega', OLD.data_entrega,
			'NEW_data_entrega', NEW.data_entrega,
			'OLD_data_criacao', OLD.data_criacao,
			'NEW_data_criacao', NEW.data_criacao,
			'OLD_data_atualizacao', OLD.data_atualizacao,
			'NEW_data_atualizacao', NEW.data_atualizacao
		)
	);

    IF (NEW.situacao IN ('PT','EN')) THEN

		DELETE FROM entregas_fechadas_temp WHERE entregas_fechadas_temp.id_entrega = NEW.id;


    END IF;

	IF (
		OLD.situacao <> NEW.situacao
		AND OLD.situacao IN ('EX','AB')
		AND NEW.situacao IN ('EX','PT','EN')
	) THEN
		DELETE FROM acompanhamento_temp
        WHERE
            acompanhamento_temp.id_tipo_frete = NEW.id_tipo_frete
            AND acompanhamento_temp.id_destinatario = NEW.id_cliente
            AND IF(NEW.id_cidade > 0,
                acompanhamento_temp.id_cidade = NEW.id_cidade,
                TRUE
            );
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER entregas_before_insert BEFORE INSERT ON entregas FOR EACH ROW BEGIN

	IF(
		EXISTS(
			SELECT 1
			FROM entregas
			INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
			WHERE
				entregas.id_tipo_frete = NEW.id_tipo_frete
				AND IF(tipo_frete.tipo_ponto = 'PM',
					(
						entregas.id_cliente = NEW.id_cliente
						AND entregas.id_cidade = NEW.id_cidade
					),
					entregas.id_cliente = NEW.id_cliente
				)
				AND entregas.situacao = 'AB'
		)
	) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Sistema não permite que exista duas entregas criadas.';
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `entregas_before_update` BEFORE UPDATE ON `entregas` FOR EACH ROW BEGIN
    IF(NEW.situacao <> OLD.situacao AND NEW.situacao = 'AB' AND (
                                                                  SELECT 1
                                                                  FROM entregas
                                                                  WHERE entregas.situacao = 'AB'
                                                                    AND entregas.id_cliente = new.id_cliente
                                                                    AND entregas.id_tipo_frete = NEW.id_tipo_frete
                                                                )) THEN
		SIGNAL sqlstate '45000'
		set MESSAGE_TEXT = 'Não é possivel ter duas entregas com situação Aberta para o mesmo cliente';
    END IF;

    IF(OLD.situacao IN ('AB', 'EX') AND NEW.situacao = 'PT') THEN
        UPDATE entregas_faturamento_item
        SET entregas_faturamento_item.id_usuario = NEW.id_usuario
        WHERE entregas_faturamento_item.id_entrega = NEW.id;
    END IF;

    IF (NEW.situacao <> OLD.situacao AND NEW.situacao = 'EN') THEN
    	SET NEW.data_entrega = NOW();
    END IF;

    IF(NEW.data_criacao <> OLD.data_criacao) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Não foi possível atualizar a entrega. Notifique a equipe de TI.';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_devolucoes_item_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `entregas_devolucoes_item_after_delete` AFTER DELETE ON `entregas_devolucoes_item` FOR EACH ROW BEGIN
	 DECLARE ID_AGUADA_ESTOQUE INT(11) DEFAULT 0;

	 IF(OLD.tipo = 'NO' AND OLD.situacao = 'CO') THEN
		 	SELECT COALESCE(produtos_aguarda_entrada_estoque.id,0) INTO ID_AGUADA_ESTOQUE
		 	FROM produtos_aguarda_entrada_estoque
		 	WHERE produtos_aguarda_entrada_estoque.id_produto = OLD.id_produto
                AND produtos_aguarda_entrada_estoque.nome_tamanho = OLD.nome_tamanho
				AND produtos_aguarda_entrada_estoque.tipo_entrada =  'TR'
		 		AND produtos_aguarda_entrada_estoque.em_estoque =  'F'
		 	LIMIT 1;
		 	IF(ID_AGUADA_ESTOQUE > 0) THEN
			 	DELETE FROM produtos_aguarda_entrada_estoque WHERE produtos_aguarda_entrada_estoque.id = ID_AGUADA_ESTOQUE;
			 ELSE
				 signal sqlstate '45000' set MESSAGE_TEXT = 'Esse produto não existe na área de entrada de estoque';
			 END IF;
	 END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_devolucoes_item_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `entregas_devolucoes_item_after_insert` AFTER INSERT ON `entregas_devolucoes_item` FOR EACH ROW BEGIN
	IF(COALESCE(NEW.nome_tamanho, '') = '')THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'O tamanho está inválido';
	END IF;

	IF (
		EXISTS(
			SELECT 1
			FROM troca_pendente_agendamento
			WHERE troca_pendente_agendamento.uuid = NEW.uuid_produto
		) OR NOT EXISTS(
			SELECT 1
			FROM lancamento_financeiro
			WHERE lancamento_financeiro.origem = 'TR'
				AND lancamento_financeiro.numero_documento = NEW.uuid_produto
		) OR NOT EXISTS(
			SELECT 1
			FROM troca_pendente_item
			WHERE troca_pendente_item.uuid = NEW.uuid_produto
		)
	) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Essa devolução não pode ser aceita. Entre em contato com a equipe de T.I.';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_devolucoes_item_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER entregas_devolucoes_item_after_update AFTER UPDATE ON entregas_devolucoes_item FOR EACH ROW BEGIN
	DECLARE _USER_ INT(11) DEFAULT 0;
	IF(NEW.id_usuario <> OLD.id_usuario AND NEW.id_usuario <> 0)THEN
		UPDATE troca_pendente_item
		SET troca_pendente_item.id_vendedor = NEW.id_usuario
		WHERE troca_pendente_item.uuid = NEW.uuid_produto;
	END IF;

	IF(NEW.nome_tamanho <> OLD.nome_tamanho)THEN
	    signal sqlstate '45000' set MESSAGE_TEXT = 'O tamanho não pode ser modificado';
	END IF;

	IF(NEW.situacao <> OLD.situacao AND OLD.situacao = 'PE' AND NEW.situacao = 'CO' ) THEN

		SET _USER_ = (SELECT troca_pendente_item.id_vendedor FROM troca_pendente_item WHERE troca_pendente_item.uuid = NEW.uuid_produto );

		IF (NEW.tipo = 'NO') THEN
			INSERT INTO produtos_aguarda_entrada_estoque(
				produtos_aguarda_entrada_estoque.id_produto,
				produtos_aguarda_entrada_estoque.nome_tamanho,
				produtos_aguarda_entrada_estoque.tipo_entrada,
				produtos_aguarda_entrada_estoque.usuario,
				produtos_aguarda_entrada_estoque.identificao
			)
				VALUE (
				 	NEW.id_produto,
					NEW.nome_tamanho,
					'TR',
					_USER_,
					NEW.uuid_produto
				);
		END IF;

		IF (OLD.tipo = 'NO' AND NEW.tipo = 'DE') THEN
			IF(EXISTS(SELECT 1 FROM produtos_aguarda_entrada_estoque
						WHERE produtos_aguarda_entrada_estoque.identificao = NEW.uuid_produto
								AND produtos_aguarda_entrada_estoque.em_estoque = 'F'))
			 THEN
				 DELETE FROM produtos_aguarda_entrada_estoque WHERE produtos_aguarda_entrada_estoque.identificao = NEW.uuid_produto;
			 ELSE
				signal sqlstate '45000' set MESSAGE_TEXT = 'Nao pode ser alterado o campo defeito, produto já voltou para o estoque';
			 END IF;
		END IF;
	END IF;

	INSERT INTO entregas_log_devolucoes_item (uuid_produto, id_usuario, mensagem) VALUES (
		OLD.uuid_produto, NEW.id_usuario, JSON_OBJECT(
			'id_usuario_OLD', OLD.id_usuario,
            'id_usuario_NEW', NEW.id_usuario,
			'id_entrega_OLD', OLD.id_entrega,
            'id_entrega_NEW', NEW.id_entrega,
			'id_transacao_OLD', OLD.id_transacao,
            'id_transacao_NEW', NEW.id_transacao,
			'id_ponto_responsavel_OLD', OLD.id_ponto_responsavel,
            'id_ponto_responsavel_NEW', NEW.id_ponto_responsavel,
			'id_produto_OLD', OLD.id_produto,
			'nome_tamanho_OLD', OLD.nome_tamanho,
            'nome_tamanho_NEW', NEW.nome_tamanho,
			'situacao_OLD', OLD.situacao,
			'situacao_NEW', NEW.situacao,
			'tipo_OLD', OLD.tipo,
			'tipo_NEW', NEW.tipo,
			'situacao_envio_OLD', OLD.situacao_envio,
			'situacao_envio_NEW', NEW.situacao_envio,
			'origem_OLD', OLD.origem,
            'origem_NEW', NEW.origem,
            'pac_reverso_OLD', OLD.pac_reverso,
            'pac_reverso_NEW', NEW.pac_reverso,
            'data_atualizacao_OLD', OLD.data_atualizacao,
            'data_criacao_OLD', OLD.data_criacao,
			'id_responsavel_estoque_OLD', OLD.id_responsavel_estoque,
            'id_responsavel_estoque_NEW', NEW.id_responsavel_estoque
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_devolucoes_item_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `entregas_devolucoes_item_before_insert` BEFORE INSERT ON `entregas_devolucoes_item` FOR EACH ROW BEGIN
	DECLARE _TIPO_ VARCHAR(2) DEFAULT 'NO';
	DECLARE _USER_ INT(11) DEFAULT 0;

	SET _TIPO_ = COALESCE((
			SELECT
				CASE
					WHEN troca_pendente_item.defeito = 1 THEN 'DE'
					ELSE 'NO'
				END
			FROM troca_pendente_item
			WHERE troca_pendente_item.uuid = NEW.uuid_produto
		),'NO');

	SET NEW.tipo = _TIPO_;


	IF( NEW.situacao = 'CO' ) THEN

		SET _USER_ = (SELECT troca_pendente_item.id_vendedor FROM troca_pendente_item WHERE troca_pendente_item.uuid = NEW.uuid_produto );

		IF (NEW.tipo = 'NO') THEN
			INSERT INTO produtos_aguarda_entrada_estoque (
				produtos_aguarda_entrada_estoque.id_produto,
				produtos_aguarda_entrada_estoque.nome_tamanho,
				produtos_aguarda_entrada_estoque.tipo_entrada,
				produtos_aguarda_entrada_estoque.usuario,
				produtos_aguarda_entrada_estoque.identificao
			)
				VALUE (
				 	NEW.id_produto,
					NEW.nome_tamanho,
					'TR',
					_USER_,
					NEW.uuid_produto
				);
		END IF;

		IF ( NEW.tipo = 'DE') THEN
			IF(EXISTS(SELECT 1 FROM produtos_aguarda_entrada_estoque
						WHERE produtos_aguarda_entrada_estoque.identificao = NEW.uuid_produto
								AND produtos_aguarda_entrada_estoque.em_estoque = 'F'))
			 THEN
				 DELETE FROM produtos_aguarda_entrada_estoque WHERE produtos_aguarda_entrada_estoque.identificao = NEW.uuid_produto;
			 ELSE
				signal sqlstate '45000' set MESSAGE_TEXT = 'Nao pode ser alterado o campo defeito, produto já voltou para o estoque';
			 END IF;
		END IF;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_faturamento_item_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER entregas_faturamento_item_after_insert AFTER INSERT ON entregas_faturamento_item FOR EACH ROW BEGIN
	IF(COALESCE(NEW.nome_tamanho, '') = '') THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'O tamanho está inválido';
	END IF;

	UPDATE logistica_item
	SET
		logistica_item.id_entrega = NEW.id_entrega
	WHERE logistica_item.uuid_produto = NEW.uuid_produto;

    INSERT INTO entregas_log_faturamento_item
			(
				entregas_log_faturamento_item.id_usuario,
				entregas_log_faturamento_item.id_entregas_fi,
				entregas_log_faturamento_item.situacao_nova,
				entregas_log_faturamento_item.mensagem
			)
			VALUES (
				NEW.id_usuario,
				NEW.id,
				NEW.situacao,
				JSON_OBJECT(
					'NEW_id', NEW.id,
					'NEW_id_usuario', NEW.id_usuario,
					'NEW_id_entrega', NEW.id_entrega,
					'NEW_id_transacao', NEW.id_transacao,
					'NEW_id_cliente', NEW.id_cliente,
					'NEW_situacao', NEW.situacao,
					'NEW_origem', NEW.origem,
					'NEW_uuid_produto', NEW.uuid_produto,
					'NEW_id_produto', NEW.id_produto,
					'NEW_nome_tamanho', NEW.nome_tamanho,
					'NEW_nome_recebedor', NEW.nome_recebedor,
					'NEW_data_criacao', NEW.data_criacao,
					'NEW_data_atualizacao', NEW.data_atualizacao,
					'NEW_id_responsavel_estoque', NEW.id_responsavel_estoque
				)
			);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_faturamento_item_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER entregas_faturamento_item_after_update AFTER UPDATE ON entregas_faturamento_item FOR EACH ROW BEGIN
	DECLARE ID_COLABORADOR_TIPO_FRETE_, _ID_PONTO INT(11) DEFAULT 0;
    DECLARE TIPO_PONTO_ CHAR(2) DEFAULT NULL;

	IF(NEW.nome_tamanho <> OLD.nome_tamanho) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'O tamanho não pode ser modificado';
	END IF;

	IF( OLD.situacao <> NEW.situacao )THEN
		IF( OLD.id_usuario <> OLD.id_usuario AND NEW.id_usuario < 1 )THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'identifique se para alterar a situacao';
		END IF;
		IF( NEW.situacao = 'EN' )THEN

			UPDATE produtos
			SET produtos.data_ultima_entrega = NOW()
			WHERE produtos.id = NEW.id_produto;

			UPDATE lancamento_financeiro_pendente
				SET lancamento_financeiro_pendente.data_vencimento = CURRENT_DATE + INTERVAL 8 DAY
			WHERE lancamento_financeiro_pendente.tipo = 'P'
				AND lancamento_financeiro_pendente.transacao_origem = NEW.id_transacao
			   AND lancamento_financeiro_pendente.origem IN (SELECT DISTINCT transacao_financeiras_produtos_itens.sigla_lancamento
															FROM transacao_financeiras_produtos_itens
															WHERE transacao_financeiras_produtos_itens.uuid_produto = NEW.uuid_produto
															  AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL)
				AND lancamento_financeiro_pendente.numero_documento = NEW.uuid_produto;

            IF (NEW.origem = 'ML') THEN
                UPDATE transacao_financeiras
                   SET transacao_financeiras.valor_credito_bloqueado = 0
                 WHERE transacao_financeiras.valor_credito_bloqueado > 0
                   AND transacao_financeiras.id = NEW.id_transacao;
            END IF;

			IF (NOT EXISTS(
				SELECT 1
				FROM avaliacao_produtos
				WHERE
					avaliacao_produtos.id_cliente = NEW.id_cliente AND
					avaliacao_produtos.id_produto = NEW.id_produto
				LIMIT 1
			)) THEN INSERT INTO avaliacao_produtos (id_cliente, id_produto, origem, data_avaliacao) VALUES (NEW.id_cliente, NEW.id_produto, 'ML', NULL);
			END IF;

			SELECT
				tipo_frete.id_colaborador,
				tipo_frete.tipo_ponto,
				entregas.id_tipo_frete
			INTO ID_COLABORADOR_TIPO_FRETE_, TIPO_PONTO_, _ID_PONTO
			FROM entregas
			JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
			WHERE entregas.id = NEW.id_entrega;

			IF (EXISTS(SELECT 1
					   FROM logistica_item
					   WHERE logistica_item.id_cliente = NEW.id_cliente
					     AND logistica_item.id_transacao = NEW.id_transacao
						 AND logistica_item.id_produto = NEW.id_produto
						 AND logistica_item.nome_tamanho = NEW.nome_tamanho
						 AND logistica_item.id_responsavel_estoque = NEW.id_responsavel_estoque
						 AND logistica_item.uuid_produto = NEW.uuid_produto
						 AND logistica_item.id_colaborador_tipo_frete <> ID_COLABORADOR_TIPO_FRETE_)) THEN
				INSERT INTO entregas_fila_processo_alterar_entregador (
					entregas_fila_processo_alterar_entregador.uuid_produto,
					entregas_fila_processo_alterar_entregador.id_colaborador_tipo_frete
				) SELECT
					NEW.uuid_produto,
					ID_COLABORADOR_TIPO_FRETE_;
			END IF;

			IF (
				_ID_PONTO AND
				TIPO_PONTO_ = 'PP' AND
				NOT EXISTS(
					SELECT 1
					FROM avaliacao_tipo_frete
					WHERE
						avaliacao_tipo_frete.id_colaborador = NEW.id_cliente AND
						avaliacao_tipo_frete.id_tipo_frete = _ID_PONTO
					LIMIT 1
				)
			) THEN INSERT INTO avaliacao_tipo_frete (id_colaborador, id_tipo_frete) VALUES (NEW.id_cliente, _ID_PONTO);
			END IF;

		END IF;
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_faturamento_item_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER entregas_faturamento_item_before_insert BEFORE INSERT ON entregas_faturamento_item FOR EACH ROW
BEGIN

  IF(EXISTS(SELECT logistica_item.situacao FROM logistica_item WHERE logistica_item.uuid_produto = NEW.uuid_produto AND logistica_item.situacao = 'CO' AND logistica_item.id_entrega IS NOT NULL)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A situação do item não permite que ele seja adicionado à entrega';
  END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.entregas_faturamento_item_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `entregas_faturamento_item_before_update` BEFORE UPDATE ON `entregas_faturamento_item` FOR EACH ROW BEGIN

    DECLARE CLIENTE_NEGATIVO BOOLEAN DEFAULT FALSE;

    IF( OLD.situacao <> NEW.situacao) THEN

        IF( OLD.situacao IN ('AR','EN') AND NEW.situacao IN ('PE') OR OLD.situacao = 'EN') THEN
            signal sqlstate '45000' set MESSAGE_TEXT = 'voce não tem permissão para modificar para esta situacao';
        END IF;

        SET CLIENTE_NEGATIVO = saldo_cliente(NEW.id_cliente) < 0;

        IF(
            NOT EXISTS(
                SELECT 1
                FROM usuarios
                WHERE
                    usuarios.id = NEW.id_usuario
                    AND usuarios.permissao REGEXP '[[:<:]](5[0-7])[[:>:]]'
                    AND usuarios.id <> 2
                )
            AND NEW.situacao = 'EN'
            AND CLIENTE_NEGATIVO
        ) THEN
            signal sqlstate '45050' set MESSAGE_TEXT = 'Para entregar esse produto é necessário entregar todas as trocas sinalizadas';
        END IF;

       	IF ( NEW.situacao = 'EN' ) THEN
       		SET NEW.data_base_troca = NOW();
       		SET NEW.data_entrega = NOW();
       	END IF;

    END IF;

	IF(NEW.data_criacao <> OLD.data_criacao) THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = 'Não foi possível atualizar a entrega item. Notifique a equipe de TI.';
	END IF;

	IF (OLD.data_entrega IS NOT NULL AND OLD.data_entrega <> NEW.data_entrega) THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = 'Não é permitido entregar o produto duas vezes. Notifique a equipe de TI.';
	END IF;

    SET @JSON_TEMP = JSON_OBJECT(
            'NEW_id', NEW.id,
            'NEW_id_usuario', NEW.id_usuario,
            'NEW_id_entrega', NEW.id_entrega,
            'NEW_id_transacao', NEW.id_transacao,
            'NEW_id_cliente', NEW.id_cliente,
            'NEW_situacao', NEW.situacao,
            'NEW_origem', NEW.origem,
            'NEW_uuid_produto', NEW.uuid_produto,
            'NEW_id_produto', NEW.id_produto,
            'NEW_nome_tamanho', NEW.nome_tamanho,
            'NEW_nome_recebedor', NEW.nome_recebedor,
            'NEW_data_criacao', NEW.data_criacao,
            'NEW_data_atualizacao', NEW.data_atualizacao,
            'NEW_id_responsavel_estoque', NEW.id_responsavel_estoque
	);

	IF CLIENTE_NEGATIVO THEN
        SET @JSON_TEMP = JSON_SET(@JSON_TEMP, '$.CLIENTE_NEGATIVO', TRUE);
    END IF;

    -- @issue https://github.com/mobilestock/web/issues/3176
    INSERT INTO entregas_log_faturamento_item (
        entregas_log_faturamento_item.id_usuario,
        entregas_log_faturamento_item.id_entregas_fi,
        entregas_log_faturamento_item.situacao_anterior,
        entregas_log_faturamento_item.situacao_nova,
        entregas_log_faturamento_item.mensagem
    ) VALUES (
        NEW.id_usuario,
        NEW.id,
        OLD.situacao,
        NEW.situacao,
        @JSON_TEMP
    );

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.estoque_grade_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `estoque_grade_after_update` AFTER UPDATE ON `estoque_grade` FOR EACH ROW BEGIN
	IF (NEW.estoque <> OLD.estoque) THEN
		UPDATE produtos
		SET produtos.data_qualquer_alteracao = CURRENT_TIMESTAMP()
		WHERE produtos.id = NEW.id_produto;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.estoque_grade_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `estoque_grade_before_delete` BEFORE DELETE ON `estoque_grade` FOR EACH ROW BEGIN
	IF (OLD.estoque > 0
		OR OLD.vendido > 0
		OR EXISTS (
			SELECT 1
			FROM pedido_item
			WHERE pedido_item.id_produto = OLD.id_produto
				AND pedido_item.id_responsavel_estoque = OLD.id_responsavel
				AND pedido_item.nome_tamanho = OLD.nome_tamanho
				AND NOT pedido_item.situacao = '1'
		)
	) THEN
		SIGNAL SQLSTATE '45422' SET MESSAGE_TEXT = 'Não é possivel deletar grades com estoque ou reservados';
	END IF;

	INSERT INTO log_estoque_grade (
		log_estoque_grade.id_produto,
		log_estoque_grade.nome_tamanho,
		log_estoque_grade.id_responsavel_estoque,
		log_estoque_grade.tipo_movimentacao,
		log_estoque_grade.descricao
	) VALUES (
		OLD.id_produto,
		OLD.nome_tamanho,
		OLD.id_responsavel,
		'R',
		CONCAT('O tamanho: ', OLD.nome_tamanho, ', do produto: ', OLD.id_produto, ', do responsável: ', OLD.id_responsavel, ', no ID: ', OLD.id, ' foi removido!')
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.estoque_grade_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `estoque_grade_before_insert` BEFORE INSERT ON `estoque_grade` FOR EACH ROW BEGIN
	IF(COALESCE(NEW.nome_tamanho, '') = '')THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'O tamanho está inválido';
	END IF;

	IF(COALESCE(NEW.descricao, '') = '' OR NEW.tipo_movimentacao <> 'I')THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Não é permitido adicionar grade sem indicar o tipo de movimentação e descrição corretos!';
	END IF;

	INSERT INTO log_estoque_grade (
		log_estoque_grade.id_produto,
		log_estoque_grade.nome_tamanho,
		log_estoque_grade.id_responsavel_estoque,
		log_estoque_grade.tipo_movimentacao,
		log_estoque_grade.descricao
	) VALUES (
		NEW.id_produto,
		NEW.nome_tamanho,
		NEW.id_responsavel,
		NEW.tipo_movimentacao,
		NEW.descricao
	);

	SET NEW.tipo_movimentacao = '';
	SET NEW.descricao = '';
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.estoque_grade_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `estoque_grade_before_update` BEFORE UPDATE ON `estoque_grade` FOR EACH ROW BEGIN

   	DECLARE NUM_PARES_ADICIONADOS INT(11) DEFAULT 0;
	DECLARE NUM_PARES_REMOVIDOS INT(11) DEFAULT 0;
	DECLARE NUM_TOTAL_PARES INT(11);
	DECLARE CONTADOR INT(11) DEFAULT 0;
	DECLARE UUID_TEMP VARCHAR(56) DEFAULT '';
	DECLARE MENSAGEM LONGTEXT DEFAULT '';
	DECLARE TEMP_PEDIDO_ITEM_UUID VARCHAR(56) DEFAULT '';

	IF(OLD.id_responsavel <> NEW.id_responsavel) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Não é possível alterar o responsável dessa grade';
	END IF;

	IF(NEW.estoque <> OLD.estoque) THEN
	  SET NUM_TOTAL_PARES =
		CASE
			WHEN NEW.tipo_movimentacao = 'M' THEN OLD.estoque + OLD.vendido
			WHEN NEW.tipo_movimentacao = 'C' THEN OLD.estoque + NEW.vendido
			ELSE NEW.estoque + OLD.vendido
	   END;
		IF ((NEW.tipo_movimentacao = 'M') OR (NEW.tipo_movimentacao = 'E')) THEN
			IF (NEW.estoque > OLD.estoque) THEN
				SET NUM_PARES_ADICIONADOS = NEW.estoque - OLD.estoque;
				IF(NEW.tipo_movimentacao = 'M') THEN
					SET NEW.vendido = NEW.vendido - NUM_PARES_ADICIONADOS;
				END IF;

			ELSEIF ((NEW.estoque < OLD.estoque) AND (NEW.tipo_movimentacao = 'M'))  THEN
				SET NUM_PARES_REMOVIDOS = OLD.estoque - NEW.estoque;
				SET NEW.vendido = NEW.vendido + NUM_PARES_REMOVIDOS;
			ELSE
				SET MENSAGEM = 'Erro! forma de atualizacao do estoque esta incorreto nao permite retirada do estoque com o tipo E';
				signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
			END IF;
		ELSEIF ((NEW.tipo_movimentacao = 'S') AND (NEW.estoque < OLD.estoque)) THEN
			SET NUM_PARES_REMOVIDOS = OLD.estoque - NEW.estoque;
			SET NEW.vendido = NEW.vendido - NUM_PARES_REMOVIDOS;
			SET NEW.estoque = OLD.estoque;
			SET CONTADOR = 0;
			WHILE ((NEW.vendido < 0) AND (CONTADOR < NUM_PARES_REMOVIDOS)) DO
				SET NEW.estoque = NEW.estoque - 1;
				SET NEW.vendido = NEW.vendido + 1;
				SET CONTADOR = CONTADOR + 1;
			END WHILE;
		ELSEIF ((NEW.tipo_movimentacao = 'X') AND (NEW.estoque < OLD.estoque)) THEN
			IF (NEW.estoque < 0) THEN
				signal sqlstate '45000' set MESSAGE_TEXT = 'Erro! Estoque nao pode ficar negativo na movimentacao X';
			END IF;


		ELSEIF ((NEW.tipo_movimentacao = 'N') AND (NEW.estoque > OLD.estoque)) THEN
			SET NUM_PARES_ADICIONADOS = NEW.estoque - OLD.estoque;
			SET NEW.estoque = OLD.estoque;
			SET NEW.vendido = OLD.vendido + NUM_PARES_ADICIONADOS;
		ELSEIF ((NEW.tipo_movimentacao = 'C') AND (CHAR_LENGTH(NEW.descricao) > 0)) THEN
			SET MENSAGEM = CONCAT(MENSAGEM, 'Correcaoo manual');
		ELSE
			SET MENSAGEM = CONCAT('Nao e permitido alterar estoque sem indicar o tipo de movimentacao correta ou descricao. ',MENSAGEM);
			signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
		END IF;

	END IF;

    IF ((NEW.estoque < 0) OR (NEW.vendido < 0)) THEN
		SET MENSAGEM = CONCAT('Erro nao e permitido estoque negativo ',MENSAGEM);
		signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
    END IF;
	IF (NUM_TOTAL_PARES <> (NEW.estoque + NEW.vendido)) THEN
		SET MENSAGEM = CONCAT(MENSAGEM, 'Erro na conferencia de dados do estoque. ');
		signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
	END IF;

	INSERT INTO log_estoque_movimentacao(
		log_estoque_movimentacao.id_produto,
		log_estoque_movimentacao.nome_tamanho,
		log_estoque_movimentacao.oldEstoque,
		log_estoque_movimentacao.newEstoque,
		log_estoque_movimentacao.oldVendido,
		log_estoque_movimentacao.newVendido,
		log_estoque_movimentacao.tipo_movimentacao,
		log_estoque_movimentacao.descricao,
		log_estoque_movimentacao.id_responsavel_estoque
	) VALUES (
		NEW.id_produto,
		NEW.nome_tamanho,
		OLD.estoque,
		NEW.estoque,
		OLD.vendido,
		NEW.vendido,
		NEW.tipo_movimentacao,
		CONCAT(MENSAGEM, NEW.descricao),
		NEW.id_responsavel
	);

	SET NEW.tipo_movimentacao = '';
	SET NEW.descricao = '';
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.faq_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `faq_after_insert` AFTER INSERT ON `faq` FOR EACH ROW BEGIN
	IF(new.tipo="PR") THEN
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem, icon) VALUES (NEW.id_cliente, NOW(), 'Dúvida',
		"Olá. Você recebeu uma dúvida sobre seu produto. Acesse <a href='/duvidas-produto.php' style='color:red'><strong>AQUI</strong></a> para responder.", "F", 11);
	 END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.faq_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `faq_after_update` AFTER UPDATE ON `faq` FOR EACH ROW BEGIN
	IF(new.tipo="PR" AND old.resposta IS NULL AND new.resposta IS NOT NULL) THEN
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem, icon) VALUES (NEW.id_cliente, NOW(), 'Dúvida',
		CONCAT("Olá. Sua dúvida foi respondida. Acesse <a href='/produto/", old.id_produto, "' style='color:red'><strong>AQUI</strong></a> para ver a resposta."), "C", 11);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamentos_financeiros_recebiveis_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamentos_financeiros_recebiveis_before_insert` BEFORE INSERT ON `lancamentos_financeiros_recebiveis` FOR EACH ROW BEGIN
	IF(NEW.tipo = 'MI') THEN
		IF(NEW.valor > (SELECT configuracoes.valor_max_mobile_inteira_transferencia FROM configuracoes LIMIT 1)) THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'O mobile não pode inteirar esse valor no saque';
		END IF;

		IF(EXISTS(SELECT 1 FROM lancamentos_financeiros_recebiveis WHERE lancamentos_financeiros_recebiveis.id_zoop_recebivel = NEW.id_zoop_recebivel)) THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Inteiração duplicada';
		END IF;
	ELSEIF(NEW.tipo = 'TR') THEN
		SET NEW.id_zoop_recebivel = CONCAT(NEW.cod_transacao, NEW.id_recebedor, NEW.num_parcela);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamentos_financeiros_recebiveis_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamentos_financeiros_recebiveis_before_update` BEFORE UPDATE ON `lancamentos_financeiros_recebiveis` FOR EACH ROW BEGIN
	/*IF(PASSWORD(CONCAT(
		COALESCE(NEW.cod_transacao, ''),
		COALESCE(NEW.id_zoop_recebivel, ''),
		NEW.valor,
		NEW.id_transacao,
		NEW.id_lancamento,
		NEW.tipo
	)) <> PASSWORD(CONCAT(
		COALESCE(OLD.cod_transacao, ''),
		COALESCE(OLD.id_zoop_recebivel, ''),
		OLD.valor,
		OLD.id_transacao,
		OLD.id_lancamento,
		OLD.tipo
	))) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Não é possivel alterar um recebivel.';
	END IF;*/
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamento_financeiro_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamento_financeiro_after_insert` AFTER INSERT ON `lancamento_financeiro` FOR EACH ROW BEGIN
DECLARE ATUAL_ DECIMAL(10,2);
DECLARE VALOR_ DECIMAL(10,2);
	IF(NEW.situacao = 2 AND (NEW.cod_transacao IS NULL OR NEW.cod_transacao = '') AND NEW.origem = 'FA')THEN
		INSERT INTO lancamentos_financeiros_recebiveis (id_lancamento,situacao,id_recebedor,valor_pago,valor,data_vencimento)
		SELECT NEW.id, 'PA',1,NEW.valor_pago,NEW.valor_pago,NOW() FROM DUAL
		WHERE NOT EXISTS(SELECT 1 FROM lancamentos_financeiros_recebiveis
							  WHERE lancamentos_financeiros_recebiveis.id_lancamento = NEW.id
									AND lancamentos_financeiros_recebiveis.valor_pago = NEW.valor);
	END IF;

	IF(NEW.origem = 'CP' AND NEW.tipo='P') THEN
		INSERT INTO notificacoes(id_cliente,data_evento,titulo,mensagem,recebida, tipo_frete)VALUES(NEW.id_colaborador, NOW(), 'Correção','CORRIGIDO',0, NEW.pedido_origem);
	END IF;



	  IF(NEW.id_lancamento_adiantamento > 0)THEN
		SELECT  saldo_emprestimo(NEW.id_lancamento_adiantamento, NEW.id_colaborador) INTO VALOR_;
		INSERT INTO notificacoes(titulo,mensagem,id_cliente, tipo_frete) VALUES ('Lançamento!',CONCAT('Lancamento ',NEW.id,' Atualizou o emprestimo ',NEW.id_lancamento_adiantamento,' com o valor: ', NEW.valor), NEW.id_colaborador, NEW.id_lancamento_adiantamento);
		UPDATE emprestimo
			SET emprestimo.valor_atual = IF(VALOR_>=0, '0',VALOR_),  emprestimo.situacao = IF(VALOR_>=0, 'PA','PE')
				WHERE  emprestimo.id_lancamento = NEW.id_lancamento_adiantamento
					 AND LENGTH(emprestimo.id_lancamento) > 0 ;

	END IF;

	IF(NEW.origem <>'EM' AND NEW.origem <> 'AU')THEN
		UPDATE emprestimo SET emprestimo.valor_atual = saldo_emprestimo(emprestimo.id_lancamento, emprestimo.id_favorecido) WHERE situacao = 'PE' AND emprestimo.id_favorecido=NEW.id_colaborador;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamento_financeiro_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamento_financeiro_after_update` AFTER UPDATE ON `lancamento_financeiro` FOR EACH ROW BEGIN
   DECLARE ACAO VARCHAR(40) DEFAULT CONCAT('Alterou a situação para ',CASE NEW.SITUACAO WHEN 2 THEN 'pago' ELSE 'Em Aberto' END);

	IF NEW.situacao != OLD.situacao THEN
		INSERT INTO lancamento_financeiro_historico
		VALUES (NEW.id,0,ACAO, NOW(), COALESCE(NEW.id_usuario_edicao,0));
	END IF;




END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamento_financeiro_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamento_financeiro_before_delete` BEFORE DELETE ON `lancamento_financeiro` FOR EACH ROW BEGIN

   IF(OLD.valor_pago > 0 OR OLD.situacao = 2)
       THEN signal sqlstate '45000' set MESSAGE_TEXT = 'Lancamento não pode ser excluido, porque já está pago';
   END IF;
   IF(OLD.origem = 'AU')
       THEN signal sqlstate '45000' set MESSAGE_TEXT = 'Lancamento não pode ser excluido, porque ele e complemento de outro lancamento';
   END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamento_financeiro_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamento_financeiro_before_insert` BEFORE INSERT ON `lancamento_financeiro` FOR EACH ROW BEGIN
	DECLARE LANCAMENTO_ INT;
	DECLARE VALOR_ DECIMAL(10,2);
	DECLARE NOVO_ DECIMAL(10,2);
	DECLARE SITUACAO_ CHAR(2);

IF(NEW.valor_pago > 0 )THEN
		if(NEW.valor <> NEW.valor_pago)THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Valor pago nao pode ser diferente do valor do pagamento';
		END IF;
		SET NEW.situacao = 2;
		SET NEW.data_pagamento = NOW();
		SET NEW.faturamento_criado_pago = 'T';
	ELSEIF(NEW.valor = 0)THEN
		SET NEW.situacao = 2;
		SET NEW.data_pagamento = NOW();
		SET NEW.faturamento_criado_pago = 'T';
	END IF;

	IF(NEW.tipo = 'R')THEN
		SET NEW.id_pagador = NEW.id_colaborador;



		SET NEW.id_recebedor = 1;
	ELSE
		SET NEW.id_pagador = 1;
		SET NEW.id_recebedor = NEW.id_colaborador;
	END IF;

	SET NEW.data_emissao = NOW();

	IF(NEW.origem NOT IN ('ES', 'PC') AND EXISTS(SELECT 1 FROM transacao_financeiras WHERE transacao_financeiras.status <> 'PA' AND transacao_financeiras.id = NEW.transacao_origem)) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Tentativa de gerando lancamento de transacao cancelada, transacao';
	END IF;

	IF (NEW.origem IN ('TR', 'TF', 'TL') AND LENGTH(NEW.numero_documento) < 5) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Todo lançamento de troca deve ter uma referencia a um produto vendido.';
	END IF;

	IF((SELECT 1 FROM emprestimo WHERE emprestimo.id_favorecido =  NEW.id_colaborador AND emprestimo.situacao = 'PE'  AND LENGTH(emprestimo.id_lancamento) > 0 ORDER BY id ASC LIMIT 1)
			AND NEW.origem <>"EM" AND NEW.origem <>"JA" AND NEW.faturamento_criado_pago = 'F'
		) THEN
		SELECT emprestimo.id_lancamento,emprestimo.valor_atual,emprestimo.situacao  INTO LANCAMENTO_,VALOR_,SITUACAO_
			FROM emprestimo
			WHERE emprestimo.id_favorecido =  NEW.id_colaborador AND emprestimo.situacao = 'PE' AND LENGTH(emprestimo.id_lancamento) > 0 ORDER BY id ASC LIMIT 1;
		SET NEW.id_lancamento_adiantamento = LANCAMENTO_;


	END IF;



END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.lancamento_financeiro_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `lancamento_financeiro_before_update` BEFORE UPDATE ON `lancamento_financeiro` FOR EACH ROW BEGIN
		DECLARE VALOR_DIFERENCA_ DECIMAL(10,2) DEFAULT 0;
	DECLARE TIPO_LANCAMENTO_ CHAR(1) DEFAULT NULL;

	IF(OLD.origem = 'EM' AND NEW.numero_documento <> OLD.numero_documento)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Campo tipo nao pode ser vazio';
	END IF;
	IF(NEW.tipo NOT IN ('P','R'))THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Campo tipo nao pode ser vazio';
	END IF;
	IF(NEW.tipo <> OLD.tipo)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Tipo de lançamento nao pode ser alterado';
	END IF;
	IF(NEW.valor_pago < 0)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Valor pago nao pode ser negativo';
	END IF;
	IF(NEW.valor <> OLD.valor)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Valor do lancamento nao pode ser alterado';
	END IF;
	IF(NEW.valor_pago <> OLD.valor_pago AND OLD.valor_pago > 0)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Valor de pagamento não pode ser alterado';
	END IF;
	IF((OLD.valor_pago = 0) AND (NEW.valor_pago > 0))THEN
		SET NEW.situacao = 2;
		SET NEW.data_pagamento = NOW();
		IF(NEW.valor > NEW.valor_pago)THEN
			SET TIPO_LANCAMENTO_ = NEW.tipo;
			SET VALOR_DIFERENCA_ = NEW.valor - NEW.valor_pago;
		ELSEIF(NEW.valor < NEW.valor_pago)THEN
			SET TIPO_LANCAMENTO_ = if(NEW.tipo='P','R','P');
			SET VALOR_DIFERENCA_ = NEW.valor_pago - NEW.valor;
		ELSE
		 	SET TIPO_LANCAMENTO_ = '';
			SET VALOR_DIFERENCA_ = 0;
		END IF;

		IF(VALOR_DIFERENCA_ > 0) THEN
			INSERT INTO lancamento_financeiro_temp(tipo,
														 documento,
														 situacao,
														 origem,
														 id_colaborador,
														 data_emissao,
														 valor,
														 valor_total,
														 numero_documento,
														 id_usuario,
														 observacao,
														 nota_fiscal,
														 pedido_origem,
														 cod_transacao,
														 lancamento_origem)
			VALUE(TIPO_LANCAMENTO_,
					 NEW.documento,
					 1,
					 'AU',
					 NEW.id_colaborador,
					 NOW(),
					 VALOR_DIFERENCA_,
					 VALOR_DIFERENCA_,
					 NEW.numero_documento,
					 NEW.id_usuario_pag,
					 CONCAT('Gerado automaticamente a partir do pagamento do lancamento ',NEW.id),
					 NEW.nota_fiscal,
					 NEW.pedido_origem,
					 NEW.cod_transacao,
					 NEW.id);
		END IF;
	ELSEIF((OLD.valor_pago >0) AND (NEW.valor_pago = 0))THEN
		IF(LENGTH(NEW.cod_transacao) > 1)THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Lançamento de origem externa não pode ser retirado o pagamento';
		ELSEIF(EXISTS(SELECT 1 FROM lancamento_financeiro WHERE lancamento_financeiro.lancamento_origem = NEW.id AND lancamento_financeiro.situacao = 2)) THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Já foram pagos lançamentos gerados a partir do pagamento desse lançamentos';
		END IF;
		SET NEW.situacao = 1;
		SET NEW.data_pagamento = null;
	ELSEIF(NEW.valor = 0.00 AND NEW.valor_pago = 0.00 AND OLD.situacao = 1)THEN
		SET NEW.situacao = 2;
		SET NEW.data_pagamento = NOW();
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.logistica_item_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `logistica_item_after_update` AFTER UPDATE ON `logistica_item` FOR EACH ROW BEGIN
	DECLARE DESCRICAO_MOVIMENTACAO VARCHAR(255) DEFAULT '';
	DECLARE TIPO_MOVIMENTACAO CHAR(1) DEFAULT '';
	DECLARE QUANTIDADE_MOVIMENTACAO TINYINT DEFAULT 0;
	DECLARE EXISTE_NEGOCIACAO BOOLEAN DEFAULT FALSE;
	DECLARE FOI_CLIENTE BOOLEAN DEFAULT FALSE;

	IF (
		(
			OLD.situacao <> 'SE'
			AND NEW.situacao = 'SE'
		) OR (
			OLD.situacao <> 'RE'
			AND NEW.situacao = 'RE'
		)
	) THEN
		SET EXISTE_NEGOCIACAO = EXISTS(
			SELECT 1
			FROM negociacoes_produto_temp
			WHERE negociacoes_produto_temp.uuid_produto = NEW.uuid_produto
		);
		IF (NEW.situacao = 'RE') THEN
			SET FOI_CLIENTE = NEW.id_usuario NOT IN (
				2,
				(
					SELECT usuarios.id
					FROM usuarios
					WHERE usuarios.id_colaborador = NEW.id_responsavel_estoque
					LIMIT 1
				)
			);
		END IF;

		IF (NEW.situacao = 'SE') THEN
			-- Produto foi separado remove uma unidade do reservado
			SET TIPO_MOVIMENTACAO = 'S';
			SET QUANTIDADE_MOVIMENTACAO = -1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item separado. transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSEIF (NOT FOI_CLIENTE AND OLD.situacao = 'PE') THEN
			-- Produto que não havia sido separado foi cancelado pelo fornecedor
			-- ou pelo sistema remove uma unidade do reservado sem voltar para o estoque
			SET TIPO_MOVIMENTACAO = 'S';
			SET QUANTIDADE_MOVIMENTACAO = -1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item cancelado. transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSEIF (EXISTE_NEGOCIACAO) THEN
			-- Produto tinha uma negociação de substituição aberta foi cancelado
			-- remove uma unidade do reservado sem voltar para o estoque
			SET TIPO_MOVIMENTACAO = 'S';
			SET QUANTIDADE_MOVIMENTACAO = -1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item tinha negociação aberta e foi cancelado. transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSEIF (FOI_CLIENTE AND NOT EXISTE_NEGOCIACAO) THEN
			-- Cliente cancelou a compra remove uma unidade do reservado e volta para o estoque
			SET TIPO_MOVIMENTACAO = IF (OLD.situacao = 'SE', 'E', 'M');
			SET QUANTIDADE_MOVIMENTACAO = 1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item removido da logistica, transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSE
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
		END IF;

		UPDATE estoque_grade SET
			estoque_grade.estoque = estoque_grade.estoque + QUANTIDADE_MOVIMENTACAO,
			estoque_grade.tipo_movimentacao = TIPO_MOVIMENTACAO,
			estoque_grade.descricao = DESCRICAO_MOVIMENTACAO
		WHERE estoque_grade.id_produto = NEW.id_produto
			AND estoque_grade.nome_tamanho = NEW.nome_tamanho
			AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque;

		IF(ROW_COUNT() = 0) THEN
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
		END IF;

		IF (
			NOT FOI_CLIENTE
			AND NEW.situacao = 'RE'
			AND NEW.id_responsavel_estoque > 1
			AND EXISTS(
				SELECT 1
				FROM estoque_grade
				WHERE estoque_grade.id_produto = NEW.id_produto
					AND estoque_grade.nome_tamanho = NEW.nome_tamanho
					AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque
					AND estoque_grade.estoque > 0
			)
		) THEN
			UPDATE estoque_grade SET
				estoque_grade.estoque = 0,
				estoque_grade.tipo_movimentacao = 'X',
				estoque_grade.descricao = CONCAT(
					'Estoque zerado por cancelamento. transacao ',
					NEW.id_transacao,
					' uuid ',
					NEW.uuid_produto
				)
			WHERE estoque_grade.id_produto = NEW.id_produto
				AND estoque_grade.nome_tamanho = NEW.nome_tamanho
				AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque;

			IF(ROW_COUNT() = 0) THEN
				SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
			END IF;
		END IF;

		IF (EXISTE_NEGOCIACAO) THEN
			INSERT INTO negociacoes_produto_log (
				negociacoes_produto_log.uuid_produto,
				negociacoes_produto_log.mensagem,
				negociacoes_produto_log.situacao,
				negociacoes_produto_log.id_usuario
			) VALUES (
				NEW.uuid_produto,
				JSON_OBJECT(
					'produto_negociado', JSON_OBJECT(
						'id_produto', NEW.id_produto,
						'nome_tamanho', NEW.nome_tamanho
					),
					'produtos_oferecidos', (
						SELECT negociacoes_produto_temp.itens_oferecidos
						FROM negociacoes_produto_temp
						WHERE negociacoes_produto_temp.uuid_produto = NEW.uuid_produto
					),
					'produto_substituto', NULL
				),
				IF (NEW.situacao = 'SE', 'CANCELADA', 'RECUSADA'),
				NEW.id_usuario
			);

			DELETE FROM negociacoes_produto_temp
			WHERE negociacoes_produto_temp.uuid_produto = NEW.uuid_produto;

			IF (ROW_COUNT() = 0) THEN
				SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao cancelar negociacao, reporte a equipe de T.I.';
			END IF;
		END IF;
	END IF;

	IF(OLD.situacao <> NEW.situacao) THEN

		INSERT INTO logistica_item_data_alteracao (
			logistica_item_data_alteracao.id_linha,
			logistica_item_data_alteracao.uuid_produto,
			logistica_item_data_alteracao.situacao_anterior,
			logistica_item_data_alteracao.situacao_nova,
			logistica_item_data_alteracao.id_usuario
		) VALUES(
			NEW.id,
			NEW.uuid_produto,
			OLD.situacao,
			NEW.situacao,
			NEW.id_usuario
		);
	END IF;

	INSERT INTO logistica_item_logs (
		logistica_item_logs.uuid_produto,
		logistica_item_logs.mensagem
	) VALUES (
		NEW.uuid_produto,
		JSON_OBJECT(
			'id', NEW.id,
			'id_usuario', NEW.id_usuario,
			'id_cliente', NEW.id_cliente,
			'id_transacao', NEW.id_transacao,
			'id_produto', NEW.id_produto,
			'id_responsavel_estoque', NEW.id_responsavel_estoque,
			'id_colaborador_tipo_frete', NEW.id_colaborador_tipo_frete,
			'id_entrega', NEW.id_entrega,
			'nome_tamanho', NEW.nome_tamanho,
			'situacao', NEW.situacao,
			'preco', NEW.preco,
			'uuid_produto', NEW.uuid_produto,
			'data_atualizacao', NEW.data_atualizacao,
			'data_criacao', NEW.data_criacao,
			'observacao', NEW.observacao
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.logistica_item_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `logistica_item_before_delete` BEFORE DELETE ON `logistica_item` FOR EACH ROW BEGIN
	IF(OLD.situacao <> 'RE') THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Situação do item não permite remoção.';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.logistica_item_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `logistica_item_before_insert` BEFORE INSERT ON `logistica_item` FOR EACH ROW BEGIN
	IF (NEW.situacao <> 'PE' OR NEW.id_entrega IS NOT NULL) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Um item de logística não pode ser criado já movimentado';
	END IF;

	INSERT INTO logistica_item_logs (
		logistica_item_logs.uuid_produto,
		logistica_item_logs.mensagem
	) VALUES (
		NEW.uuid_produto,
		JSON_OBJECT(
			'id', NEW.id,
			'id_usuario', NEW.id_usuario,
			'id_cliente', NEW.id_cliente,
			'id_transacao', NEW.id_transacao,
			'id_produto', NEW.id_produto,
			'id_responsavel_estoque', NEW.id_responsavel_estoque,
			'id_colaborador_tipo_frete', NEW.id_colaborador_tipo_frete,
			'id_entrega', NEW.id_entrega,
			'nome_tamanho', NEW.nome_tamanho,
			'situacao', NEW.situacao,
			'preco', NEW.preco,
			'uuid_produto', NEW.uuid_produto,
			'data_atualizacao', NEW.data_atualizacao,
			'data_criacao', NEW.data_criacao,
			'observacao', NEW.observacao
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.logistica_item_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `logistica_item_before_update` BEFORE UPDATE ON `logistica_item` FOR EACH ROW BEGIN
   IF(OLD.situacao <> NEW.situacao AND ((OLD.situacao = 'PE' AND NEW.situacao NOT IN ('SE', 'RE')) OR
	 									(OLD.situacao = 'SE' AND NEW.situacao NOT IN('CO', 'RE')) OR
	 									(OLD.situacao = 'CO' AND NEW.situacao < 4) OR
	 									(OLD.situacao > 3))) THEN
        signal sqlstate '45000' set MESSAGE_TEXT = 'Item não pode mudar para essa situacao';
   END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.metas_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `metas_after_insert` AFTER INSERT ON `metas` FOR EACH ROW BEGIN
	SET @METAMENSAL = (SELECT meta_mensal_valor FROM configuracoes);
	if(new.valor=@METAMENSAL AND new.gerado=1) THEN
		call gera_lancamento_cashback('MT',new.id_cliente);
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem, icon) VALUES (NEW.id_cliente, NOW(), 'Você atingiu a meta!', CONCAT("Você atingiu a meta mensal e ganhou um cashback de R$20,00. Confira suas metas <a style='color:red;' href='metas'><strong>AQUI</strong></a>"), "C", 10);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.metas_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `metas_after_update` AFTER UPDATE ON `metas` FOR EACH ROW BEGIN
	SET @METAMENSAL = (SELECT meta_mensal_valor FROM configuracoes);
	IF(old.gerado=0 AND new.gerado=1 AND old.valor<@METAMENSAL AND new.valor=@METAMENSAL) THEN
		call gera_lancamento_cashback('MT',new.id_cliente);
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem, icon) VALUES (NEW.id_cliente, NOW(), 'Você atingiu a meta!', CONCAT("Você atingiu a meta mensal e ganhou um cashback de R$20,00. Confira suas metas <a style='color:red;' href='metas'><strong>AQUI</strong></a>"), "C", 10);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_after_delete` AFTER DELETE ON `pedido_item` FOR EACH ROW BEGIN
  IF(NOT EXISTS(SELECT 1
  				FROM transacao_financeiras_produtos_itens
				WHERE transacao_financeiras_produtos_itens.uuid_produto = OLD.uuid
				  AND transacao_financeiras_produtos_itens.tipo_item IN ('PR','RF'))) THEN
    DELETE FROM pedido_item_meu_look WHERE pedido_item_meu_look.uuid = OLD.uuid;
  END IF;

	INSERT INTO pedido_item_logs (
		pedido_item_logs.uuid_produto,
		pedido_item_logs.mensagem
	) VALUES (
		OLD.uuid,
		JSON_OBJECT(
			'id', OLD.id,
			'id_cliente', OLD.id_cliente,
			'id_produto', OLD.id_produto,
			'nome_tamanho', OLD.nome_tamanho,
			'preco', OLD.preco,
			'situacao', OLD.situacao,
			'tipo_adicao', OLD.tipo_adicao,
			'data_criacao', OLD.data_criacao,
			'data_atualizacao', OLD.data_atualizacao,
			'uuid', OLD.uuid,
			'id_responsavel_estoque', OLD.id_responsavel_estoque,
			'id_transacao', OLD.id_transacao,
			'observacao', OLD.observacao
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_after_insert` AFTER INSERT ON `pedido_item` FOR EACH ROW BEGIN
    INSERT INTO pedido_item_logs (
		pedido_item_logs.uuid_produto,
		pedido_item_logs.mensagem
	) VALUES (
		NEW.uuid,
		JSON_OBJECT(
			'id', NEW.id,
			'id_cliente', NEW.id_cliente,
			'id_produto', NEW.id_produto,
			'nome_tamanho', NEW.nome_tamanho,
			'preco', NEW.preco,
			'situacao', NEW.situacao,
			'tipo_adicao', NEW.tipo_adicao,
			'data_criacao', NEW.data_criacao,
			'data_atualizacao', NEW.data_atualizacao,
			'uuid', NEW.uuid,
			'id_responsavel_estoque', NEW.id_responsavel_estoque,
			'id_transacao', NEW.id_transacao,
			'observacao', NEW.observacao
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_after_update` AFTER UPDATE ON `pedido_item` FOR EACH ROW BEGIN
	IF (OLD.id_responsavel_estoque <> NEW.id_responsavel_estoque) THEN
		UPDATE pedido_item_meu_look
		SET pedido_item_meu_look.id_responsavel_estoque = NEW.id_responsavel_estoque
		WHERE pedido_item_meu_look.uuid = NEW.uuid;
	END IF;

	IF(OLD.situacao = 1 AND NEW.situacao = 2) THEN
		UPDATE estoque_grade
		  SET estoque_grade.estoque = estoque_grade.estoque - 1, estoque_grade.tipo_movimentacao = 'M',
		          estoque_grade.descricao = CONCAT('Foi aberto um pagamento, cliente',NEW.id_cliente,' uuid = ',NEW.uuid)
		WHERE estoque_grade.id_produto = NEW.id_produto
			AND estoque_grade.nome_tamanho = NEW.nome_tamanho
			AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque;

		IF(ROW_COUNT() = 0) THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
		END IF;
	ELSEIF (OLD.situacao <> 1 AND NEW.situacao = 1) THEN
		UPDATE estoque_grade
		  SET estoque_grade.estoque = estoque_grade.estoque + 1, estoque_grade.tipo_movimentacao = 'M',
		  estoque_grade.descricao = CONCAT('Produto reservado voltou para o estoque. uuid ', NEW.uuid)
		WHERE estoque_grade.id_produto = NEW.id_produto
			AND estoque_grade.nome_tamanho = NEW.nome_tamanho
			AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque;

		IF(ROW_COUNT() = 0) THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
		END IF;
	END IF;

	INSERT INTO pedido_item_logs (
		pedido_item_logs.uuid_produto,
		pedido_item_logs.mensagem
	) VALUES (
		NEW.uuid,
		JSON_OBJECT(
			'id', NEW.id,
			'id_cliente', NEW.id_cliente,
			'id_produto', NEW.id_produto,
			'nome_tamanho', NEW.nome_tamanho,
			'preco', NEW.preco,
			'situacao', NEW.situacao,
			'tipo_adicao', NEW.tipo_adicao,
			'data_criacao', NEW.data_criacao,
			'data_atualizacao', NEW.data_atualizacao,
			'uuid', NEW.uuid,
			'id_responsavel_estoque', NEW.id_responsavel_estoque,
			'id_transacao', NEW.id_transacao,
			'observacao', NEW.observacao
		)
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_before_delete` BEFORE DELETE ON `pedido_item` FOR EACH ROW BEGIN

	IF(OLD.situacao IN ('2', '3')) THEN
			SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Item esta em pagamento nao pode ser excluido';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_before_insert` BEFORE INSERT ON `pedido_item` FOR EACH ROW BEGIN
	SET NEW.id_responsavel_estoque = (
		SELECT
			estoque_grade.id_responsavel
		FROM estoque_grade
		WHERE estoque_grade.id_produto = NEW.id_produto
			AND estoque_grade.nome_tamanho = NEW.nome_tamanho
		ORDER BY estoque_grade.id_responsavel ASC
		LIMIT 1
	);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_before_update` BEFORE UPDATE ON `pedido_item` FOR EACH ROW BEGIN
 IF(OLD.uuid <> NEW.uuid)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'UUID não pode ser alterado';
	END IF;

	IF(OLD.premio = 0 AND NEW.preco <=0 )THEN
		SET NEW.preco = OLD.preco;
	END IF;

	IF(OLD.situacao <> 1 AND (
		OLD.id_responsavel_estoque <> NEW.id_responsavel_estoque OR
		OLD.nome_tamanho <> NEW.nome_tamanho OR
		OLD.id_produto <> NEW.id_produto
	)) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'É possível modificar apenas produtos em aberto';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pedido_item_meu_look_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pedido_item_meu_look_before_update` BEFORE UPDATE ON `pedido_item_meu_look` FOR EACH ROW BEGIN
	IF(OLD.preco <> NEW.preco AND EXISTS(SELECT 1 FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.uuid_produto = NEW.uuid)) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Não é possivel atualizar o preço desse item';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.pontos_coleta_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `pontos_coleta_after_delete` AFTER DELETE ON `pontos_coleta` FOR EACH ROW BEGIN
	UPDATE tipo_frete
	SET tipo_frete.id_colaborador_ponto_coleta = 32254
	WHERE tipo_frete.id_colaborador_ponto_coleta = OLD.id_colaborador
		AND tipo_frete.id_colaborador_ponto_coleta <> tipo_frete.id_colaborador;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_after_update` AFTER UPDATE ON `produtos` FOR EACH ROW BEGIN
		IF(NEW.especial <> OLD.especial OR NEW.id_fornecedor <> OLD.id_fornecedor OR NEW.bloqueado <> OLD.bloqueado) THEN
			UPDATE publicacoes
			SET publicacoes.id_colaborador = NEW.id_colaborador_publicador_padrao
			WHERE publicacoes.tipo_publicacao = 'AU' AND
				publicacoes.id IN (SELECT publicacoes_produtos.id_publicacao
									FROM publicacoes_produtos
									WHERE publicacoes_produtos.id_produto = NEW.id);
		END IF;

		IF(NEW.valor_custo_produto <> OLD.valor_custo_produto) THEN
			UPDATE pedido_item
			SET pedido_item.preco = NEW.valor_venda_ms
			WHERE pedido_item.id_produto = NEW.id AND pedido_item.premio = 0;

			SET @ID_PROMOCAO_TEMPORARIA = (
				SELECT catalogo_fixo.id
				FROM catalogo_fixo
				WHERE catalogo_fixo.id_produto = NEW.id
					AND catalogo_fixo.tipo = 'PROMOCAO_TEMPORARIA'
                LIMIT 1
			);

            SET @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA = COALESCE((
                SELECT JSON_VALUE(configuracoes.produtos_promocoes, '$.PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA')
                FROM configuracoes
                LIMIT 1
            ), 30);

			IF (@ID_PROMOCAO_TEMPORARIA IS NOT NULL) THEN
                IF (NEW.preco_promocao < @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA) THEN
                    UPDATE catalogo_fixo
                    SET catalogo_fixo.expira_em = NOW()
                    WHERE catalogo_fixo.id = @ID_PROMOCAO_TEMPORARIA;
                    IF (ROW_COUNT() = 0) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao deletar promoção temporária';
                    END IF;
                ELSE
                    UPDATE catalogo_fixo
                    SET catalogo_fixo.valor_venda_ms = NEW.valor_venda_ms,
                        catalogo_fixo.valor_venda_ml = NEW.valor_venda_ml
                    WHERE catalogo_fixo.id = @ID_PROMOCAO_TEMPORARIA;
                    IF (ROW_COUNT() = 0) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar promoção temporária';
                    END IF;
                END IF;
            ELSE
                IF (NEW.preco_promocao >= @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA) THEN
                    SET @REPUTACAO_FORNECEDOR = (
                        SELECT reputacao_fornecedores.reputacao
                        FROM reputacao_fornecedores
                        WHERE reputacao_fornecedores.id_colaborador = NEW.id_fornecedor
                        LIMIT 1
                    );

                    IF((@REPUTACAO_FORNECEDOR IS NULL OR @REPUTACAO_FORNECEDOR <> 'RUIM')
                        AND OLD.promocao = 0
                        AND NEW.promocao = 1
                    ) THEN
                        IF(NEW.preco_promocao >= @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA) THEN
                            INSERT INTO catalogo_fixo (
                                id_publicacao,
                                tipo,
                                expira_em,
                                id_publicacao_produto,
                                id_produto,
                                nome_produto,
                                valor_venda_ml,
                                valor_venda_ml_historico,
                                valor_venda_ms,
                                valor_venda_ms_historico,
                                possui_fulfillment,
                                foto_produto,
                                quantidade_acessos,
                                quantidade_vendida,
                                id_fornecedor
                            ) VALUES (
                                (
                                    SELECT publicacoes_produtos.id_publicacao
                                    FROM publicacoes_produtos
                                    WHERE publicacoes_produtos.id_produto = NEW.id
                                        AND publicacoes_produtos.situacao = 'CR'
                                    ORDER BY RAND(NEW.id)
                                    LIMIT 1
                                ),
                                'PROMOCAO_TEMPORARIA',
                                NOW() + INTERVAL COALESCE((
                                    SELECT JSON_VALUE(configuracoes.produtos_promocoes, '$.HORAS_DURACAO_PROMOCAO_TEMPORARIA')
                                    FROM configuracoes
                                    LIMIT 1
                                ), 24) HOUR,
                                (
                                    SELECT publicacoes_produtos.id
                                    FROM publicacoes_produtos
                                    WHERE publicacoes_produtos.id_produto = NEW.id
                                        AND publicacoes_produtos.situacao = 'CR'
                                    ORDER BY RAND(NEW.id)
                                    LIMIT 1
                                ),
                                NEW.id,
                                LOWER(IF(LENGTH(NEW.nome_comercial) > 0, NEW.nome_comercial, NEW.descricao)),
                                NEW.valor_venda_ml,
                                NEW.valor_venda_ml_historico,
                                NEW.valor_venda_ms,
                                NEW.valor_venda_ms_historico,
                                EXISTS(
                                    SELECT 1
                                    FROM estoque_grade
                                    WHERE estoque_grade.id_produto = NEW.id
                                        AND estoque_grade.id_responsavel = 1
                                    LIMIT 1
                                ),
                                (
                                    SELECT produtos_foto.caminho
                                    FROM produtos_foto
                                    WHERE produtos_foto.id = NEW.id
                                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                    LIMIT 1
                                ),
                                0,
                                (
                                    SELECT COUNT(pedido_item_meu_look.uuid)
                                    FROM pedido_item_meu_look
                                    WHERE pedido_item_meu_look.id_produto = NEW.id
                                        AND pedido_item_meu_look.situacao = 'PA'
                                ),
                                NEW.id_fornecedor
                            );
                        END IF;
                    END IF;
                END IF;
			END IF;
        END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_aguarda_entrada_estoque_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_aguarda_entrada_estoque_after_insert` AFTER INSERT ON `produtos_aguarda_entrada_estoque` FOR EACH ROW BEGIN
	IF(COALESCE(NEW.nome_tamanho, '') = '') THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'O tamanho está inválido';
 END IF;

 IF (NEW.tipo_entrada IN ('TR', 'CO')) THEN
 	IF (NOT EXISTS(
	 	SELECT 1
		FROM estoque_grade
		WHERE estoque_grade.id_produto = NEW.id_produto
			AND estoque_grade.id_responsavel = 1
			AND estoque_grade.nome_tamanho = NEW.nome_tamanho
	)) THEN
		INSERT INTO estoque_grade (
			estoque_grade.id_produto,
			estoque_grade.nome_tamanho,
			estoque_grade.id_responsavel,
			estoque_grade.sequencia,
			estoque_grade.tipo_movimentacao,
			estoque_grade.descricao
		) VALUES (
			NEW.id_produto,
			NEW.nome_tamanho,
			1,
			(
				SELECT produtos_grade.sequencia
				FROM produtos_grade
				WHERE produtos_grade.id_produto = NEW.id_produto
					AND produtos_grade.nome_tamanho = NEW.nome_tamanho
			),
			'I',
			CONCAT('O tamanho: ', NEW.nome_tamanho, ', do produto: ', NEW.id_produto, ', do responsável: ', 1, ' foi criado pelo usuário: ', NEW.usuario,'!')
		);
	END IF;
 END IF;

 IF (NEW.tipo_entrada = 'SP')THEN
		INSERT INTO produtos_separacao_fotos(
			produtos_separacao_fotos.id_produto,
			produtos_separacao_fotos.nome_tamanho,
			produtos_separacao_fotos.separado,
			produtos_separacao_fotos.tipo_separacao,
			produtos_separacao_fotos.data_emissao,
			produtos_separacao_fotos.id_produto_agu_estoque
		) VALUES (
			NEW.id_produto,
			NEW.nome_tamanho,
			'F',
			'P',
			NOW(),
			NEW.id
		);
 END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_aguarda_entrada_estoque_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_aguarda_entrada_estoque_after_update` AFTER UPDATE ON `produtos_aguarda_entrada_estoque` FOR EACH ROW BEGIN
	IF(NEW.nome_tamanho <> OLD.nome_tamanho) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'O tamanho não pode ser modificado';
	END IF;

	IF((OLD.em_estoque = 'F') AND (NEW.em_estoque = 'T')) THEN
		IF (NEW.tipo_entrada = 'CO') THEN
			UPDATE produtos SET produtos.data_entrada = NOW() WHERE produtos.id = NEW.id_produto;
		END IF;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_aguarda_entrada_estoque_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_aguarda_entrada_estoque_before_delete` BEFORE DELETE ON `produtos_aguarda_entrada_estoque` FOR EACH ROW BEGIN
	IF(EXISTS(SELECT 1 FROM produtos_separacao_fotos WHERE produtos_separacao_fotos.id_produto_agu_estoque = OLD.id AND produtos_separacao_fotos.separado = 'F')) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Item não pode ser removido porque ja esta em foto';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_aguarda_entrada_estoque_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_aguarda_entrada_estoque_before_insert` BEFORE INSERT ON `produtos_aguarda_entrada_estoque` FOR EACH ROW BEGIN
	DECLARE EXIST_FOTO INT(1) DEFAULT (EXISTS(SELECT 1 FROM produtos_foto WHERE produtos_foto.id = NEW.id_produto AND produtos_foto.tipo_foto = 'MD'));
	DECLARE LOCALIZACAO_TEMP VARCHAR(10) DEFAULT (SELECT produtos.localizacao FROM produtos WHERE produtos.id = NEW.id_produto);
	DECLARE TAMANHO_TEMP VARCHAR(50);
	DECLARE NUM_PARA_DIVIZAO INT(3) DEFAULT 0;
	DECLARE TAMANHO_PADRAO INT(3) DEFAULT 0;
	DECLARE NOME_TAMANHO VARCHAR(50);

/* IF((NEW.tipo_entrada = 'CO') AND
		(EXIST_FOTO = 0) AND
		(NOT EXISTS(SELECT 1 FROM produtos_separacao_fotos WHERE produtos_separacao_fotos.id_produto = NEW.id_produto)))THEN

		IF(COALESCE(NEW.tamanho_foto, '') <> '')THEN
			SET TAMANHO_TEMP = NEW.tamanho_foto;
		ELSEIF(NOT EXISTS(
			SELECT 1
			FROM produtos
			WHERE produtos.id = NEW.id_produto
				AND produtos.permitido_reposicao = 1
				AND EXISTS (
					SELECT 1
					FROM produtos_foto
					WHERE produtos_foto.id = produtos.id
				) AND EXISTS (
					SELECT 1
					FROM estoque_grade
					WHERE estoque_grade.id_produto = produtos.id
					AND estoque_grade.id_responsavel <> 1
				)
		)) THEN
			SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Erro produto nao tem foto padrao';
		END IF;

		IF(NEW.nome_tamanho = TAMANHO_TEMP) THEN
			SET NEW.tipo_entrada = 'SP';
		END IF;
	END IF;
*/
	SET NEW.usuario_resp = 2;
	SET NEW.localizacao = LOCALIZACAO_TEMP;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_before_insert` BEFORE INSERT ON `produtos` FOR EACH ROW BEGIN
	DECLARE
		VALOR_CALCULO_PORCENTAGEM_,
		COMISSAO_MS,
		COMISSAO_PONTO_COLETA,
		COMISSAO_ML
	DECIMAL(10,2) DEFAULT 0;

	SET NEW.proporcao_caixa = 1;
	SELECT
		configuracoes.porcentagem_comissao_ms,
		configuracoes.porcentagem_comissao_ponto_coleta,
        configuracoes.porcentagem_comissao_ml
		INTO
			COMISSAO_MS,
			COMISSAO_PONTO_COLETA,
			COMISSAO_ML
	FROM configuracoes
	LIMIT 1;

	SET NEW.porcentagem_comissao_ms = COMISSAO_MS,
		NEW.porcentagem_comissao_ponto_coleta = COMISSAO_PONTO_COLETA,
        NEW.porcentagem_comissao_ml = COMISSAO_ML;

	IF(NEW.valor_custo_produto > 0)THEN

		IF(NEW.preco_promocao > 0 AND NEW.preco_promocao <= 100 ) THEN
			SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto - ( NEW.valor_custo_produto * ( NEW.preco_promocao / 100 ) );
		ELSE
			SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto;
		END IF;

		SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( NEW.porcentagem_comissao_ms / ( 100 - NEW.porcentagem_comissao_ms ) ) ),
			 NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ml / 100, 2)
															 + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2),
			 NEW.valor_venda_sem_comissao = VALOR_CALCULO_PORCENTAGEM_;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_before_update` BEFORE UPDATE ON `produtos` FOR EACH ROW
BEGIN
	DECLARE VALOR_CALCULO_PORCENTAGEM_ DECIMAL(10,2) DEFAULT 0;

	IF( NEW.preco_promocao < 0 OR NEW.preco_promocao > 100 ) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'A porcentagem de promoção deve ter um valor valido de 0 a 100';
	END IF;

	IF(NEW.data_primeira_entrada IS NULL AND (OLD.data_entrada <> NEW.data_entrada ))THEN
		SET NEW.data_primeira_entrada = NOW();
	END IF;

	IF ( OLD.valor_custo_produto <> NEW.valor_custo_produto AND NEW.preco_promocao > 0 ) THEN
		SET NEW.preco_promocao = 0;
	END IF;

	SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto;

	IF ( NEW.preco_promocao <> OLD.preco_promocao) THEN
		IF ( NEW.preco_promocao = 0) THEN
			SET NEW.valor_custo_produto = NEW.valor_custo_produto_historico;
		ELSE
			SET NEW.promocao = 1;

            IF (OLD.promocao = 0) THEN
                IF (TRUE IN (
                    OLD.data_ultima_entrega IS NULL,
                    OLD.data_atualizou_valor_custo IS NOT NULL AND OLD.data_ultima_entrega < OLD.data_atualizou_valor_custo
                )) THEN
                    SET @MENSAGEM = (
                        SELECT CONCAT('O produto ', OLD.id, ' precisa de uma venda entregue antes de entrar em promoção')
                    );
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @MENSAGEM;
                END IF;

                SET @HORAS_ESPERA_REATIVAR_PROMOCAO = COALESCE((
                    SELECT JSON_VALUE(configuracoes.produtos_promocoes, '$.HORAS_ESPERA_REATIVAR_PROMOCAO')
                    FROM configuracoes
                    LIMIT 1
                ), 72);

                IF(NOW() - INTERVAL @HORAS_ESPERA_REATIVAR_PROMOCAO HOUR < OLD.data_atualizou_valor_custo) THEN
                    SET @MENSAGEM = (
                        SELECT CONCAT('O produto ', OLD.id, ' só poderá entrar em promoção após ', @HORAS_ESPERA_REATIVAR_PROMOCAO, ' horas')
                    );
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @MENSAGEM;
                END IF;
            END IF;

			if( OLD.preco_promocao >= 1 AND OLD.valor_custo_produto = NEW.valor_custo_produto ) THEN
				SET NEW.valor_custo_produto = NEW.valor_custo_produto_historico * ( ( 100 - NEW.preco_promocao ) / 100 );
				SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto_historico - ( NEW.valor_custo_produto_historico * ( NEW.preco_promocao / 100 ) );
			ELSE
				SET NEW.valor_custo_produto_historico = NEW.valor_custo_produto;
				SET NEW.valor_custo_produto = NEW.valor_custo_produto * ( ( 100 - NEW.preco_promocao ) / 100 );
				SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto ;

			END IF;

			IF( NEW.preco_promocao = 100 AND NEW.premio_pontos >= 100 ) THEN
				SET NEW.premio = 1;
			END IF;
		END IF;
	END IF;

	IF(NEW.porcentagem_comissao_ms >= 0 )THEN
		SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( NEW.porcentagem_comissao_ms / ( 100 - NEW.porcentagem_comissao_ms ) ) );
	END IF;

	SET NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ml / 100, 2)
														+ ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2),
        NEW.valor_venda_sem_comissao = VALOR_CALCULO_PORCENTAGEM_;


	IF(OLD.promocao = 0 AND NEW.promocao = 1) THEN
         SET NEW.valor_venda_ms_historico = OLD.valor_venda_ms;
         SET NEW.valor_venda_ml_historico = OLD.valor_venda_ml;
	END IF;

	IF(
		( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 )
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.premio = 0 )
		OR ( NEW.valor_custo_produto <= 0 AND NEW.promocao = 0 )
		OR ( NEW.valor_custo_produto <= 0 AND NEW.preco_promocao = 0 )
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.porcentagem_comissao_ms <= 0)
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.valor_venda_ms = 0 )
		OR ( NEW.valor_venda_ml_historico <= 0 AND NEW.valor_venda_ml <= 0 )
		)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Opa, produto com valor zerado não, por favor verifique os valores do produto';
	END IF;
	IF( NEW.preco_promocao = 100 AND NEW.premio_pontos < 100 )THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Opa, este produto não pode ser categorizado como premio, adicione um valor igual ou maior que 100 no campo premio_pontos';
	END IF;

	IF( ( NEW.preco_promocao > 0 AND NEW.preco_promocao <= 100) OR NEW.data_entrada <> OLD.data_entrada )THEN
		SET NEW.data_alteracao = NOW();
	END IF;

	IF(OLD.bloqueado = 0 AND
	   NEW.bloqueado = 1 AND
	   (EXISTS(SELECT 1 FROM estoque_grade
	           WHERE estoque_grade.id_produto = NEW.id AND
	           estoque_grade.estoque > 0))) THEN
	   signal sqlstate '45000' set MESSAGE_TEXT = 'Esse produto ainda não pode ser bloquado porque possui estoque';
	END IF;

    IF (OLD.valor_custo_produto <> NEW.valor_custo_produto) THEN
        SET NEW.data_atualizou_valor_custo = NOW();
    END IF;

	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'data_alteracao', OLD.data_alteracao, NEW.data_alteracao );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_custo_produto', OLD.valor_custo_produto, NEW.valor_custo_produto );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'preco_promocao', OLD.preco_promocao, NEW.preco_promocao );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'promocao', OLD.promocao, NEW.promocao );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'premio', OLD.premio, NEW.premio );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_venda_ms_historico', OLD.valor_venda_ms_historico, NEW.valor_venda_ms_historico );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_venda_ml_historico', OLD.valor_venda_ml_historico, NEW.valor_venda_ml_historico );
    CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_custo_produto_historico', OLD.valor_custo_produto_historico, NEW.valor_custo_produto_historico );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_venda_ms', OLD.valor_venda_ms, NEW.valor_venda_ms );
    CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_venda_sem_comissao', OLD.valor_venda_sem_comissao, NEW.valor_venda_sem_comissao );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_venda_ml', OLD.valor_venda_ml, NEW.valor_venda_ml );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'data_atualizou_valor_custo', OLD.data_atualizou_valor_custo, NEW.data_atualizou_valor_custo );
	CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'data_up', OLD.data_up, NEW.data_up );
    CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'id_fornecedor', OLD.id_fornecedor, NEW.id_fornecedor );
    CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'porcentagem_comissao_ms', OLD.porcentagem_comissao_ms, NEW.porcentagem_comissao_ms );
    CALL salva_log_alteracao_produtos( NEW.usuario, NEW.id, 'valor_custo_produto_fornecedor', OLD.valor_custo_produto_fornecedor, NEW.valor_custo_produto_fornecedor );

	SET NEW.promocao = if(NEW.preco_promocao > 0,1,0);
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_foto_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_foto_after_delete` AFTER DELETE ON `produtos_foto` FOR EACH ROW BEGIN
		UPDATE publicacoes
		SET publicacoes.situacao =  'RE'
		WHERE
			publicacoes.tipo_publicacao = 'AU' AND
			publicacoes.situacao = 'CR' AND
			publicacoes.foto LIKE OLD.caminho;
	END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_foto_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_foto_after_insert` AFTER INSERT ON `produtos_foto` FOR EACH ROW BEGIN
	DECLARE ID_FOTOGRAFO_ INT DEFAULT 0;
	UPDATE produtos SET produtos.data_entrada = NOW() WHERE produtos.id = NEW.id;

	IF(NEW.tipo_foto <> 'SM') THEN
		SELECT produtos.id_colaborador_publicador_padrao FROM produtos WHERE produtos.id = NEW.id
		INTO ID_FOTOGRAFO_;

		INSERT INTO publicacoes (publicacoes.id_colaborador, publicacoes.foto, publicacoes.tipo_publicacao)
		SELECT ID_FOTOGRAFO_, NEW.caminho, 'AU' FROM DUAL
		WHERE NOT EXISTS(SELECT 1 FROM publicacoes WHERE publicacoes.id_colaborador = ID_FOTOGRAFO_ AND publicacoes.foto = NEW.caminho AND publicacoes.tipo_publicacao = 'AU');

		IF(ROW_COUNT() > 0) THEN
			INSERT INTO publicacoes_produtos (publicacoes_produtos.id_publicacao, publicacoes_produtos.id_produto, publicacoes_produtos.foto_publicacao)
			SELECT LAST_INSERT_ID(), NEW.id, NEW.caminho;
		END IF;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_foto_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_foto_before_delete` BEFORE DELETE ON `produtos_foto` FOR EACH ROW BEGIN
	IF (OLD.id <> 0
		AND NOT EXISTS(
			SELECT 1
			FROM produtos_foto
			WHERE produtos_foto.id = OLD.id
				AND produtos_foto.caminho <> OLD.caminho
				AND produtos_foto.tipo_foto <> 'SM'
		)
	) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Esse produto precisa ter pelo menos uma foto';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_separacao_fotos_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_separacao_fotos_after_delete` AFTER DELETE ON `produtos_separacao_fotos` FOR EACH ROW BEGIN
	DECLARE LOCAL_PRODUTO VARCHAR(10) DEFAULT 0;
	IF(OLD.separado = 'T') THEN
		INSERT INTO produtos_aguarda_entrada_estoque(
			produtos_aguarda_entrada_estoque.id_produto,
			produtos_aguarda_entrada_estoque.nome_tamanho,
			produtos_aguarda_entrada_estoque.localizacao,
			produtos_aguarda_entrada_estoque.tipo_entrada,
			produtos_aguarda_entrada_estoque.em_estoque,
			produtos_aguarda_entrada_estoque.data_hora,
			produtos_aguarda_entrada_estoque.usuario
		) VALUES (
			OLD.id_produto,
			OLD.nome_tamanho,
			LOCAL_PRODUTO,
			'FT',
			'F',
			NOW(),
			OLD.usuario_solicita
		);
	ELSEIF (OLD.separado = 'F') THEN
		IF(OLD.id_produto_agu_estoque > 0) THEN
			UPDATE produtos_aguarda_entrada_estoque
				SET produtos_aguarda_entrada_estoque.tipo_entrada = 'FT'
			WHERE produtos_aguarda_entrada_estoque.id = OLD.id_produto_agu_estoque;
		END IF;
	ELSE
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Item não pode ser removido enquanto separado = F';
   END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_separacao_fotos_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_separacao_fotos_after_update` AFTER UPDATE ON `produtos_separacao_fotos` FOR EACH ROW BEGIN

	DECLARE ID_PRODUTO_AGUARDA_ESTOQUE INT(7) DEFAULT 0;
	IF ((OLD.separado = 'F') AND (NEW.separado = 'T')) THEN
		IF (NEW.id_produto_agu_estoque > 0) THEN
			DELETE FROM produtos_aguarda_entrada_estoque WHERE produtos_aguarda_entrada_estoque.id = NEW.id_produto_agu_estoque;
		END IF;
	ELSEIF ((OLD.separado = 'T') AND (NEW.separado = 'F')) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Apos item ser marcado como separado, nao e permitido a reversao do processo';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.produtos_separacao_fotos_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `produtos_separacao_fotos_before_insert` BEFORE INSERT ON `produtos_separacao_fotos` FOR EACH ROW BEGIN
	DECLARE ID_PRODUTO_AGUARDA_ESTOQUE INT(7) DEFAULT 0;
	IF ((NEW.separado = 'F') AND (NEW.tipo_separacao <> 'P') OR (NEW.tipo_separacao IS NULL)) THEN
		SELECT COALESCE(produtos_aguarda_entrada_estoque.id,0) INTO ID_PRODUTO_AGUARDA_ESTOQUE
			FROM produtos_aguarda_entrada_estoque
			WHERE produtos_aguarda_entrada_estoque.id_produto = NEW.id_produto
					AND produtos_aguarda_entrada_estoque.nome_tamanho = NEW.nome_tamanho
					AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
			LIMIT 1;
		IF (ID_PRODUTO_AGUARDA_ESTOQUE > 0) THEN
			UPDATE produtos_aguarda_entrada_estoque
				SET produtos_aguarda_entrada_estoque.tipo_entrada = 'SP'
				WHERE produtos_aguarda_entrada_estoque.id = ID_PRODUTO_AGUARDA_ESTOQUE;
			SET NEW.tipo_separacao = 'P';
			SET NEW.id_produto_agu_estoque = ID_PRODUTO_AGUARDA_ESTOQUE;
		ELSE
			SET NEW.tipo_separacao = 'E';
		END IF;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.publicacoes_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `publicacoes_after_update` AFTER UPDATE ON `publicacoes` FOR EACH ROW BEGIN
IF (OLD.situacao <> NEW.situacao) THEN
	UPDATE publicacoes_produtos
	SET publicacoes_produtos.situacao = NEW.situacao
	WHERE publicacoes_produtos.id_publicacao = NEW.id;
END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.publicacoes_produtos_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `publicacoes_produtos_before_insert` BEFORE INSERT ON `publicacoes_produtos` FOR EACH ROW BEGIN
	DECLARE PERMITE_ CHAR(1) DEFAULT
		IF(COALESCE((SELECT publicacoes.tipo_publicacao FROM publicacoes WHERE publicacoes.id = NEW.id_publicacao LIMIT 1), 'ML') = 'ML',
			(SELECT configuracoes.permite_criar_look_com_qualquer_produto FROM configuracoes LIMIT 1),
			'T'
		);

	IF(PERMITE_ = 'F' AND LENGTH(COALESCE(NEW.uuid, '')) < 5 AND NOT EXISTS (SELECT usuarios.id_colaborador FROM usuarios
																										INNER JOIN publicacoes
																									 WHERE permissao REGEXP '60|50|51|52|53|54|55|56|57|58'
																									 	AND usuarios.id_colaborador=publicacoes.id_colaborador )) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Para criar uma publicação é necessário usar um produto comprado';
	END IF;

	IF(
		PERMITE_ = 'F' AND
		(SELECT COUNT(publicacoes_produtos.id) FROM publicacoes_produtos
		INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao AND publicacoes.situacao = 'CR'
		WHERE publicacoes_produtos.uuid = NEW.uuid) > ((SELECT configuracoes.qtd_vezes_produto_pode_ser_adicionado_publicacao FROM configuracoes LIMIT 1) - 1)
	) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Limite de publicações alcançado';
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.status_pedido_item_log_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `status_pedido_item_log_update` AFTER UPDATE ON `pedido_item` FOR EACH ROW begin
if NEW.situacao <> OLD.situacao THEN
INSERT into status_pedido_item_log VALUES(OLD.id_cliente,OLD.id_produto,OLD.situacao,NEW.situacao,NEW.uuid);
END IF;
end//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.tipo_frete_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tipo_frete_after_insert` AFTER INSERT ON `tipo_frete` FOR EACH ROW BEGIN
    SET @ID_CIDADE_ = (SELECT colaboradores_enderecos.id_cidade FROM colaboradores_enderecos WHERE colaboradores_enderecos.id_colaborador = NEW.id_colaborador AND colaboradores_enderecos.eh_endereco_padrao = 1);

	IF(NEW.categoria = 'ML' AND (LENGTH(COALESCE(NEW.latitude, '')) = 0 OR LENGTH(COALESCE(NEW.longitude, '')) = 0)) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Para cadastrar um ponto de retirada é necessário ter a localização cadastrada no usuário';
	END IF;


	IF(NEW.categoria = 'ML') THEN

		UPDATE usuarios
		SET usuarios.permissao = IF(
			LOCATE(',60',usuarios.permissao) = 0,
			CONCAT(usuarios.permissao, ',60'),
			usuarios.permissao
		)
		WHERE usuarios.id_colaborador=NEW.id_colaborador;
	END IF;


	IF(NEW.categoria = 'MS') THEN

		UPDATE usuarios
		SET usuarios.permissao = IF(
			LOCATE(',61',usuarios.permissao) = 0,
			CONCAT(usuarios.permissao, ',61'),
			usuarios.permissao
		)
		WHERE usuarios.id_colaborador=NEW.id_colaborador;
	END IF;


	INSERT INTO tipo_frete_log (
		tipo_frete_log.mensagem,
		tipo_frete_log.id_usuario
	) VALUES (
		JSON_OBJECT(
			'NEW_id', NEW.id,
			'NEW_nome', NEW.nome,
			'NEW_titulo', NEW.titulo,
			'NEW_mensagem', NEW.mensagem,
			'NEW_tipo_ponto', NEW.tipo_ponto,
			'NEW_mensagem_cliente', NEW.mensagem_cliente,
			'NEW_mapa', NEW.mapa,
			'NEW_foto', NEW.foto,
			'NEW_id_colaborador', NEW.id_colaborador,
			'NEW_latitude', NEW.latitude,
			'NEW_longitude', NEW.longitude,
			'NEW_previsao_entrega', NEW.previsao_entrega,
			'NEW_categoria', NEW.categoria,
			'NEW_percentual_comissao', NEW.percentual_comissao,
			'NEW_horario_de_funcionamento', NEW.horario_de_funcionamento,
			'NEW_emitir_nota_fiscal', NEW.emitir_nota_fiscal,
			'NEW_id_usuario', NEW.id_usuario,
			'NEW_id_colaborador_ponto_coleta', NEW.id_colaborador_ponto_coleta
		),
		NEW.id_usuario
	);

    IF (NEW.tipo_ponto = 'PP') THEN
        INSERT IGNORE INTO transportes_cidades
            (
                transportes_cidades.id_colaborador,
                transportes_cidades.id_cidade,
                transportes_cidades.valor,
                transportes_cidades.ativo,
                transportes_cidades.id_usuario
            )
        VALUES
            (
                NEW.id_colaborador,
                @ID_CIDADE_,
                0,
                TRUE,
                NEW.id_usuario
            );
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.tipo_frete_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tipo_frete_after_update` AFTER UPDATE ON `tipo_frete` FOR EACH ROW BEGIN
	IF(NEW.categoria = 'ML' AND (LENGTH(COALESCE(NEW.latitude, '')) = 0 OR LENGTH(COALESCE(NEW.longitude, '')) = 0)) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Para cadastrar um ponto de retirada é necessário ter a localização cadastrada no usuário';
	END IF;

    # Cria LOG
    INSERT INTO tipo_frete_log (
        tipo_frete_log.mensagem,
        tipo_frete_log.id_usuario
    ) VALUES (
        JSON_OBJECT(
            'OLD_id', OLD.id,
            'NEW_id', NEW.id,
            'OLD_nome', OLD.nome,
            'NEW_nome', NEW.nome,
            'OLD_titulo', OLD.titulo,
            'NEW_titulo', NEW.titulo,
            'OLD_mensagem', OLD.mensagem,
            'NEW_mensagem', NEW.mensagem,
            'OLD_tipo_ponto', OLD.tipo_ponto,
            'NEW_tipo_ponto', NEW.tipo_ponto,
            'OLD_mensagem_cliente', OLD.mensagem_cliente,
            'NEW_mensagem_cliente', NEW.mensagem_cliente,
            'OLD_mapa', OLD.mapa,
            'NEW_mapa', NEW.mapa,
            'OLD_foto', OLD.foto,
            'NEW_foto', NEW.foto,
            'OLD_id_colaborador', OLD.id_colaborador,
            'NEW_id_colaborador', NEW.id_colaborador,
            'OLD_latitude', OLD.latitude,
            'NEW_latitude', NEW.latitude,
            'OLD_longitude', OLD.longitude,
            'NEW_longitude', NEW.longitude,
            'OLD_previsao_entrega', OLD.previsao_entrega,
            'NEW_previsao_entrega', NEW.previsao_entrega,
            'OLD_categoria', OLD.categoria,
            'NEW_categoria', NEW.categoria,
            'OLD_percentual_comissao', OLD.percentual_comissao,
            'NEW_percentual_comissao', NEW.percentual_comissao,
            'OLD_horario_de_funcionamento', OLD.horario_de_funcionamento,
            'NEW_horario_de_funcionamento', NEW.horario_de_funcionamento,
            'OLD_emitir_nota_fiscal', OLD.emitir_nota_fiscal,
            'NEW_emitir_nota_fiscal', NEW.emitir_nota_fiscal,
            'OLD_id_usuario', OLD.id_usuario,
            'NEW_id_usuario', NEW.id_usuario,
            'OLD_id_colaborador_ponto_coleta', OLD.id_colaborador_ponto_coleta,
            'NEW_id_colaborador_ponto_coleta', NEW.id_colaborador_ponto_coleta
        ),
        NEW.id_usuario
    );
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.tipo_frete_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `tipo_frete_before_insert` BEFORE INSERT ON `tipo_frete` FOR EACH ROW BEGIN
		IF(EXISTS(SELECT 1 FROM tipo_frete WHERE tipo_frete.id_colaborador = NEW.id_colaborador)) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Não é possivel cadastrar dois pontos de retirada para um usuario';
	END IF;

	IF(NEW.categoria = 'ML' AND (LENGTH(COALESCE(NEW.latitude, '')) = 0 OR LENGTH(COALESCE(NEW.longitude, '')) = 0)) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Para cadastrar um ponto de retirada é necessário ter a localização cadastrada no usuário';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.tipo_frete_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER tipo_frete_before_update
	BEFORE UPDATE
	ON tipo_frete
	FOR EACH ROW
BEGIN
    IF(OLD.categoria <> 'ML' AND NEW.categoria = 'ML') THEN
        SET NEW.percentual_comissao = (SELECT DEFAULT(tipo_frete.percentual_comissao) FROM tipo_frete LIMIT 1);
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_after_update` AFTER UPDATE ON `transacao_financeiras` FOR EACH ROW BEGIN
	# https://github.com/mobilestock/web/issues/3152
	DECLARE _ID_PEDIDO VARCHAR(255) DEFAULT NULL;
	 IF(OLD.status = 'CR' AND NEW.status <> 'CR') THEN
		UPDATE pedido_item
		INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
			SET pedido_item.situacao = '3'
		WHERE transacao_financeiras_produtos_itens.id_transacao = NEW.id
		  AND transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF');

    ELSEIF(OLD.status <> 'CA' AND NEW.status = 'CA')THEN
        UPDATE transacao_financeiras_produtos_trocas
        SET transacao_financeiras_produtos_trocas.id_nova_transacao = 0
        WHERE transacao_financeiras_produtos_trocas.id_nova_transacao = NEW.id;

		IF (NEW.origem_transacao = 'MP') THEN
			SELECT transacao_financeiras_metadados.valor
			INTO _ID_PEDIDO
			FROM transacao_financeiras_metadados
			WHERE transacao_financeiras_metadados.id_transacao = NEW.id
				AND transacao_financeiras_metadados.chave = 'ID_PEDIDO';

			IF (COALESCE(_ID_PEDIDO, '') <> '') THEN
				DROP TEMPORARY TABLE IF EXISTS transacoes_para_deletar;

				CREATE TEMPORARY TABLE transacoes_para_deletar
                SELECT transacao_financeiras_metadados.id_transacao
                FROM transacao_financeiras_metadados
                WHERE transacao_financeiras_metadados.chave = 'ID_PEDIDO'
                	AND transacao_financeiras_metadados.valor = _ID_PEDIDO;

                DELETE transacao_financeiras_metadados FROM transacao_financeiras_metadados
                INNER JOIN transacoes_para_deletar ON transacoes_para_deletar.id_transacao = transacao_financeiras_metadados.id_transacao;

                IF (ROW_COUNT() < 1) THEN
                    SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Removida quantidade errada de metadados';
                END IF;
			END IF;
		END IF;

        IF(OLD.status = 'PE')THEN
            DELETE FROM transacao_financeiras_produtos_itens
            WHERE transacao_financeiras_produtos_itens.id_transacao = NEW.id;
        ELSE
        	signal sqlstate '45000' set MESSAGE_TEXT = 'Situação não permite cancelamento.';
        END IF;
    END IF;

	IF(OLD.status <> 'PA' AND NEW.status = 'PA' AND NEW.origem_transacao = 'ML') THEN

		INSERT INTO notificacoes (
			notificacoes.id_cliente,
			notificacoes.destino,
			notificacoes.titulo,
			notificacoes.imagem,
			notificacoes.mensagem,
			notificacoes.tipo_mensagem,
			notificacoes.data_evento
		) VALUES (
			NEW.pagador,
			'ML',
			'Pagamento',
			NULL,
			CONCAT(
				'Pagamento aprovado com sucesso! <b><a style="text-decoration:underline;" href="/usuario/historico">Clique aqui</a></b> para visualizar o andamento do seu Pedido.'),
			'C',
			NOW()
		);

    END IF;

	IF (
		OLD.status <> NEW.status
		OR OLD.metodo_pagamento <> NEW.metodo_pagamento
		OR OLD.numero_parcelas <> NEW.numero_parcelas
	) THEN
		INSERT INTO transacao_financeiras_logs (
			transacao_financeiras_logs.id_transacao,
			transacao_financeiras_logs.status,
			transacao_financeiras_logs.metodo_pagamento,
			transacao_financeiras_logs.numero_parcelas,
			transacao_financeiras_logs.transacao_json
		) VALUES (
			NEW.id,
			NEW.status,
			NEW.metodo_pagamento,
			NEW.numero_parcelas,
			JSON_OBJECT(
				'id', NEW.id,
				'cod_transacao', NEW.cod_transacao,
				'data_criacao', NEW.data_criacao,
				'data_atualizacao', NEW.data_atualizacao,
				'status', NEW.status,
				'url_boleto', NEW.url_boleto,
				'valor_total', NEW.valor_total,
				'valor_credito', NEW.valor_credito,
				'valor_credito_bloqueado', NEW.valor_credito_bloqueado,
				'valor_acrescimo', NEW.valor_acrescimo,
				'valor_desconto', NEW.valor_desconto,
				'valor_comissao_fornecedor', NEW.valor_comissao_fornecedor,
				'valor_liquido', NEW.valor_liquido,
				'valor_itens', NEW.valor_itens,
				'valor_taxas', NEW.valor_taxas,
				'juros_pago_split', NEW.juros_pago_split,
				'numero_transacao', NEW.numero_transacao,
				'responsavel', NEW.responsavel,
				'pagador', NEW.pagador,
				'metodo_pagamento', NEW.metodo_pagamento,
				'metodos_pagamentos_disponiveis', NEW.metodos_pagamentos_disponiveis,
				'numero_parcelas', NEW.numero_parcelas,
				'id_usuario', NEW.id_usuario,
				'id_usuario_pagamento', NEW.id_usuario_pagamento,
				'barcode', NEW.barcode,
				'origem_transacao', NEW.origem_transacao,
				'qrcode_pix', NEW.qrcode_pix,
				'qrcode_text_pix', NEW.qrcode_text_pix,
				'emissor_transacao', NEW.emissor_transacao,
				'url_fatura', NEW.url_fatura,
				'uuid_requisicao_pagamento', NEW.uuid_requisicao_pagamento
			)
		);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_before_delete` BEFORE DELETE ON `transacao_financeiras` FOR EACH ROW BEGIN
	# https://github.com/mobilestock/web/issues/3152
	DECLARE _ID_PEDIDO VARCHAR(255) DEFAULT NULL;
	IF(OLD.status NOT IN ('LK','CR')) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Transacao nao pode ser removida';
	END IF;

	DELETE FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.id_transacao = OLD.id;


	IF (OLD.origem_transacao = 'MP') THEN
		SELECT transacao_financeiras_metadados.valor
		INTO _ID_PEDIDO
		FROM transacao_financeiras_metadados
		WHERE transacao_financeiras_metadados.id_transacao = OLD.id
			AND transacao_financeiras_metadados.chave = 'ID_PEDIDO';

		IF (COALESCE(_ID_PEDIDO, '') <> '') THEN
			DROP TEMPORARY TABLE IF EXISTS transacoes_para_deletar;

			CREATE TEMPORARY TABLE transacoes_para_deletar
            SELECT transacao_financeiras_metadados.id_transacao
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.chave = 'ID_PEDIDO'
            	AND transacao_financeiras_metadados.valor = _ID_PEDIDO;

            DELETE transacao_financeiras_metadados FROM transacao_financeiras_metadados
            INNER JOIN transacoes_para_deletar ON transacoes_para_deletar.id_transacao = transacao_financeiras_metadados.id_transacao;

            IF (ROW_COUNT() < 1) THEN
                SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Removida quantidade errada de metadados';
            END IF;
		END IF;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_before_insert` BEFORE INSERT ON `transacao_financeiras` FOR EACH ROW BEGIN
	SET NEW.valor_liquido = NEW.valor_acrescimo + NEW.valor_itens - NEW.valor_credito;
	SET NEW.valor_total = NEW.valor_liquido + NEW.valor_credito;

	IF(EXISTS(SELECT 1 FROM transacao_financeiras WHERE transacao_financeiras.pagador = NEW.pagador AND transacao_financeiras.status = 'CR')) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Sistema nao permite que exista duas transacoes criadas';
	END IF;

	IF(NEW.origem_transacao = 'ED') THEN
		SET NEW.status = 'LK';
		SET NEW.metodos_pagamentos_disponiveis = 'PX';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_before_update` BEFORE UPDATE ON `transacao_financeiras` FOR EACH ROW BEGIN
	IF(OLD.status = 'CA' AND NEW.status <> 'CA')THEN
		INSERT INTO notificacoes (id_cliente, data_evento, titulo, mensagem, tipo_mensagem)
		VALUES(1,NOW(),'Transação',CONCAT('Transacao ',NEW.id,' Estava Cancelada e voltou para ',NEW.status),'Z');
	END IF;

	IF(OLD.status NOT IN ('LK','CR','PE'))THEN
		IF (PASSWORD(CONCAT(NEW.valor_total,
				NEW.valor_credito,
				NEW.valor_acrescimo,
				NEW.valor_comissao_fornecedor,
				NEW.valor_itens,
				NEW.valor_taxas,
				NEW.juros_pago_split,
				NEW.numero_parcelas,
				NEW.metodo_pagamento,
				NEW.responsavel,
				NEW.pagador)) <>
			 PASSWORD(CONCAT(OLD.valor_total,
				OLD.valor_credito,
				OLD.valor_acrescimo,
				OLD.valor_comissao_fornecedor,
				OLD.valor_itens,
				OLD.valor_taxas,
				OLD.juros_pago_split,
				OLD.numero_parcelas,
				OLD.metodo_pagamento,
				OLD.responsavel,
				OLD.pagador)))THEN
			SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Transacao nao pode ter valores alterados';
		END IF;

		IF(ABS(NEW.valor_liquido - OLD.valor_liquido) > 3) THEN
			SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Transacao nao pode ter valor liquido alterado';
		END IF;
	END IF;
	SET NEW.valor_liquido = NEW.valor_acrescimo + NEW.valor_itens - NEW.valor_credito - NEW.valor_desconto;
	SET NEW.valor_total = NEW.valor_liquido + NEW.valor_credito;

	IF(NEW.valor_credito_bloqueado < 0) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Sistema não permite utilizar credito bloqueado negativo';
	END IF;

	IF(OLD.status <> 'CR' AND NEW.status = 'CR') THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Transacao nao pode voltar para aberto';
	END IF;

	IF(OLD.status <> NEW.status AND OLD.status NOT IN ('LK', 'CR') AND NEW.status = 'PE') THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Transacao nao pode voltar para pendente';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_metadados_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_metadados_before_insert` BEFORE INSERT ON `transacao_financeiras_metadados` FOR EACH ROW BEGIN
	IF (NEW.chave = 'ID_COLABORADOR_TIPO_FRETE'
			AND NOT EXISTS(SELECT 1
							   FROM tipo_frete
							   WHERE tipo_frete.id_colaborador = NEW.valor
				     				AND IF(tipo_frete.categoria = 'ML',
					 							EXISTS(SELECT 1
								   					FROM transportes_cidades
								   					WHERE transportes_cidades.id_colaborador = tipo_frete.id_colaborador), 1)
								)
		) THEN
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este ID destinatário não existe.';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_produtos_itens_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_produtos_itens_after_delete` AFTER DELETE ON `transacao_financeiras_produtos_itens` FOR EACH ROW BEGIN
	UPDATE pedido_item SET pedido_item.situacao = 1 WHERE pedido_item.uuid = OLD.uuid_produto;


	IF(EXISTS(
		SELECT 1
		FROM transacao_financeiras
		WHERE
			transacao_financeiras.id = OLD.id_transacao AND
			transacao_financeiras.origem_transacao = 'ED' AND
			transacao_financeiras.status = 'CA'
	)) THEN
		DELETE pedido_item FROM pedido_item WHERE pedido_item.uuid = OLD.uuid_produto;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_produtos_itens_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_produtos_itens_before_insert` BEFORE INSERT ON `transacao_financeiras_produtos_itens` FOR EACH ROW BEGIN
	IF(NEW.tipo_item IN ('PR', 'RF') AND EXISTS(SELECT 1 FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF') AND transacao_financeiras_produtos_itens.uuid_produto = NEW.uuid_produto)) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Erro produto já existe';
	END IF;
	IF(NEW.tipo_item IN ('PR', 'RF') AND COALESCE(NEW.nome_tamanho, '') = '') THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'nome_tamanho não pode ser NULL';
	END IF;

	IF(NEW.momento_pagamento IS NULL) THEN
		SET NEW.momento_pagamento = IF(NEW.tipo_item IN ('PR','CC','CE','CL','CM_PONTO_COLETA','CM_ENTREGA') AND
										NEW.uuid_produto IS NOT NULL AND
										EXISTS(SELECT 1
											  FROM transacao_financeiras
											  WHERE transacao_financeiras.id = NEW.id_transacao
											    AND transacao_financeiras.origem_transacao = 'ML'),
									       'CARENCIA_ENTREGA',
									       'PAGAMENTO'
									);
	END IF;

	IF(NEW.sigla_lancamento IS NULL) THEN
		SET NEW.sigla_lancamento = CASE
										WHEN NEW.tipo_item = 'AC' THEN 'CM'
										WHEN NEW.tipo_item = 'PR' THEN 'SC'
										WHEN NEW.tipo_item IN ('CC','CE','CL','CM_PONTO_COLETA','CM_ENTREGA')
											THEN NEW.tipo_item
										ELSE NULL
								   END;
	END IF;

	IF(NEW.sigla_estorno IS NULL) THEN
		SET NEW.sigla_estorno = CASE NEW.tipo_item
								   	WHEN 'PR' THEN 'TF'
									WHEN 'CC' THEN 'TC'
									WHEN 'CE' THEN 'TE'
									WHEN 'CL' THEN 'TL'
									WHEN 'CM_PONTO_COLETA' THEN 'TR_PONTO_COLETA'
									ELSE NULL
								END;
	END IF;

	IF (NEW.preco <= 0) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'preco deve ser maior que 0';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_produtos_trocas_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_produtos_trocas_before_delete` BEFORE DELETE ON `transacao_financeiras_produtos_trocas` FOR EACH ROW BEGIN
	IF(OLD.situacao = 'PA') THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'não é possivel deletar troca';
	END IF;

	IF((saldo_cliente(OLD.id_cliente) + COALESCE((
	   SELECT SUM(troca_pendente_agendamento.preco)
	      FROM troca_pendente_agendamento
	      INNER JOIN transacao_financeiras_produtos_trocas ON transacao_financeiras_produtos_trocas.uuid = troca_pendente_agendamento.uuid
	      WHERE transacao_financeiras_produtos_trocas.id_cliente = OLD.id_cliente
	        AND transacao_financeiras_produtos_trocas.uuid <> OLD.uuid
	  ), 0) + COALESCE((SELECT SUM(IF(lancamento_financeiro_pendente.tipo = 'P', lancamento_financeiro_pendente.valor, lancamento_financeiro_pendente.valor * -1))
	  							FROM lancamento_financeiro_pendente
								WHERE lancamento_financeiro_pendente.id_colaborador = OLD.id_cliente
									AND lancamento_financeiro_pendente.origem IN ('PC', 'ES')),0)
	) < 0) THEN
	 signal sqlstate '45000' set MESSAGE_TEXT = 'Para deletar uma troca de uma transação é necessário cancelar a transacao';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeiras_produtos_trocas_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeiras_produtos_trocas_before_update` BEFORE UPDATE ON `transacao_financeiras_produtos_trocas` FOR EACH ROW BEGIN
	IF(NEW.situacao = 'PA' AND OLD.situacao <> 'PA' AND COALESCE(NEW.id_nova_transacao, 0) > 0 AND LENGTH(COALESCE(NEW.uuid, '')) > 1) THEN
		SET NEW.uuid = NULL;
	END IF;

	IF(NEW.situacao <> 'PA' AND OLD.situacao = 'PA') THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Não é possivel voltar um pagamento de credito bloqueado para pendente';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeira_split_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeira_split_after_insert` AFTER INSERT ON `transacao_financeira_split` FOR EACH ROW BEGIN
	UPDATE colaboradores_prioridade_pagamento
      SET colaboradores_prioridade_pagamento.valor_pago = colaboradores_prioridade_pagamento.valor_pago + NEW.valor
   WHERE colaboradores_prioridade_pagamento.id = NEW.id_transferencia;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeira_split_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeira_split_after_update` AFTER UPDATE ON `transacao_financeira_split` FOR EACH ROW BEGIN
	IF(OLD.situacao <> 'CA' AND NEW.situacao = 'CA') THEN
		UPDATE colaboradores_prioridade_pagamento
	      SET colaboradores_prioridade_pagamento.valor_pago = colaboradores_prioridade_pagamento.valor_pago - NEW.valor
	   WHERE colaboradores_prioridade_pagamento.id = NEW.id_transferencia;
	ELSEIF(OLD.situacao <> 'NA' AND NEW.situacao = 'NA') THEN
		UPDATE colaboradores_prioridade_pagamento
	      SET colaboradores_prioridade_pagamento.valor_pago = colaboradores_prioridade_pagamento.valor_pago + NEW.valor
	   WHERE colaboradores_prioridade_pagamento.id = NEW.id_transferencia;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transacao_financeira_split_before_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transacao_financeira_split_before_delete` BEFORE DELETE ON `transacao_financeira_split` FOR EACH ROW BEGIN
	signal sqlstate '45000' set MESSAGE_TEXT = 'Nao pode deletar';
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.transportadoras_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `transportadoras_after_update` AFTER UPDATE ON `transportes` FOR EACH ROW BEGIN
	DECLARE TIPO_COLABORADOR_ VARCHAR(1) DEFAULT "";

	IF (NEW.situacao <> OLD.situacao) THEN
		IF (NEW.situacao = 'PR') THEN
			SET TIPO_COLABORADOR_ = 'T';
		ELSE
			SET TIPO_COLABORADOR_ = 'C';
		END IF;
		UPDATE colaboradores SET colaboradores.tipo = TIPO_COLABORADOR_ WHERE colaboradores.id = NEW.id_colaborador;
	END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_fila_solicitacoes_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_fila_solicitacoes_after_delete` AFTER DELETE ON `troca_fila_solicitacoes` FOR EACH ROW BEGIN
	DELETE FROM troca_pendente_agendamento WHERE troca_pendente_agendamento.uuid = OLD.uuid_produto;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_fila_solicitacoes_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_fila_solicitacoes_after_update` AFTER UPDATE ON `troca_fila_solicitacoes` FOR EACH ROW BEGIN
	IF (OLD.situacao IN ('SOLICITACAO_PENDENTE', 'APROVADO', 'EM_DISPUTA') AND NEW.situacao = 'CANCELADO_PELO_CLIENTE') THEN
		DELETE FROM troca_pendente_agendamento WHERE troca_pendente_agendamento.uuid = OLD.uuid_produto;
	END IF;
	IF(OLD.situacao <> 'REPROVADA_POR_FOTO' AND NEW.situacao = 'REPROVADA_POR_FOTO') THEN
		UPDATE troca_pendente_agendamento
		SET troca_pendente_agendamento.data_vencimento = troca_pendente_agendamento.data_vencimento + INTERVAL 7 DAY
		WHERE troca_pendente_agendamento.uuid = NEW.uuid_produto;
	END IF;
	IF(OLD.situacao <> 'PENDENTE_FOTO' AND NEW.situacao = 'PENDENTE_FOTO') THEN
		UPDATE troca_pendente_agendamento
		SET troca_pendente_agendamento.data_vencimento = troca_pendente_agendamento.data_vencimento + INTERVAL 7 DAY
		WHERE troca_pendente_agendamento.uuid = NEW.uuid_produto;
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_agendamento_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_pendente_agendamento_after_delete` AFTER DELETE ON `troca_pendente_agendamento` FOR EACH ROW BEGIN
	IF(EXISTS(SELECT 1 FROM transacao_financeiras_produtos_trocas WHERE transacao_financeiras_produtos_trocas.uuid = OLD.uuid AND transacao_financeiras_produtos_trocas.situacao = 'PE')) THEN
		DELETE FROM transacao_financeiras_produtos_trocas WHERE transacao_financeiras_produtos_trocas.uuid = OLD.uuid AND transacao_financeiras_produtos_trocas.situacao = 'PE';
	END IF;
	IF(EXISTS(SELECT 1 FROM troca_fila_solicitacoes WHERE troca_fila_solicitacoes.uuid_produto = OLD.uuid AND troca_fila_solicitacoes.situacao = 'APROVADO')) THEN
		UPDATE troca_fila_solicitacoes SET troca_fila_solicitacoes.situacao = 'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO' WHERE troca_fila_solicitacoes.uuid_produto = OLD.uuid;
		IF(ROW_COUNT() = 0) THEN
			SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Nenhuma troca expirada';
		END IF;
	END IF;
	DELETE FROM lancamento_financeiro_pendente
	WHERE lancamento_financeiro_pendente.numero_documento = OLD.uuid
	  AND (lancamento_financeiro_pendente.origem = 'TR'
	   	   OR EXISTS(SELECT 1
		             FROM transacao_financeiras_produtos_itens
					 WHERE transacao_financeiras_produtos_itens.uuid_produto = lancamento_financeiro_pendente.numero_documento
					   AND transacao_financeiras_produtos_itens.sigla_estorno = lancamento_financeiro_pendente.origem));

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_agendamento_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_pendente_agendamento_after_insert` AFTER INSERT ON `troca_pendente_agendamento` FOR EACH ROW BEGIN
	IF(NEW.tipo_agendamento = 'ML') THEN
	INSERT INTO notificacoes (id_cliente, destino, data_evento, titulo, mensagem, tipo_mensagem, icon)
	VALUES (
		NEW.id_cliente,
		'ML',
		NOW(),
		'Notificação de troca!',
		CONCAT("Geramos um crédito de R$ ", NEW.preco, " pra você <a href='/usuario/", NEW.id_cliente, "'><strong>comprar um novo produto</strong></a>"),
		"C",
		2
	);
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_agendamento_before_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER troca_pendente_agendamento_before_insert BEFORE INSERT ON troca_pendente_agendamento FOR EACH ROW
BEGIN
	IF(
		NOT EXISTS(
			SELECT 1
			FROM entregas_faturamento_item
			WHERE entregas_faturamento_item.uuid_produto = NEW.uuid
		)
	) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Para efetuar a devolução o produto deve estar entregue, Notifique o suporte!';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_item_after_delete
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_pendente_item_after_delete` AFTER DELETE ON `troca_pendente_item` FOR EACH ROW BEGIN
















	DELETE FROM entregas_devolucoes_item WHERE entregas_devolucoes_item.uuid_produto = OLD.uuid;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_item_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_pendente_item_after_update` AFTER UPDATE ON `troca_pendente_item` FOR EACH ROW BEGIN
















	 IF (NEW.defeito <> OLD.defeito) THEN
        IF (NEW.defeito = 0) THEN
            UPDATE entregas_devolucoes_item SET entregas_devolucoes_item.tipo = 'NO' WHERE entregas_devolucoes_item.uuid_produto = NEW.uuid;
        ELSEIF(OLD.defeito = 0) THEN
            IF( EXISTS(SELECT 1 FROM entregas_devolucoes_item WHERE entregas_devolucoes_item.uuid_produto = NEW.uuid AND entregas_devolucoes_item.situacao <> 'CO')) THEN
                UPDATE entregas_devolucoes_item SET entregas_devolucoes_item.tipo = 'DE' WHERE entregas_devolucoes_item.uuid_produto = NEW.uuid;
            ELSE
                signal sqlstate '45000' set MESSAGE_TEXT = 'Nao pode ser alterado o campo defeito, produto já voltou para o estoque';
            END IF;
        END IF;
    END IF;

END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_item_before_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_pendente_item_before_update` BEFORE UPDATE ON `troca_pendente_item` FOR EACH ROW BEGIN
	SET NEW.data_hora = OLD.data_hora;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.troca_pendente_item_notif_after_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `troca_pendente_item_notif_after_insert` AFTER INSERT ON `troca_pendente_item` FOR EACH ROW INSERT INTO notificacoes (id_cliente, destino, data_evento, titulo, mensagem, tipo_mensagem, icon)
VALUES (
	NEW.id_cliente,
	'MM',
	NOW(),
	'Notificação de troca!',
	CONCAT("Troca inserida com sucesso! <a style='color:red' href='/mobilepay'><strong>Confira aqui</strong></a> o crédito de R$", new.preco),
	"C",
	2
)//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger mobile_stock.usuarios_after_update
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `usuarios_after_update` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
	IF (INSTR(NEW.tipos, 'E') = 0 AND INSTR(OLD.tipos, 'E') > 0)THEN
		UPDATE produtos_aguarda_entrada_estoque SET produtos_aguarda_entrada_estoque.usuario_resp = (SELECT usuarios.id
        FROM usuarios
        WHERE usuarios.tipos REGEXP 'E'
          AND (SELECT COUNT(DISTINCT produtos_aguarda_entrada_estoque.id_produto) FROM produtos_aguarda_entrada_estoque
            WHERE produtos_aguarda_entrada_estoque.usuario_resp = usuarios.id
            AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
             HAVING COUNT(distinct produtos_aguarda_entrada_estoque.id_produto) = (SELECT
              MIN((SELECT COUNT(DISTINCT produtos_aguarda_entrada_estoque.id_produto) FROM produtos_aguarda_entrada_estoque
                WHERE produtos_aguarda_entrada_estoque.usuario_resp = usuarios.id
                  AND produtos_aguarda_entrada_estoque.em_estoque = 'F')) qtd_ordens
        FROM usuarios
        WHERE usuarios.tipos REGEXP 'E')
       ) IS NOT NULL
    ORDER BY RAND()
    LIMIT 1) WHERE produtos_aguarda_entrada_estoque.usuario_resp = OLD.id;
    END IF;

    IF(OLD.permissao <> NEW.permissao AND OLD.permissao REGEXP '30' AND NOT NEW.permissao REGEXP '30')THEN
		UPDATE colaboradores
		SET colaboradores.bloqueado_repor_estoque = 'T'
		WHERE colaboradores.id = NEW.id_colaborador
			AND colaboradores.bloqueado_repor_estoque <> 'T';
	END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

ALTER TABLE configuracoes
	ADD COLUMN json_configuracoes_job_atualizar_opensearch JSON DEFAULT '{"delay_entre_requests":5,"tamanho_lote":100,"ativo":true}';

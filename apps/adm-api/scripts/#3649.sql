ALTER TABLE `configuracoes`
    DROP COLUMN `horarios_separacao_fulfillment`,
	ADD COLUMN `json_logistica` LONGTEXT NOT NULL DEFAULT '{}',
	ADD CONSTRAINT `json_logistica` CHECK (json_valid(`json_logistica`));

UPDATE configuracoes
SET configuracoes.json_logistica = '{"separacao_fulfillment": {"horarios": ["08:00", "14:00"], "horas_carencia_retirada": "02:30"}}'
WHERE TRUE;

INSERT INTO pontos_coleta_agenda_acompanhamento (
    pontos_coleta_agenda_acompanhamento.id_colaborador,
    pontos_coleta_agenda_acompanhamento.dia,
    pontos_coleta_agenda_acompanhamento.horario,
    pontos_coleta_agenda_acompanhamento.frequencia,
    pontos_coleta_agenda_acompanhamento.id_usuario
) VALUES (
    32254,
    'SEGUNDA',
    '08:00',
    'RECORRENTE',
    2
), (
    32254,
    'SEGUNDA',
    '14:00',
    'RECORRENTE',
    2
), (
    32254,
    'TERCA',
    '08:00',
    'RECORRENTE',
    2
), (
    32254,
    'TERCA',
    '14:00',
    'RECORRENTE',
    2
), (
    32254,
    'QUARTA',
    '08:00',
    'RECORRENTE',
    2
), (
    32254,
    'QUARTA',
    '14:00',
    'RECORRENTE',
    2
), (
    32254,
    'QUINTA',
    '08:00',
    'RECORRENTE',
    2
), (
    32254,
    'QUINTA',
    '14:00',
    'RECORRENTE',
    2
), (
    32254,
    'SEXTA',
    '08:00',
    'RECORRENTE',
    2
), (
    32254,
    'SEXTA',
    '14:00',
    'RECORRENTE',
    2
);

<?php

namespace MobileStock\model;

/**
 * @property int $id
 * @property int $id_colaborador
 * @property string $dia
 * @property string $horario
 * @property string $frequencia
 * @property int $id_usuario
 */
class PontosColetaAgendaAcompanhamento
{
    public string $nome_tabela = 'pontos_coleta_agenda_acompanhamento';
    public const DIAS_SEMANA = ['DOMINGO', 'SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA', 'SABADO'];
    public const FREQUENCIA_RECORRENTE = 'RECORRENTE';
    public const FREQUENCIA_PONTUAL = 'PONTUAL';

    public function extrair(): array
    {
        $extraido = get_object_vars($this);

        return $extraido;
    }
}

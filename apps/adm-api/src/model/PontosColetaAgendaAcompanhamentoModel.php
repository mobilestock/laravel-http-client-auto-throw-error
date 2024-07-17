<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 * @property int $id_colaborador
 * @property string $dia
 * @property string $horario
 * @property string $frequencia
 */
class PontosColetaAgendaAcompanhamentoModel extends Model
{
    protected $table = 'pontos_coleta_agenda_acompanhamento';
    protected $fillable = ['id_colaborador', 'dia', 'horario', 'frequencia', 'id_usuario'];
    public $timestamps = false;

    /**
     * @param array<string> $horariosDeletar
     */
    public static function removeHorariosSeNecessario(array $horariosDeletar): void
    {
        [$sql, $bind] = ConversorArray::criaBindValues($horariosDeletar, 'horario');

        DB::delete(
            "DELETE FROM pontos_coleta_agenda_acompanhamento
            WHERE pontos_coleta_agenda_acompanhamento.horario IN ($sql)",
            $bind
        );
    }
}

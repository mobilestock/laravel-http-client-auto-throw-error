<?php

namespace MobileStock\model;

use DateInterval;
use DateTime;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;

/**
 * https://github.com/mobilestock/backend/issues/131
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

    public static function agendaRetiradaPrevisao(): array
    {
        /**
         * TODO: criar teste para essa função
         */
        $fatores = ConfiguracaoService::buscaFatoresSeparacaoFulfillment();
        [$hora, $minuto] = explode(':', $fatores['horas_carencia_retirada']);
        $tempoAcrescimo = DateInterval::createFromDateString("$hora hours $minuto minutes");

        $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
        $agenda->id_colaborador = TipoFrete::ID_COLABORADOR_CENTRAL;
        $pontoColeta = $agenda->buscaPrazosPorPontoColeta();

        $agendaSemana = array_map(function (array $dia) use ($tempoAcrescimo): array {
            $dia['horario'] = DateTime::createFromFormat('H:i', $dia['horario'])
                ->add($tempoAcrescimo)
                ->format('H:i');

            return $dia;
        }, $pontoColeta['agenda']);

        return $agendaSemana;
    }
}

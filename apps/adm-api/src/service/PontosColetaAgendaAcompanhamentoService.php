<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\helper\GeradorSql;
use MobileStock\model\PontosColetaAgendaAcompanhamento;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PontosColetaAgendaAcompanhamentoService extends PontosColetaAgendaAcompanhamento
{
    private PDO $conexao;
    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }
    public function salva(): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->insertSemFilter();

        $sql = $this->conexao->prepare($sql);
        $sql->execute($geradorSql->bind);

        $this->id = $this->conexao->lastInsertId();
    }
    public function remove(): void
    {
        if (empty($this->id)) {
            throw new NotFoundHttpException('Agendamento de acompanhamento não encontrado.');
        }

        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->deleteSemGetter();

        $sql = $this->conexao->prepare($sql);
        $sql->execute($geradorSql->bind);

        if ($sql->rowCount() !== 1) {
            throw new UnprocessableEntityHttpException('Não foi possível remover agendamento de acompanhamento.');
        }
    }
    public function buscaPrazosPorPontoColeta(): array
    {
        if (empty($this->id_colaborador)) {
            throw new NotFoundHttpException('Ponto de coleta não encontrado.');
        }

        $pontoColeta = DB::selectOne(
            "SELECT
                pontos_coleta.dias_pedido_chegar,
                (
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT(
                            'id', pontos_coleta_agenda_acompanhamento.id,
                            'dia', pontos_coleta_agenda_acompanhamento.dia,
                            'horario', DATE_FORMAT(pontos_coleta_agenda_acompanhamento.horario, '%H:%i'),
                            'frequencia', pontos_coleta_agenda_acompanhamento.frequencia
                        )),
                        ']'
                    )
                    FROM pontos_coleta_agenda_acompanhamento
                    WHERE pontos_coleta_agenda_acompanhamento.id_colaborador = pontos_coleta.id_colaborador
                ) AS `json_agenda`
            FROM pontos_coleta
            WHERE pontos_coleta.id_colaborador = :id_colaborador
            GROUP BY pontos_coleta.id_colaborador;",
            ['id_colaborador' => $this->id_colaborador]
        );
        if (empty($pontoColeta)) {
            throw new NotFoundHttpException('Ponto de coleta não encontrado.');
        }
        $pontoColeta['agenda'] ??= [];

        return $pontoColeta;
    }
    public function limpaHorarios(): void
    {
        if (empty($this->horario)) {
            throw new InvalidArgumentException('Não foi possível encontrar agendamentos de acompanhamento.');
        }

        $sql = $this->conexao->prepare(
            "DELETE FROM pontos_coleta_agenda_acompanhamento
            WHERE pontos_coleta_agenda_acompanhamento.horario = :horario;"
        );
        $sql->bindValue(':horario', $this->horario, PDO::PARAM_STR);
        $sql->execute();
    }
    public function buscaPontosColetaAgendados(string $dia, string $horario): array
    {
        $sql = $this->conexao->prepare(
            "SELECT pontos_coleta_agenda_acompanhamento.id_colaborador
            FROM pontos_coleta_agenda_acompanhamento
            WHERE pontos_coleta_agenda_acompanhamento.horario = :horario
                AND pontos_coleta_agenda_acompanhamento.dia = :dia;"
        );
        $sql->bindValue(':horario', $horario, PDO::PARAM_STR);
        $sql->bindValue(':dia', $dia, PDO::PARAM_STR);
        $sql->execute();
        $pontosColeta = $sql->fetchAll(PDO::FETCH_COLUMN);
        $pontosColeta = array_map('intVal', $pontosColeta);

        return $pontosColeta;
    }
    public function removeHorariosPontuais(string $dia, string $horario): void
    {
        $sql = $this->conexao->prepare(
            "DELETE FROM pontos_coleta_agenda_acompanhamento
            WHERE pontos_coleta_agenda_acompanhamento.horario = :horario
                AND pontos_coleta_agenda_acompanhamento.dia = :dia
                AND pontos_coleta_agenda_acompanhamento.frequencia = :frequencia;"
        );
        $sql->bindValue(':horario', $horario, PDO::PARAM_STR);
        $sql->bindValue(':dia', $dia, PDO::PARAM_STR);
        $sql->bindValue(':frequencia', PontosColetaAgendaAcompanhamento::FREQUENCIA_PONTUAL, PDO::PARAM_STR);
        $sql->execute();
    }
}

<?php

namespace MobileStock\service\TransacaoFinanceira;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransacaoFinanceiraLogCriacaoService
{
    /**
     * Gera um log da transação com detalhes do aparelho do cliente e onde estava.
     *
     * @param $idTransacao int id da transação em transacao_financeiras.
     * @param $idColaborador int id do cliente.
     * @param $ip string IP do cliente
     * @param $user_agent string user-agent do cliente
     * @param $log int latitude do cliente
     * @param $longitude int longitude do colaborador
     */
    public static function criarLogTransacao(
        int $idTransacao,
        string $ip,
        string $userAgent,
        ?float $latitude,
        ?float $longitude
    ): void {
        $userAgent = substr($userAgent, 0, 200) ?? 'Desconhecido';

        $query = "INSERT INTO transacao_financeiras_logs_criacao (
                transacao_financeiras_logs_criacao.id_transacao,
                transacao_financeiras_logs_criacao.id_colaborador,
                transacao_financeiras_logs_criacao.ip,
                transacao_financeiras_logs_criacao.user_agent,
                transacao_financeiras_logs_criacao.latitude,
                transacao_financeiras_logs_criacao.longitude
            ) VALUES (:id_transacao, :id_colaborador, :ip, :user_agent, :latitude, :longitude)";

        $binds = [
            ':id_transacao' => $idTransacao,
            ':id_colaborador' => Auth::user()->id_colaborador,
            ':ip' => $ip,
            ':user_agent' => $userAgent,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
        ];

        DB::insert($query, $binds);
    }
}

<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDO;

class LoggerService
{
    // /**
    //  * Gera um log quando alguém clica em um link no meulook.
    //  *
    //  * @param $conexao PDO de conexão com o banco
    //  * @param $ip string IP do cliente
    //  * @param $userAgent string user-agent do cliente
    //  * @param $log string log do cliente
    //  * @param $idColaborador int id do colaborador
    //  * @param $idLink int id do colaborador que é dono do link.
    //  */
    // public static function criarLogLink(PDO $conexao, string $ip, string $userAgent, string $log, int $idColaborador, int $idLink)
    // {
    //     $sql = "INSERT INTO log_meulook_acesso_link (ip, user_agent, log, id_colaborador, id_compartilhador_link) VALUES (:ip, :user_agent, :log, :id_colaborador, :id_compartilhador_link)";
    //     $stm = $conexao->prepare($sql);
    //     $stm->bindValue(':ip', $ip);
    //     $stm->bindValue(':user_agent', $userAgent);
    //     $stm->bindValue(':log', $log);
    //     $stm->bindValue(':id_colaborador', $idColaborador);
    //     $stm->bindValue(':id_compartilhador_link', $idLink);
    //     $stm->execute();
    // }

    /**
     * Gera um log das pesquisas dos usuarios no meulook.
     *
     * @param $pesquisa string a pesquisa pra ser criada um log
     */
    public static function criarLogPesquisa(string $pesquisa): void
    {
        DB::insert(
            'INSERT INTO log_pesquisa (
                log_pesquisa.pesquisa,
                log_pesquisa.id_colaborador
            ) VALUES (
                :pesquisa,
                :id_colaborador
            )',
            [
                'pesquisa' => substr($pesquisa, 0, 255),
                'id_colaborador' => Auth::user()->id_colaborador,
            ]
        );
    }

    /**
     * Busca as ultimas pesquisas realizadas no meulook.
     *
     * @param $conexao PDO de conexão com o banco
     */
    public static function buscaLogPesquisa(PDO $conexao): array
    {
        $stmt = $conexao->query("SELECT
                                    COALESCE(colaboradores.razao_social, 'Anônimo') AS `razao_social`,
                                    log_pesquisa.pesquisa,
                                    date_format(log_pesquisa.data_criacao, '%d/%m/%Y - %H:%i') AS horario
                                 FROM log_pesquisa
                                 LEFT JOIN colaboradores ON colaboradores.id = log_pesquisa.id_colaborador
                                 ORDER BY log_pesquisa.data_criacao DESC
                                 LIMIT 200");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        return $logs;
    }
}

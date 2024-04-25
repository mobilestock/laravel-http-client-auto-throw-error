<?php

namespace MobileStock\service\TransacaoFinanceira;

use PDO;

class TransacaoFinanceiraLogCriacaoService
{

    /**
     * Gera um log da transação com detalhes do aparelho do cliente e onde estava.
     *
     * @param $conexao PDO de conexão com o banco
     * @param $idTransacao int id da transação em transacao_financeiras.
     * @param $idColaborador int id do cliente.
     * @param $ip string IP do cliente
     * @param $user_agent string user-agent do cliente
     * @param $log int latitude do cliente
     * @param $longitude int longitude do colaborador
     */
    public static function criarLogTransacao(PDO $conexao, int $idTransacao, int $idColaborador, string $ip, string $userAgent, ?float $latitude, ?float $longitude)
    {
        $userAgent = substr($userAgent, 0, 200) ?? 'Desconhecido';

        $sql = "INSERT INTO transacao_financeiras_logs_criacao (id_transacao, id_colaborador, ip, user_agent, latitude, longitude) VALUES (:id_transacao, :id_colaborador, :ip, :user_agent, :latitude, :longitude)";
        $stm = $conexao->prepare($sql);
        $stm->bindValue(':id_transacao', $idTransacao, PDO::PARAM_INT);
        $stm->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $stm->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
        $stm->bindValue(':latitude', $latitude, PDO::PARAM_STR);
        $stm->bindValue(':longitude', $longitude, PDO::PARAM_STR);
        $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stm->execute();
    }
}
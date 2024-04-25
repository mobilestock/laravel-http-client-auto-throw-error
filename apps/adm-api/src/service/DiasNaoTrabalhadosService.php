<?php

namespace MobileStock\service;

use DateTime;
use MobileStock\helper\GeradorSql;
use MobileStock\model\DiasNaoTrabalhados;

class DiasNaoTrabalhadosService extends DiasNaoTrabalhados
{
    public function salva(\PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
		$sql = $geradorSql->insert();
		$prepare = $conexao->prepare($sql);
        $prepare->execute($geradorSql->bind);

		$this->id = $conexao->lastInsertId();
    }
    public function remove(\PDO $conexao): void
    {
        $geradorSql = new GeradorSql($this);
		$sql = $geradorSql->deleteSemGetter();
		$prepare = $conexao->prepare($sql);
        $prepare->execute($geradorSql->bind);
    }
    public static function lista(\PDO $conexao): array
    {
        $sql = "SELECT 
                    dias_nao_trabalhados.id,
                    dias_nao_trabalhados.data,
                    UNIX_TIMESTAMP(dias_nao_trabalhados.data) AS `data_unix`,
                    dias_nao_trabalhados.id_usuario,
                    dias_nao_trabalhados.data_criacao
                FROM dias_nao_trabalhados
                ORDER BY data_unix ASC";
        $dados = $conexao->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $dadosFormatados = array_map(function ($item) {
            $item['data'] = (new DateTime($item['data']))->format('d/m/Y');
            $item['data_criacao'] = (new DateTime($item['data_criacao']))->format('d/m/Y');
            $item['id_usuario'] = (int) $item['id_usuario'];
            $item['id'] = (int) $item['id'];
            return $item;
        }, $dados);

        return $dadosFormatados;
    }
}
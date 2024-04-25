<?php

namespace MobileStock\service\TransacaoFinanceira;

use MobileStock\helper\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraLink;

class TransacaoFinanceiraLinksService
{
    private \PDO $conexao;
    private TransacaoFinanceiraLink $transacaoFinanceiraLink;

    public function __construct(\PDO $conn, TransacaoFinanceiraLink $link)
    {
        $this->conexao = $conn;
        $this->transacaoFinanceiraLink = $link;
    }

    public function insere(): void
    {
        $geradorSql = new GeradorSql($this->transacaoFinanceiraLink);
        $sql = $geradorSql->insert();

        DB::exec($sql, $geradorSql->bind, $this->conexao);

        $this->transacaoFinanceiraLink->id = $this->conexao->lastInsertId();
    }
}
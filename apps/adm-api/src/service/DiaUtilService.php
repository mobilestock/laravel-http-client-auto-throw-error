<?php

namespace MobileStock\service;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MobileStock\service\Cache\CacheManager;
use PDO;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DiaUtilService
{
    const LIMITE_DIAS_CALCULOS = 30;
    protected array $diasUteis;
    protected PDO $conexao;
    protected CacheInterface $cache;
    public function __construct(CacheInterface $cache, PDO $conexao)
    {
        $this->conexao = $conexao;
        $this->cache = $cache;
    }
    /**
     * Verifica se a data informada é um dia útil
     * @param \PDO $conexao
     * @param string $data Data no formato Y-m-d | Y-m-d H:i:s
     * @return bool
     */
    public static function ehDiaUtil(string $data): bool
    {
        $resultado = DB::selectOneColumn('SELECT VERIFICA_DIA_UTIL(?) eh_dia_util', [$data]);

        return $resultado;
    }
    public function buscaProximosDiasUteis(Carbon $dataCalculo = null): array
    {
        $dataCalculo ??= new Carbon('NOW');
        $binds = [];
        $partesSql = [];
        for ($index = 0; $index < self::LIMITE_DIAS_CALCULOS; $index++) {
            $bindKey = ":data_$index";
            $binds[$bindKey] = $dataCalculo->format('Y-m-d');
            $partesSql[] = "SELECT $bindKey AS `data`, VERIFICA_DIA_UTIL($bindKey) AS `eh_dia_util`";
            $dataCalculo->add('1 DAY');
        }

        $sql = implode(' UNION ALL ', $partesSql);
        //Event::listenOnce(function (StatementPrepared $event) {
        //    $event->statement->setFetchMode(PDO::FETCH_COLUMN, 0);
        //});
        $sql = $this->conexao->prepare(
            "SELECT _tabela_datas_uteis.data
            FROM ( $sql ) AS `_tabela_datas_uteis`
            WHERE _tabela_datas_uteis.eh_dia_util;",
            $binds
        );
        $sql->execute($binds);
        $diasUteis = $sql->fetchAll(PDO::FETCH_COLUMN);

        return $diasUteis;
    }
    public function buscaCacheProximosDiasUteis(): array
    {
        if (!empty($this->diasUteis)) {
            return $this->diasUteis;
        }

        $this->diasUteis = $this->cache->get('dias_uteis', function (ItemInterface $item): array {
            $resultado = $this->buscaProximosDiasUteis();
            CacheManager::sobrescreveMergeByLifetime($this->cache);
            $item->expiresAfter(60 * 60 * 24);

            return $resultado;
        });

        return $this->diasUteis;
    }
}

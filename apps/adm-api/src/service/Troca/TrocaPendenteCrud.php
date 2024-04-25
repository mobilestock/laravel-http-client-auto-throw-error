<?php

namespace MobileStock\service\Troca;

use MobileStock\database\Conexao;
use MobileStock\model\TrocaAgendadaItem;
use MobileStock\model\TrocaPendenteItem;
use MobileStock\repository\MobileStockBD;
use MobileStock\repository\TrocaPendenteRepository;
use MobileStock\service\Lancamento\LancamentoService;
use MobileStock\service\LogisticaItemService;
use PDO;

abstract class TrocaPendenteCrud
{
    public static function salva(TrocaPendenteItem $troca, PDO $conexao = null, bool $forcarTroca = false)
    {
        $conexao = is_null($conexao) ? Conexao::criarConexao() : $conexao;

        self::rotinaSalvaAntes($troca, $conexao);

        $query = '';

        $dados = $troca->extrair();
        $dados = array_filter($dados, function ($i) {
            return $i !== null;
        });
        $size = sizeof($dados);

        $count = 0;
        $query = 'INSERT INTO ' . $troca->nome_tabela . ' (';
        foreach ($dados as $key => $l) {
            $count++;
            $query .= $size > $count ? $key . ', ' : $key;
        }

        $count = 0;
        $query .= ')VALUES(';
        foreach ($dados as $key => $l) {
            $count++;
            $query .= $size > $count ? ':' . $key . ', ' : ':' . $key;
        }

        $query .= ')';
        //        echo '<pre>';
        //        echo $query;
        //        var_dump($troca);
        $sth = $conexao->prepare($query);
        $bindValues = [];
        foreach ($dados as $key => $l) {
            $bindValues[":$key"] = $l;
        }
        if ($forcarTroca && ($bindValues[':taxa'] ?? 0)) {
            $bindValues[':taxa'] = 0;
        }

        $sth->execute($bindValues);

        return self::rotinaSalvaDepois($troca, $conexao, $forcarTroca);
    }

    public static function busca(array $params, $trocaAgendada = false, PDO $conn = null)
    {
        $conn = $conn === null ? Conexao::criarConexao() : $conn;

        $obj = $trocaAgendada
            ? new TrocaAgendadaItem(0, 0, 0, 0.0, '312321321', '')
            : new TrocaPendenteItem(0, 0, 0, 0, 0, 0, 0, 0, 0);
        return MobileStockBD::operacao_listar(
            $obj,
            [
                'where' => $params,
            ],
            $conn
        );
    }

    public static function deleta(TrocaPendenteItem $troca, PDO $conn = null)
    {
        $conn = $conn === null ? Conexao::criarConexao() : $conn;

        self::rotinaRemoveAntes($troca, $conn);

        $sql = 'DELETE FROM ' . $troca->nome_tabela . ' WHERE uuid = :uuid';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('uuid', $troca->getUuid());
        $stmt->execute();

        return $troca;
    }

    private static function rotinaSalvaDepois(TrocaPendenteItem $troca, PDO $conexao, bool $forcarTroca)
    {
        if ($troca instanceof TrocaAgendadaItem) {
            $troca->setId($conexao->lastInsertId());
            return $troca;
        }

        TrocaPendenteRepository::removeTrocaAgendadaPorUuid($troca, $conexao);
        LancamentoService::criaCreditoDebitoDevolucao($troca, $conexao, $forcarTroca);

        try {
            $itemLogistica = new LogisticaItemService();
            $itemLogistica->uuid_produto = $troca->getUuid();
            $itemLogistica->situacao = $troca->getSituacaoFaturamentoItem();
            $itemLogistica->atualiza($conexao);
        } catch (\InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(
                'O produto "' .
                    $troca->getUuid() .
                    '" não pode ser adicionado na troca pois ele está sendo usado em outro processo'
            );
        }

        return $troca;
    }

    private static function rotinaRemoveAntes(TrocaPendenteItem $troca, PDO $conn)
    {
        if ($troca instanceof TrocaAgendadaItem) {
            return $troca;
        } else {
            throw new \DomainException('Não é possivel remover uma troca');
        }
    }

    private static function rotinaSalvaAntes(TrocaPendenteItem $troca, PDO $conexao)
    {
        if ($troca->getDefeito() === true && !$troca->getDescricaoDefeito()) {
            throw new \InvalidArgumentException('Não é possivel adicionar uma troca defeito sem descrição');
        }
    }
}

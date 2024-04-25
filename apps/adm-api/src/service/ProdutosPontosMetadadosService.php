<?php

namespace MobileStock\service;

use Exception;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\Validador;
use PDO;

class ProdutosPontosMetadadosService
{
    const GRUPO_PRODUTOS_PONTOS = 'PRODUTOS_PONTOS';
    const GRUPO_REPUTACAO_FORNECEDORES = 'REPUTACAO_FORNECEDORES';

    public static function buscaMetadados(PDO $conexao, string $grupo): array
    {
        $stmt = $conexao->prepare(
            "SELECT produtos_pontos_metadados.id,
                produtos_pontos_metadados.chave,
                produtos_pontos_metadados.valor,
                produtos_pontos_metadados.observacao
            FROM produtos_pontos_metadados
            WHERE produtos_pontos_metadados.grupo = :grupo"
        );
        $stmt->bindValue(':grupo', $grupo, PDO::PARAM_STR);
        $stmt->execute();
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (sizeof($consulta) === 0) throw new Exception("Não há metadados de pontuação, contate o suporte");
        return $consulta;
    }

    public static function buscaValoresMetadados(PDO $conexao, array $chaves = []): array
    {
        $where = '';
        $bind = [];
        if ($chaves) {
            [$bindKeys, $bind] = ConversorArray::criaBindValues($chaves);
            $where = " AND produtos_pontos_metadados.chave IN ($bindKeys)";
        }
        $stmt = $conexao->prepare(
            "SELECT produtos_pontos_metadados.chave,
                produtos_pontos_metadados.valor
            FROM produtos_pontos_metadados
            WHERE TRUE $where"
        );
        $stmt->execute($bind);
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($consulta)) throw new Exception("Não há metadados de pontuação, contate o suporte");
        $consulta = array_reduce($consulta, function ($inicial, $atual) {
            return array_merge($inicial, [ $atual['chave'] => $atual['valor'] ]);
        }, []);
        return $consulta;
    }

    public static function alterarMetadados(PDO $conexao, array $dados, string $grupo): void
    {
        $bind = [':grupo' => $grupo];
        $cases = 'CASE';
        $chaves = [];
        foreach ($dados as $key => $value) {
            Validador::validar($value, [
                'valor' => [Validador::NUMERO],
                'chave' => [Validador::OBRIGATORIO]
            ]);
            $bindChave = ":chave_{$key}";
            $chaves[] = $bindChave;
            $bindValor = ":valor_{$key}";
            $bind[$bindChave] = $value['chave'];
            $bind[$bindValor] = $value['valor'];
            $cases .= " WHEN produtos_pontos_metadados.chave = $bindChave THEN $bindValor";
        }
        $cases .= ' END';

        $stmt = $conexao->prepare(
            "UPDATE produtos_pontos_metadados
            SET produtos_pontos_metadados.valor = ($cases)
            WHERE produtos_pontos_metadados.grupo = :grupo
                AND produtos_pontos_metadados.chave IN (" . implode(',', $chaves) . ")"
        );
        $stmt->execute($bind);
        if ($stmt->rowCount() === 0) throw new Exception('Nenhum dado foi atualizado!');
    }
}
<?php

namespace MobileStock\service;

use Exception;
use MobileStock\database\Conexao;
use PDO;

/**
 * @deprecated
 * Class LancamentoService
 * @package MobileStock\service
 */
class LancamentoService
{
    public static function atualiza(array $parametros, PDO $conexao = null): bool
    {
        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();

        if ($conexao->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $sql = "UPDATE lancamento_financeiro SET id = id";
        $bindValues = [];

        if (isset($parametros['SET'])) {
            foreach ($parametros['SET'] as $key => $valor) {
                $stripos = stripos($valor, '(CAMPO)');
                if ($stripos !== false || $valor === 'now()') {
                    if ($stripos) {
                        $valor = substr($valor, 0, $stripos);
                    }

                    $sql .= ", $key = {$valor} ";
                    continue;
                }


                $sql .= ", $key = :{$key} ";
                $bindValues = array_merge($bindValues, [":{$key}" => $valor]);
            }
        }

        $sql .= ' WHERE 1 = 1 ';
        if (isset($parametros['WHERE'])) {
            foreach ($parametros['WHERE'] as $key => $valor) {
                $sinal = '=';
                if (stripos(strtolower($valor), '[regexp]')) {
                    $sinal = 'REGEXP';
                } elseif (stripos(strtolower($valor), '[like]')) {
                    $sinal = 'LIKE';
                }

                $valor = trim(preg_replace('/\[[a-zA-Z]*\]/', '', $valor));

                $sql .= " AND {$key} $sinal :{$key} ";
                $bindValues = array_merge($bindValues, [":{$key}" => $valor]);
            }
        }

        $sql .= ';
         call atualiza_lancamento(:idLancamentoStoredProcedure);';

        $bindValues = array_merge($bindValues, [
            ':idLancamentoStoredProcedure' => $parametros['WHERE']['id'] ?? 0
        ]);

        $stmt = $conexao->prepare($sql);
        return $stmt->execute($bindValues);
    }


    public static function buscaLancamentoFinanceiroPorSplit(PDO $conexao, string $split)
    {
        $query = "SELECT id, pedido_origem, id_colaborador FROM lancamento_financeiro WHERE id_split='{$split}';";
        $stm = $conexao->prepare($query);
        $stm->execute();
        return $stm->fetch(PDO::FETCH_ASSOC);
    }
}
<?php

namespace MobileStock\service\Reembolso;

use Conexao;
use Exception;
use MobileStock\model\Reembolso;
use PDO;

class ReembolsoService extends Reembolso
{
    public function criar(PDO $conexao): bool
    {
        $dados = [];
        $value = [];
        $parametro = false;
        $sql = "INSERT INTO reembolso ";

        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            array_push($dados, $key);
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            array_push($value, $valor);
            if (in_array($key, ['id_recebedor', 'id_pagador', 'id_lancamento_origem', 'valor', 'conta', 'data_emissao', 'id_atendimento'])) {
                (!$parametro) ? $parametro = $key . " = " . $valor : $parametro .= " AND " . $key . " = " . $valor;
            }
        }
        if (sizeof($dados) === 0) {
            throw new Exception('Não Existem informações para adiconar na tabela');
        }

        $sql .= "(" . implode(',', $dados) . ")VALUES(" . implode(',', $value) . ");";
        $result = $conexao->exec($sql);
        return $result;
    }

    public static function buscarTodos()
    {
        $conexao = Conexao::criarConexao();
        $sql = "SELECT *,(SELECT razao_social FROM colaboradores WHERE colaboradores.id = reembolso.id_recebedor) as nome FROM reembolso ORDER BY reembolso.situacao ASC;";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }
    public function atualiza(PDO $conexao)
    {
        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();

        if ($conexao->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $id = $this->id;
        $campos = $this;
        unset($campos->id);
        $sqlLoop = "";
        $bindValues = [];
        foreach ($campos as $campo => $value) {

            $sqlLoop .= "$campo = :$campo,";
            $bindValues = array_merge($bindValues, [":$campo" => $value]);
        }
        $sqlLoopCorreto = substr($sqlLoop, 0, strlen($sqlLoop) - 1);
        $sql = "UPDATE reembolso SET $sqlLoopCorreto WHERE id = {$id} ;";

        $execSql = $conexao->prepare($sql);

        foreach ($bindValues as $key => $valor) :
            $execSql->bindValue($key, $valor);
        endforeach;

        $Q1 = $execSql->execute();
        //$Q2 = $conexao->query('CALL atualiza_lancamento(0);')->execute();
        if ($Q1) :
            return 1;
        else :
            throw new Exception("Erro ao atualizar lançamento", 500);
        endif;
    }
}

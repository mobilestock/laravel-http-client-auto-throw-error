<?php

namespace MobileStock\service\Lancamento;

use Exception;
use MobileStock\database\Conexao;
use MobileStock\model\Lancamento;
use MobileStock\service\Lancamento\LancamentoService;
use PDO;

abstract class LancamentoCrud extends LancamentoService
{
    /**
     * @param array $params
     * @param PDO|null $conn
     * @return Lancamento[]
     */
    
    public static function busca(array $params, PDO $conn = null): array
    {
        $conn = !is_null($conn) ? $conn : Conexao::criarConexao();

        $sql = 'SELECT * FROM lancamento_financeiro WHERE 1 = 1';
        foreach ($params as $key => $param) {

            $sinal = '=';

            if (stripos($param, '[regexp]') !== false) {
                $sinal = 'REGEXP';
                $param = "'" . trim(substr($param, 0, stripos($param, '[regexp]'))) . "'";
            }

            if (stripos($param, '[<>]') !== false) {
                $sinal = '<>';
                $param = substr($param, 0, stripos($param, '[<>]'));
            }

            $sql .= " AND $key $sinal $param ";
        }

        $sql .= " ";
        $arrayLancamentos = $conn->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $listaObjetosColaboradores = [];
        foreach ($arrayLancamentos as $colaborador) {


            $listaObjetosColaboradores[] = Lancamento::hidratar($colaborador);
        }
        return $listaObjetosColaboradores;
    }

    public static function salva(PDO $conexao, Lancamento $lancamento): Lancamento
    {
        $query = '';

        $dados = $lancamento->extrair();

        //$lancamento = array_filter($lancamento);

        $dados = array_filter($dados, function ($i) {
            return $i !== null;
        });
        $size = sizeof($dados);

        $count = 0;
        $query = "INSERT INTO lancamento_financeiro (";
        foreach ($dados as $key => $l) {
            $count++;
            $query .= $size > $count ? $key . ", " : $key;
        }

        $count = 0;
        $query .= ")VALUES(";
        foreach ($dados as $key => $l) {
            $count++;
            $query .= $size > $count ? ":" . $key . ", " : ":" . $key;
        }

        $query .= ")";
        //        echo '<pre>';
        //        echo $query;
        //        var_dump($lancamento);
        $sth = $conexao->prepare($query);
        foreach ($dados as $key => $l) {
            $sth->bindValue($key, $l, (new LancamentoService)->typeof($l));
        }

        if (!$sth->execute())
            throw new Exception('Erro ao gerar lancamento financeiro', 1);

        $lancamento->id = $conexao->lastInsertId();
        return $lancamento;
    }

    public static function deleta(Lancamento $lancamento, PDO $conexao = null)
    {
        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();

        $stmt = $conexao->prepare('DELETE FROM lancamento_financeiro WHERE id = :id');
        $stmt->bindValue('id', $lancamento->id, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (\PDOException $exception) {
            throw new \InvalidArgumentException("Lançamento {$lancamento->id} não pode ser removido pois está pago");
        }
    }

    public static function atualiza(Lancamento $lancamento, PDO $conexao = null): Lancamento
    {

        $conexao = !is_null($conexao) ? $conexao : Conexao::criarConexao();


        if ($conexao->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        $campos = $lancamento->extrair();
        $sqlLoop = "";
        $bindValues = [];
        foreach ($campos as $campo => $value) {

            $sqlLoop .= "$campo = :$campo,";
            $bindValues = array_merge($bindValues, [":$campo" => $value]);
        }
        $sqlLoopCorreto = substr($sqlLoop, 0, strlen($sqlLoop) - 1);
        $sql = "UPDATE lancamento_financeiro SET origem = origem, $sqlLoopCorreto WHERE id = {$lancamento->id} ;";

        $execSql = $conexao->prepare($sql);

        foreach ($bindValues as $key => $valor) :
            $execSql->bindValue($key, $valor);
        endforeach;

        $Q1 = $execSql->execute();
        $Q2 = $conexao->query(' CALL atualiza_lancamento(0);')->execute();
        if ($Q1 && $Q2) :
            return $lancamento;
        else :
            throw new Exception("Erro ao atualizar lançamento", 500);
        endif;
    }
}

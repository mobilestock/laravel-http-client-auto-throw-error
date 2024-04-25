<?php

namespace MobileStock\repository;

use MobileStock\database\Conexao;
use PDO;

class MovimentacoesManuaisCaixaRepository extends MobileStockBD
{
    public static function criarMovimentacaoManual(array $parametros = ['tipo' => 'E', 'valor' => '000.00', 'motivo' => 'exemplo', 'responsavel' => 0], $transacao = ''): bool
    {
        $conexao = $transacao instanceof PDO ? $transacao : Conexao::criarConexao();

        if (!$conexao instanceof PDO) {
            throw new \PDOException("Opa faltou a conexao com o banco de dados.", 500);
        }

        $paramentrosPermitidos = [
            'tipo',
            'valor',
            'motivo',
            'responsavel'
        ];

        foreach ($parametros as $key => $dados) {
            if (!in_array($key, $paramentrosPermitidos) || !$key) {
                throw new \InvalidArgumentException("Parametro $key invalido, preencha para efetuar sua inserção", 400);
            }
        }

        if (!$parametros['responsavel'] || !is_int($parametros['responsavel'])) {
            throw new \InvalidArgumentException("Parametro invalido, o campo responsavel deve ser preenchido para efetuar sua inserção", 400);
        }

        if (!$parametros['valor']) {
            throw new \InvalidArgumentException("Parametro invalido, o campo valor deve ser preenchido para efetuar sua inserção", 400);
        }

        $tipo = strtoupper($parametros['tipo']);


        $query = "INSERT INTO  movimentacoes_manuais_caixa (
             tipo , 
             valor ,  
             motivo , 
             responsavel,
             criado_em ) 
             VALUES ( 
                 '{$tipo}' , 
                 '{$parametros['valor']}' , 
                 '{$parametros['motivo']}' , 
                 '{$parametros['responsavel']}' , 
                 now() );";

        $banco = $conexao->prepare($query);

        if ($banco->execute()) {

            return true;
        }

        throw new \PDOException("Não foi possivel salvar esta movimentação no banco de dados", 400);
    }
    public static function atualizaMovimentacaoManual(array $parametros = ['id' => 0, 'idColaborador' => 0], $transacao = ''): bool
    {
        $conexao = $transacao instanceof PDO ? $transacao : Conexao::criarConexao();

        if (!$conexao instanceof PDO) {
            throw new \PDOException("Opa faltou a conexao com o banco de dados.", 500);
        }

        $paramentrosPermitidos = [
            'id',
            'idColaborador'
        ];

        foreach ($parametros as $key => $dados) {
            if (!in_array($key, $paramentrosPermitidos) || !$key || !$dados) {
                throw new \InvalidArgumentException("Parametro $key invalido, preencha para efetuar sua inserção", 400);
            }
        }

        $query = "UPDATE movimentacoes_manuais_caixa SET conferido_por = '{$parametros['idColaborador']}' , conferido_em = NOW() WHERE id = '{$parametros['id']}';";

        $banco = $conexao->prepare($query);

        if ($banco->execute()) {

            return true;
        }

        throw new \PDOException("Não foi possivel salvar esta movimentação no banco de dados", 400);
    }
    public function saldoTotal($data_inicio, $data_fim)
    {
        $conexao = Conexao::criarConexao();
        if ($data_inicio != '' && $data_fim != '') {
            $filtro = " DATE(criado_em) >= 'DATE({$data_inicio})' AND DATE(criado_em) <= 'DATE({$data_fim})'";
        } else if ($data_inicio) {
            $filtro = " DATE(criado_em) >= 'DATE({$data_inicio})'";
        } else if ($data_fim) {
            $filtro = "DATE(criado_em) <= 'DATE({$data_fim})'";
        } else {
            $filtro = "DATE(criado_em) <=DATE(NOW())";
        }
        $sql = "SELECT SUM(
                            CASE WHEN movimentacoes_manuais_caixa.tipo = 'E' THEN movimentacoes_manuais_caixa.valor ELSE 0 END
                           
                        )receber, 
                        SUM(
                            CASE WHEN movimentacoes_manuais_caixa.tipo = 'S' THEN movimentacoes_manuais_caixa.valor ELSE 0 END
                        )pagar 
                            FROM movimentacoes_manuais_caixa WHERE {$filtro}; ";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = floatVal($resultado['receber']) - floatVal($resultado['pagar']);
        return $total;
    }
    public function busca($data_inicio, $data_fim)
    {
        $conexao = Conexao::criarConexao();
        if ($data_inicio != '' && $data_fim != '') {
            $filtro = " DATE(criado_em) >= DATE('{$data_inicio}') AND DATE(criado_em) <= DATE('{$data_fim}')";
        } else if ($data_inicio) {
            $filtro = " DATE(criado_em) >= DATE('{$data_inicio}')";
        } else if ($data_fim) {
            $filtro = " DATE(criado_em) <= DATE('{$data_fim}')";
        } else {
            $filtro = " DATE(criado_em) = DATE(NOW())";
        }
        $sql = "SELECT movimentacoes_manuais_caixa.id, movimentacoes_manuais_caixa.motivo, movimentacoes_manuais_caixa.tipo,movimentacoes_manuais_caixa.valor,
                CONCAT(movimentacoes_manuais_caixa.id_faturamento,'/', COALESCE((SELECT transacao_financeiras_faturamento.id_faturamento FROM transacao_financeiras_faturamento WHERE transacao_financeiras_faturamento.id_transacao = movimentacoes_manuais_caixa.id_faturamento),'')) id_faturamento, 
                DATE_FORMAT(movimentacoes_manuais_caixa.conferido_em,'%d/%m/%Y %H:%i:%s') AS conferido_em,  DATE_FORMAT(movimentacoes_manuais_caixa.criado_em,'%d/%m/%Y %H:%i:%s') AS criado_em, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = movimentacoes_manuais_caixa.responsavel)responsavel FROM movimentacoes_manuais_caixa WHERE {$filtro} ORDER BY criado_em DESC; ";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }
}

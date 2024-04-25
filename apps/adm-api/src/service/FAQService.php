<?php

namespace MobileStock\service;

use Error;
use MobileStock\model\FAQ;
use PDO;

class FAQService extends FAQ
{
    public function inserir(PDO $conexao)
    {
        $sql = 'INSERT INTO faq(' . implode(',', array_keys(array_filter(get_object_vars($this)))) . ') VALUES (';
        foreach ($this as $key => $value) {
            if (!$value) {
                continue;
            }

            $sql .= ":{$key},";
        }

        $sql = mb_substr($sql, 0, mb_strlen($sql) - 1) . ')';
        $stmt = $conexao->prepare($sql);
        $bind = array_filter(get_object_vars($this));
        $stmt->execute($bind);

        $this->id = $conexao->lastInsertId();
        return $this->id;
    }
    public function atualizar(PDO $conexao)
    {
        $dados = [];
        $sql = 'UPDATE faq SET ';

        foreach ($this as $key => $valor) {
            if (!$valor) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            $dados[] = $key . ' = ' . $valor;
        }
        if (sizeof($dados) === 0) {
            throw new Error('Não Existe informações para ser atualizada');
        }

        $sql .= ' ' . implode(',', $dados) . " WHERE faq.id = '" . $this->id . "'";

        return $conexao->exec($sql);
    }
    public function responder(PDO $conexao)
    {
        $sql = 'UPDATE faq SET faq.resposta = "' . $this->resposta . '" WHERE faq.id = ' . $this->id;
        $stmt = $conexao->prepare($sql);
        $resultado = $stmt->execute();
        return $resultado;
    }
    public static function buscaMobilePay(PDO $conexao, int $chave = 0)
    {
        $condition = ' AND faq.resposta is not null';
        $order = 'order by frequencia,id DESC';
        if ($chave != 0) {
            switch ($chave) {
                case '1':
                    $condition = ' AND faq.resposta is null ';
                    break;
                case '2':
                    $order = ' ORDER BY faq.id DESC ';
                    break;
            }
        }
        $sql = "SELECT * FROM faq WHERE faq.tipo = 'MP' $condition $order   LIMIT 50 ";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function buscarId(PDO $conexao)
    {
        $sql = 'SELECT * FROM faq WHERE faq.id = ' . $this->id;
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }
}

?>

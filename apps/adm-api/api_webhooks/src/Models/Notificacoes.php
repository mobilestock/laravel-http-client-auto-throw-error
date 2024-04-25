<?php

namespace api_webhooks\Models;


use PDO;
use PDOException;

class Notificacoes
{
    public static function criaNotificacoes(PDO $conexao, string $mensagem, string $prioridade = 'NA')
    {
        if ($mensagem) {
        $consulta = $conexao->prepare("INSERT INTO notificacoes(id_cliente,titulo,mensagem,recebida,tipo_mensagem,prioridade)VALUES(1,'Aviso',:mensagem,0,'Z',:prioridade);");
        $consulta->bindParam(":mensagem", $mensagem,  PDO::PARAM_STR);
        $consulta->bindParam(":prioridade", $prioridade,  PDO::PARAM_STR);
        $consulta->execute(); 

        }
    }
}

<?php

namespace MobileStock\repository;

use MobileStock\database\Conexao;
use MobileStock\model\ModelInterface;

abstract class MobileStockBD
{
    /**
 *  estou construindo uma classe central para acesso ao CRUD de uma tabela
 * de forma facil, se quiser ajudar so convesar comigo
 * 
 * OBSERVAÇÂO: ESTA EM CONTRUÇÂO NÂO RECOMENTO SAIR USANDO NO SITEMA TODO
 * @author Gustavo210
*/
    public static function operacao_listar(ModelInterface $class = self::class | NULL, array $parametros = ['where' => [], 'group' => '', 'order' => [], 'pagina' => 1, 'limit' => 100] , $transacao = ''):array
    {

        $conexao = $transacao instanceof \PDO ? $transacao : Conexao::criarConexao();

        if (!$conexao instanceof \PDO) {
            throw new \PDOException("Opa faltou a conexao com o banco de dados.", 500);
        }

        $paramentrosPermitidos = ['where', 'group', 'order', 'pagina', 'limit'];

        foreach ($parametros as $key => $dados) {
            if (!in_array($key, $paramentrosPermitidos)) {
                throw new \PDOException("Parametro de busca não permitido, use where, group, order, pagina ou limite para efetuar sua busca", 400);
            }
        }

        $query = "SELECT * FROM {$class->nome_tabela} WHERE 1 = 1";
        $WHERE  = isset($parametros['where'])  ? $parametros['where']  : [];
        $GROUP  = isset($parametros['group'])  ? $parametros['group']  : '';
        $ORDER  = isset($parametros['order'])  ? $parametros['order']  : [];
        $PAGINA = isset($parametros['pagina']) ? $parametros['pagina'] : 1;
        $LIMIT  = isset($parametros['limit'])  ? $parametros['limit']  : 100;


        if (!!$WHERE && Count($parametros['where']) !== 0) {

            foreach ($WHERE as $campo => $busca) {
                if (is_array($busca)) {
                    $condicao = strtoupper($busca[0]);

                    switch ($condicao) {
                        case 'LIKE':
                            $query .= " AND $campo $condicao '%$busca[1]%' ";
                            break;

                        case '>=':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        case '<=':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        case '=':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        case '<>':
                            $query .= " AND $campo $condicao '$busca[1]' ";
                            break;

                        default:
                            break;
                    }
                } else {
                    $query .= " AND $campo = '$busca'";
                }
            }
        }

        if (!!$GROUP) {
            $query .= " GROUP BY {$GROUP} ";
        }

        if (!!$ORDER && Count($ORDER) >= 1) {

            $query .= " ORDER BY ";

            foreach ($ORDER as $ordem => $sentido) {
                $ordenador = strtoupper($sentido);

                if ($ordem) {

                    $query .= " $ordem $ordenador, ";
                } else {
                    $query .= " $sentido, ";
                }
            }

            $query .= " 1";
        }

        $offset = $PAGINA ? $PAGINA * $LIMIT - $LIMIT  : 0;

        $query .= " LIMIT $LIMIT OFFSET $offset";

        if ($resultado = $conexao->query($query)) {
            $listaNotificacoes = $resultado->fetchAll(\PDO::FETCH_ASSOC);

            $listaObjetosNotificacao = [];

            foreach ($listaNotificacoes as $notificacao) {

                $listaObjetosNotificacao[] = $class::hidratar($notificacao);
            }

            return $listaObjetosNotificacao;
        }
    }

    public static function operacao_salvar( $class = self::class | NULL, $parametros = [])
    {
        throw new \Exception("Este metodo não esta disponivel para uso", 500);
    }

    public static function operacao_atualizar( $class = self::class | NULL, $parametros = [])
    {
        throw new \Exception("Este metodo não esta disponivel para uso", 500);
    }

    public static function operacao_deletar( $class = self::class | NULL, $parametros = [])
    {
        throw new \Exception("Este metodo não esta disponivel para uso", 500);
    }
}

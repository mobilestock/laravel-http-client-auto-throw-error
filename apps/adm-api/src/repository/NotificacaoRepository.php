<?php

namespace MobileStock\repository;

use MobileStock\database\Conexao;
use MobileStock\model\Notificacao;
use PDO;

/**
 * @author gustavo
 *
 * Classe responsavel por todas as notificações do sistema
 * OBS: classe criada em 26/10/2020, neste momento ela não tem responsabilidade total do sistema, mas a ideia é centralizar aqui.
 */
class NotificacaoRepository
{
    /**
     * @return bool
     *
     * Enviar notificação para 1 cliente
     *
     * $colaboradores formato [ 10 , 11 , 12 , 13 ]
     *
     */
    public static function enviar(
        array $parametros = [
            'colaboradores' => [],
            'titulo' => '',
            'imagem' => null,
            'mensagem' => '',
            'tipoMensagem' => 'A',
            'tipoFrete' => 0,
            'destino' => 'MS',
        ],
        $transacao = ''
    ): bool {
        $conexao = $transacao instanceof PDO ? $transacao : Conexao::criarConexao();

        if (!$conexao instanceof PDO) {
            throw new \PDOException('Opa faltou a conexao com o banco de dados.', 500);
        }

        $paramentrosPermitidos = [
            'colaboradores',
            'titulo',
            'imagem',
            'mensagem',
            'tipoMensagem',
            'tipoFrete',
            'destino',
        ];

        foreach ($parametros as $key => $dados) {
            if (!in_array($key, $paramentrosPermitidos) || !$key) {
                throw new \PDOException(
                    'Parametro invalido, use colaboradores, titulo, imagem, mensagem, tipoMensagem e tipoFrete para efetuar sua inserção',
                    400
                );
            }
        }

        if (!$parametros['titulo']) {
            $parametros['titulo'] = 'Notificação'; // Titulo padrão 100% criativo para notificações sem titulos.
        }

        if (!$parametros['mensagem']) {
            throw new \PDOException(
                'Parametro invalido, o campo mensagem deve ser preenchido para efetuar sua inserção',
                400
            );
        }

        if (!$parametros['colaboradores']) {
            throw new \PDOException(
                'Parametro invalido, o campo colaboradores deve ser preenchido para efetuar sua inserção',
                400
            );
        }

        $tipoMensagem = $parametros['tipoMensagem'] ?? 'A';
        $tipoFrete = $parametros['tipoFrete'] ?? 0;

        $tipoMensagemTratada = mb_strtoupper($tipoMensagem);

        $query = '';

        foreach ($parametros['colaboradores'] as $colaborador) {
            $query .= "INSERT INTO  notificacoes ( id_cliente , titulo, imagem, mensagem, destino, tipo_frete , tipo_mensagem , data_evento ) VALUES ( {$colaborador} , '{$parametros['titulo']}', '{$parametros['imagem']}', '{$parametros['mensagem']}', '{$parametros['destino']}', {$tipoFrete} , '{$tipoMensagemTratada}' , now() );";
        }

        $banco = $conexao->prepare($query);

        if ($banco->execute()) {
            return true;
        }

        throw new \PDOException('Não foi possivel salvar esta notificação no banco de dados', 400);
    }
    /**
     * @return bool
     *
     * Enviar notificação para 1 cliente
     *
     * $colaboradores formato [ 10 , 11 , 12 , 13 ]
     *
     */
    /**
     * OBS: Jose pediu para retirar a validação de erros neste medodo.
     * motivo explicado: nao posso jogar um erro dentro do catch.
     * data:27/01/2021
     */
    public static function enviarSemValidacaoDeErro(
        array $parametros = [
            'colaboradores' => [],
            'titulo' => 'Aviso',
            'mensagem' => '',
            'tipoMensagem' => 'A',
            'tipoFrete' => 0,
        ],
        PDO $conexao
    ): bool {
        $tipoMensagem = $parametros['tipoMensagem'] ?? 'A';
        $tipoFrete = $parametros['tipoFrete'] ?? 0;

        $tipoMensagemTratada = mb_strtoupper($tipoMensagem);

        $parametros['mensagem'] = str_replace("'", '', $parametros['mensagem']);
        $query = '';

        foreach ($parametros['colaboradores'] as $colaborador) {
            $query .= "INSERT INTO  notificacoes ( id_cliente , titulo, mensagem , tipo_frete , tipo_mensagem , data_evento ) VALUES ( {$colaborador} , '{$parametros['titulo']}', '{$parametros['mensagem']}' , {$tipoFrete} , '{$tipoMensagemTratada}' , now() );";
        }

        $banco = $conexao->prepare($query);

        if ($banco->execute()) {
            return true;
        }
        return false;
    }

    /**
     * lista todas as notificações dada aos parametros especificados.
     */
    public static function listar(
        array $parametros = ['where' => [], 'group' => '', 'order' => [], 'pagina' => 1, 'limit' => 100],
        $transacao = ''
    ): array {
        $conexao = $transacao instanceof PDO ? $transacao : Conexao::criarConexao();

        if (!$conexao instanceof PDO) {
            throw new \PDOException('Opa faltou a conexao com o banco de dados.', 500);
        }

        $paramentrosPermitidos = ['where', 'group', 'order', 'pagina', 'limit'];

        foreach ($parametros as $key => $dados) {
            if (!in_array($key, $paramentrosPermitidos)) {
                throw new \PDOException(
                    'Parametro de busca não permitido, use where, group, order, pagina ou limite para efetuar sua busca',
                    400
                );
            }
        }

        $query = 'SELECT * FROM notificacoes WHERE 1 = 1';
        $WHERE = $parametros['where'] ?? [];
        $GROUP = $parametros['group'] ?? '';
        $ORDER = $parametros['order'] ?? [];
        $PAGINA = $parametros['pagina'] ?? 1;
        $LIMIT = $parametros['limit'] ?? 100;

        if (!!$WHERE && Count($parametros['where']) !== 0) {
            foreach ($WHERE as $campo => $busca) {
                if (is_array($busca)) {
                    $condicao = mb_strtoupper($busca[0]);

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
            $query .= ' ORDER BY ';

            foreach ($ORDER as $ordem => $sentido) {
                $ordenador = mb_strtoupper($sentido);

                if ($ordem) {
                    $query .= " $ordem $ordenador, ";
                } else {
                    $query .= " $sentido, ";
                }
            }

            $query .= ' 1';
        }

        $offset = $PAGINA ? $PAGINA * $LIMIT - $LIMIT : 0;

        $query .= " LIMIT $LIMIT OFFSET $offset";

        if ($resultado = $conexao->query($query)) {
            $listaNotificacoes = $resultado->fetchAll(\PDO::FETCH_ASSOC);

            $listaObjetosNotificacao = [];

            foreach ($listaNotificacoes as $notificacao) {
                $listaObjetosNotificacao[] = Notificacao::hidratar($notificacao);
            }

            return $listaObjetosNotificacao;
        }
    }

    public static function buscaNaCentralNotificacoes($busca = null, $data = null, $tipo = null, $pagina = null)
    {
        $conexao = Conexao::criarConexao();

        $where = '';
        if ($busca) {
            $where .= "(notificacoes.id = '$busca' OR
                colaboradores.id = '$busca' OR
                notificacoes.mensagem LIKE '%$busca%' OR
                colaboradores.razao_social LIKE '%$busca%') AND ";
        }

        if ($data) {
            $where .= "DATE(notificacoes.data_evento) = '$data' AND ";
        }

        if ($tipo && $tipo !== 'All') {
            $where .= "notificacoes.tipo_mensagem = '$tipo' AND ";
        }

        $itemsPorPagina = 25;
        $offset = '';
        if ($pagina) {
            $offset .= 'OFFSET ' . ($pagina - 1) * $itemsPorPagina;
        }

        $query = "SELECT
            notificacoes.*,
            'notificacoes' nome_tabela
        FROM notificacoes
        INNER JOIN colaboradores ON colaboradores.id = notificacoes.id_cliente
        WHERE $where 1=1
        ORDER BY notificacoes.id DESC
        LIMIT $itemsPorPagina $offset";

        return $conexao->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}

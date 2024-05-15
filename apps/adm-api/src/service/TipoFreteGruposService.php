<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\model\TipoFrete;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TipoFreteGruposService extends TipoFrete
{
    public static function buscarGruposTipoFrete(PDO $conexao): array
    {
        $query = "SELECT
                    tipo_frete_grupos.id,
                    tipo_frete_grupos.nome_grupo,
                    tipo_frete_grupos.ativado,
                    tipo_frete_grupos.data_criacao,
                    tipo_frete_grupos.dia_fechamento,
                    (
                        SELECT usuarios.nome
                        FROM usuarios
                        WHERE usuarios.id = tipo_frete_grupos.id_usuario
                    ) AS `usuario_criacao`
                FROM tipo_frete_grupos
                ORDER BY tipo_frete_grupos.id DESC";

        $stmt = $conexao->prepare($query);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = array_map(function ($item) {
            $item['id'] = (int) $item['id'];
            $item['ativado'] = (bool) $item['ativado'];
            $item['data_criacao'] = date('d/m/Y H:i:s', strtotime($item['data_criacao']));
            return $item;
        }, $resultado);

        return $resultado;
    }

    public static function criarGrupoTipoFrete(
        PDO $conexao,
        string $nomeGrupo,
        string $diaFechamento,
        int $idUsuario
    ): int {
        $query = "INSERT INTO tipo_frete_grupos (
                    tipo_frete_grupos.nome_grupo,
                    tipo_frete_grupos.id_usuario,
                    tipo_frete_grupos.dia_fechamento
                    ) VALUES (
                        :nome_grupo,
                        :id_usuario,
                        :dia_fechamento
                    )";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':nome_grupo', $nomeGrupo, PDO::PARAM_STR);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':dia_fechamento', $diaFechamento, PDO::PARAM_STR);
        $stmt->execute();
        $idGrupoTpoFrete = $conexao->lastInsertId();

        return $idGrupoTpoFrete;
    }

    public static function criaItemGrupoTipoFrete(
        PDO $conexao,
        int $idGrupoTipoFrete,
        array $idsTipoFrete,
        int $idUsuario
    ): void {
        $pontosProibidos = array_intersect(explode(',', TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE), $idsTipoFrete);

        if ($pontosProibidos) {
            throw new Exception('Ponto proibido de ser agrupado: ' . implode(', ', $pontosProibidos));
        }

        $sql = '';
        $bind = [
            ':id_grupo_tipo_frete' => $idGrupoTipoFrete,
            ':id_usuario' => $idUsuario,
        ];
        foreach ($idsTipoFrete as $key => $idTipoFrete) {
            $bind[":id_tipo_frete_$key"] = $idTipoFrete;
            $sql .= "INSERT INTO tipo_frete_grupos_item (
                    tipo_frete_grupos_item.id_tipo_frete_grupos,
                    tipo_frete_grupos_item.id_tipo_frete,
                    tipo_frete_grupos_item.id_usuario
                ) VALUES (
                    :id_grupo_tipo_frete,
                    :id_tipo_frete_$key,
                    :id_usuario
                );";
        }
        $sql = $conexao->prepare($sql);
        $sql->execute($bind);
    }

    public static function mudarSituacaoGrupoTipoFrete(PDO $conexao, int $idGrupoTipoFrete, int $idUsuario): void
    {
        $query = "UPDATE tipo_frete_grupos
                    SET tipo_frete_grupos.ativado = NOT tipo_frete_grupos.ativado,
                        tipo_frete_grupos.id_usuario = :id_usuario
                    WHERE tipo_frete_grupos.id = :id_grupo_tipo_frete";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_grupo_tipo_frete', $idGrupoTipoFrete, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception('Não foi possível alterar a situação do grupo');
        }
    }

    public static function apagarGrupoTipoFrete(PDO $conexao, int $idGrupoTipoFrete): void
    {
        $query = "DELETE FROM tipo_frete_grupos
                    WHERE tipo_frete_grupos.id = :id_grupo_tipo_frete";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_grupo_tipo_frete', $idGrupoTipoFrete, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception('Não foi possível apagar o grupo');
        }
    }

    public static function buscarDetalhesGrupoTipoFrete(PDO $conexao, int $idGrupoTipoFrete): array
    {
        $query = "SELECT
                    tipo_frete_grupos.id,
                    tipo_frete_grupos.nome_grupo,
                    tipo_frete_grupos.dia_fechamento,
                    CONCAT('[',
                        GROUP_CONCAT(JSON_OBJECT(
                            'tipo_frete_grupos_item_id', tipo_frete_grupos_item.id,
                            'tipo_frete_id', tipo_frete.id,
                            'tipo_frete_grupos_item_nome', tipo_frete.nome,
                            'adicionados', (
                                SELECT COUNT(tipo_frete_grupos_item.id_tipo_frete)
                                FROM tipo_frete_grupos_item
                                WHERE tipo_frete_grupos_item.id_tipo_frete = tipo_frete.id
                            )
                        )
                    ),']') AS `tipo_frete_grupo_item`
                FROM tipo_frete_grupos
                INNER JOIN tipo_frete_grupos_item ON tipo_frete_grupos_item.id_tipo_frete_grupos = tipo_frete_grupos.id
                INNER JOIN tipo_frete ON tipo_frete.id = tipo_frete_grupos_item.id_tipo_frete
                WHERE tipo_frete_grupos.id = :id_grupo_tipo_frete";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_grupo_tipo_frete', $idGrupoTipoFrete, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($resultado)) {
            return [];
        }

        $resultado['tipo_frete_grupo_item'] = json_decode($resultado['tipo_frete_grupo_item'], true);

        return $resultado ?: [];
    }

    public static function editarGrupoTipoFrete(
        PDO $conexao,
        int $idGrupoTipoFrete,
        string $nomeGrupo,
        string $diaFechamento,
        array $idsTipoFrete,
        int $idUsuario
    ): void {
        $query = "UPDATE
                        tipo_frete_grupos
                    SET
                        tipo_frete_grupos.id_usuario = :id_usuario,
                        tipo_frete_grupos.nome_grupo = :nome_grupo,
                        tipo_frete_grupos.dia_fechamento = :dia_fechamento
                    WHERE
                        tipo_frete_grupos.id = :id_grupo_tipo_frete";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':nome_grupo', $nomeGrupo, PDO::PARAM_STR);
        $stmt->bindValue(':id_grupo_tipo_frete', $idGrupoTipoFrete, PDO::PARAM_INT);
        $stmt->bindValue(':dia_fechamento', $diaFechamento, PDO::PARAM_STR);
        $stmt->execute();

        $query = "DELETE FROM tipo_frete_grupos_item
                    WHERE tipo_frete_grupos_item.id_tipo_frete_grupos = :id_grupo_tipo_frete";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_grupo_tipo_frete', $idGrupoTipoFrete, PDO::PARAM_INT);
        $stmt->execute();

        self::criaItemGrupoTipoFrete($conexao, $idGrupoTipoFrete, $idsTipoFrete, $idUsuario);
    }

    public static function listarTipoFretePorGrupo(PDO $conexao, int $idGrupoEntrega): array
    {
        $query = "SELECT entregas.id_tipo_frete,
                         tipo_frete.nome,
                         entregas.id AS `id_entrega`
                    FROM tipo_frete_grupos_item
                    INNER JOIN entregas ON
                        entregas.id_tipo_frete = tipo_frete_grupos_item.id_tipo_frete
                        AND entregas.situacao = 'AB'
                    INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                    WHERE tipo_frete_grupos_item.id_tipo_frete_grupos = :id_grupo_entrega";
        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_grupo_entrega', $idGrupoEntrega, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public static function listarGruposPorTipoFrete(PDO $conexao, int $idTipoFrete): array
    {
        $query = "SELECT
                        tipo_frete_grupos_item.id_tipo_frete_grupos,
                        tipo_frete_grupos.nome_grupo
                    FROM tipo_frete_grupos_item
                    INNER JOIN tipo_frete_grupos ON tipo_frete_grupos.id = tipo_frete_grupos_item.id_tipo_frete_grupos
                    WHERE tipo_frete_grupos_item.id_tipo_frete = :id_tipo_frete
                    AND tipo_frete_grupos.ativado = 1";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_tipo_frete', $idTipoFrete, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($resultado) === 1) {
            return self::listarDestinosDoGrupo($resultado[0]['id_tipo_frete_grupos']);
        }

        return [
            'retorno' => $resultado,
            'tipo_retorno' => 'GRUPO',
        ];
    }

    public static function listarDestinosDoGrupo(int $idTipoFreteGrupos): array
    {
        $query = "SELECT
                    tipo_frete.nome,
                    tipo_frete.id AS `id_tipo_frete`,
                    tipo_frete.id_colaborador AS `id_colaborador_tipo_frete`,
                    tipo_frete.tipo_ponto,
                    CONCAT(
                        '[',
                        (
                            GROUP_CONCAT(
                                    DISTINCT JSON_OBJECT(
                                        'id_cidade', transportadores_raios.id_cidade,
                                        'id_raio', IF(tipo_frete.tipo_ponto = 'PM', transportadores_raios.id, NULL),
                                        'apelido', IF(tipo_frete.tipo_ponto = 'PM',
                                                        COALESCE(
                                                            CONCAT('(', transportadores_raios.id, ') ', transportadores_raios.apelido),
                                                            transportadores_raios.id
                                                        ),
                                                    NULL),
                                        'cidade', (
                                            SELECT CONCAT(municipios.nome, ' (', municipios.uf, ')')
                                            FROM municipios
                                            WHERE municipios.id = transportadores_raios.id_cidade
                                        )
                                    )
                                )
                        )
                        ,']'
                    ) AS `json_destinos`
                FROM tipo_frete_grupos_item
                INNER JOIN tipo_frete ON
                    tipo_frete.id = tipo_frete_grupos_item.id_tipo_frete
                    AND tipo_frete.categoria <> 'PE'
                INNER JOIN transportadores_raios ON
                    transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                    AND transportadores_raios.esta_ativo
                WHERE tipo_frete_grupos_item.id_tipo_frete_grupos = :id_tipo_frete_grupo
                GROUP BY tipo_frete.id";
        $resultado = DB::select($query, ['id_tipo_frete_grupo' => $idTipoFreteGrupos]);
        if (empty($resultado)) {
            throw new NotFoundHttpException("Não há entregas em aberto para o grupo $idTipoFreteGrupos");
        }

        $resultado = array_map(function ($item) {
            foreach ($item['destinos'] as &$destino) {
                $destino['identificador'] = "{$item['id_tipo_frete']}_{$destino['id_cidade']}{$destino['id_raio']}";
            }

            return $item;
        }, $resultado);

        return [
            'retorno' => $resultado,
            'id_grupo_origem' => $idTipoFreteGrupos,
            'tipo_retorno' => 'DESTINOS',
        ];
    }

    // public static function adicionarAcompanhamentoDestino(
    //     PDO $conexao,
    //     int $idDestinatario,
    //     int $idTipoFrete,
    //     int $idCidade,
    //     int $idUsuario
    // ): void {

    //     $acompanhamento = app(AcompanhamentoTempService::class);
    //     $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

    //     $query = "SELECT tipo_frete.tipo_ponto FROM tipo_frete WHERE tipo_frete.id = :id_tipo_frete";
    //     $stmt = $conexao->prepare($query);
    //     $stmt->bindValue(':id_tipo_frete', $idTipoFrete, PDO::PARAM_INT);
    //     $stmt->execute();
    //     $tipoPonto = $stmt->fetch(PDO::FETCH_COLUMN);

    //     $insert = "";
    //     $insertValues = "";

    //     if ($tipoPonto === 'PM') {
    //         $insert .= ' id_cidade, ';
    //         $insertValues .= ' :id_cidade, ';
    //     }

    //     $query = "INSERT IGNORE INTO acompanhamento_temp (
    //                 $insert
    //                 acompanhamento_temp.id_destinatario,
    //                 acompanhamento_temp.id_tipo_frete,
    //                 acompanhamento_temp.id_usuario
    //             ) VALUES (
    //                 $insertValues
    //                 :id_destinatario,
    //                 :id_tipo_frete,
    //                 :id_usuario
    //             )";

    //     $stmt = $conexao->prepare($query);
    //     $stmt->bindValue(':id_destinatario', $idDestinatario, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_tipo_frete', $idTipoFrete, PDO::PARAM_INT);
    //     if ($tipoPonto === 'PM') {
    //         $stmt->bindValue(':id_cidade', $idCidade, PDO::PARAM_INT);
    //     }
    //     $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
    //     $stmt->execute();
    //     $idAcompanhamento = (int)$conexao->lastInsertId();

    //     $sql = "SELECT
    //                 logistica_item.uuid_produto
    //             FROM logistica_item
    //             INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //             INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_cliente
    //             LEFT JOIN entregas ON entregas.id = logistica_item.id_entrega
    //             WHERE
    //                 tipo_frete.id = :id_tipo_frete
    //                 AND IF(logistica_item.id_entrega > 0, entregas.situacao = 'AB', TRUE)
    //                 AND CASE
    //                     WHEN tipo_frete.id IN ($idTipoFreteEntregaCliente) THEN
    //                         logistica_item.id_cliente = :id_cliente
    //                     WHEN tipo_frete.tipo_ponto = 'PM' THEN
    //                         logistica_item.id_colaborador_tipo_frete = :id_colaborador_tipo_frete AND colaboradores.id_cidade = :id_cidade
    //                     ELSE
    //                         logistica_item.id_colaborador_tipo_frete = :id_colaborador_tipo_frete
    //                 END";

    //     $stmt = $conexao->prepare($sql);
    //     $stmt->bindValue(':id_tipo_frete', $idTipoFrete, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_cliente', $idDestinatario, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_colaborador_tipo_frete', $idDestinatario, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_cidade', $idCidade, PDO::PARAM_INT);
    //     $stmt->execute();
    //     $uuidProdutos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    //     if (empty($uuidProdutos)) {
    //         throw new BadRequestHttpException('Não há produtos para acompanhar');
    //     }

    //     foreach ($uuidProdutos as $uuid) {
    //         $acompanhamento->adicionaItemAcompanhamento(
    //             $uuid,
    //             $idAcompanhamento,
    //             $idUsuario
    //         );
    //     }
    // }

    // public static function removerAcompanhamentoDestino(
    //     PDO $conexao,
    //     int $idDestinatario,
    //     int $idTipoFrete,
    //     int $idCidade
    // ): void {

    //     $query = "DELETE FROM acompanhamento_temp
    //                 WHERE
    //                     acompanhamento_temp.id_destinatario = :id_destinatario
    //                     AND acompanhamento_temp.id_tipo_frete = :id_tipo_frete
    //                     AND acompanhamento_temp.id_cidade = :id_cidade";

    //     $stmt = $conexao->prepare($query);
    //     $stmt->bindValue(':id_destinatario', $idDestinatario, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_tipo_frete', $idTipoFrete, PDO::PARAM_INT);
    //     $stmt->bindValue(':id_cidade', $idCidade, PDO::PARAM_INT);
    //     $stmt->execute();

    //     if ($stmt->rowCount() === 0) {
    //         throw new Exception('Não foi possível remover o acompanhamento');
    //     }
    // }

    // public static function listarAcompanhamentoParaSeparar(PDO $conexao): array
    // {
    //     $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
    //     $idcolaboradorTipoFreteEntregaCliente = TipoFrete::ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE;

    //     $query = "SELECT
    //                     colaboradores.id AS `id_cliente`,
    //                     IF(tipo_frete.id IN ($idTipoFreteEntregaCliente), colaboradores.razao_social, tipo_frete.nome) AS `razao_social`,
    //                     colaboradores.foto_perfil,
    //                     COUNT(logistica_item.uuid_produto) AS `qtd_produtos`,
    //                     DATE_FORMAT(MAX(logistica_item.data_criacao), '%d/%m/%Y %H:%i:%s') AS `data_ultima_liberacao`,
    //                     GROUP_CONCAT(logistica_item.uuid_produto) AS `uuids`,
    //                     IF (
    //                         tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
    //                     ) AS `tipo_ponto`,
    //                     tipo_frete.id IN ($idTipoFreteEntregaCliente) AS `entrega_cliente`
    //                 FROM logistica_item
    //                 INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //                 INNER JOIN acompanhamento_temp ON
    //                     acompanhamento_temp.id_destinatario = logistica_item.id_cliente
    //                     AND acompanhamento_temp.id_tipo_frete = tipo_frete.id
    //                 INNER JOIN colaboradores ON colaboradores.id = acompanhamento_temp.id_destinatario
    //                 WHERE
    //                     logistica_item.situacao = 'PE'
    //                     AND logistica_item.id_responsavel_estoque = 1
    //                     AND logistica_item.id_colaborador_tipo_frete IN ($idcolaboradorTipoFreteEntregaCliente)
    //                 GROUP BY
    //                     acompanhamento_temp.id_destinatario,
    //                     acompanhamento_temp.id_tipo_frete
    //                 ORDER BY logistica_item.data_criacao ASC";

    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();
    //     $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     $resultado = array_map(function ($item) {
    //         $item['id'] = rand();
    //         $item['id_cliente'] = (int) $item['id_cliente'];
    //         $item['qtd_produtos'] = (int) $item['qtd_produtos'];
    //         $item['uuids'] = explode(',', $item['uuids']);
    //         $item['entrega_cliente'] = (bool) $item['entrega_cliente'];
    //         return $item;
    //     }, $resultado);

    //     return $resultado;
    // }

    // public static function listarAcompanhamentoConferidos(PDO $conexao): array
    // {
    //     $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

    //     $query = "SELECT
    //                     colaboradores.id AS `id_cliente`,
    //                     IF(tipo_frete.id IN ($idTipoFreteEntregaCliente), colaboradores.razao_social, tipo_frete.nome) AS `razao_social`,
    //                     colaboradores.foto_perfil,
    //                     COUNT(logistica_item.uuid_produto) AS `qtd_produtos`,
    //                     DATE_FORMAT(MAX(logistica_item.data_atualizacao), '%d/%m/%Y %H:%i:%s') AS `data_ultima_conferencia`,
    //                     logistica_item.uuid_produto AS `ultimo_uuid`,
    //                     IF (
    //                         tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
    //                     ) AS `tipo_ponto`,
    //                     tipo_frete.id IN ($idTipoFreteEntregaCliente) AS `entrega_cliente`,
    //                     IF (tipo_frete.id IN ($idTipoFreteEntregaCliente) OR tipo_frete.tipo_ponto = 'PM', (
    //                         SELECT CONCAT(municipios.nome, ' (', municipios.uf, ')')
    //                         FROM municipios
    //                         WHERE municipios.id = colaboradores_cliente.id_cidade
    //                         LIMIT 1
    //                     ),'') AS `cidade`
    //                 FROM logistica_item
    //                 INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
    //                 INNER JOIN colaboradores AS `colaboradores_cliente` ON colaboradores_cliente.id = logistica_item.id_cliente
    //                 INNER JOIN acompanhamento_temp ON
    //                     IF(
    //                         tipo_frete.id IN ($idTipoFreteEntregaCliente),
    //                         acompanhamento_temp.id_destinatario = logistica_item.id_cliente,
    //                         acompanhamento_temp.id_destinatario = tipo_frete.id_colaborador
    //                     )
    //                     AND acompanhamento_temp.id_tipo_frete = tipo_frete.id
    //                     AND IF(tipo_frete.tipo_ponto = 'PM', acompanhamento_temp.id_cidade = colaboradores_cliente.id_cidade, TRUE)
    //                 INNER JOIN colaboradores ON colaboradores.id = acompanhamento_temp.id_destinatario
    //                 WHERE
    //                     logistica_item.situacao = 'CO'
    //                     AND logistica_item.id_entrega IS NULL
    //                     AND NOT EXISTS (
    //                         SELECT 1
    //                         FROM logistica_item AS `ignora_logistica_item`
    //                         WHERE
    //                             tipo_frete.id IN ($idTipoFreteEntregaCliente)
    //                             AND ignora_logistica_item.id_cliente = acompanhamento_temp.id_destinatario
    //                             AND ignora_logistica_item.id_colaborador_tipo_frete = tipo_frete.id_colaborador
    //                             AND ignora_logistica_item.situacao IN ('PE','SE')
    //                             AND ignora_logistica_item.id_responsavel_estoque = 1
    //                     )
    //                 GROUP BY
    //                     acompanhamento_temp.id_destinatario,
    //                     acompanhamento_temp.id_tipo_frete,
    //                     IF(tipo_frete.tipo_ponto = 'PM', colaboradores_cliente.id_cidade, TRUE)
    //                 ORDER BY logistica_item.id ASC";

    //     $stmt = $conexao->prepare($query);
    //     $stmt->execute();
    //     $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     $resultado = array_map(function ($item) {
    //         $item['id_cliente'] = (int) $item['id_cliente'];
    //         $item['qtd_produtos'] = (int) $item['qtd_produtos'];
    //         $item['entrega_cliente'] = (bool) $item['entrega_cliente'];
    //         return $item;
    //     }, $resultado);

    //     return $resultado ?: [];
    // }
}

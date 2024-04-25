<?php

namespace MobileStock\service\EntregaService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\Entrega\Entregas;
use MobileStock\model\TipoFrete;
use PDO;

class EntregasFechadasTempService extends Entregas
{
    /**
     * @issue: https://github.com/mobilestock/web/issues/3218
     * @deprecated
     */
    public static function adicionaEntregaFechadaTemp(int $idEntrega): void
    {
        DB::insert(
            "INSERT INTO entregas_fechadas_temp (
                entregas_fechadas_temp.id_entrega,
                entregas_fechadas_temp.id_usuario
            ) VALUES (
                :id_entrega,
                :id_usuario
            );",
            [':id_entrega' => $idEntrega, ':id_usuario' => Auth::user()->id]
        );
    }

    public static function buscarEntregasFechadasTemp(string $entregaManipulada): array
    {
        $idsEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $where = '';

        switch ($entregaManipulada) {
            case 'MANIPULADA':
                $where = ' AND entregas_fechadas_temp.entrega_manipulada = 1 ';
                break;
            case 'NAO_MANIPULADA':
                $where = ' AND entregas_fechadas_temp.entrega_manipulada = 0 ';
                break;
        }

        $resultado = DB::select("SELECT
                        entregas.id AS `id_entrega`,
                        entregas.volumes,
                        entregas_fechadas_temp.entrega_manipulada AS `bool_entrega_manipulada`,
                        entregas_fechadas_temp.data_criacao,
                        IF(
                            entregas_fechadas_temp.data_atualizacao = 0, null, DATE_FORMAT(entregas_fechadas_temp.data_atualizacao, '%d/%m/%Y %H:%i:%s')
                        ) AS `data_manipulacao`,
                        (
                            SELECT CONCAT('(',usuarios.id,') ',usuarios.nome)
                            FROM usuarios
                            WHERE usuarios.id = entregas_fechadas_temp.id_usuario
                        ) AS `usuario_manipulador`,
                        @entrega_cliente := tipo_frete.id IN ($idsEntregaCliente) AS `bool_entrega_cliente`,
                        CASE
                            WHEN @entrega_cliente = TRUE THEN (
                                SELECT JSON_OBJECT(
                                    'id_colaborador', colaboradores.id,
                                    'id_usuario', usuarios.id,
                                    'nome', colaboradores.razao_social,
                                    'cidade', JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade'),
                                    'uf', JSON_VALUE(transacao_financeiras_metadados.valor, '$.uf'),
                                    'tipo_embalagem', colaboradores.tipo_embalagem
                                )
                                FROM colaboradores
                                JOIN usuarios ON usuarios.id_colaborador = colaboradores.id
                                WHERE colaboradores.id = entregas.id_cliente
                                LIMIT 1
                            )
                            WHEN @entrega_cliente = FALSE AND tipo_frete.tipo_ponto = 'PP' THEN (
                                SELECT JSON_OBJECT(
                                    'id_colaborador', colaboradores.id,
                                    'nome', tipo_frete.nome,
                                    'cidade', JSON_VALUE(transacao_financeiras_metadados.valor, '$.cidade'),
                                    'uf', JSON_VALUE(transacao_financeiras_metadados.valor, '$.uf'),
                                    'tipo_embalagem', colaboradores.tipo_embalagem
                                )
                                FROM colaboradores
                                WHERE colaboradores.id = tipo_frete.id_colaborador
                            )
                            WHEN @entrega_cliente = FALSE AND tipo_frete.tipo_ponto = 'PM' THEN (
                                SELECT JSON_OBJECT(
                                    'cidade', municipios.nome,
                                    'uf', municipios.uf,
                                    'nome_entregador', tipo_frete.nome,
                                    'nome_ponto_coleta', colaboradores.razao_social,
                                    'id_colaborador', tipo_frete.id_colaborador,
                                    'id_colaborador_ponto_coleta', tipo_frete.id_colaborador_ponto_coleta,
                                    'tipo_embalagem', colaboradores.tipo_embalagem
                                )
                                FROM municipios
                                INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador_ponto_coleta
                                WHERE municipios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade')
                            )
                        END AS `json_destino`,
                        IF (
                            tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
                        ) AS `tipo_ponto`,
                        tipo_frete_grupos.id AS `id_tipo_frete_grupo`,
                        tipo_frete_grupos.nome_grupo,
                        tipo_frete.id AS `id_tipo_frete`
                    FROM entregas_fechadas_temp
                    INNER JOIN entregas ON entregas.id = entregas_fechadas_temp.id_entrega
                    INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                    INNER JOIN transacao_financeiras_metadados ON
                        transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
                        AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                    LEFT JOIN tipo_frete_grupos ON tipo_frete_grupos.id = entregas_fechadas_temp.id_tipo_frete_grupos
                    INNER JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
                    WHERE TRUE $where
                    GROUP BY entregas_fechadas_temp.id_entrega
                    ORDER BY entregas_fechadas_temp.data_criacao ASC");

        $resultado = array_map(function ($item) {
            $item['cidade'] = $item['destino']['cidade'] . ' - ' . $item['destino']['uf'];
            $item['ponto_coleta'] = isset($item['destino']['nome_ponto_coleta'])
                ? '(' . $item['destino']['id_colaborador_ponto_coleta'] . ') ' . $item['destino']['nome_ponto_coleta']
                : null;
            $item['tipo_embalagem'] = $item['destino']['tipo_embalagem'];

            if (in_array($item['tipo_ponto'], ['PP', 'ENVIO_TRANSPORTADORA'])) {
                $item['destino'] = '(' . $item['destino']['id_colaborador'] . ') ' . trim($item['destino']['nome']);
            } else {
                $item['destino'] =
                    "({$item['destino']['id_colaborador']}) " . trim($item['destino']['nome_entregador']);
            }

            return $item;
        }, $resultado);

        return $resultado;
    }

    public static function manipularEntregaFechada(PDO $conexao, int $idEntrega, int $idUsuario): void
    {
        $query = "UPDATE entregas_fechadas_temp SET
                        entregas_fechadas_temp.entrega_manipulada = 1,
                        entregas_fechadas_temp.id_usuario = :id_usuario
                    WHERE entregas_fechadas_temp.id_entrega = :id_entrega";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(':id_entrega', $idEntrega, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }
}

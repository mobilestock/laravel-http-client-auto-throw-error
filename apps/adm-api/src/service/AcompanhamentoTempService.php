<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\TipoFrete;

class AcompanhamentoTempService
{
    public function buscaProdutosParaAdicionarNoAcompanhamentoPorPontosColeta(array $pontosColeta): array
    {
        [$sql, $binds] = ConversorArray::criaBindValues($pontosColeta, 'id_colaborador_ponto_coleta');
        $binds[':situacao_logistica'] = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;

        $produtos = DB::selectColumns(
            "SELECT logistica_item.uuid_produto
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            LEFT JOIN entregas ON entregas.id = logistica_item.id_entrega
            WHERE tipo_frete.id_colaborador_ponto_coleta IN ($sql)
                AND IF (
                    logistica_item.id_entrega > 0,
                    entregas.situacao = 'AB',
                    TRUE
                )
                AND logistica_item.situacao <= :situacao_logistica
            GROUP BY logistica_item.uuid_produto;",
            $binds
        );

        return $produtos;
    }

    public function adicionaItemAcompanhamento(array $listaDeUuidProduto, int $idAcompanhamento): void
    {
        DB::table('acompanhamento_item_temp')->insertOrIgnore(
            array_map(
                fn(string $uuidProduto) => [
                    'id_acompanhamento' => $idAcompanhamento,
                    'uuid_produto' => $uuidProduto,
                    'id_usuario' => Auth::id(),
                ],
                $listaDeUuidProduto
            )
        );
    }

    public function listarAcompanhamentoDestino(): array
    {
        $query = "SELECT
                    SUM(acompanhamento_temp.situacao = 'AGUARDANDO_SEPARAR') AS `pendentes`,
                    SUM(acompanhamento_temp.situacao = 'AGUARDANDO_ADICIONAR_ENTREGA') AS `conferidos`,
                    SUM(acompanhamento_temp.situacao = 'ENTREGA_EM_ABERTO') AS `entregasAB`,
                    (
                        SELECT
                            COUNT(entregas_fechadas_temp.id_entrega) AS `entregasEX`
                        FROM entregas_fechadas_temp
                        INNER JOIN entregas ON entregas.id = entregas_fechadas_temp.id_entrega
                        WHERE entregas.situacao = 'EX'
                    ) AS `entregasEX`
                FROM acompanhamento_temp";

        $resultado = DB::selectOne($query);

        return [
            'pendentes' => (int) $resultado['pendentes'],
            'conferidos' => (int) $resultado['conferidos'],
            'entregasAB' => (int) $resultado['entregasAB'],
            'entregasEX' => (int) $resultado['entregasEX'],
        ];
    }

    public function listarAcompanhamentoParaSeparar(): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;

        $query = "SELECT
                        colaboradores.id AS `id_cliente`,
                        IF(tipo_frete.id IN ($idTipoFreteEntregaCliente), colaboradores.razao_social, tipo_frete.nome) AS `razao_social`,
                        COALESCE(colaboradores.foto_perfil, '{$_ENV['URL_MOBILE']}/images/avatar-padrao-mobile.jpg') AS `foto_perfil`,
                        COUNT(acompanhamento_item_temp.uuid_produto) AS `qtd_produtos`,
                        DATE_FORMAT(MAX(acompanhamento_item_temp.data_criacao), '%d/%m/%Y %H:%i:%s') AS `data_ultima_liberacao`,
                        GROUP_CONCAT(acompanhamento_item_temp.uuid_produto) AS `uuids`,
                        IF (
                            tipo_frete.id = 2, 'ENVIO_TRANSPORTADORA', tipo_frete.tipo_ponto
                        ) AS `tipo_ponto`,
                        tipo_frete.id IN ($idTipoFreteEntregaCliente) AS `eh_entrega_cliente`
                    FROM acompanhamento_temp
                    INNER JOIN acompanhamento_item_temp ON acompanhamento_item_temp.id_acompanhamento = acompanhamento_temp.id
                    INNER JOIN colaboradores ON colaboradores.id = acompanhamento_temp.id_destinatario
                    INNER JOIN tipo_frete ON tipo_frete.id = acompanhamento_temp.id_tipo_frete
                    INNER JOIN logistica_item ON
                        logistica_item.uuid_produto = acompanhamento_item_temp.uuid_produto
                        AND logistica_item.id_responsavel_estoque = 1
                        AND logistica_item.situacao = 'PE'
                    WHERE acompanhamento_temp.situacao = 'AGUARDANDO_SEPARAR'
                    GROUP BY acompanhamento_temp.id";

        $resultado = DB::select($query);

        $resultado = array_map(function ($item) {
            $item['id'] = rand();
            $item['uuids'] = explode(',', $item['uuids']);
            return $item;
        }, $resultado);

        return $resultado;
    }
}

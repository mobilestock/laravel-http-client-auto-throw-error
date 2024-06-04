<?php

namespace MobileStock\service\Monitoramento;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MonitoramentoService
{
    public static function trataRetornoDeBusca(array $dados): array
    {
        $dados = array_map(function ($item) {
            if (isset($item['data_atualizacao'])) {
                $item['data_atualizacao'] = date_format(date_create($item['data_atualizacao']), 'd/m/Y H:i');
            }
            if (isset($item['data_criacao'])) {
                $item['data_criacao'] = date_format(date_create($item['data_criacao']), 'd/m/Y H:i');
            }
            return $item;
        }, $dados);

        return $dados;
    }

    public static function buscaProdutosQuantidade(): array
    {
        $idColaborador = Auth::user()->id_colaborador;
        $idUsuario = Auth::user()->id;

        $entregas = "SELECT
                SUM(entregas_faturamento_item.situacao = 'PE' AND entregas.situacao IN ('EN', 'EX', 'PT')) chegada,
                SUM(entregas.situacao = 'EN' AND entregas_faturamento_item.situacao IN ('AR')) entregar,
                SUM(entregas_faturamento_item.situacao = 'PE'
                    AND entregas.situacao IN ('EN', 'PT')
                    AND (DATEDIFF(NOW(), (
                                SELECT
                                    entregas_logs.data_criacao
                                FROM entregas_logs
                                WHERE
                                    entregas_logs.id_entrega = entregas_faturamento_item.id_entrega
                                    AND entregas_logs.situacao_nova IN ('PT','EN')
                                ORDER BY entregas_logs.id DESC
                               LIMIT 1
                                )
                    ) >= (
                            SELECT
                                configuracoes.dias_atraso_para_chegar_no_ponto
                            FROM configuracoes
                            LIMIT 1
                        )
                    )) atraso_chegada,
                SUM(entregas.situacao = 'EN'
                    AND entregas_faturamento_item.situacao IN ('AR')
                    AND (DATEDIFF(NOW(), entregas_faturamento_item.data_atualizacao) >= (
                        SELECT configuracoes.dias_atraso_para_entrega_ao_cliente FROM configuracoes  LIMIT 1))) atraso_entrega
            FROM entregas
                INNER JOIN tipo_frete ON entregas.id_tipo_frete = tipo_frete.id
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
            WHERE
                entregas.situacao IN ('PT', 'EN')
                AND entregas_faturamento_item.situacao IN ('PE', 'AR')
                AND tipo_frete.id_colaborador = :id_colaborador
                ORDER BY entregas_faturamento_item.data_atualizacao ASC";

        $trocas = "SELECT
                SUM(entregas_devolucoes_item.situacao = 'PE'
                AND (DATEDIFF(NOW(), entregas_devolucoes_item.data_criacao) >=
                (
                    SELECT configuracoes.dias_atraso_para_trocas_ponto FROM configuracoes  LIMIT 1))
                ) atraso_troca,
                SUM(entregas_devolucoes_item.situacao = 'PE') total_trocas
            FROM entregas_devolucoes_item
                INNER JOIN tipo_frete ON entregas_devolucoes_item.id_ponto_responsavel = tipo_frete.id
            WHERE tipo_frete.id_colaborador = :id_colaborador
            AND entregas_devolucoes_item.id_usuario = :id_usuario
            AND entregas_devolucoes_item.situacao = 'PE'";

        $retornoEntregas = DB::selectOne($entregas, ['id_colaborador' => $idColaborador]);
        $retornoTrocas = DB::selectOne($trocas, ['id_colaborador' => $idColaborador, 'id_usuario' => $idUsuario]);

        $resultado = [
            'chegada' => $retornoEntregas['chegada'],
            'entregar' => $retornoEntregas['entregar'],
            'trocas' => $retornoTrocas['total_trocas'],
            'atraso_chegada' => $retornoEntregas['atraso_chegada'],
            'atraso_entrega' => $retornoEntregas['atraso_entrega'],
            'atraso_troca' => $retornoTrocas['atraso_troca'],
        ];

        return $resultado;
    }

    public static function buscaProdutosChegada(?int $idColaborador = null): array
    {
        $idColaborador ??= Auth::user()->id_colaborador;

        $query = "SELECT
                entregas_faturamento_item.id,
                entregas_faturamento_item.id_produto,
                entregas_faturamento_item.uuid_produto,
                    (
                        SELECT
                            COALESCE(produtos.nome_comercial, produtos.descricao)
                            FROM produtos
                        WHERE
                        produtos.id = entregas_faturamento_item.id_produto
                    ) nome_produto,
                    (
                        SELECT produtos_foto.caminho
                        FROM produtos_foto
                        WHERE
                        produtos_foto.id = entregas_faturamento_item.id_produto
                        ORDER BY produtos_foto.tipo_foto = 'MD' DESC LIMIT 1
                    ) foto,
                entregas_faturamento_item.nome_tamanho,
            colaboradores.razao_social,
            JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.nome_destinatario' ) nome_destinatario,
            colaboradores.telefone,
            DATE_FORMAT(entregas_faturamento_item.data_atualizacao, '%d/%m/%Y') AS `data_atualizacao`,
            (DATEDIFF(NOW(), (
                                SELECT
                                    entregas_logs.data_criacao
                                FROM entregas_logs
                                WHERE
                                    entregas_logs.id_entrega = entregas_faturamento_item.id_entrega
                                    AND entregas_logs.situacao_nova IN ('PT','EN')
                                ORDER BY entregas_logs.id DESC
                               LIMIT 1
                                )
                    ) >= (
                            SELECT
                                configuracoes.dias_atraso_para_chegar_no_ponto
                            FROM configuracoes
                            LIMIT 1
                        )
                    ) bool_em_atraso
        FROM
            entregas_faturamento_item
        INNER JOIN
            colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
        INNER JOIN
            transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
            AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
        WHERE entregas_faturamento_item.situacao = 'PE'
            AND entregas_faturamento_item.id_entrega IN
            (
                SELECT
                    entregas.id
                FROM tipo_frete
                    INNER JOIN entregas ON entregas.id_tipo_frete = tipo_frete.id
                WHERE tipo_frete.id_colaborador = :id_colaborador
                    AND entregas.situacao IN ('PT', 'EN')
            )
        ORDER BY entregas_faturamento_item.data_atualizacao ASC";

        $resultado = DB::select($query, ['id_colaborador' => $idColaborador]);

        return $resultado ?: [];
    }

    public static function buscaProdutosEntrega(int $idColaborador): array
    {
        $query = "SELECT
            entregas_faturamento_item.id,
            entregas_faturamento_item.id_cliente,
            entregas_faturamento_item.id_produto,
            entregas_faturamento_item.id_entrega,
            entregas_faturamento_item.uuid_produto,
            (
                SELECT COALESCE(produtos.nome_comercial, produtos.descricao)
                FROM produtos
                WHERE produtos.id = entregas_faturamento_item.id_produto
            )nome_produto,
            (
                SELECT produtos_foto.caminho
                FROM produtos_foto
                WHERE produtos_foto.id = entregas_faturamento_item.id_produto
                ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                LIMIT 1
            ) foto,
            entregas_faturamento_item.nome_tamanho,
            colaboradores.razao_social,
            colaboradores.telefone,
            JSON_EXTRACT(transacao_financeiras_metadados.valor, '$.nome_destinatario' ) nome_destinatario,
            entregas_faturamento_item.data_atualizacao,
        (DATEDIFF(NOW(), entregas_faturamento_item.data_atualizacao) >= (SELECT configuracoes.dias_atraso_para_entrega_ao_cliente
                                                FROM configuracoes
                                                LIMIT 1)) esta_em_atraso
        FROM entregas_faturamento_item
        INNER JOIN colaboradores ON colaboradores.id = entregas_faturamento_item.id_cliente
        INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.id_transacao = entregas_faturamento_item.id_transacao
            AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
        WHERE entregas_faturamento_item.situacao = 'AR'
            AND entregas_faturamento_item.id_entrega IN
            (SELECT entregas.id
            FROM tipo_frete
            INNER JOIN entregas ON entregas.id_tipo_frete = tipo_frete.id
            WHERE tipo_frete.id_colaborador = :id_colaborador AND entregas.situacao = 'EN')
        ORDER BY entregas_faturamento_item.data_atualizacao ASC";

        $resultado = DB::select($query, ['id_colaborador' => $idColaborador]);

        $resultado = self::trataRetornoDeBusca($resultado);

        return $resultado;
    }

    public static function buscaIdColaborador(int $idPonto): int
    {
        $query = "SELECT
                tipo_frete.id_colaborador
            FROM
                tipo_frete
            WHERE
                tipo_frete.id = :id_ponto";

        $resultado = DB::selectOneColumn($query, [
            'id_ponto' => $idPonto,
        ]);
        if ($resultado) {
            return $resultado;
        } else {
            throw new NotFoundHttpException('Ponto não encontrado');
        }
    }
}

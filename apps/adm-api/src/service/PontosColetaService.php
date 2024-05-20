<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Validador;
use MobileStock\model\Entrega\Entregas;
use MobileStock\model\PontosColeta;
use PDO;
use RuntimeException;

class PontosColetaService extends PontosColeta
{
    private PDO $conexao;
    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }
    public function atualizar(): void
    {
        $geradorSql = new GeradorSql($this);
        $sql = $geradorSql->updatePorCampo(['id_colaborador']);
        $sql = $this->conexao->prepare($sql);
        $sql->execute($geradorSql->bind);
        if ($sql->rowCount() !== 1) {
            throw new RuntimeException('Erro ao tentar atualizar o ponto de coleta');
        }
    }
    public static function buscaListaPontosDeColeta(string $pesquisa): array
    {
        $pontosColeta = DB::select(
            "SELECT
                pontos_coleta.id_colaborador,
                colaboradores.razao_social,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.uf
            FROM pontos_coleta
            JOIN colaboradores ON colaboradores.id = pontos_coleta.id_colaborador
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1
            WHERE LOWER(CONCAT_WS(' ',
                colaboradores.razao_social,
                colaboradores.id,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.uf
            )) REGEXP LOWER(:pesquisa);",
            [':pesquisa' => $pesquisa]
        );

        return $pontosColeta;
    }
    /**
     * @param array<int> $valores
     * @param string $tipoValores 'AFILIADOS' | 'ENTREGAS'
     * @return array
     */
    public static function buscarDadosEntregasExpedidasUltimos3Dias(array $valores, string $tipoValores): array
    {
        Validador::validar(
            ['tipo_valores' => $tipoValores, 'valores' => $valores],
            [
                'tipo_valores' => [Validador::ENUM('AFILIADOS', 'ENTREGAS')],
                'valores' => [Validador::TAMANHO_MINIMO(1)],
            ]
        );
        $situacaoExpedicao = Entregas::SITUACAO_EXPEDICAO;
        [$bind, $valores] = ConversorArray::criaBindValues($valores, "id_$tipoValores");
        $whereEntregas = '';
        $whereEntregasLog = '';
        if ($tipoValores === 'AFILIADOS') {
            $whereEntregas = ' entregas.id_tipo_frete ';
            $whereEntregasLog = " JSON_EXTRACT(_entregas_log_entregas.mensagem, '$.NEW_id_tipo_frete') ";
        } elseif ($tipoValores === 'ENTREGAS') {
            $whereEntregas = ' entregas.id ';
            $whereEntregasLog = ' _entregas_log_entregas.id_entrega ';
        }

        $sql = "SELECT
                GROUP_CONCAT(
                    DISTINCT _entregas_log_entregas.id_entrega
                    ORDER BY _entregas_log_entregas.id_entrega ASC
                ) AS `ids_entrega`,
                DATE_FORMAT(_entregas_log_entregas.data_criacao, '%d/%m/%Y') AS `data_expedicao`,
                SUM(_entregas.valor_custo_produto) AS `valor_custo_produto`
            FROM (
                SELECT
                    entregas_logs.id_entrega,
                    entregas_logs.mensagem,
                    entregas_logs.data_criacao
                FROM entregas_logs
                WHERE entregas_logs.situacao_nova = 'PT'
                GROUP BY entregas_logs.id_entrega
                ORDER BY entregas_logs.id DESC
            ) AS `_entregas_log_entregas`
            INNER JOIN (
                SELECT
                    entregas_faturamento_item.id_entrega,
                    SUM(transacao_financeiras_produtos_itens.comissao_fornecedor) AS `valor_custo_produto`
                FROM entregas
                INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                    AND transacao_financeiras_produtos_itens.id_transacao = entregas_faturamento_item.id_transacao
                    AND transacao_financeiras_produtos_itens.uuid_produto = entregas_faturamento_item.uuid_produto
                WHERE entregas.situacao > $situacaoExpedicao
                    AND $whereEntregas IN ($bind)
                GROUP BY entregas_faturamento_item.id_entrega
                ORDER BY entregas.id DESC
            ) AS `_entregas` ON _entregas.id_entrega = _entregas_log_entregas.id_entrega
            WHERE $whereEntregasLog IN ($bind)
            GROUP BY DATE(_entregas_log_entregas.data_criacao)
            ORDER BY DATE(_entregas_log_entregas.data_criacao) DESC
            LIMIT 3;";

        $dias = DB::select($sql, $valores);

        return $dias;
    }
    public static function calculaTarifaPontoColeta(
        array $afiliados,
        float $valorCustoFrete,
        float $porcentagemAnterior = 10
    ): array {
        $dias = self::buscarDadosEntregasExpedidasUltimos3Dias($afiliados, 'AFILIADOS');
        $listaIdEntregas = [];
        $mediaValorEntregas = 0;
        $porcentagemNova = $porcentagemAnterior / 100;

        if (count($dias) >= 3) {
            $listaIdEntregas = array_column($dias, 'ids_entrega');
            $mediaValorEntregas = array_sum(array_column($dias, 'valor_custo_produto')) / 3;
            $porcentagemNova = $valorCustoFrete > 0 ? $valorCustoFrete / $mediaValorEntregas : 0;
        }

        $retorno = [
            'lista_id_entregas' => implode(',', $listaIdEntregas),
            'media_valor_entregas' => round($mediaValorEntregas, 2),
            'porcentagem_frete' => round($porcentagemNova * 100, 2),
        ];

        return $retorno;
    }
    public static function atualizaTarifaPontoColeta(
        PDO $conexao,
        int $idColaboradorPontoColeta,
        float $valorCustoFrete,
        float $percentualFrete,
        ?bool $deveRecalcularPercentual = null
    ): void {
        $update = '';
        if (is_bool($deveRecalcularPercentual)) {
            $update = ', pontos_coleta.deve_recalcular_percentual = :deve_recalcular_percentual ';
        }

        $sql = $conexao->prepare(
            "UPDATE pontos_coleta SET
                pontos_coleta.porcentagem_frete = :porcentagem_frete,
                pontos_coleta.valor_custo_frete = :valor_custo_frete
                $update
            WHERE pontos_coleta.id_colaborador = :id_colaborador;"
        );
        $sql->bindValue(':id_colaborador', $idColaboradorPontoColeta, PDO::PARAM_INT);
        $sql->bindValue(':valor_custo_frete', $valorCustoFrete, PDO::PARAM_STR);
        $sql->bindValue(':porcentagem_frete', $percentualFrete, PDO::PARAM_STR);
        if (is_bool($deveRecalcularPercentual)) {
            $sql->bindValue(':deve_recalcular_percentual', $deveRecalcularPercentual, PDO::PARAM_BOOL);
        }
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new \Exception(
                'Erro ao atualizar tarifa do ponto de coleta, verifique se você não está tentando para um valor já cadastrado.'
            );
        }
    }
    public static function listaPontosDeColeta(int $idColaborador = 0): array
    {
        $where = '';
        $binds = [];
        if (!empty($idColaborador)) {
            $where = ' AND pontos_coleta.id_colaborador = :id_colaborador ';
            $binds['id_colaborador'] = $idColaborador;
        }

        $pontosColeta = DB::select(
            "SELECT
                tipo_frete.id,
                tipo_frete.nome,
                tipo_frete.categoria,
                tipo_frete.tipo_ponto,
                CONCAT('[', GROUP_CONCAT(DISTINCT tipo_frete.id),']') AS `json_afiliados`,
                colaboradores.razao_social,
                colaboradores.telefone,
                colaboradores_enderecos.cidade,
                colaboradores_enderecos.uf,
                pontos_coleta.id_colaborador,
                pontos_coleta.valor_custo_frete,
                pontos_coleta.porcentagem_frete,
                pontos_coleta.deve_recalcular_percentual,
                DATE_FORMAT(pontos_coleta.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                DATE_FORMAT(pontos_coleta.data_alteracao, '%d/%m/%Y às %H:%i') AS `data_alteracao`,
                pontos_coleta_agenda_acompanhamento.id IS NOT NULL AS `possui_horario`,
                COALESCE((
                    SELECT pontos_coleta_calculo_percentual_frete_logs.lista_id_entrega
                    FROM pontos_coleta_calculo_percentual_frete_logs
                    WHERE pontos_coleta_calculo_percentual_frete_logs.id_colaborador_ponto_coleta = pontos_coleta.id_colaborador
                    GROUP BY pontos_coleta_calculo_percentual_frete_logs.id
                    ORDER BY pontos_coleta_calculo_percentual_frete_logs.id DESC
                    LIMIT 1
                ), '') AS `entregas`,
                EXISTS(
                    SELECT 1
                    FROM tipo_frete_grupos_item
                    WHERE tipo_frete_grupos_item.id_tipo_frete = tipo_frete.id
                ) AS `tem_grupo`
            FROM pontos_coleta
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador_ponto_coleta = pontos_coleta.id_colaborador
            INNER JOIN colaboradores ON colaboradores.id = pontos_coleta.id_colaborador
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            LEFT JOIN pontos_coleta_agenda_acompanhamento ON pontos_coleta_agenda_acompanhamento.id_colaborador = pontos_coleta.id_colaborador
            WHERE TRUE $where
            GROUP BY pontos_coleta.id
            ORDER BY pontos_coleta.id DESC;",
            $binds
        );
        if (empty($pontosColeta)) {
            return [];
        } elseif (empty($idColaborador)) {
            return $pontosColeta;
        } else {
            $pontoColeta = reset($pontosColeta);
            unset(
                $pontoColeta['categoria'],
                $pontoColeta['cidade'],
                $pontoColeta['data_alteracao'],
                $pontoColeta['data_criacao'],
                $pontoColeta['id'],
                $pontoColeta['id_colaborador'],
                $pontoColeta['nome'],
                $pontoColeta['razao_social'],
                $pontoColeta['telefone'],
                $pontoColeta['tipo_ponto'],
                $pontoColeta['uf']
            );

            return $pontoColeta;
        }
    }

    /**
     * O ideal seria criar um gerador padrão de vários INSERT's.
     * Link da issue: https://github.com/mobilestock/backend/issues/192
     */
    public static function insereLogCalculoPercentualFretePontosColeta(
        int $idColaboradorPontoColeta,
        string $listaIdEntrega,
        float $valorCustoFrete,
        float $percentualFrete
    ): void {
        DB::insert(
            "INSERT INTO pontos_coleta_calculo_percentual_frete_logs (
                pontos_coleta_calculo_percentual_frete_logs.id_colaborador_ponto_coleta,
                pontos_coleta_calculo_percentual_frete_logs.lista_id_entrega,
                pontos_coleta_calculo_percentual_frete_logs.valor_custo_frete,
                pontos_coleta_calculo_percentual_frete_logs.porcentagem_frete
            ) VALUES (
                :id_colaborador_ponto_coleta,
                :ids_entrega,
                :valor_custo_frete,
                :porcentagem_frete
            );",
            [
                ':id_colaborador_ponto_coleta' => $idColaboradorPontoColeta,
                ':ids_entrega' => $listaIdEntrega,
                ':valor_custo_frete' => $valorCustoFrete,
                ':porcentagem_frete' => $percentualFrete,
            ]
        );
    }
    public static function deveRecalcularPercentualPontoColeta(int $idColaboradorPontoColeta): bool
    {
        $sql = "SELECT pontos_coleta.deve_recalcular_percentual
            FROM pontos_coleta
            WHERE pontos_coleta.id_colaborador = :id_colaborador;";

        $deveRecalcular = DB::selectOneColumn($sql, [':id_colaborador' => $idColaboradorPontoColeta]);

        return $deveRecalcular;
    }
    public static function buscaInformacoesUltimoCalculo(int $idColaboradorPontoColeta): array
    {
        $sql = "SELECT
                pontos_coleta_calculo_percentual_frete_logs.valor_custo_frete,
                pontos_coleta_calculo_percentual_frete_logs.porcentagem_frete,
                pontos_coleta_calculo_percentual_frete_logs.lista_id_entrega
            FROM pontos_coleta_calculo_percentual_frete_logs
            WHERE pontos_coleta_calculo_percentual_frete_logs.id_colaborador_ponto_coleta = :id_colaborador_ponto_coleta
            ORDER BY pontos_coleta_calculo_percentual_frete_logs.id DESC
            LIMIT 1;";

        $informacoesCalculo = DB::selectOne($sql, [':id_colaborador_ponto_coleta' => $idColaboradorPontoColeta]);

        if (empty($informacoesCalculo)) {
            $informacoesCalculo = [
                'valor_custo_frete' => 0,
                'porcentagem_frete' => 10,
                'lista_id_entrega' => '',
            ];
        }

        return $informacoesCalculo;
    }
    public static function detalhesTarifaPontoColeta(int $idColaboradorPontoColeta): array
    {
        $logTarifaPontoColeta = self::buscaInformacoesUltimoCalculo($idColaboradorPontoColeta);
        $deveRecalcular = self::deveRecalcularPercentualPontoColeta($idColaboradorPontoColeta);
        $retorno = [
            'lista_id_entrega' => $logTarifaPontoColeta['lista_id_entrega'],
            'valor_custo_frete' => $logTarifaPontoColeta['valor_custo_frete'],
            'porcentagem_frete' => $logTarifaPontoColeta['porcentagem_frete'],
            'deve_recalcular' => $deveRecalcular,
            'media_valor_entregas' => 0,
            'entregas' => [],
        ];
        if (!empty($logTarifaPontoColeta['lista_id_entrega'])) {
            $dias = self::buscarDadosEntregasExpedidasUltimos3Dias(
                explode(',', $logTarifaPontoColeta['lista_id_entrega']),
                'ENTREGAS'
            );
            $mediaValorEntregas = array_sum(array_column($dias, 'valor_custo_produto')) / 3;
            $retorno['media_valor_entregas'] = round($mediaValorEntregas, 2);
            $retorno['entregas'] = $dias;
        }

        return $retorno;
    }

    public static function buscaPontosIneficientes(): array
    {
        $pontosIneficientes = DB::select(
            "SELECT
                tipo_frete.id_colaborador,
                tipo_frete.nome,
                tipo_frete.tipo_ponto,
                colaboradores.telefone,
                COUNT(entregas_qtd.id_tipo_frete) AS `qtd_entregas`,
                pontos_coleta.porcentagem_frete,
                configuracoes.percentual_para_cortar_pontos,
                configuracoes.minimo_entregas_para_cortar_pontos
            FROM tipo_frete
            JOIN configuracoes
            JOIN pontos_coleta ON pontos_coleta.id_colaborador = tipo_frete.id_colaborador
            JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            INNER JOIN (
                SELECT
                    entregas.id_tipo_frete
                FROM entregas
                GROUP BY DATE(entregas.data_criacao), entregas.id_tipo_frete
            ) entregas_qtd ON entregas_qtd.id_tipo_frete = tipo_frete.id
            WHERE tipo_frete.categoria = 'ML'
            GROUP BY entregas_qtd.id_tipo_frete
            HAVING `qtd_entregas` > configuracoes.minimo_entregas_para_cortar_pontos
                AND pontos_coleta.porcentagem_frete > configuracoes.percentual_para_cortar_pontos"
        );

        return $pontosIneficientes;
    }
    public static function pontoColetaExiste(PDO $conexao, int $idColaborador): bool
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM pontos_coleta
            WHERE pontos_coleta.id_colaborador = :id_colaborador;"
        );
        $sql->bindValue(':id_colaborador', $idColaborador, PDO::PARAM_INT);
        $sql->execute();
        $existe = (bool) $sql->fetchColumn();

        return $existe;
    }
    public static function listaEntregadoresEPontoDeColeta(): array
    {
        $sql = "SELECT
                    tipo_frete.id,
                    tipo_frete.id_colaborador,
                    colaboradores.razao_social,
                    CONCAT(
                        '[',
                        (
                            SELECT
                                GROUP_CONCAT(
                                    DISTINCT
                                    JSON_OBJECT(
                                        'nome', municipios.nome,
                                        'esta_ativo', transportadores_raios.esta_ativo
                                    )
                                    ORDER BY transportadores_raios.esta_ativo = 1 DESC
                                )
                            FROM transportadores_raios
                            INNER JOIN municipios ON municipios.id = transportadores_raios.id_cidade
                            WHERE
                                transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                        ),
                        ']'
                    ) json_cidades,
                    (
                        SELECT
                            COUNT(entregas_faturamento_item.id)
                        FROM entregas
                        INNER JOIN entregas_faturamento_item ON entregas_faturamento_item.id_entrega = entregas.id
                        WHERE
                            entregas_faturamento_item.situacao <> 'EN'
                            AND entregas.situacao = 'EN'
                            AND entregas.id_tipo_frete = tipo_frete.id
                    ) quantidade_produtos
                FROM tipo_frete
                INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
                WHERE
                    tipo_frete.id_colaborador_ponto_coleta = :idColaborador
                    OR tipo_frete.id_colaborador = :idColaborador
                HAVING quantidade_produtos > 0
                ORDER BY quantidade_produtos DESC;
        ";
        $dados = DB::select($sql, [':idColaborador' => Auth::user()->id_colaborador]);

        $dadosFormatados = array_map(function ($item) {
            $cidadesAtivas = array_filter($item['cidades'], fn(array $cidade): bool => $cidade['esta_ativo']);
            $cidadesInativas = array_filter($item['cidades'], fn(array $cidade): bool => !$cidade['esta_ativo']);
            $item['cidades'] = empty($cidadesAtivas) ? $cidadesInativas : $cidadesAtivas;

            return $item;
        }, $dados);
        return $dadosFormatados;
    }

    public static function buscaCentrais(): array
    {
        $centrais = DB::select(
            "SELECT
                tipo_frete.id id_tipo_frete,
                colaboradores.telefone,
                colaboradores.id id_colaborador,
                colaboradores.usuario_meulook responsavel,
                colaboradores.razao_social nome_ponto,
                (CONCAT(colaboradores_enderecos.logradouro, ' - ', colaboradores_enderecos.numero)) endereco_formatado,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.bairro,
                (
                    SELECT
                        CONCAT(municipios.nome, ' - ', municipios.uf)
                    FROM municipios
                    WHERE municipios.id = colaboradores_enderecos.id_cidade
                    LIMIT 1
                ) cidade,
                colaboradores.foto_perfil,
                NULL previsoes,
                NULL distancia,
                FALSE esta_selecionado,
                'COLETA' tipo_ponto
            FROM pontos_coleta
            JOIN colaboradores ON colaboradores.id = pontos_coleta.id_colaborador
            JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            JOIN tipo_frete ON tipo_frete.id_colaborador = pontos_coleta.id_colaborador
            ORDER BY pontos_coleta.id ASC;"
        );

        return $centrais;
    }
}

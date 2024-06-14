<?php

namespace MobileStock\service;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\ConversorArray;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\model\Origem;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IBGEService
{
    // public function buscarEstados()
    // {
    //     $sql = "SELECT nome, uf FROM estados";
    //     $conexao = Conexao::criarConexao();
    //     $stmt = $conexao->prepare($sql);
    //     $stmt->execute();
    //     $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $lista;
    // }
    public static function buscarUF()
    {
        $sql = 'SELECT nome, uf FROM estados';
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }
    // public function buscarMunicipios($estado)
    // {
    //     if ($estado != '0') {
    //         $sql = "SELECT nome FROM municipios WHERE uf='{$estado}' ORDER BY municipios.nome ASC";
    //     } else {
    //         $sql = "SELECT nome FROM municipios ORDER BY municipios.nome ASC";
    //     }
    //     $conexao = Conexao::criarConexao();
    //     $stmt = $conexao->prepare($sql);
    //     $stmt->execute();
    //     $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     return $lista;
    // }
    public static function buscarCidades($estado)
    {
        if ($estado != '0') {
            $sql = "SELECT id, nome FROM municipios WHERE uf='{$estado}' ORDER BY municipios.nome ASC";
        } else {
            $sql = 'SELECT id, nome FROM municipios ORDER BY municipios.nome ASC';
        }
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }

    public static function buscarCidadesMeuLook(PDO $conexao, string $pesquisa): array
    {
        $sql = "SELECT
            municipios.id,
            municipios.nome,
            municipios.uf,
            municipios.latitude,
            municipios.longitude
        FROM municipios
        WHERE municipios.latitude IS NOT NULL AND municipios.longitude IS NOT NULL
        AND (municipios.nome REGEXP :pesquisa OR municipios.uf REGEXP :pesquisa)";
        $stmt = $conexao->prepare($sql);
        $stmt->execute([
            ':pesquisa' => $pesquisa,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarCidadesMeuLookPontos(string $pesquisa): array
    {
        $where = '';
        if (mb_strlen($pesquisa) < 3) {
            return [];
        } elseif (is_numeric($pesquisa)) {
            $where = ' AND municipios.id = :pesquisa ';
        } else {
            $where = " AND LOWER(CONCAT_WS(
                    ' - ',
                    municipios.nome,
                    municipios.uf
                )) REGEXP LOWER(:pesquisa) ";
        }

        $sql = "SELECT
            municipios.id,
            CONCAT(municipios.nome, ' - ', municipios.uf) label,
            municipios.nome,
            municipios.uf,
            municipios.latitude,
            municipios.longitude,
            EXISTS(
                SELECT 1
                FROM colaboradores
                INNER JOIN colaboradores_enderecos ON
                    colaboradores_enderecos.id_colaborador = colaboradores.id AND
                    colaboradores_enderecos.eh_endereco_padrao = 1
                WHERE colaboradores.id IN (
                    SELECT tipo_frete.id_colaborador
                    FROM tipo_frete
                    WHERE tipo_frete.id_colaborador > 0
                ) AND colaboradores_enderecos.id_cidade = municipios.id
            ) tem_ponto
        FROM municipios
        WHERE TRUE $where
        ORDER BY tem_ponto DESC, municipios.nome";
        $cidades = DB::select($sql, [
            ':pesquisa' => $pesquisa,
        ]);

        return $cidades;
    }

    public static function buscarCidadesFiltro(PDO $conexao, string $pesquisa): array
    {
        $sql = "SELECT
            municipios.id,
            municipios.nome,
            municipios.uf,
            municipios.valor_comissao_bonus,
            municipios.latitude,
            municipios.longitude
        FROM municipios
        WHERE (municipios.nome REGEXP :pesquisa OR municipios.uf REGEXP :pesquisa)";
        $stmt = $conexao->prepare($sql);
        $stmt->execute([
            ':pesquisa' => $pesquisa,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarIDCidade(PDO $conexao, string $cidade, string $uf): int
    {
        $sql = "SELECT
                    municipios.id
                FROM municipios
                WHERE municipios.nome REGEXP :cidade
                    AND municipios.uf REGEXP :uf
                LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->execute([
            ':cidade' => ConversorStrings::removeAcentos($cidade),
            ':uf' => $uf,
        ]);
        $id = $stmt->fetchColumn(0);
        return $id;
    }

    public function salvarCidade(PDO $conexao)
    {
        $dados = [];
        $sql = 'UPDATE municipios SET ';

        foreach ($this as $key => $valor) {
            if (!$valor || in_array($key, [''])) {
                continue;
            }
            if (gettype($valor) == 'string') {
                $valor = "'" . $valor . "'";
            }
            $dados[] = $key . ' = ' . $valor;
        }
        if (sizeof($dados) === 0) {
            throw new InvalidArgumentException('Não Existe informações para ser atualizada');
        }

        $sql .= ' ' . implode(',', $dados) . " WHERE municipios.id = '" . $this->id . "'";

        return $conexao->exec($sql);
    }

    public static function buscarInfoCidade(int $idCidade): array
    {
        $cidade = DB::selectOne(
            "SELECT
                municipios.id,
                municipios.nome,
                municipios.uf,
                municipios.latitude,
                municipios.longitude
            FROM municipios
            WHERE municipios.id = :id_cidade;",
            ['id_cidade' => $idCidade]
        );
        if (empty($cidade)) {
            throw new NotFoundHttpException('Cidade não encontrada.');
        }

        return $cidade;
    }
    // --Commented out by Inspection START (18/08/2022 17:57):
    //    public static function buscaPontoRetiradaMaisPróximo(): int
    //    {
    //        return 0;
    //    }
    // --Commented out by Inspection STOP (18/08/2022 17:57)

    // public static function buscaLatitudeLongitudePorCidadeApiGoogle(string $cidade): array
    // {
    //     $url = "https://maps.googleapis.com/maps/api/geocode/json?" . http_build_query([
    //         'address' => $cidade,
    //         'key' => $_ENV['GOOGLE_TOKEN_GEOLOCALIZACAO']
    //     ]);
    //     $curl = curl_init($url);
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //     $resposta = json_decode(curl_exec($curl), true);

    //     if (empty($resposta) || empty($resposta['results'] ?? [])) {
    //         $latitude = 0;
    //         $longitude = 0;
    //     }

    //     if ($latitude !== 0) {

    //         $latitude = $resposta['results'][0]['geometry']['location']['lat'];
    //         $longitude = $resposta['results'][0]['geometry']['location']['lng'];
    //     }

    //     return [
    //         'latitude' => $latitude,
    //         'longitude' => $longitude
    //     ];
    // }
    public static function buscaDadosEnderecoApiGoogle(string $endereco): array
    {
        $url =
            'https://maps.googleapis.com/maps/api/geocode/json?' .
            http_build_query([
                'address' => $endereco,
                'key' => $_ENV['GOOGLE_TOKEN_GEOLOCALIZACAO'],
                'language' => 'PT-BR',
                'location_type' => 'ROOFTOP',
                'result_type' => 'street_address',
            ]);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $request = curl_exec($curl);
        $resposta = json_decode($request, true) ?: [];
        return $resposta;
    }

    public static function buscaDadosEnderecoApiViaCep(string $cep): array
    {
        $url = 'https://viacep.com.br/ws/' . $cep . '/json/';
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $request = curl_exec($curl);
        $resposta = json_decode($request, true) ?: [];
        return $resposta;
    }

    public static function buscaPontosRetiradaDisponiveis(
        string $tipoPesquisa,
        array $produtosPedido,
        string $origem,
        array $geolocalizacao
    ): array {
        $idCliente = Auth::user()->id_colaborador;
        $selectSql = '';
        $whereSql = '';
        $joinSql = '';
        $origemCalculo = '';
        $valorVenda = '';
        $somaComissaoMobile = '';
        $valores = [];
        $idProduto = reset($produtosPedido);
        $previsao = app(PrevisaoService::class);
        $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
        if (is_numeric($idProduto)) {
            $mediasEnvio = $previsao->calculoDiasSeparacaoProduto($idProduto);
        }

        $dadosCliente['id_cidade'] = DB::selectOneColumn(
            "SELECT colaboradores_enderecos.id_cidade
            FROM colaboradores_enderecos
            WHERE colaboradores_enderecos.id_colaborador = :id_colaborador
	            AND colaboradores_enderecos.eh_endereco_padrao;",
            ['id_colaborador' => $idCliente]
        );
        if (empty($dadosCliente['id_cidade'])) {
            throw new NotFoundHttpException('Cliente não encontrado.');
        }
        $dadosCliente['latitude'] = $geolocalizacao['latitude'];
        $dadosCliente['longitude'] = $geolocalizacao['longitude'];

        switch ($origem) {
            case Origem::ML:
                $origemCalculo = ' pedido_item.uuid AND pedido_item_meu_look.uuid IS NOT NULL ';
                $valorVenda = ' produtos.valor_venda_ml ';
                break;

            case Origem::MS:
                $origemCalculo = 'pedido_item.uuid AND pedido_item_meu_look.uuid IS NULL ';
                $valorVenda = ' produtos.valor_venda_ms ';
                $somaComissaoMobile = ' + (
                                            SELECT
                                                configuracoes.porcentagem_comissao_ponto_coleta
                                            FROM configuracoes LIMIT 1
                                        )';
                break;
        }

        if (!empty($produtosPedido)) {
            [$bind, $valores] = ConversorArray::criaBindValues($produtosPedido);
            if (is_numeric($idProduto)) {
                $selectSql .= "
                    ,
                    ROUND(transportadores_raios.valor_entrega, 2)
                    + ROUND(
                        SUM(
                            ROUND(
                                produtos.valor_custo_produto
                                * (
                                    (
                                        COALESCE(
                                            (
                                                SELECT pontos_coleta.porcentagem_frete
                                                FROM pontos_coleta
                                                WHERE pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                                            ), 0
                                        ) $somaComissaoMobile
                                    ) / 100
                                ), 2
                            )
                        ), 2
                    ) preco,
                    ROUND(
                      ROUND(
                        $valorVenda + transportadores_raios.valor_entrega, 2
                      )
                      + ROUND(
                        SUM(
                          ROUND(
                            produtos.valor_custo_produto
                            * (
                              (
                                COALESCE(
                                  (
                                    SELECT pontos_coleta.porcentagem_frete
                                    FROM pontos_coleta
                                    WHERE pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                                  ), 0
                                ) $somaComissaoMobile
                              ) / 100
                            ), 2
                          )
                        ), 2
                      ), 2
                    ) valor_total
                ";
                $joinSql .= "INNER JOIN produtos ON produtos.id IN ($bind)";
            } else {
                $selectSql .= "
                    ,
                    ROUND(
                        ROUND(SUM($origemCalculo) * transportadores_raios.valor_entrega, 2)
                        + ROUND(
                            SUM(ROUND(produtos.valor_custo_produto
                                * (
                                    (COALESCE(
                                        (
                                            SELECT pontos_coleta.porcentagem_frete
                                            FROM pontos_coleta
                                            WHERE pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                                        ), 0
                                    ) $somaComissaoMobile) / 100
                                )
                            , 2)), 2
                        )
                    , 2) AS `preco`,
                    ROUND(
                        ROUND(
                            SUM($valorVenda) + ROUND(COUNT(pedido_item.uuid) * transportadores_raios.valor_entrega, 2)
                            , 2
                        ) + ROUND(
                                SUM(ROUND(
                                    produtos.valor_custo_produto * (
                                        (COALESCE(
                                            (
                                                SELECT pontos_coleta.porcentagem_frete
                                                FROM pontos_coleta
                                                WHERE pontos_coleta.id_colaborador = tipo_frete.id_colaborador_ponto_coleta
                                            ), 0
                                        ) $somaComissaoMobile) / 100
                                    )
                                , 2)
                            ), 2
                        ),
                    2) valor_total
                ";
                $joinSql .= "
                    INNER JOIN pedido_item ON
                        pedido_item.situacao NOT IN ('3', 'FR')
                        AND pedido_item.id_cliente = $idCliente
                        AND pedido_item.uuid IN ($bind)
                    LEFT JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = pedido_item.uuid
                    INNER JOIN produtos ON produtos.id = pedido_item.id_produto
                ";

                $produtos = DB::select(
                    "SELECT
                            pedido_item.id_produto,
                            pedido_item.nome_tamanho
                        FROM pedido_item
                        WHERE pedido_item.uuid IN ($bind)
                        GROUP BY pedido_item.id_produto, pedido_item.nome_tamanho;",
                    $valores
                );
                $produtos = array_map(function (array $produto) use ($previsao): array {
                    $produto['medias_envio'] = $previsao->calculoDiasSeparacaoProduto(
                        $produto['id_produto'],
                        $produto['nome_tamanho']
                    );

                    return $produto;
                }, $produtos);
            }
        }
        if ($tipoPesquisa === 'LOCAL') {
            $whereSql = ' AND colaboradores_enderecos.id_cidade = :id_cidade ';
            $valores[':id_cidade'] = $dadosCliente['id_cidade'];
        }

        $consulta = DB::select(
            "SELECT
                tipo_frete.id id_tipo_frete,
                tipo_frete.id_colaborador_ponto_coleta,
                colaboradores.id id_colaborador,
                colaboradores.usuario_meulook responsavel,
                tipo_frete.nome nome_ponto,
                tipo_frete.mensagem endereco_formatado,
                colaboradores_enderecos.id_cidade,
                colaboradores_enderecos.bairro,
                colaboradores.telefone,
                tipo_frete.latitude,
                tipo_frete.longitude,
                transportadores_raios.dias_entregar_cliente,
                transportadores_raios.dias_margem_erro,
                (
                    SELECT CONCAT(municipios.nome, ' - ', municipios.uf)
                    FROM municipios
                    WHERE municipios.id = colaboradores_enderecos.id_cidade
                ) cidade,
                colaboradores.foto_perfil,
                tipo_frete.horario_de_funcionamento,
                tipo_frete.tipo_ponto
                $selectSql
            FROM tipo_frete
            INNER JOIN colaboradores ON colaboradores.id = tipo_frete.id_colaborador
            LEFT JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN transportadores_raios ON transportadores_raios.esta_ativo
                AND transportadores_raios.id_colaborador = tipo_frete.id_colaborador
            $joinSql
            WHERE tipo_frete.categoria = 'ML'
                AND tipo_frete.tipo_ponto = 'PP'
                AND LENGTH(COALESCE(tipo_frete.latitude, '')) > 1
                AND LENGTH(COALESCE(tipo_frete.longitude, '')) > 1
                $whereSql
            GROUP BY tipo_frete.id;",
            $valores
        );

        foreach ($consulta as &$pontoRetirada) {
            $pontoRetirada['previsoes'] = null;
            $pontoRetirada['eh_local'] = $pontoRetirada['id_cidade'] === $dadosCliente['id_cidade'];
            if (round($dadosCliente['latitude']) === 0) {
                $pontoRetirada['distancia'] = $pontoRetirada['id_cidade'] === $dadosCliente['id_cidade'] ? 0 : 1;
            } else {
                $pontoRetirada['distancia'] = Globals::Haversine(
                    $pontoRetirada['latitude'],
                    $pontoRetirada['longitude'],
                    $dadosCliente['latitude'],
                    $dadosCliente['longitude']
                );
            }
            if (empty($produtosPedido)) {
                continue;
            }

            $agenda->id_colaborador = $pontoRetirada['id_colaborador_ponto_coleta'];
            $pontoColeta = $agenda->buscaPrazosPorPontoColeta();
            if (empty($pontoColeta['agenda'])) {
                continue;
            }
            $diasProcessoEntrega = [
                'dias_pedido_chegar' => $pontoColeta['dias_pedido_chegar'],
                'dias_entregar_cliente' => $pontoRetirada['dias_entregar_cliente'],
                'dias_margem_erro' => $pontoRetirada['dias_margem_erro'],
            ];
            if (is_numeric($idProduto)) {
                $pontoRetirada['previsoes'] = $previsao->calculaPorMediasEDias(
                    $mediasEnvio,
                    $diasProcessoEntrega,
                    $pontoColeta['agenda']
                );
                continue;
            }

            $previsoes = array_map(
                fn(array $produto): array => $previsao->calculaPorMediasEDias(
                    $produto['medias_envio'],
                    $diasProcessoEntrega,
                    $pontoColeta['agenda']
                ),
                $produtos
            );
            $previsoes = array_merge(...$previsoes);
            if (empty($previsoes)) {
                continue;
            }

            $filtro = array_unique(array_column($previsoes, 'responsavel'));
            $ordenamento = function (bool $verMenorPrazo): Closure {
                return function (array $a, array $b) use ($verMenorPrazo): int {
                    $contadorA = $verMenorPrazo && $a['responsavel'] === 'FULFILLMENT' ? 2 : 0;
                    $contadorB = $verMenorPrazo && $b['responsavel'] === 'FULFILLMENT' ? 2 : 0;

                    if ($verMenorPrazo) {
                        $contadorA += (int) $a['dias_minimo'] < $b['dias_minimo'];
                        $contadorB += (int) $a['dias_minimo'] > $b['dias_minimo'];
                    } else {
                        $contadorA += (int) $a['dias_maximo'] > $b['dias_maximo'];
                        $contadorB += (int) $a['dias_maximo'] < $b['dias_maximo'];
                    }

                    return -$contadorA + $contadorB;
                };
            };
            usort($previsoes, $ordenamento(true));
            $minima = $previsoes[0];
            usort($previsoes, $ordenamento(false));
            $maxima = $previsoes[0];
            if (count($filtro) > 1) {
                $pontoRetirada['previsoes'] = array_unique([$minima, $maxima], SORT_REGULAR);
            } else {
                $pontoRetirada['previsoes'] = [
                    [
                        'dias_minimo' => $minima['dias_minimo'],
                        'dias_maximo' => $maxima['dias_maximo'],
                        'media_previsao_inicial' => $maxima['media_previsao_inicial'],
                        'media_previsao_final' => $maxima['media_previsao_final'],
                        'responsavel' => reset($filtro),
                    ],
                ];
            }
        }
        return $consulta;
    }

    public static function buscaCidadesComBonus(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                municipios.id,
                municipios.valor_comissao_bonus,
                CONCAT(municipios.nome, ' - ', municipios.uf) AS `cidade`
            FROM municipios
            WHERE municipios.valor_comissao_bonus > 0"
        );
        $stmt->execute();
        $consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $consulta ?: [];
    }

    public static function alteraValorCidade(PDO $conexao, int $idCidade, float $preco): void
    {
        $stmt = $conexao->prepare(
            "UPDATE municipios
             SET municipios.valor_comissao_bonus = :preco
             WHERE municipios.id = :id_cidade"
        );
        $stmt->bindValue(':preco', $preco, PDO::PARAM_STR);
        $stmt->bindValue(':id_cidade', $idCidade, PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->rowCount()) {
            throw new \Exception('Nenhum valor foi alterado.');
        }
    }

    public static function gerenciarPontoColeta(
        PDO $conexao,
        int $idColaboradorPonto,
        bool $ativar,
        int $idUsuario
    ): void {
        if ($ativar) {
            $sql = $conexao->prepare(
                "INSERT INTO pontos_coleta (
                    pontos_coleta.id_colaborador
                ) VALUES (
                    :id_colaborador
                );"
            );
        } else {
            $sql = $conexao->prepare(
                "DELETE FROM pontos_coleta
                WHERE pontos_coleta.id_colaborador = :id_colaborador;"
            );
        }
        $sql->bindValue(':id_colaborador', $idColaboradorPonto, PDO::PARAM_INT);
        $sql->execute();
        if ($sql->rowCount() > 1) {
            throw new \Exception(
                $ativar ? 'Não foi possível ativar o ponto de coleta.' : 'Não foi possível desativar o ponto de coleta.'
            );
        }

        if ($ativar) {
            TipoFreteService::adicionaCentralColeta($conexao, $idColaboradorPonto, $idColaboradorPonto, $idUsuario);
        }
    }

    public static function buscaStatusPontoColeta(PDO $conexao, int $idColaboradorPonto): bool
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM pontos_coleta
            WHERE pontos_coleta.id_colaborador = :id_colaborador_ponto;"
        );
        $sql->bindValue(':id_colaborador_ponto', $idColaboradorPonto, PDO::PARAM_INT);
        $sql->execute();
        $ativo = (bool) $sql->fetchColumn();

        return $ativo;
    }

    public static function buscaPontoSelecionado(int $idTransacao): array
    {
        $pontoSelecionado = DB::selectOne(
            "SELECT
                colaboradores.razao_social responsavel,
                tipo_frete.id AS `id_tipo_frete`,
                tipo_frete.mensagem endereco_formatado,
                tipo_frete.tipo_ponto,
                tipo_frete.categoria,
                colaboradores.telefone,
                colaboradores.foto_perfil
            FROM transacao_financeiras_metadados
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = transacao_financeiras_metadados.valor
            INNER JOIN colaboradores ON colaboradores.id = transacao_financeiras_metadados.valor
            WHERE transacao_financeiras_metadados.id_transacao = :id_transacao
                AND transacao_financeiras_metadados.chave = 'ID_COLABORADOR_TIPO_FRETE';",
            [':id_transacao' => $idTransacao]
        );
        if (empty($pontoSelecionado)) {
            throw new NotFoundHttpException('Ponto não encontrado.');
        }

        return $pontoSelecionado;
    }

    public static function buscaIDTipoFretePadraoTransportadoraMeulook(): array
    {
        $dados = DB::selectOne(
            "SELECT
                municipios.valor_frete,
                municipios.valor_adicional,
                (
                    SELECT
                        COALESCE(tipo_frete.id, 2)
                    FROM configuracoes
                    LEFT JOIN tipo_frete ON tipo_frete.id_colaborador = configuracoes.id_colaborador_tipo_frete_transportadora_meulook
                    LIMIT 1
                ) AS `id_tipo_frete_transportadora_meulook`
            FROM municipios
            JOIN colaboradores_enderecos ON colaboradores_enderecos.id_cidade = municipios.id
                AND colaboradores_enderecos.id_colaborador = :id_colaborador
                AND colaboradores_enderecos.eh_endereco_padrao = 1;",
            ['id_colaborador' => Auth::user()->id_colaborador]
        );

        return $dados;
    }
}

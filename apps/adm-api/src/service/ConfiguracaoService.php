<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use MobileStock\model\Origem;
use PDO;
use RuntimeException;

class ConfiguracaoService
{
    public static function buscaQtdMaximaDiasEstoqueParadoFulfillment(): int
    {
        $qtdDias = DB::selectOneColumn(
            "SELECT configuracoes.qtd_maxima_dias_produto_fulfillment_parado
            FROM configuracoes;"
        );

        return $qtdDias;
    }
    public static function alteraQtdDiasEstoqueParadoFulfillment(int $qtdDias): void
    {
        $linhasAlteradas = DB::update(
            "UPDATE configuracoes
            SET configuracoes.qtd_maxima_dias_produto_fulfillment_parado = :qtd_dias;",
            ['qtd_dias' => $qtdDias]
        );

        if ($linhasAlteradas !== 1) {
            throw new RuntimeException('Não foi possível alterar a quantidade de dias do estoque parado');
        }
    }
    public static function horariosSeparacaoFulfillment(PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT configuracoes.horarios_separacao_fulfillment
            FROM configuracoes;"
        );
        $sql->execute();
        $horarios = $sql->fetchColumn();
        $horarios = json_decode($horarios, true);

        return $horarios;
    }
    public static function salvaHorariosSeparacaoFulfillment(PDO $conexao, array $horarios): void
    {
        sort($horarios);
        $sql = $conexao->prepare(
            "UPDATE configuracoes
            SET configuracoes.horarios_separacao_fulfillment = :horarios;"
        );
        $sql->bindValue(':horarios', json_encode($horarios), PDO::PARAM_STR);
        $sql->execute();
    }
    public static function consultaInfoMeiosPagamento(PDO $conexao)
    {
        $infoMetodosPagamento = $conexao
            ->query(
                "SELECT configuracoes.informacoes_metodos_pagamento
                FROM configuracoes
        LIMIT 1;"
            )
            ->fetch(PDO::FETCH_ASSOC)['informacoes_metodos_pagamento'];

        return json_decode($infoMetodosPagamento, true);
    }

    public static function atualizaMeiosPagamento(PDO $conexao, array $meiosPagamento): void
    {
        $meiosPagamento = \json_encode($meiosPagamento, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE);
        $conexao->exec("UPDATE configuracoes SET configuracoes.informacoes_metodos_pagamento = '$meiosPagamento'");
    }

    public static function buscaConfiguracaoProdutoPago(PDO $conexao): array
    {
        $consulta = $conexao
            ->query(
                "SELECT
                        configuracoes.num_dias_remover_produto_pago,
                        configuracoes.valor_taxa_remove_produto_pago
                      FROM configuracoes"
            )
            ->fetch(PDO::FETCH_NUM);

        return $consulta;
    }

    public static function consultaDiasVencimento(PDO $conexao): int
    {
        $dias = $conexao->query('SELECT configuracoes.num_dias_venc_boleto FROM configuracoes')->fetchColumn();

        return $dias;
    }

    public function buscarIDZoopPadrao(int $regime)
    {
        if ($regime == 1) {
            return $this->id_zoop_juridico;
        }
        return $this->id_zoop_fisico;
    }

    public function buscarIdInternoFornecedorPadrao(int $regime)
    {
        if ($regime == 1) {
            return $this->fornecedor_juridico;
        }
        return $this->fornecedor_fisico;
    }

    public static function permiteMonitoramentoSentry(): bool
    {
        $dado = DB::selectOneColumn('SELECT configuracoes.permite_monitoramento_sentry FROM configuracoes');
        return $dado;
    }

    public static function RetornaLocalPagamento(string $metodoPagamento, string $valorLiquido)
    {
        if ($metodoPagamento === 'CA' && $valorLiquido > 2000) {
            return 'Cielo';
        }
        // return Conexao::criarConexao()->query("SELECT IF(api_colaboradores.id_zoop IS NULL or api_colaboradores.id_zoop = '' or configuracoes.valor_limit_recebe_cartao_dia >
        //                                             COALESCE((SELECT SUM(transacao_financeiras.valor_liquido)
        //                                                         FROM transacao_financeiras
        //                                                         WHERE transacao_financeiras.metodo_pagamento = 'CA'
        //                                                             AND DATE(transacao_financeiras.data_criacao) = CURDATE()
        //                                                             AND transacao_financeiras.status IN ('PE','PA')),0),'cielo','zoop') valor
        //                                         FROM configuracoes LEFT JOIN api_colaboradores ON(api_colaboradores.id_colaborador=". $id.") LIMIT 1;")->fetch(PDO::FETCH_ASSOC)['valor'];

        $infoMetodosPagamento = Conexao::criarConexao()
            ->query(
                "SELECT configuracoes.informacoes_metodos_pagamento
                FROM configuracoes
        LIMIT 1;"
            )
            ->fetch(PDO::FETCH_ASSOC)['informacoes_metodos_pagamento'];

        $infoMetodosPagamento = json_decode($infoMetodosPagamento, true);

        return $infoMetodosPagamento[$metodoPagamento]['local_pagamento'];
    }

    public static function buscaInterfacesPagamento(
        PDO $conexao,
        string $metodoPagamento,
        float $valorLiquido,
        float $valorParcela
    ): array {
        if ($valorLiquido === 0.0) {
            $metodoPagamento = 'CR';
            $locaisPagamento = ['Interno'];
        } elseif (
            ($metodoPagamento === 'CA' && $valorLiquido > 4000) ||
            ($metodoPagamento === 'CA' && $valorParcela < 5)
        ) {
            $locaisPagamento = ['Cielo'];
        } else {
            $infoMetodosPagamento = self::consultaInfoMeiosPagamento($conexao);

            $locaisPagamento = array_reduce(
                $infoMetodosPagamento,
                function (array $total, array $item) use ($metodoPagamento) {
                    if (!empty($total)) {
                        return $total;
                    }

                    if ($item['prefixo'] === $metodoPagamento) {
                        return array_column(
                            array_filter(
                                $item['meios_pagamento'],
                                fn(array $meioPagamento) => $meioPagamento['situacao'] === 'ativo'
                            ),
                            'local_pagamento'
                        );
                    }

                    return [];
                },
                []
            );
        }

        $interfaces = [];
        foreach ($locaisPagamento as $localPagamento) {
            foreach (Globals::INTERFACES_PAGAMENTO as $interfacePagamento) {
                if (
                    $interfacePagamento::SUPORTA_METODO_PAGAMENTO($metodoPagamento) &&
                    $interfacePagamento::SUPORTA_LOCAL_PAGAMENTO($localPagamento)
                ) {
                    $interfaces[] = $interfacePagamento;
                }
            }
        }

        return $interfaces;
    }

    public static function consultaDadosPagamentoPadrao(): array
    {
        $dadosPagamentoPadrao = DB::selectOneColumn(
            'SELECT configuracoes.dados_pagamento_padrao
            AS `json_dados_pagamento_padrao`
            FROM configuracoes'
        );

        return $dadosPagamentoPadrao;
    }

    public static function consultaPeriodoDevolucao(PDO $conexao): int
    {
        return $conexao
            ->query('SELECT configuracoes.qtd_dias_disponiveis_troca_normal FROM configuracoes LIMIT 1')
            ->fetch(PDO::FETCH_ASSOC)['qtd_dias_disponiveis_troca_normal'];
    }

    public static function consultaPermiteCriarLookComQualquerProduto(PDO $conexao): bool
    {
        return $conexao
            ->query('SELECT configuracoes.permite_criar_look_com_qualquer_produto FROM configuracoes LIMIT 1')
            ->fetch(PDO::FETCH_ASSOC)['permite_criar_look_com_qualquer_produto'] === 'T';
    }

    public static function consultaHorarioFinalDiaRankingMeuLook(PDO $conexao): string
    {
        $horario = $conexao
            ->query(
                "SELECT IF (
                NOW() >= CONCAT(DATE_FORMAT(CURDATE(), '%Y-%m-%d '), configuracoes.horario_final_dia_ranking_meulook),
                CONCAT(DATE_FORMAT(CURDATE() + INTERVAL 1 DAY, '%Y-%m-%d '), configuracoes.horario_final_dia_ranking_meulook),
                CONCAT(DATE_FORMAT(CURDATE(), '%Y-%m-%d '), configuracoes.horario_final_dia_ranking_meulook)
            ) horario
            FROM configuracoes
            LIMIT 1"
            )
            ->fetch(PDO::FETCH_ASSOC);

        if (empty($horario)) {
            $horario = ['horario' => date('Y/m/d') . ' 22:00:00'];
        }

        return $horario['horario'];
    }

    public static function consultaTempoMargemErroRequisicaoPremiacaoRankingMeulook(PDO $conexao): int
    {
        $tempo = $conexao
            ->query(
                "SELECT COALESCE(configuracoes.margem_erro_minutos_premiacao_ranking_meulook, 5) tempo
            FROM configuracoes
            LIMIT 1"
            )
            ->fetch(PDO::FETCH_ASSOC);

        if (empty($tempo)) {
            $tempo = ['tempo' => 5];
        }

        return $tempo['tempo'];
    }

    // public static function consultaQtdProdutosPermiteCompraValorCnpj(\PDO $conexao): int
    // {
    //     return $conexao->query(
    //         "SELECT
    //             configuracoes.qtd_produtos_permite_compra_valor_cnpj
    //         FROM configuracoes"
    //     )->fetch(\PDO::FETCH_ASSOC)['qtd_produtos_permite_compra_valor_cnpj'];
    // }

    // public static function buscaQuantidadeMaximaDeProdutos(\PDO $conexao)
    // {
    //     $consulta = $conexao->query(
    //         "SELECT configuracoes.quantidade_maxima_produtos_publicacoes_meu_look
    //         FROM configuracoes
    //         LIMIT 1"
    //     )->fetch(\PDO::FETCH_ASSOC);

    //     if (empty($consulta)) return 5;

    //     return (int) $consulta['quantidade_maxima_produtos_publicacoes_meu_look'];
    // }
    public static function buscaDiasDeCancelamentoAutomatico(PDO $conexao)
    {
        $consulta = $conexao
            ->query(
                "SELECT configuracoes.dias_para_cancelamento_automatico
            FROM configuracoes
            LIMIT 1"
            )
            ->fetch(PDO::FETCH_ASSOC);

        return (int) $consulta['dias_para_cancelamento_automatico'] ?: 0;
    }
    public static function buscaDiasAtrasoParaSeparacao(): int
    {
        $diasAtrasoParaSeparacao = DB::selectOneColumn(
            "SELECT configuracoes.dias_atraso_para_separacao
            FROM configuracoes
            LIMIT 1;"
        );

        return $diasAtrasoParaSeparacao ?: 0;
    }
    public static function buscaDiasAtrasoParaConferencia(): int
    {
        $diasAtrasoParaConferencia = DB::selectOneColumn(
            "SELECT configuracoes.dias_atraso_para_conferencia
            FROM configuracoes
            LIMIT 1;"
        );

        return $diasAtrasoParaConferencia ?: 0;
    }

    public static function salvaTokenRodonaves(PDO $conexao, string $token): void
    {
        $sql = "UPDATE configuracoes SET configuracoes.token_rodonaves = '$token'";
        $conexao->exec($sql);
    }

    public static function consultaTokenRodonaves(PDO $conexao): string
    {
        $consulta = $conexao->query('SELECT token_rodonaves FROM configuracoes')->fetch(PDO::FETCH_ASSOC);
        return $consulta['token_rodonaves'];
    }

    public static function consultaDatasDeTroca(PDO $conexao): array
    {
        $configuracoes =
            $conexao
                ->query(
                    'SELECT qtd_dias_disponiveis_troca_normal, qtd_dias_disponiveis_troca_defeito FROM configuracoes LIMIT 1;'
                )
                ->fetchAll(PDO::FETCH_ASSOC) ?:
            [];

        return $configuracoes;
    }

    public static function consultaTaxasFrete(PDO $conexao): array
    {
        $configuracoes = $conexao
            ->query('SELECT porcentagem_comissao_freteiros_por_km FROM configuracoes LIMIT 1;')
            ->fetch(PDO::FETCH_ASSOC);
        $configuracoes = $configuracoes['porcentagem_comissao_freteiros_por_km'];
        $configuracoes = json_decode($configuracoes, true);
        return $configuracoes;
    }

    public static function atualizaTaxasFrete(PDO $conexao, array $taxas): void
    {
        $stmt = $conexao->prepare('UPDATE configuracoes SET porcentagem_comissao_freteiros_por_km = :taxas');
        $stmt->bindValue(':taxas', json_encode($taxas));
        $stmt->execute();
    }

    // public static function buscaRequisitosMelhorFabricante(\PDO $conexao): array
    // {
    //     $stmt = $conexao->query(
    //         "SELECT COALESCE(configuracoes.valor_minimo_vendido_destaque_melhores_fabricantes, 2000) valor_minimo_venda,
    //             COALESCE(configuracoes.media_envio_minimo_destaque_melhor_fabricante, 2) media_dias_envio,
    //             COALESCE(configuracoes.porcentagem_maxima_cancelamentos_destaque_melhor_fabricante, 5) porcentagem_maxima_cancelamento
    //         FROM configuracoes"
    //     );
    //     $requisitos = $stmt->fetch(PDO::FETCH_ASSOC);
    //     $requisitos['valor_minimo_venda'] = (float) $requisitos['valor_minimo_venda'];
    //     $requisitos['media_dias_envio'] = (int) $requisitos['media_dias_envio'];
    //     $requisitos['porcentagem_maxima_cancelamento'] = (float) $requisitos['porcentagem_maxima_cancelamento'];
    //     $requisitos['dias_ultimas_vendas'] = ReputacaoFornecedoresService::DIAS_MENSURAVEIS;
    //     return $requisitos;
    // }
    public static function informacaoPagamentoAutomaticoTransferenciasAtivo(PDO $conexao): bool
    {
        $sql = $conexao->prepare(
            "SELECT configuracoes.permite_pagamento_automatico_transferencias
            FROM configuracoes;"
        );
        $sql->execute();
        $ativado = (bool) $sql->fetchColumn();

        return $ativado;
    }
    public static function modificaPagamentoAutomaticoTransferencia(PDO $conexao, bool $permitir): void
    {
        $sql = $conexao->prepare(
            "UPDATE configuracoes
            SET configuracoes.permite_pagamento_automatico_transferencias = :permitir
            WHERE NOT configuracoes.permite_pagamento_automatico_transferencias = :permitir;"
        );
        $sql->bindValue(':permitir', $permitir, PDO::PARAM_BOOL);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível modificar o Pagamento Automático');
        }
    }
    public static function porcentagencComissoesProdutos(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                configuracoes.porcentagem_comissao_ms,
                configuracoes.porcentagem_comissao_ml,
                configuracoes.porcentagem_comissao_ponto_coleta
            FROM configuracoes"
        );
        $stmt->execute();
        $porcentagens = $stmt->fetch(PDO::FETCH_ASSOC);
        $porcentagens['porcentagem_comissao_ml'] = (float) $porcentagens['porcentagem_comissao_ml'];
        $porcentagens['porcentagem_comissao_ponto_coleta'] = (float) $porcentagens['porcentagem_comissao_ponto_coleta'];
        $porcentagens['porcentagem_comissao_ms'] = (float) $porcentagens['porcentagem_comissao_ms'];

        return $porcentagens;
    }

    public static function buscaDiasTransferenciaColaboradores(PDO $conexao): array
    {
        $query = "SELECT
                    configuracoes.dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE,
                    configuracoes.dias_pagamento_transferencia_fornecedor_EXCELENTE,
                    configuracoes.dias_pagamento_transferencia_fornecedor_REGULAR,
                    configuracoes.dias_pagamento_transferencia_fornecedor_RUIM,
                    configuracoes.dias_pagamento_transferencia_CLIENTE,
                    configuracoes.dias_pagamento_transferencia_ENTREGADOR,
                    configuracoes.dias_pagamento_transferencia_fornecedor_NOVATO
                FROM
                    configuracoes";

        $stmt = $conexao->prepare($query);
        $stmt->execute();

        $datas = $stmt->fetch(PDO::FETCH_ASSOC);

        return $datas;
    }
    public static function atualizarDiasTransferenciaColaboradores(PDO $conexao, array $diasPagamento): void
    {
        $query = "UPDATE configuracoes SET
                    configuracoes.dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE = :diasPagamentoMelhorFabricante,
                    configuracoes.dias_pagamento_transferencia_fornecedor_EXCELENTE = :diasPagamentoExcelente,
                    configuracoes.dias_pagamento_transferencia_fornecedor_REGULAR = :diasPagamentoRegular,
                    configuracoes.dias_pagamento_transferencia_fornecedor_RUIM = :diasPagamentoRuim,
                    configuracoes.dias_pagamento_transferencia_CLIENTE = :diasPagamentoCliente,
                    configuracoes.dias_pagamento_transferencia_ENTREGADOR = :diasPagamentoEntregador,
                    configuracoes.dias_pagamento_transferencia_fornecedor_NOVATO = :diasPagamentoNovato";

        $stmt = $conexao->prepare($query);
        $stmt->bindValue(
            ':diasPagamentoMelhorFabricante',
            $diasPagamento['dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE'],
            PDO::PARAM_INT
        );
        $stmt->bindValue(
            ':diasPagamentoExcelente',
            $diasPagamento['dias_pagamento_transferencia_fornecedor_EXCELENTE'],
            PDO::PARAM_INT
        );
        $stmt->bindValue(
            ':diasPagamentoRegular',
            $diasPagamento['dias_pagamento_transferencia_fornecedor_REGULAR'],
            PDO::PARAM_INT
        );
        $stmt->bindValue(
            ':diasPagamentoRuim',
            $diasPagamento['dias_pagamento_transferencia_fornecedor_RUIM'],
            PDO::PARAM_INT
        );
        $stmt->bindValue(
            ':diasPagamentoCliente',
            $diasPagamento['dias_pagamento_transferencia_CLIENTE'],
            PDO::PARAM_INT
        );
        $stmt->bindValue(
            ':diasPagamentoEntregador',
            $diasPagamento['dias_pagamento_transferencia_ENTREGADOR'],
            PDO::PARAM_INT
        );
        $stmt->bindValue(
            ':diasPagamentoNovato',
            $diasPagamento['dias_pagamento_transferencia_fornecedor_NOVATO'],
            PDO::PARAM_INT
        );
        $stmt->execute();

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Nenhum dia de pagamento foi atualizado');
        }
    }
    public static function buscaIdColaboradorTipoFreteTransportadoraMeuLook(): int
    {
        $idColaborador = DB::selectOneColumn(
            "SELECT configuracoes.id_colaborador_tipo_frete_transportadora_meulook
            FROM configuracoes
            LIMIT 1;"
        );

        return $idColaborador;
    }
    // public static function buscaAlertaChatAtendimento(\PDO $conexao): ?string
    // {
    //     $sql = $conexao->prepare(
    //        "SELECT configuracoes.alerta_chat_atendimento
    //         FROM configuracoes
    //         LIMIT 1"
    //     );
    //     $sql->execute();
    //     $alerta = $sql->fetchColumn();

    //     return $alerta;
    // }
    // public static function atualizaAlertaChatAtendimento(\PDO $conexao, string $alerta): void
    // {
    //     $sql = $conexao->prepare(
    //         "UPDATE configuracoes
    //         SET configuracoes.alerta_chat_atendimento = :alerta"
    //     );
    //     $sql->bindValue(":alerta", $alerta, PDO::PARAM_STR);
    //     $sql->execute();
    // }

    public static function buscaTaxaAdiantamento(PDO $conexao): string
    {
        $stmt = $conexao->prepare(
            "SELECT configuracoes.taxa_adiantamento
            FROM configuracoes"
        );
        $stmt->execute();
        $data = $stmt->fetchColumn();
        return $data;
    }
    public static function retornaQtdDiasAprovacaoAutomatica(): int
    {
        $sql = "SELECT configuracoes.qtd_dias_aprovacao_automatica
            FROM configuracoes";

        $qtdDiasAprovacaoAutomatica = DB::selectOneColumn($sql);

        return $qtdDiasAprovacaoAutomatica;
    }

    public static function buscaComissaoPontoColeta(): int
    {
        $comissao = DB::selectOneColumn(
            "SELECT
                configuracoes.porcentagem_comissao_ponto_coleta
            FROM configuracoes"
        );

        return $comissao;
    }

    public static function buscaPorcentagemComissoes(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT
                configuracoes.porcentagem_comissao_ms,
                configuracoes.porcentagem_comissao_ml,
                configuracoes.porcentagem_comissao_ponto_coleta
            FROM configuracoes"
        );
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public static function alteraPorcentagensComissoes(PDO $conexao, array $porcentagens): void
    {
        $sql = $conexao->prepare(
            "UPDATE configuracoes
            JOIN produtos
            SET
                configuracoes.porcentagem_comissao_ms = :comissao_ms,
               configuracoes.porcentagem_comissao_ml = :comissao_ml,
               configuracoes.porcentagem_comissao_ponto_coleta = :comissao_ponto_coleta,
                produtos.porcentagem_comissao_ms = :comissao_ms,
                produtos.porcentagem_comissao_ml = :comissao_ml,
                produtos.porcentagem_comissao_ponto_coleta = :comissao_ponto_coleta"
        );
        $sql->bindParam(':comissao_ml', $porcentagens['comissao_ml']);
        $sql->bindParam(':comissao_ms', $porcentagens['comissao_ms']);
        $sql->bindParam(':comissao_ponto_coleta', $porcentagens['comissao_ponto_coleta']);
        $sql->execute();

        if ($sql->rowCount() === 0) {
            throw new Exception('Não foi possível alterar as porcentagens de comissão');
        }
    }

    public static function buscaTravaJobAtualizarOpensearch(): void
    {
        DB::selectOne("SELECT GET_LOCK('JOB_ATUALIZAR_OPENSEARCH', 99999)");
    }

    public static function buscaValorMinimoEntrarFraude(PDO $conexao): float
    {
        $stmt = $conexao->prepare(
            "SELECT configuracoes.valor_minimo_fraude
            FROM configuracoes"
        );
        $stmt->execute();
        $valorMinimo = (float) $stmt->fetchColumn();

        return $valorMinimo;
    }

    public static function buscaPorcentagemAntecipacao(PDO $conexao): int
    {
        $sql = $conexao->prepare(
            "SELECT configuracoes.porcentagem_antecipacao
            FROM configuracoes"
        );
        $sql->execute();
        $porcentagem = $sql->fetchColumn();

        return $porcentagem;
    }

    public static function buscaConfiguracoesDeFrete(PDO $conexao): object
    {
        $sql = $conexao->prepare(
            "SELECT
                configuracoes.tamanho_raio_padrao_ponto_parado,
                configuracoes.percentual_para_cortar_pontos,
                configuracoes.minimo_entregas_para_cortar_pontos
            FROM configuracoes
            LIMIT 1"
        );
        $sql->execute();
        $configuracoes = $sql->fetch(PDO::FETCH_OBJ);
        return $configuracoes;
    }

    public static function alteraConfiguracoesDeFrete(
        PDO $conexao,
        float $percentualCortePontos,
        int $tamanhoRaioPadraoPontoParado,
        int $minimoEntregasParaCorte
    ): void {
        $sql = $conexao->prepare(
            "UPDATE configuracoes
            SET
                configuracoes.percentual_para_cortar_pontos = :percentualCortePontos,
                configuracoes.tamanho_raio_padrao_ponto_parado = :tamanhoRaioPadraoPontoParado,
                configuracoes.minimo_entregas_para_cortar_pontos = :minimoEntregasParaCorte"
        );
        $sql->bindParam(':percentualCortePontos', $percentualCortePontos, PDO::PARAM_STR);
        $sql->bindParam(':tamanhoRaioPadraoPontoParado', $tamanhoRaioPadraoPontoParado, PDO::PARAM_INT);
        $sql->bindParam(':minimoEntregasParaCorte', $minimoEntregasParaCorte, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível alterar as configurações de frete');
        }
    }

    public static function alteraPorcentagemAntecipacao(PDO $conexao, int $porcentagem): void
    {
        $sql = $conexao->prepare(
            "UPDATE configuracoes
            SET configuracoes.porcentagem_antecipacao = :porcentagem"
        );
        $sql->bindParam(':porcentagem', $porcentagem, PDO::PARAM_INT);
        $sql->execute();

        if ($sql->rowCount() !== 1) {
            throw new Exception('Não foi possível alterar a porcentagem de antecipação');
        }
    }

    public static function alteraValorMinimoPraEntrarNaFraude(PDO $conexao, float $valorMinimo): void
    {
        $stmt = $conexao->prepare(
            "UPDATE configuracoes
            SET configuracoes.valor_minimo_fraude = :valorMinimo"
        );
        $stmt->bindValue(':valorMinimo', $valorMinimo, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new Exception('Não foi possível alterar o valor mínimo para entrar na fraude');
        }
    }

    public static function buscaAuxiliaresTroca(string $origem): array
    {
        $qtdDiasDisponiveisTrocaNormal = 'configuracoes.qtd_dias_disponiveis_troca_normal';
        $qtdDiasDisponiveisTrocaDefeito = 'configuracoes.qtd_dias_disponiveis_troca_defeito';
        if ($origem === Origem::MS) {
            $qtdDiasDisponiveisTrocaNormal = 'configuracoes.qtd_dias_disponiveis_troca_normal_ms';
            $qtdDiasDisponiveisTrocaDefeito = 'configuracoes.qtd_dias_disponiveis_troca_defeito_ms';
        }

        $auxiliares = DB::selectOne(
            "SELECT
                $qtdDiasDisponiveisTrocaNormal AS `dias_normal`,
                $qtdDiasDisponiveisTrocaDefeito AS `dias_defeito`,
                configuracoes.qtd_dias_aprovacao_automatica AS `aprovacao_automatica`
            FROM configuracoes;"
        );
        if (empty($auxiliares)) {
            throw new RuntimeException('Não foi possível buscar os auxiliares de troca');
        }

        return $auxiliares;
    }

    public static function buscarOrdenamentosFiltroCatalogo(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT configuracoes.filtros_pesquisa_padrao,
                configuracoes.filtros_pesquisa_ordenados
            FROM configuracoes
            LIMIT 1"
        );
        $stmt->execute();
        $configuracoes = $stmt->fetch(PDO::FETCH_ASSOC);
        $configuracoes['filtros_pesquisa_padrao'] = json_decode($configuracoes['filtros_pesquisa_padrao'], true);
        $configuracoes['filtros_pesquisa_ordenados'] = json_decode($configuracoes['filtros_pesquisa_ordenados'], true);
        return $configuracoes;
    }

    public static function alterarOrdenamentoFiltroCatalogo(PDO $conexao, array $filtros): void
    {
        $stmt = $conexao->prepare(
            "UPDATE configuracoes
            SET configuracoes.filtros_pesquisa_ordenados = :filtros"
        );
        $stmt->bindValue(':filtros', json_encode($filtros));
        $stmt->execute();
    }

    public static function buscarTempoExpiracaoCacheFiltro(PDO $conexao): int
    {
        $stmt = $conexao->prepare(
            "SELECT configuracoes.minutos_expiracao_cache_filtros
            FROM configuracoes
            LIMIT 1"
        );
        $stmt->execute();
        $tempo = (int) $stmt->fetchColumn();
        return $tempo;
    }

    public static function produtosPromocoes(PDO $conexao): array
    {
        $stmt = $conexao->prepare(
            "SELECT configuracoes.produtos_promocoes
            FROM configuracoes
            LIMIT 1"
        );
        $stmt->execute();
        $produtos = $stmt->fetchColumn();
        $produtos = json_decode($produtos, true);
        return $produtos;
    }

    public static function buscarTaxaProdutoErrado(): float
    {
        Event::listenOnce(function (StatementPrepared $event) {
            $event->statement->setFetchMode(PDO::FETCH_COLUMN, 0);
        });

        $resultado = DB::selectOne("SELECT
            JSON_VALUE(configuracoes.logistica_reversa, '$.devolucao.taxa_produto_errado')
            FROM configuracoes");

        return $resultado;
    }

    public static function alterarTaxaProdutoErrado(float $taxa): void
    {
        $rowCount = DB::update(
            "UPDATE configuracoes
            SET configuracoes.logistica_reversa = JSON_SET(
                configuracoes.logistica_reversa, '$.devolucao.taxa_produto_errado', :taxa
            )",
            ['taxa' => $taxa]
        );

        if ($rowCount !== 1) {
            throw new Exception('Não foi possível alterar a taxa de devolução produto errado.');
        }
    }
    public static function buscaTaxaBloqueioFornecedor(PDO $conexao): int
    {
        $stmt = $conexao->prepare(
            "SELECT JSON_VALUE(configuracoes.logistica_reversa, '$.cancelamento.taxa_minima_bloqueio_fornecedor')
            FROM configuracoes"
        );
        $stmt->execute();
        $retorno = $stmt->fetchColumn();

        return $retorno;
    }

    public static function alteraTaxaBloqueioFornecedor(PDO $conexao, int $taxaBloqueioFornecedor): void
    {
        $stmt = $conexao->prepare(
            "UPDATE configuracoes
            SET configuracoes.logistica_reversa = JSON_SET(
                configuracoes.logistica_reversa, '$.cancelamento.taxa_minima_bloqueio_fornecedor', :taxa_cancelamento
            );"
        );
        $stmt->bindValue(':taxa_cancelamento', $taxaBloqueioFornecedor, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() !== 1) {
            throw new Exception('Não foi possível fazer a alteração');
        }
    }
    public static function calculaDiasTrocaPorDataEntrega(string $dataEntrega): array
    {
        $stmt = "SELECT
            :dataEntrega + INTERVAL configuracoes.qtd_dias_disponiveis_troca_normal DAY AS `data_normal`,
            NOW() + INTERVAL configuracoes.qtd_dias_disponiveis_troca_defeito DAY AS `data_defeito`
        FROM configuracoes
        LIMIT 1;";

        $diasTroca = DB::selectOne($stmt, ['dataEntrega' => $dataEntrega]);

        return $diasTroca;
    }
}

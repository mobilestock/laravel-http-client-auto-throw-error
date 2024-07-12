<?php

namespace MobileStock\service;

use DateInterval;
use DateTime;
use DomainException;
use Illuminate\Log\Logger;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\TipoFrete;
use PDO;

class PrevisaoService
{
    public Carbon $data;
    private PDO $conexao;
    private array $diasSemana;
    protected DiaUtilService $diaUtilService;

    public function __construct(PDO $conexao, DiaUtilService $diaUtilService)
    {
        // https://github.com/mobilestock/backend/issues/153
        date_default_timezone_set('America/Sao_Paulo');
        $this->conexao = $conexao;
        $this->diaUtilService = $diaUtilService;
        $this->data = new Carbon('NOW');
        $this->diasSemana = Globals::DIAS_SEMANA;
    }

    public function calculoDiasSeparacaoProduto(
        int $idProduto,
        ?string $nomeTamanho = null,
        ?int $idResponsavelEstoque = null
    ): array {
        $retorno = [
            'FULFILLMENT' => null,
            'EXTERNO' => null,
        ];
        if (empty($idResponsavelEstoque)) {
            $where = '';
            if (!empty($nomeTamanho)) {
                $where .= ' AND estoque_grade.nome_tamanho = :nome_tamanho ';
            }

            $sql = $this->conexao->prepare(
                "SELECT estoque_grade.id_responsavel
                FROM estoque_grade
                WHERE estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = :id_produto
                    $where
                GROUP BY estoque_grade.id_responsavel
                ORDER BY estoque_grade.id_responsavel ASC;"
            );
            $sql->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
            if (!empty($nomeTamanho)) {
                $sql->bindValue(':nome_tamanho', $nomeTamanho, PDO::PARAM_STR);
            }
            $sql->execute();
            $responsaveis = $sql->fetchAll(PDO::FETCH_COLUMN);
            if (empty($responsaveis)) {
                return [];
            }
        } else {
            $responsaveis = [$idResponsavelEstoque];
        }

        if (in_array(1, $responsaveis)) {
            $retorno['FULFILLMENT'] = 0;
        }

        $responsaveis = array_filter($responsaveis, fn(int $idResponsavel): bool => $idResponsavel > 1);
        if (!empty($responsaveis)) {
            $idResponsavelEstoque = (int) reset($responsaveis);
            $sql = $this->conexao->prepare(
                "SELECT reputacao_fornecedores.media_envio
                FROM reputacao_fornecedores
                WHERE reputacao_fornecedores.id_colaborador = :id_responsavel_estoque;"
            );
            $sql->bindValue(':id_responsavel_estoque', $idResponsavelEstoque, PDO::PARAM_INT);
            $sql->execute();
            $diasParaSeparar = $sql->fetchColumn();
            if ($diasParaSeparar !== false) {
                $retorno['EXTERNO'] = (int) $diasParaSeparar;
            }
        }

        return $retorno;
    }

    public function buscaTransportadorPadrao(): array
    {
        $idTipoFreteEntregaCliente = TipoFrete::ID_TIPO_FRETE_ENTREGA_CLIENTE;
        $transportador = DB::selectOne(
            "SELECT
                municipios.uf,
                municipios.nome,
                transportadores_raios.id_cidade,
                tipo_frete.id_colaborador,
                tipo_frete.tipo_ponto,
                tipo_frete.id_colaborador_ponto_coleta,
                transportadores_raios.dias_entregar_cliente,
                transportadores_raios.dias_margem_erro
            FROM tipo_frete
            INNER JOIN colaboradores ON colaboradores.id_tipo_entrega_padrao = tipo_frete.id
            INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id
                AND colaboradores_enderecos.eh_endereco_padrao = 1
            INNER JOIN transportadores_raios ON transportadores_raios.esta_ativo
                AND transportadores_raios.id_colaborador = tipo_frete.id_colaborador
                AND IF (
                    tipo_frete.tipo_ponto = 'PM',
                    (
                        distancia_geolocalizacao(
                            colaboradores_enderecos.latitude,
                            colaboradores_enderecos.longitude,
                            transportadores_raios.latitude,
                            transportadores_raios.longitude
                        ) * 1000
                    ) <= transportadores_raios.raio
                    AND transportadores_raios.id_cidade = colaboradores_enderecos.id_cidade,
                    TRUE
                )
            INNER JOIN municipios ON municipios.id = transportadores_raios.id_cidade
            WHERE tipo_frete.id NOT IN ($idTipoFreteEntregaCliente)
                AND tipo_frete.categoria = 'ML'
                AND colaboradores.id = :id_cliente;",
            ['id_cliente' => Auth::user()->id_colaborador]
        );
        if (empty($transportador)) {
            return [];
        }

        $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
        $agenda->id_colaborador = $transportador['id_colaborador_ponto_coleta'];
        $pontoColeta = $agenda->buscaPrazosPorPontoColeta();
        $transportador['horarios'] = $pontoColeta['agenda'];
        $transportador['dias_pedido_chegar'] = $pontoColeta['dias_pedido_chegar'];

        return $transportador;
    }

    public function calculaProximaData(array $agenda): array
    {
        $IDXSemana = ((int) $this->data->format('N')) % 7;
        $totalDiasPassou = 0;
        $qtdDiasEnviar = 0;
        $proLog = null;
        $diasUteis = $this->diaUtilService->buscaCacheProximosDiasUteis();
        $primeiroDiaFoiUtil = false;
        while (true) {
            if ($totalDiasPassou > DiaUtilService::LIMITE_DIAS_CALCULOS) {
                app(Logger::class)->withContext([
                    'data' => $this->data->format('d/m/Y'),
                    'dataCalculo' => ($dataCalculo ?? new DateTime('NOW'))->format('d/m/Y'),
                    'agenda' => $agenda,
                    'IDXSemana' => $IDXSemana,
                    'qtdDiasEnviar' => $qtdDiasEnviar,
                    'diasUteis' => $diasUteis,
                    'informacoesHorariosDisponiveis' => $proLog,
                    'ehDiaUtil' => $ehDiaUtil ?? null,
                    'horariosDisponiveis' => $horariosDisponiveis ?? null,
                ]);
                throw new DomainException('Não foi possível calcular a previsão para o próximo dia de envios.');
            }

            $diaAtual = $this->diasSemana[$IDXSemana];
            $horariosDisponiveis = array_filter($agenda, function (array $item) use (
                $totalDiasPassou,
                $diaAtual,
                &$proLog
            ): bool {
                $hojeTem = $item['dia'] === $diaAtual;
                $proLog['hoje_tem'] = $hojeTem;
                if ($totalDiasPassou > 0) {
                    return $hojeTem;
                }

                $UNIXHorariaAgendado = DateTime::createFromFormat('H:i', $item['horario']);
                $UNIXHorarioAtual = DateTime::createFromFormat('H:i', $this->data->format('H:i'));
                $proLog['horario_agendado'] = $UNIXHorariaAgendado->format('d/m/Y H:i');
                $proLog['horario_atual'] = $UNIXHorarioAtual->format('d/m/Y H:i');

                return $hojeTem && $UNIXHorariaAgendado > $UNIXHorarioAtual;
            });

            $dataCalculo = clone $this->data;
            $dataCalculo->add(new DateInterval('P' . $totalDiasPassou . 'D'));
            $ehDiaUtil = in_array($dataCalculo->format('Y-m-d'), $diasUteis);
            if ($totalDiasPassou === 0) {
                $primeiroDiaFoiUtil = $ehDiaUtil;
            }
            if (!empty($horariosDisponiveis) && $ehDiaUtil) {
                if (!$primeiroDiaFoiUtil) {
                    $qtdDiasEnviar++;
                }
                break;
            }

            if ($ehDiaUtil) {
                $qtdDiasEnviar++;
            }
            $totalDiasPassou++;
            $IDXSemana = ($IDXSemana + 1) % 7;
        }

        return [
            'dias_enviar_ponto_coleta' => $qtdDiasEnviar,
            'data_envio' => $dataCalculo->format('d/m/Y'),
            'horarios_disponiveis' => $horariosDisponiveis,
        ];
    }

    private function calculaPrevisao(int $diasParaSeparar, array $diasProcessoEntrega): array
    {
        if (empty($diasProcessoEntrega)) {
            return [];
        }

        $dataCalculo = clone $this->data;
        $dadosMinimo = Arr::except($diasProcessoEntrega, 'dias_margem_erro');
        $qtdMinimaDias = array_sum([$diasParaSeparar, ...array_values($dadosMinimo)]);
        $dataMinimo = $dataCalculo->acrescentaDiasUteis($qtdMinimaDias)->format('d/m/Y');
        $dataMaximo = $dataCalculo->acrescentaDiasUteis($diasProcessoEntrega['dias_margem_erro'])->format('d/m/Y');

        return [
            'dias_minimo' => $qtdMinimaDias,
            'dias_maximo' => $qtdMinimaDias + $diasProcessoEntrega['dias_margem_erro'],
            'media_previsao_inicial' => $dataMinimo,
            'media_previsao_final' => $dataMaximo,
        ];
    }

    /**
     * @param array $mediasenvio
     *  [
     *      'FULFILLMENT' => int | null,
     *      'EXTERNO' => int | null
     *  ]
     * @param array $diasProcessoEntrega
     *  [
     *      'dias_entregar_cliente' => int,
     *      'dias_coletar_produto' => int,
     *      'dias_margem_erro' => int,
     *      'dias_pedido_chegar' => int
     *  ]
     * @param array $agenda
     * [
     *     * => [
     *          'id' => int,
     *          'dia' => string,
     *          'horario' => string,
     *          'frequencia' => string
     *     ]
     * ]
     */
    public function calculaPorMediasEDias(array $mediasEnvio, array $diasProcessoEntrega, array $agenda): array
    {
        if (empty($mediasEnvio) || empty($diasProcessoEntrega) || empty($agenda)) {
            return [];
        }

        $previsoes = [];
        $proximoEnvio = $this->calculaProximaData($agenda);

        $dataEnvio = $proximoEnvio['data_envio'];
        $horarioEnvio = current($proximoEnvio['horarios_disponiveis'])['horario'];
        $dataLimite = "$dataEnvio às $horarioEnvio";
        $diasProcessoEntrega['dias_enviar_ponto_coleta'] = $proximoEnvio['dias_enviar_ponto_coleta'];

        foreach ($mediasEnvio as $key => $valor) {
            if ($valor === null) {
                continue;
            }

            $datas = $this->calculaPrevisao($valor, $diasProcessoEntrega);
            $previsoes[] = array_merge($datas, [
                'responsavel' => $key,
                'data_limite' => $dataLimite,
            ]);
        }

        return $previsoes;
    }

    public function buscaHorarioSeparando(): string
    {
        $horariosFulfillment = ConfiguracaoService::horariosSeparacaoFulfillment();
        $menorDiferenca = PHP_INT_MAX;
        $horarioMaisProximo = null;

        foreach ($horariosFulfillment as $horario) {
            $tempo = DateTime::createFromFormat('H:i', $horario);
            $diferenca = abs($this->data->getTimestamp() - $tempo->getTimestamp());

            if ($diferenca < $menorDiferenca) {
                $menorDiferenca = $diferenca;
                $horarioMaisProximo = $horario;
            }
        }

        return $horarioMaisProximo;
    }

    /**
     * @param int $idColaboradorPontoColeta
     * @param array $diasProcessoEntrega
     *  [
     *      'dias_entregar_cliente' => int,
     *      'dias_coletar_produto' => int,
     *      'dias_margem_erro' => int
     *  ]
     * @param array $produtos
     *  [
     *      [
     *          'id' => int,
     *          'nome_tamanho' => string,
     *          'id_responsavel_estoque' => int
     *      ]
     *  ]
     */
    public function processoCalcularPrevisoes(
        int $idColaboradorPontoColeta,
        array $diasProcessoEntrega,
        array $produtos,
        ?array $validador = null
    ): array {
        $validador ??= [Validador::SE(Validador::OBRIGATORIO, [Validador::NUMERO])];

        Validador::validar($diasProcessoEntrega, [
            'dias_margem_erro' => [Validador::NAO_NULO, Validador::NUMERO],
        ]);

        $agenda = app(PontosColetaAgendaAcompanhamentoService::class);
        $agenda->id_colaborador = $idColaboradorPontoColeta;
        $pontoColeta = $agenda->buscaPrazosPorPontoColeta();
        if (empty($pontoColeta['agenda'])) {
            return $produtos;
        }

        $diasProcessoEntrega['dias_pedido_chegar'] = $pontoColeta['dias_pedido_chegar'];
        $produtos = array_map(function (array $produto) use ($diasProcessoEntrega, $pontoColeta, $validador): array {
            Validador::validar($produto, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_tamanho' => [],
                'id_responsavel_estoque' => $validador,
            ]);

            $mediasEnvio = $this->calculoDiasSeparacaoProduto(
                $produto['id'],
                $produto['nome_tamanho'] ?: null,
                $produto['id_responsavel_estoque'] ?: null
            );
            $previsoes = $this->calculaPorMediasEDias($mediasEnvio, $diasProcessoEntrega, $pontoColeta['agenda']);

            $produto['previsoes'] = null;
            if (!empty($previsoes)) {
                $produto['previsoes'] = $previsoes;
            }

            return $produto;
        }, $produtos);

        return $produtos;
    }

    /**
     * @param int $idColaboradorPontoColeta
     * @param array $diasProcessoEntrega
     *  [
     *      'dias_entregar_cliente' => int,
     *      'dias_coletar_produto' => int,
     *      'dias_margem_erro' => int
     *  ]
     * @param array $produtos
     *  [
     *      [
     *          'id' => int,
     *          'nome_tamanho' => string|null,
     *          'id_responsavel_estoque' => int|null
     *      ]
     *  ]
     */
    public function processoCalcularPrevisaoResponsavelFiltrado(
        int $idColaboradorPontoColeta,
        array $diasProcessoEntrega,
        array $produtos
    ): array {
        $produtos = $this->processoCalcularPrevisoes($idColaboradorPontoColeta, $diasProcessoEntrega, $produtos, [
            Validador::OBRIGATORIO,
            Validador::NUMERO,
        ]);

        $produtos = array_map(function ($produto) {
            $produto['previsao'] = $produto['previsoes'][0] ?? null;
            unset($produto['previsoes']);

            return $produto;
        }, $produtos);

        return $produtos;
    }
}

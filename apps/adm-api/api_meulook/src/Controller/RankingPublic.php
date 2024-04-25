<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use DateTime;
use DateTimeZone;
use Exception;
use MobileStock\helper\Validador;
use MobileStock\model\LancamentoPendente;
use MobileStock\repository\NotificacaoRepository;
use MobileStock\service\Pagamento\LancamentoPendenteService;
use MobileStock\service\Ranking\RankingService;
use MobileStock\service\Ranking\RankingVencedoresItensService;

class RankingPublic extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }

    // public function buscaTopInfluencersOficiais()
    // {
    //     try {
    //         $periodo = $this->request->get('periodo') ?? 'mes-atual';
    //         $quantidade = intval($this->request->get('quantidade') ?? '-1');
    //         $retornarItens = $this->request->get('retornar-itens') !== null;

    //         $influencers = RankingService::rankingInfluencersOficiais(
    //             $this->conexao,
    //             $periodo,
    //             $this->idCliente ? -1 : $quantidade,
    //             $retornarItens
    //         );

    //         $influencerLogado = null;

    //         $premios = RankingService::buscaRankingPremiosPorChave($this->conexao, 'top-influencers-oficiais', sizeof($influencers));
    //         foreach ($influencers as $influencerIndex => $influencer) {
    //             $influencer['posicao'] = $influencerIndex + 1;

    //             if ($influencerIndex < sizeof($premios)) {
    //                 $porcentagem = (float) $premios[$influencerIndex]['porcentagem'] ?? 0;
    //                 $influencer['porcentagem'] = $porcentagem;
    //                 $influencer['premio'] = round($influencer['valor_total'] * ($porcentagem / 100), 2);
    //             } else $influencer['premio'] = 0;

    //             if ($influencerIndex >= $quantidade && $influencer['id'] == $this->idCliente) $influencerLogado = $influencer;

    //             $influencers[$influencerIndex] = $influencer;
    //         }

    //         if ($this->idCliente) {
    //             $influencers = array_splice($influencers, 0, $quantidade);
    //             if ($influencerLogado) array_push($influencers, $influencerLogado);
    //         }

    //         $this->retorno['data'] = $influencers;
    //         $this->retorno['message'] = 'Influencers oficiais buscados com sucesso';
    //         $this->status = 200;

    //     } catch (\PDOException $pdoException) {
    //         $this->retorno['message'] = $pdoException->getMessage();
    //         $this->status = 500;

    //     } catch (\Throwable $ex) {
    //         $this->retorno['message'] = $ex->getMessage();
    //         $this->status = 400;

    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function buscaRankingsApuracao(array $dados)
    {
        try {
            $chaveRanking = $dados['ranking'];
            $rankingsApuracao = RankingService::buscaRankingEmApuracao($this->conexao, $chaveRanking);
            foreach ($rankingsApuracao as $rankingIndex => $ranking) {
                $participantes = $ranking['participantes'];
                $premios = [];
                foreach ($participantes as $index => $participante) {
                    $participantes[$index]['posicao'] = $index + 1;
                    array_push(
                        $premios,
                        $ranking['origem'] === 'MR' ? $participante['premio'] : $participante['porcentagem']
                    );
                }
                arsort($premios);
                $premios = array_values($premios);
                foreach ($participantes as $index => $participante) {
                    $participantes[$index]['premio'] = $ranking['origem'] === 'MR'
                        ? $premios[$index]
                        : round($participante['valor_total'] * ($premios[$index] / 100), 2);
                }
                unset($rankingsApuracao[$rankingIndex]['origem']);
                $rankingsApuracao[$rankingIndex]['participantes'] = $participantes;
            }

            $this->retorno['data'] = $rankingsApuracao;
            $this->retorno['message'] = 'Rankings em apuração buscados com sucesso';
            $this->status = 200;

        } catch (\PDOException $pdoException) {
            $this->retorno['message'] = $pdoException->getMessage();
            $this->status = 500;

        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;

        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
            exit;
        }
    }

    public function buscaQuantidadesApuracao(array $dados)
    {
        try {
            Validador::validar($dados, [
                'ranking' => [Validador::OBRIGATORIO, Validador::STRING],
                'mes' => [Validador::OBRIGATORIO, Validador::NUMERO]
            ]);
            $this->retorno['data'] = RankingService::buscaQuantidadesApuracao($this->conexao, $dados['ranking'], $dados['mes']);
            $this->retorno['message'] = 'Quantidades em apuração buscadas com sucesso';
            $this->status = 200;

        } catch (\PDOException $pdoException) {
            $this->retorno['message'] = $pdoException->getMessage();
            $this->status = 500;

        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;

        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
            exit;
        }
    }

    // public function buscaUltimoRankingConcluido(array $dados)
    // {
    //     try {
    //         $chaveRanking = $dados['ranking'];
    //         $this->retorno['data'] = RankingService::buscaUltimoRankingConcluido($this->conexao, $chaveRanking);
    //         $this->retorno['message'] = 'Ranking concluido buscado com sucesso';
    //         $this->status = 200;

    //     } catch (\PDOException $pdoException){
    //         $this->retorno['message'] = $pdoException->getMessage();
    //         $this->status = 500;

    //     } catch (\Throwable $ex) {
    //         $this->retorno['message'] = $ex->getMessage();
    //         $this->status = 400;

    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function fechamentoRanking()
    {
        $notificacoesErros = '';
        try {
            // Valida header manual
            $headerManual = $this->request->headers->get('header-manual');
            if (!$headerManual) throw new Exception('Não foi possível autenticar!');
            if ($headerManual != '}((C]^xW%"V+FW~,L62&~u:BZ$3}p\k[') throw new Exception('Autenticação inválida!');

            // Verifica se a premiação (contagem) já ocorreu
            if (RankingService::ocorreuPremiacaoRecente($this->conexao, true)) throw new Exception('Tentativa de premiação duplicada (Contagem)');

            // Carrega rankings ativos (Tabela rankings)
            $rankings = RankingService::buscaRankingsAtivos($this->conexao);
            if (!$rankings) throw new Exception('Não foi possível carregar os rankings');
            if (sizeof($rankings) === 0) throw new Exception('Não há rankings ativos a serem premiados');

            $dataAtual = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));

            // Faz um looping em cada ranking
            foreach($rankings as $ranking) {
                // Busca premiações desse ranking
                $premios = RankingService::buscaPremiacoesRanking($this->conexao, $ranking['id']);
                if (sizeof($premios) === 0) {
                    $notificacoesErros .= "[ AVISO: {$ranking['chave']} não possui colocações ativas ]";
                    continue;
                }

                // Faz requisição pelos influencers vencedores desse ranking
                $curl = curl_init(
                    $_ENV['URL_MOBILE'] .
                    $ranking['url_endpoint'] .
                    '?quantidade=' . strval(sizeof($premios)) .
                    '&periodo=mes-passado' .
                    '&retornar-itens=true'
                );
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $influencers = json_decode(curl_exec($curl), true)['data'];

                if (!isset($influencers) || sizeof($influencers) == 0) {
                    $notificacoesErros .= "[ AVISO: Não há participantes no ranking {$ranking['chave']} ]";
                    continue;
                }

                // Looping pelos vencedores
                foreach($influencers as $influencerIndex => $influencer) {
                    // Obtém o prêmio do influencer baseado no index
                    $premio = $premios[$influencerIndex];
                    $porcentagem = $influencer['porcentagem'];

                    $idInfluencer = $influencer['id_colaborador'] ?? $influencer['id'];
                    $posicao = $premio['posicao'];
                    $observacao = "{$posicao}° colocação ranking: {$ranking['nome']}";

                    if (sizeof($influencer['lista_itens']) == 0) {
                        $notificacoesErros .= "[ ERRO: Os itens que favoreceram o ranking {$ranking['chave']} na posição {$posicao} do colaborador {$idInfluencer} não foram carregados, logo a colocação não será premiada ]";
                        continue;
                    }

                    // Faz o lançamento do prêmio para o influencer
                    $lancamento = new LancamentoPendente(
                        'P', // O mobile paga o influencer
                        1, // Sempre 1
                        'RK', // Origem "Ranking"
                        $idInfluencer,
                        $dataAtual->format('Y-m-d H:i:s'),
                        floatVal($porcentagem),
                        $influencer['id_usuario'],
                        '7' // Campo antigo
                    );
                    $lancamento->observacao = $observacao; // Adição de dado
                    $lancamento->numero_movimento = $posicao; // Armazena posição no ranking para consultas
                    $lancamento->numero_documento = $ranking['chave']; // Armazena chave do ranking para consultas
                    $novoLancamentoPendente = LancamentoPendenteService::criar($this->conexao, $lancamento);

                    // Looping pelos itens
                    foreach($influencer['lista_itens'] as $itemLista) {
                        // Salva os itens que fizeram o colaborador vencer
                        $rankingVencedorItem = new RankingVencedoresItensService();
                        $rankingVencedorItem->uuid_produto = $itemLista;
                        $rankingVencedorItem->id_lancamento_pendente = $novoLancamentoPendente->id;
                        $rankingVencedorItem->adiciona($this->conexao);
                    }
                }
            }

            if ($notificacoesErros != "") {
                $mensagem = "Premiação finalizada com algum(ns) erros : {$notificacoesErros}";
                $this->retorno['message'] = $mensagem;

                // Lança notificação de alguns erros
                NotificacaoRepository::enviar([
                    'colaboradores' => [ 1 ],
                    'mensagem' => $mensagem,
                    'tipoMensagem' => 'Z',
                    'titulo' => 'Premiação Meulook',
                    'destino' => 'ML',
                    'imagem' => $_ENV['URL_MOBILE'] . "/images/trofeus/erro.png"
                ], '');

            } else {
                $this->retorno['message'] = 'Premiação finalizada com sucesso!';
            }

            $this->status = 200;

        } catch (\Throwable $ex) {
            $erros = "[ {$ex->getMessage()} ][ $notificacoesErros ]";

            $this->retorno['message'] = "Catch + Erros: $erros";
            $this->retorno['status'] = false;
            $this->status = 400;

            // Lança notificação de erro
            NotificacaoRepository::enviarSemValidacaoDeErro([
                'colaboradores' => [ 1 ],
                'mensagem' => "Erro na premiação do ranking meulook: $erros",
                'tipoMensagem' => 'Z',
                'titulo' => 'Premiação Meulook (Contagem de Itens)',
                'imagem' => $_ENV['URL_MOBILE'] . "/images/trofeus/erro.png"
            ], $this->conexao);

        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
            exit;
        }
    }

    // public function pagamentoRanking()
    // {
    //     $notificacoesErros = '';
    //     $premiacoesValidadas = 0;
    //     try {
    //         // Valida header manual
    //         $headerManual = $this->request->headers->get('header-manual');
    //         if (!$headerManual) throw new Exception('Não foi possível autenticar!');
    //         if ($headerManual != 'NAO_PREMIAR') throw new Exception('Autenticação inválida!');

    //         // Verifica se a premiação (pagamento) já ocorreu
    //         if (RankingService::ocorreuPremiacaoRecente($this->conexao)) throw new Exception('Tentativa de premiação duplicada (Pagamento)');

    //         // Busca as premiações pendentes para verificação
    //         $premiacoesPendentes = RankingVencedoresItensService::buscaLancamentosPendentes($this->conexao);

    //         // Looping pela premiações pendentes
    //         foreach($premiacoesPendentes as $premiacao) {
    //             // Se não estiver concluído, vai para a próxima iteração
    //             if (!$premiacao['pagar']) continue;

    //             $lancamentos = $premiacao['lancamentos'];

    //             $premios = RankingService::buscaRankingPremiosPorChave(
    //                 $this->conexao,
    //                 $premiacao['chave_ranking'],
    //                 sizeof($lancamentos)
    //             );

    //             // Looping pelos prêmios
    //             foreach($premios as $indexLancamento => $premio) {
    //                 $lancamento = $lancamentos[$indexLancamento];
    //                 $idLancamentoPendente = $lancamento['id_lancamento_pendente'];

    //                 // Se já premiou essa pessoa, vai para a próxima iteração
    //                 if (LancamentoPendenteService::existeSequenciaLancamentoReal($this->conexao, $idLancamentoPendente)) {
    //                     $notificacoesErros .= "[ AVISO: Lançamento pendente duplicado: $idLancamentoPendente ]";
    //                     continue;
    //                 }

    //                 $idColaborador = $lancamento['id_colaborador'];
    //                 $idUsuario = $lancamento['id_usuario'];
    //                 $valor = $lancamento['premio'];
    //                 $posicao = $lancamento['posicao'];

    //                 if ($premio['recontar_premios']) {
    //                     $valor = $premio['valor'];
    //                     $posicao = $indexLancamento + 1;
    //                 }

    //                 $observacao = $posicao . "° lugar do ranking '" . $premio['nome'] . "'";

    //                 // Faz o lançamento REAL
    //                 $novoLancamento = new Lancamento(
    //                     'P', // O mobile paga o influencer
    //                     1, // Sempre 1
    //                     'MR', // Origem "Meulook ranking"
    //                     $idColaborador,
    //                     $premiacao['data'],
    //                     $valor,
    //                     $idUsuario,
    //                     '7' // Campo antigo
    //                 );

    //                 if ($valor == 0) {
    //                     // Se tudo foi trocado ou corrigido, define esses parâmetros
    //                     // para não atrapalhar outros processos.
    //                     $novoLancamento->faturamento_criado_pago = 'T';
    //                     $novoLancamento->valor_pago = $valor;
    //                 }

    //                 $novoLancamento->observacao = $observacao; // Adição de dado
    //                 $novoLancamento->sequencia = $idLancamentoPendente; // Id do lançamento pendente
    //                 $novoLancamento->numero_movimento = $posicao; // Armazena posição no ranking para consultas
    //                 $novoLancamento->numero_documento = $premiacao['chave_ranking']; // Armazena chave do ranking para consultas
    //                 $lancamentoCriado = LancamentoCrud::salva($this->conexao, $novoLancamento);

    //                 // Atualiza os produtos com o lançamento REAL
    //                 RankingVencedoresItensService::atualizaItensLancamentoPendente(
    //                     $this->conexao,
    //                     $idLancamentoPendente,
    //                     $lancamentoCriado->id
    //                 );

    //                 // Remove o lançamento pendente
    //                 LancamentoPendenteService::deletarLancamentoPendente($this->conexao, $idLancamentoPendente);

    //                 $trofeu = 'chumbo';
    //                 if ($posicao == 1) $trofeu = 'ouro';
    //                 else if ($posicao == 2) $trofeu = 'prata';
    //                 else if ($posicao == 3) $trofeu = 'bronze';

    //                 // Lança notificação pro influencer
    //                 NotificacaoRepository::enviar([
    //                     'colaboradores' => [ $idColaborador ],
    //                     'mensagem' => "Seu prêmio já está disponível na sua conta Mobile Pay",
    //                     'tipoMensagem' => 'C',
    //                     'titulo' => 'Prêmio Ranking Meulook',
    //                     'destino' => 'ML',
    //                     'imagem' => $_ENV['URL_MOBILE'] . "/images/trofeus/" . $trofeu . ".png"
    //                 ], '');
    //             }
    //             $premiacoesValidadas += 1;
    //         }

    //         if ($notificacoesErros != "") {
    //             $mensagem = "Premiação (Pagamento) finalizada com algum(ns) erros : {$notificacoesErros}";
    //             $this->retorno['message'] = $mensagem;

    //             // Lança notificação de alguns erros
    //             NotificacaoRepository::enviar([
    //                 'colaboradores' => [ 1 ],
    //                 'mensagem' => $mensagem,
    //                 'tipoMensagem' => 'Z',
    //                 'titulo' => 'Premiação Meulook (Pagamento)',
    //                 'destino' => 'ML',
    //                 'imagem' => $_ENV['URL_MOBILE'] . "/images/trofeus/erro.png"
    //             ], '');

    //         } else {
    //             $this->retorno['message'] = "Premiação finalizada com sucesso! {$premiacoesValidadas} premiação(s) validada(s)";
    //         }

    //         $this->status = 200;

    //     } catch (\Throwable $ex) {
    //         $erros = "[ {$ex->getMessage()} ][ $notificacoesErros ]";

    //         $this->retorno['message'] = "Catch + Erros: $erros";
    //         $this->retorno['status'] = false;
    //         $this->status = 400;

    //         // Lança notificação de erro
    //         NotificacaoRepository::enviarSemValidacaoDeErro([
    //             'colaboradores' => [ 1 ],
    //             'mensagem' => "Erro ao liberar dinheiro do ranking: " . $erros,
    //             'tipoMensagem' => 'Z',
    //             'titulo' => 'Premiação Meulook (Liberar prêmio)',
    //             'imagem' => $_ENV['URL_MOBILE'] . "/images/trofeus/erro.png"
    //         ], $this->conexao);

    //         // Lança notificação no telegram
    //         $msgService = new MessageService();
    //         $msgService->sendBroadcastTelegram('Ranking (Etapa 2): ' . $ex->getMessage());

    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

}
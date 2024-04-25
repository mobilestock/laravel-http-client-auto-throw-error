<?php

namespace MobileStock\service\Pagamento;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\database\exceptions\PDOExceptionDeadlock;
use MobileStock\helper\ClienteException;
use MobileStock\helper\IuguEstaIndisponivel;
use MobileStock\helper\Pagamento\PagamentoAntiFraudeException;
use MobileStock\helper\Pagamento\PagamentoTransacaoNaoExisteException;
use MobileStock\model\LancamentoPendente;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraTentativaPagamentoService;
use PDO;
use Psr\Log\LogLevel;
use Throwable;

class ProcessadorPagamentos
{
    private PDO $conexao;
    private TransacaoFinanceiraService $transacao;
    private array $interfacesPagamento;
    /**
     * @var TransacaoFinanceiraTentativaPagamentoService[]
     */
    private array $tentativasPagamento;

    private LogManager $logger;

    /**
     * issue migrar para o model TransacaoFinanceiraModel
     * https://github.com/mobilestock/web/issues/3067
     */
    public function __construct(PDO $conexao, TransacaoFinanceiraService $transacao, array $interfacesPagamento)
    {
        if (!App::runningUnitTests() && app('log_level') !== LogLevel::EMERGENCY) {
            throw new InvalidArgumentException(
                'Para utilizar essa classe é necessário gerar logs de emergência em caso de erro.'
            );
        }
        $this->conexao = $conexao;
        $this->transacao = $transacao;
        $this->interfacesPagamento = $interfacesPagamento;
        $this->tentativasPagamento = [];
        $this->logger = app(LogManager::class);
    }

    /**
     * Essa função precisa de um try catch pois precisa que toda vez que ocorre um erro no pagamento tem que verificar se na api está pago e gerar notificação
     * Quando der um erro de antifraude pagamento será tentado na próxima interface de pagamento
     *
     * Quando pagamento utilizar alguma API (Iugu, Cielo, Zoop etc...) transação será commitada no meio e depois re-commitada quando processo tiver 100% concluido.
     * @throws PDOExceptionDeadlock
     * @throws PagamentoTransacaoNaoExisteException
     * @throws Throwable
     */
    public function executa(): void
    {
        try {
            $this->transacao->BloqueiaLinhaTransacao($this->conexao);

            $this->transacao->status = 'PE';

            if ($this->transacao->atualizaTransacao($this->conexao) !== 1) {
                throw new ClienteException('Tentativa de pagamento duplicado.');
            }

            $this->transacao->abateCreditoCliente($this->conexao);

            $this->conexao->exec('SAVEPOINT LANCAMENTOS_GERADOS;');

            [$interfacePagamento, $tipoPagamento, $errosPagamento] = $this->comunicaApis();

            $this->conexao->exec('RELEASE SAVEPOINT LANCAMENTOS_GERADOS;');

            if ($tipoPagamento::SUPORTA_LOCAL_PAGAMENTO('Interno')) {
                $this->transacao->status = 'PE';
                $this->transacao->emissor_transacao = $interfacePagamento::$LOCAL_PAGAMENTO;
                $this->transacao->atualizaTransacao($this->conexao);

                $this->processoAposComunicacaoApi($interfacePagamento, $tipoPagamento);

                return;
            }

            $this->conexao->commit();

            $this->transacao->status = 'PE';
            $this->transacao->emissor_transacao = $interfacePagamento::$LOCAL_PAGAMENTO;

            $this->tentaVariasVezes(function () {
                $this->transacao->atualizaTransacao($this->conexao);
            });

            $this->tentaVariasVezes(
                function () use ($interfacePagamento, $tipoPagamento) {
                    $this->conexao->beginTransaction();
                    $this->processoAposComunicacaoApi($interfacePagamento, $tipoPagamento);
                    $this->conexao->commit();
                },
                function () {
                    $this->conexao->rollBack();
                }
            );
        } catch (\Throwable $err) {
            if ($err instanceof ClienteException) {
                throw $err;
            }

            if (!empty($this->transacao->cod_transacao)) {
                $this->logger->emergency(
                    'Transação existe somente na api externa: ' . $this->transacao->cod_transacao,
                    ['title' => 'PAGAMENTO', 'exception' => $err]
                );
                throw new ClienteException(
                    'Ocorreu um erro ao processarmos o seu pagamento, entre em contato com a ' .
                        'equipe de suporte e NÃO TENTE NOVAMENTE.',
                    0,
                    $err
                );
            }
            throw $err;
        } finally {
            if (count($this->tentativasPagamento) > 0) {
                App::forgetInstance(PDO::class);
                $conexaoAnterior = DB::getPdo();
                DB::reconnect();
                DB::table('transacao_financeiras_tentativas_pagamento')->insert(
                    array_map(function (TransacaoFinanceiraTentativaPagamentoService $tentativa) {
                        return $tentativa->extrair();
                    }, $this->tentativasPagamento)
                );

                DB::setPdo($conexaoAnterior);
                $connection = DB::connection();
                \Closure::bind(function () {
                    $this->transactions++;
                }, $connection, $connection)();
            }
        }
    }

    public static function criarPorInterfacesPadroes(PDO $conexao, TransacaoFinanceiraService $transacao): self
    {
        $interfacesPagamento = ConfiguracaoService::buscaInterfacesPagamento(
            $conexao,
            $transacao->metodo_pagamento,
            $transacao->valor_liquido,
            round($transacao->valor_liquido / $transacao->numero_parcelas, 2)
        );
        return new self($conexao, $transacao, $interfacesPagamento);
    }

    protected function tentaVariasVezes(callable $processo, ?callable $quandoDerErro = null)
    {
        $processoFoiConcluido = false;
        for ($i = 1; $i <= 15; $i++) {
            try {
                $processo();
                $processoFoiConcluido = true;
                break;
            } catch (PDOExceptionDeadlock $deadlock) {
                if ($quandoDerErro) {
                    $quandoDerErro();
                }

                sleep(min($i, 5));
                continue;
            }

            break;
        }

        if (!$processoFoiConcluido) {
            throw new \DomainException(
                'Infelizmente sua transação foi corrompida, entre em contato com o suporte para solucionarmos o seu problema.'
            );
        }
    }

    protected function processoAposComunicacaoApi($interfacePagamento, PagamentoAbstrato $tipoPagamento): void
    {
        $lanc = new LancamentoPendente(
            'R',
            1,
            'FA',
            $this->transacao->pagador,
            date('Y-m-d H:i:s'),
            $this->transacao->valor_liquido,
            Auth::user()->id,
            15
        );
        $lanc->pares = 0;
        $lanc->transacao_origem = $this->transacao->id;
        $lanc->cod_transacao = $this->transacao->cod_transacao ?? null;
        $lanc->valor_total = $this->transacao->valor_liquido;
        $lanc->valor = $this->transacao->valor_liquido;
        $lanc->sequencia = 1;
        $lanc->parcelamento = $this->transacao->numero_parcelas;

        LancamentoPendenteService::criar($this->conexao, $lanc);

        if ($tipoPagamento->pagamentoEstaConfirmado()) {
            $this->transacao->status = 'PA';
            $this->transacao->atualizaSituacaoTransacao($this->conexao);
        }
    }

    /**
     * transacao_financeiras_tentativas_pagamento.id_transacao
     * transacao_financeiras_tentativas_pagamento.emissor_transacao
     * transacao_financeiras_tentativas_pagamento.cod_transacao
     * @param TransacaoFinanceiraTentativaPagamentoService $tentativa
     * @return void
     */
    protected function sincronizaTentativa(TransacaoFinanceiraTentativaPagamentoService $tentativa)
    {
        $tentativa->id_transacao = $this->transacao->id;

        if ($this->transacao->emissor_transacao) {
            $tentativa->emissor_transacao = $this->transacao->emissor_transacao;
        }

        if (!empty($this->transacao->cod_transacao)) {
            $tentativa->cod_transacao = $this->transacao->cod_transacao;
        }

        $tentativa->transacao_json = \json_encode($this->transacao);
    }

    /**
     * @throws IuguEstaIndisponivel
     * @throws Throwable
     */
    public function comunicaApis(): array
    {
        $processoFechamentoConcluido = false;
        $errosPagamento = [];
        if (empty($this->interfacesPagamento)) {
            throw new InvalidArgumentException('não implementado');
        }

        foreach ($this->interfacesPagamento as $indiceInterfacePagamento => $interfacePagamento) {
            if (!class_exists($interfacePagamento)) {
                throw new InvalidArgumentException('Classe de pagamento não existe');
            }

            /** @var PagamentoAbstrato $tipoPagamento */
            $tipoPagamento = app($interfacePagamento, [
                'transacao' => $this->transacao,
            ]);
            $tentativa = new TransacaoFinanceiraTentativaPagamentoService();
            $this->transacao->emissor_transacao = $interfacePagamento::$LOCAL_PAGAMENTO;
            $this->sincronizaTentativa($tentativa);

            try {
                $tipoPagamento->validaTransacao();

                $this->transacao = $tipoPagamento->comunicaApi();

                $this->sincronizaTentativa($tentativa);

                $processoFechamentoConcluido = true;
                break;
            } catch (PagamentoAntiFraudeException $antiFraudeException) {
                $this->sincronizaTentativa($tentativa);
                $tentativa->mensagem_erro = $antiFraudeException;
                $errosPagamento[] = "Erro ao efetuar pagamento Cliente {$this->transacao->pagador} - mensagem de erro: $antiFraudeException";
                $this->conexao->exec('ROLLBACK TO SAVEPOINT LANCAMENTOS_GERADOS');
                continue;
            } catch (\Throwable $exception) {
                $this->sincronizaTentativa($tentativa);
                $tentativa->mensagem_erro = $exception;
                $existeProximaInterfacePagamentos = isset($this->interfacesPagamento[$indiceInterfacePagamento + 1]);

                if ($this->transacao->metodo_pagamento === 'PX' && $existeProximaInterfacePagamentos) {
                    continue;
                } elseif (
                    $this->transacao->metodo_pagamento === 'CA' &&
                    $exception instanceof IuguEstaIndisponivel &&
                    $existeProximaInterfacePagamentos
                ) {
                    $conexao = App::build(App::getBindings()[PDO::class]['concrete']);

                    $meiosPagamento = ConfiguracaoService::consultaInfoMeiosPagamento($conexao);

                    foreach ($meiosPagamento as &$metodos) {
                        if ($metodos['prefixo'] !== 'CA') {
                            continue;
                        }

                        foreach ($metodos['meios_pagamento'] as &$meioPagamento) {
                            if ($meioPagamento['local_pagamento'] !== $tipoPagamento::$LOCAL_PAGAMENTO) {
                                continue;
                            }

                            $meioPagamento['situacao'] = 'desativado';
                        }
                    }
                    ConfiguracaoService::atualizaMeiosPagamento($conexao, $meiosPagamento);
                    $this->logger
                        ->driver('telegram')
                        ->info("Desativado meio pagamento CA {$tipoPagamento::$LOCAL_PAGAMENTO}.", [
                            'title' => 'PAGAMENTO',
                        ]);
                }

                throw $exception;
            } finally {
                $this->tentativasPagamento[$interfacePagamento] = $tentativa;
            }
        }

        if (!$processoFechamentoConcluido) {
            throw new InvalidArgumentException('Erro no pagamento: ' . implode(' - ', $errosPagamento));
        }

        return [$interfacePagamento, $tipoPagamento, $errosPagamento];
    }
}

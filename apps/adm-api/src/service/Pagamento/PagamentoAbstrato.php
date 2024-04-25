<?php

namespace MobileStock\service\Pagamento;

use MobileStock\helper\Pagamento\PagamentoAntiFraudeException;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;

abstract class PagamentoAbstrato
{
    public static array $METODOS_PAGAMENTO_SUPORTADOS;
    public static string $LOCAL_PAGAMENTO;
    protected bool $pagamentoEstaConfirmado = false;

    protected PDO $conexao;
    protected TransacaoFinanceiraService $transacao;

    public function __construct(PDO $conexao, TransacaoFinanceiraService $transacao)
    {
        $this->conexao = $conexao;
        $this->transacao = $transacao;
    }

    protected function buscaValorSplitavel(float $valorSplitsUtilizado): float
    {
        return 0;
    }

    /**
     * Todos os erros que acontecerem nesse processo devem ser tratados e jogados com exception,
     * deve retornar uma transacao financeira que será atualiza após essa função.
     * Quando essa função jogar um erro de antifraude será tentado com outro metodo de pagamento
     *
     * @throws PagamentoAntiFraudeException
     * [ATENÇÃO]
     * Essa função não pode atualizar ou inserir nada sozinha, pois se der erro não será dado rollback se tiver outra interface de pagamento para tentativa.
     */
    abstract public function comunicaApi(): TransacaoFinanceiraService;

    public function pagamentoEstaConfirmado(): bool
    {
        return $this->pagamentoEstaConfirmado;
    }

    public function validaTransacao(): void {}

    public static function SUPORTA_METODO_PAGAMENTO(string $metodoPagamento): bool
    {
        $classeFilho = get_called_class();
        return in_array($metodoPagamento, $classeFilho::$METODOS_PAGAMENTO_SUPORTADOS);
    }

    public static function SUPORTA_LOCAL_PAGAMENTO(string $localPagamento): bool
    {
        $classeFilho = get_called_class();
        return $localPagamento === $classeFilho::$LOCAL_PAGAMENTO;
    }
}
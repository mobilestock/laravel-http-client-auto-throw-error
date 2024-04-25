<?php

namespace MobileStock\service\Pagamento;

use Illuminate\Support\Arr;
use MobileStock\helper\ClienteException;
use MobileStock\helper\Pagamento\PagamentoAntiFraudeException;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\IuguService\IuguServiceMensagensErroCartao;
use MobileStock\service\Pagamento\Traits\ValidacaoCartaoTrait;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use Throwable;

class PagamentoCartaoIugu extends PagamentoAbstrato
{
    use ValidacaoCartaoTrait;

    public static string $LOCAL_PAGAMENTO = 'Iugu';
    public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];

    protected function buscaValorSplitavel(float $valorSplitsUtilizado): float
    {
        return 0;
    }

    /**
     * @inheritDoc
     * @throws ClienteException
     */
    public function comunicaApi(): TransacaoFinanceiraService
    {
        $nomeCompleto = array_filter(explode(' ', $this->transacao->dados_cartao['holderName']));
        $nome = array_shift($nomeCompleto);
        $lastName = last($nomeCompleto);

        $iugu = new IuguHttpClient();
        $iugu->listaCodigosPermitidos = [200];
        try {
            $iugu->post('payment_token', [
                'data' => [
                    'number' => $this->transacao->dados_cartao['cardNumber'],
                    'verification_value' => $this->transacao->dados_cartao['secureCode'],
                    'first_name' => $nome,
                    'last_name' => $lastName,
                    'month' => $this->transacao->dados_cartao['expirationMonth'],
                    'year' => $this->transacao->dados_cartao['expirationYear']
                ],
                'method' => 'credit_card',
                'account_id' => $_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'],
                'test' => $_ENV['AMBIENTE'] !== 'producao'
            ]);
        } catch (Throwable $exception) {
            if ($iugu->codigoRetorno === 400) {
                throw new ClienteException($exception->getMessage(), 422, $exception);
            }

            throw $exception;
        }
        ['id' => $tokenCartao] = $iugu->body;

        $iugu->criaFatura(
            ColaboradoresService::consultaDadosPagamento($this->conexao, $this->transacao->pagador),
            Arr::only($this->transacao->jsonSerialize(), ['valor_liquido', 'id']),
            ['credit_card'],
            ConfiguracaoService::consultaDiasVencimento($this->conexao),
            '_tentativa_cartao_' . sha1(uniqid(rand(), true))
        );
        $this->transacao->cod_transacao = $iugu->body['id'];

        $iugu->post('charge', [
            'invoice_id' => $this->transacao->cod_transacao,
            'token' => $tokenCartao,
            'months' => $this->transacao->numero_parcelas
        ]);

        if ($iugu->body['status'] !== 'captured') {
            $mensagemErro         = $iugu->body['info_message'];
            $serviceMensagensErro = new IuguServiceMensagensErroCartao($this->conexao);

            if (in_array($iugu->body['LR'], ['AF01', 'AF02', 'AF03', '1', '2', '59', '01', '02'])) {
                throw new PagamentoAntiFraudeException("Ocorreu um erro de antifraude ao processar seu cartão.",
                                                       422);
            }

            $mensagemErroBuscada = $serviceMensagensErro->consultaMensagemErro($iugu->body['LR']);

            if ( ! is_null($mensagemErroBuscada)) {
                $mensagemErro = $mensagemErroBuscada;
            }

            throw new ClienteException("Transação {$iugu->body['invoice_id']} $mensagemErro");
        }

        $this->pagamentoEstaConfirmado = true;
        return $this->transacao;
    }
}
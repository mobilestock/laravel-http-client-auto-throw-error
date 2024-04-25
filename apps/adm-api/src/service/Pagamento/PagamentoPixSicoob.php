<?php

namespace MobileStock\service\Pagamento;

use DateTime;
use DateTimeZone;
use DomainException;
use MobileStock\helper\Globals;
use MobileStock\service\SicoobHttpClient;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class PagamentoPixSicoob extends PagamentoAbstrato
{
    public static string $LOCAL_PAGAMENTO = 'Sicoob';
    public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX'];
    private SicoobHttpClient $sicoob;

    public function __construct(\PDO $conexao, TransacaoFinanceiraService $transacao, SicoobHttpClient $sicoob)
    {
        $this->sicoob = $sicoob;
        parent::__construct($conexao, $transacao);
    }

    /**
     * @inheritDoc
     */
    public function comunicaApi(): TransacaoFinanceiraService
    {
        if (in_array($_ENV['AMBIENTE'], ['producao', 'homologado'])) {
            $this->sicoob->post('https://api.sicoob.com.br/pix/api/v2/cob', [
                'calendario' => [
                    'expiracao' => 2 * 60 * 60,
                ],
                'valor' => [
                    'original' => number_format($this->transacao->valor_liquido, 2, '.', ''),
                ],
                'chave' => $_ENV['SICOOB_CHAVE_PIX'],
            ]);

            if ($this->sicoob->codigoRetorno !== 201) {
                throw new DomainException($this->sicoob->body['detail']);
            }

            $this->transacao->cod_transacao = $this->sicoob->body['txid'];
            $this->transacao->qrcode_text_pix = $this->sicoob->body['brcode'];
            $this->transacao->qrcode_pix = Globals::geraQRCODE($this->sicoob->body['brcode']);
        } else {
            $this->transacao->cod_transacao = uniqid(rand(), true);
            $this->transacao->qrcode_text_pix = uniqid(rand(), true);
            $this->transacao->qrcode_pix = Globals::geraQRCODE($this->transacao->qrcode_text_pix);
        }

        return $this->transacao;
    }

    public function converteSituacaoApi(): ?string
    {
        $this->sicoob->get('https://api.sicoob.com.br/pix/api/v2/cob/' . $this->transacao->cod_transacao);

        if ($this->sicoob->body['status'] === 'CONCLUIDA') {
            return 'paid';
        } elseif ($this->sicoob->body['status'] === 'ATIVA') {
            $dataCriacao = new DateTime($this->sicoob->body['calendario']['criacao'], new DateTimeZone('UTC'));
            $dataCriacao->setTimezone(new DateTimeZone('America/Sao_Paulo'));

            $expiracao = new \DateInterval("PT{$this->sicoob->body['calendario']['expiracao']}S");
            if (new DateTime() < $dataCriacao->add($expiracao)) {
                return null;
            }
            return 'expired';
        } else {
            return null;
        }
    }
}

<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use api_estoque\Cript\Cript;
use DateTime;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\Lancamento;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\PedidoItem\PedidoItem;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class Cancelamento extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = 4;
        parent::__construct();
        $this->conexao = app(PDO::class);
    }

    public function removeProdutoPago(array $data)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($data, [
                'uuid' => [Validador::OBRIGATORIO],
            ]);

            $produtoPago = new PedidoItem();
            $produtoPago->uuid = $data['uuid'];
            $produtoPago->id_cliente = $this->idCliente;
            $produtoPago->removeProdutoPago($this->conexao);

            ['comissoes' => $comissoesProduto] = TransacaoFinanceiraItemProdutoService::buscaComissoesProduto(
                $this->conexao,
                $data['uuid']
            );

            if (empty($comissoesProduto)) {
                throw new DomainException('Não foi possivel realizar o abate financeiro.');
            }

            foreach ($comissoesProduto as $comissao) {
                $estorno = new Lancamento(
                    'R',
                    1,
                    'ES',
                    $comissao['id_fornecedor'],
                    null,
                    $comissao['comissao_fornecedor'],
                    $this->idUsuario,
                    12
                );
                $estorno->numero_documento = $data['uuid'];
                $estorno->transacao_origem = $comissao['id_transacao'];

                LancamentoCrud::salva($this->conexao, $estorno);
            }

            [
                $numeroDiasRemoverProdutoPago,
                $valorTaxaRemoverProdutoPago,
            ] = ConfiguracaoService::buscaConfiguracaoProdutoPago($this->conexao);
            if (
                (new DateTime())->diff(new DateTime($produtoPago->data_atualizacao))->days >
                $numeroDiasRemoverProdutoPago
            ) {
                $lancTaxa = new Lancamento(
                    'R',
                    1,
                    'TX',
                    $this->idCliente,
                    date('Y-m-d h:i:s'),
                    $valorTaxaRemoverProdutoPago,
                    $this->idUsuario,
                    12
                );
                $lancTaxa->numero_documento = $data['uuid'];
                $lancTaxa->transacao_origem = $comissao['id_transacao'];
                $lancTaxa->observacao = "Cobrado por remover com mais de $numeroDiasRemoverProdutoPago dias o produto do painel";

                LancamentoCrud::salva($this->conexao, $lancTaxa);
            }

            $credito = new Lancamento(
                'P',
                1,
                'TR',
                $this->idCliente,
                null,
                TransacaoFinanceiraItemProdutoService::buscaValorCobradorProdutoTransacao(
                    $this->conexao,
                    $data['uuid']
                ),
                $this->idUsuario,
                12
            );
            $credito->numero_documento = $data['uuid'];
            $credito->transacao_origem = $comissao['id_transacao'];
            $credito->observacao = 'removeu item pago do painel';
            LancamentoCrud::salva($this->conexao, $credito);

            $this->conexao->commit();
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function removeTransacao($idTransacao, TransacaoFinanceiraService $transacao)
    {
        if (!is_numeric($idTransacao)) {
            $idTransacao = @Cript::dcriptInt($idTransacao, '');
        }

        $motivoCancelamento = Request::input('motivo_cancelamento', 'CLIENTE_DESISTIU');

        $transacao->id = $idTransacao;
        if ($transacao->valorEstornadoTransacao() > 0) {
            throw new BadRequestHttpException('Não pode remover a transação pois um item já está cancelado.');
        }

        Validador::validar(
            ['motivo_cancelamento' => $motivoCancelamento],
            ['motivo_cancelamento' => [Validador::ENUM('CLIENTE_DESISTIU', 'FRAUDE')]]
        );

        $transacao->motivo_cancelamento = $motivoCancelamento;
        $transacao->consultaTransacaoCancelamento();
        DB::getLock();
        DB::beginTransaction();
        $transacao->removeTransacaoPaga(DB::getPdo(), Auth::id());

        DB::commit();
    }
}

<?php
/*

namespace MobileStock\model;

use DateTime;
use Exception;
use DateTimeZone;
use DateInterval;
use Illuminate\Support\Facades\Date;
use MobileStock\Repository\MovimentacoesFinanceirasRepository;

define('__DATA_CONFIGURACAO', '2020-01-01');

class MovimentacoesFinanceiras
{

    private int $id_fornecedor;
    private int $tipo;
    private int $id;
    private float $saldo;
    private float $saldo_anterior;
    private float $saldo_defeitos;
    private float $valor_lancamento;
    private string $data;
    private string $data_ultimo_lancamento;

    public function __construct(int $id_fornecedor)
    {

        $this->dbo = new MovimentacoesFinanceirasRepository();
        $this->id_fornecedor = $id_fornecedor;
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $agora = new DateTime('now', $timezone);
        $this->data = $agora->format('Y-m-d H:i:s');
        if (!$lancamento = $this->dbo->buscaLancamento($this->id_fornecedor, '', 2)) { //se existe lançamento
            $this->data_ultimo_lancamento = __DATA_CONFIGURACAO;
            $this->id = 0;
            $this->saldo = 0;
            $this->saldo_anterior = 0;
            $this->saldo_defeitos = 0;
            $this->calculaSaldoDevedorFornecedor($this->id_fornecedor);
        } else {
            $this->hidratar($lancamento);
            $this->data_ultimo_lancamento = $lancamento['data'];
            $this->atualizaSaldoDevedorFornecedor();
        }
    }


    // ---------------------------------------- GETTERS AND SETTERS  ----------------------------------------
    public function getSaldo()
    {
        return $this->saldo;
    }

    public function getDataUltimoLancamento()
    {
        return $this->data_ultimo_lancamento;
    }

    public function getSaldoProdutosDefeituosos()
    {
        return $this->dbo->buscaSaldoProdutosDefeituosos($this->id_fornecedor);
    }

    public function getExtratoVendas(int $id_produto, string $data)
    {
        return $this->dbo->buscaExtratoVendas($id_produto, $data);
    }

    public static function gerarRelatorio()
    {
        $dbo = new MovimentacoesFinanceirasRepository();
        return $dbo->buscaUltimoRegistroTodosFornecedores();
    }


    // ----------------------------------------   METHODS    --------------------------------------------------

    // public function show(int $pagina, string $data)
    // {
    //     $retorno['listaLancamentos'] = $this->dbo->getResumoFinanceiro($this->id_fornecedor, $data, $pagina);
    //     $retorno['produtosDefeituosos'] = $this->getSaldoProdutosDefeituosos();
    //     $resumo = $this->dbo->buscaResumoSaldos($this->id_fornecedor, $data);
    //     $retorno['saldo'] = $resumo['saldo'];
    //     $retorno['saldo_anterior'] = $resumo['saldo_anterior'];
    //     $retorno['total_pago'] = $this->dbo->somaLancamentosByTipo($this->id_fornecedor, $data, 1);
    //     $retorno['total_vendidos'] = $this->dbo->somaLancamentosByTipo($this->id_fornecedor, $data, 2);
    //     $retorno['listaMovimentacao'] = $this->dbo->buscaMovimentacaoFornecedor($this->id_fornecedor, $data, __DATA_CONFIGURACAO);
    //     return $retorno;
    // }

    public function update(array $lancamento)
    {
        return $this->dbo->editarLancamentoFinanceiro($this->hidratar($lancamento));
    }

    public function store(array $lancamento)
    {
        return $this->id = $this->dbo->novoLancamentoFinanceiro($this->hidratar($lancamento));
    }

    public function hidratar(array $lancamento = []): array
    {
        try {
            if (!$lancamento) {
                return $this->extrair();
            }
            foreach ($lancamento as $key => $value) {
                $this->$key = $value;
            }
            return $this->hidratar();
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function extrair(): array
    {
        return  [
            'id' => $this->id,
            'id_fornecedor' => $this->id_fornecedor,
            'data' => $this->data,
            'valor_lancamento' => $this->valor_lancamento,
            'valor_total_produtos' => 0,
            'saldo_anterior' => $this->saldo_anterior,
            'saldo' => $this->saldo,
            'saldo_defeitos' => $this->saldo_defeitos,
            'tipo' => $this->tipo
        ];
    }


    // --------------------------------------    PRIVATE MOTHODS    ---------------------------------------------------

    private function calculaSaldoDevedorFornecedor()
    {
        $entrada = new DateTime($this->data_ultimo_lancamento);
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $agora = new DateTime('now', $timezone);
        $intervalo = $agora->diff($entrada);
        $umMes = new DateInterval('P1M');
        do {
            if ($valor = $this->dbo->buscaSaldoDevedorFornecedorMensal($this->id_fornecedor, $entrada->format('m'))) {
                $this->tipo = 2;
                $entrada->modify('last day of this month');
                $this->data =  $entrada->format('Y-m-d 23:59:59');
                $saldo_anterior = $this->dbo->buscaSaldoMesAnterior($this->id_fornecedor, $entrada->format('m'));
                $lancamentos = $this->dbo->somaLancamentosByTipo($this->id_fornecedor, $this->data, 1);
                $this->saldo = floatval($saldo_anterior) + floatval($lancamentos);
                $this->valor_lancamento = $valor;
                $this->soma($valor);
                $this->store($this->extrair());
            }
            $entrada->modify('first day of this month');
            $entrada->add($umMes);
            $intervalo = $agora->diff($entrada);
        } while ($intervalo->m != 0);
        if ($valor = $this->dbo->buscaSaldoDevedorFornecedorMensal($this->id_fornecedor, $entrada->format('m'))) {
            $this->tipo = 2;
            $this->data =  $agora->format('Y-m-d H:i:s');
            $saldo_anterior = $this->dbo->buscaSaldoMesAnterior($this->id_fornecedor, $entrada->format('m'));
            $lancamentos = $this->dbo->somaLancamentosByTipo($this->id_fornecedor, $this->data, 1);
            $this->saldo = floatval($saldo_anterior) + floatval($lancamentos);
            $this->valor_lancamento = $valor;
            $this->soma($valor);
            $this->store($this->extrair());
        }

        $this->dbo->atualizaSaldos($this->id_fornecedor);
        return false;
    }

    private function atualizaSaldoDevedorFornecedor()
    {
        if ($valor = $this->dbo->buscaSaldoDevedorFornecedor($this->id_fornecedor, $this->data_ultimo_lancamento)) {
            $timezone = new DateTimeZone('America/Sao_Paulo');
            $agora = new DateTime('now', $timezone);
            $ultimo_lancamento = $this->dbo->buscaLancamento($this->id_fornecedor);
            $this->saldo = $ultimo_lancamento['saldo'];
            $this->data = $agora->format('Y-m-d H:i:s');
            if ($this->comparaUltimoLancamento()) {
                $this->atualiza_valores($valor);
                $this->update($this->extrair());
            } else {
                $this->tipo = 2;
                $this->soma($valor);
                $this->store($this->extrair());
            }
        }
    }

    private function atualiza_valores(float $valor)
    {
        // $this->saldo_anterior = $this->saldo;
        $this->saldo += ($valor);
        $this->valor_lancamento += $valor;
    }

    private function soma(float $valor)
    {
        $this->saldo_anterior = $this->saldo;
        $this->saldo +=  $valor;
        $this->valor_lancamento = $valor;
    }

    private function comparaUltimoLancamento(): bool
    {
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $agora = new DateTime('now', $timezone);
        $entrada = new DateTime($this->data_ultimo_lancamento);
        $intervalo = $agora->diff($entrada);
        return ($intervalo->d == 0 && $entrada->format('d') == $agora->format('d') && $this->tipo == 2); // Se o ultimo lançamento e a data atual estão no mesmo mes e se é um débito
    }
}
*/
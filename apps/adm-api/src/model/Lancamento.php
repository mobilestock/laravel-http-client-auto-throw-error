<?php

namespace MobileStock\model;

/**
 * @property int $transacao_origem
 * @property string $numero_documento
 * @property int $sequencia
 */
class Lancamento implements ModelInterface
{
    private $id,
        $tipo,
        $documento,
        $documento_pagamento,
        $situacao,
        $origem,
        $id_colaborador,
        $id_representante,
        $data_emissao,
        $data_vencimento,
        $valor,
        $juros,
        $valor_total,
        $valor_pago,
        $numero_documento,
        $numero_movimento,
        $id_usuario,
        $id_usuario_pag,
        $observacao,
        $data_pagamento,
        $tabela,
        $pares,
        $acerto,
        $credito_usado,
        $nota_fiscal,
        $id_usuario_edicao,
        $status_estorno,
        $atendimento,
        $taxa_pagamento,
        $cod_transacao,
        $notificacao,
        $bloqueado,
        $id_split,
        $data_liquidacao,
        $lancamento_origem,
        $parcelamento,
        $id_pagador,
        $id_recebedor,
        $faturamento_criado_pago,
        $transacao_origem,
        $id_prioridade_saque;

    /**
     * @deprecated
     */
    private int $pedido_origem;

    /**
     * @deprecated
     */
    private int $pedido_destino;

    public function __construct(
        string $tipo,
        int $situacao,
        string $origem,
        int $id_colaborador,
        ?string $data_vencimento,
        float $valor,
        int $id_usuario,
        int $documento
    ) {
        date_default_timezone_set('America/Sao_Paulo');
        $this->data_emissao = (new \DateTime())->format('Y-m-d H:i:s');
        $this->tipo = $tipo;
        $this->situacao = $situacao;
        $this->origem = $origem;
        $this->id_colaborador = $id_colaborador;
        $this->valor = $valor;
        $this->id_usuario = $id_usuario;
        $this->documento = $documento;
        $this->insereDataVencimento(
            is_null($data_vencimento) ? (new \DateTime())->format('Y-m-d H:i:s') : $data_vencimento
        );
    }

    public static function hidratar(array $dados): ModelInterface
    {
        if (empty($dados)) {
            throw new \InvalidArgumentException('Dados inválidos');
        }
        $lanc = new self(1, 1, 1, 1, null, 1, 1, 1);
        foreach ($dados as $key => $dado) {
            $lanc->$key = $dado;
        }
        return $lanc;
    }

    public function __get($atributo)
    {
        $array = explode('_', $atributo);
        $metodo = 'recupera';
        for ($i = 0; $i < sizeof($array); $i++) {
            $metodo .= ucfirst($array[$i]);
        }
        return $this->$metodo();
    }

    public function __set($atributo, $valor): void
    {
        $array = explode('_', $atributo);
        $metodo = 'insere';
        for ($i = 0; $i < sizeof($array); $i++) {
            $metodo .= ucfirst($array[$i]);
        }
        $this->$metodo($valor);
    }
    public function recuperaTabela(): int
    {
        return $this->tabela;
    }
    public function recuperaIdLancamentoPag(): int
    {
        return $this->id_lancamento_pag;
    }
    public function recuperaId()
    {
        return $this->id;
    }
    public function recuperaFaturamentoCriadoPago()
    {
        return $this->faturamento_criado_pago;
    }

    public function recuperaSequencia()
    {
        return $this->sequencia ?? 1;
    }

    public function recuperaTipo()
    {
        return $this->tipo;
    }
    public function recuperaTransacaoOrigem(): int
    {
        return $this->transacao_origem;
    }

    public function recuperaDocumento()
    {
        return $this->documento;
    }

    public function recuperaDocumentoPagamento()
    {
        return $this->documento_pagamento;
    }

    public function recuperaSituacao()
    {
        return $this->situacao;
    }

    public function recuperaOrigem()
    {
        return $this->origem;
    }

    public function recuperaIdColaborador()
    {
        return $this->id_colaborador;
    }

    public function recuperaIdRepresentante()
    {
        return $this->id_representante;
    }

    public function recuperaDataEmissao()
    {
        return $this->data_emissao;
    }

    public function recuperaDataVencimento()
    {
        return $this->data_vencimento;
    }

    public function recuperaValor()
    {
        return $this->valor;
    }

    public function recuperaJuros()
    {
        return $this->juros;
    }

    public function recuperaValorTotal()
    {
        return $this->valor_total;
    }

    public function recuperaValorPago()
    {
        return $this->valor_pago;
    }

    public function recuperaNumeroDocumento()
    {
        return $this->numero_documento;
    }

    public function recuperaNumeroMovimento()
    {
        return $this->numero_movimento;
    }

    public function recuperaIdUsuario()
    {
        return $this->id_usuario;
    }

    public function recuperaIdUsuarioPag()
    {
        return $this->id_usuario_pag;
    }

    public function recuperaObservacao()
    {
        return $this->observacao;
    }

    public function recuperaDataPagamento()
    {
        return $this->data_pagamento;
    }

    public function recuperaPares()
    {
        return $this->pares;
    }

    public function recuperaAcerto()
    {
        return $this->acerto;
    }

    public function recuperaCreditoUsado()
    {
        return $this->credito_usado;
    }

    public function recuperaNotaFiscal()
    {
        return $this->nota_fiscal;
    }

    public function recuperaPedidoOrigem()
    {
        return $this->pedido_origem;
    }

    public function recuperaPedidoDestino()
    {
        return $this->pedido_destino;
    }

    public function recuperaIdUsuarioEdicao()
    {
        return $this->id_usuario_edicao;
    }

    public function recuperaStatusEstorno()
    {
        return $this->status_estorno;
    }

    public function recuperaAtendimento()
    {
        return $this->atendimento;
    }

    public function recuperaTaxaPagamento()
    {
        return $this->taxa_pagamento;
    }

    public function recuperaCodTransacao()
    {
        return $this->cod_transacao;
    }

    public function recuperaNotificacao()
    {
        return $this->notificacao;
    }

    public function recuperaBloqueado()
    {
        return $this->bloqueado;
    }

    public function recuperaIdSplit()
    {
        return $this->id_split;
    }

    public function recuperaDataLiquidacao()
    {
        return $this->data_liquidacao;
    }

    public function recuperaLancamentoOrigem()
    {
        return $this->lancamento_origem;
    }

    public function recuperaParcelamento()
    {
        return $this->parcelamen;
    }

    public function recuperaIdPagador()
    {
        return $this->id_pagador;
    }

    public function recuperaIdRecebedor()
    {
        return $this->id_recebedor;
    }

    public function recuperaIdPrioridadeSaque(): int
    {
        return $this->id_prioridade_saque;
    }

    public function insereId($var)
    {
        $this->id = $var;
    }

    public function insereTabela(int $tabela)
    {
        $this->tabela = $tabela;
    }

    public function insereSequencia($var)
    {
        $this->sequencia = $var;
    }

    public function insereTipo($var)
    {
        $this->tipo = $var;
    }

    public function insereDocumento($var)
    {
        $this->documento = $var;
    }

    public function insereDocumentoPagamento($var)
    {
        $this->documento_pagamento = $var;
    }

    public function insereSituacao(int $var)
    {
        $this->situacao = $var;
    }
    public function insereTransacaoOrigem(int $transacao)
    {
        $this->transacao_origem = $transacao;
    }

    public function insereOrigem($var)
    {
        $this->origem = $var;
    }

    public function insereIdColaborador($var)
    {
        $this->id_colaborador = $var;
    }

    public function insereIdRepresentante($var)
    {
        $this->id_representante = $var;
    }

    public function insereDataEmissao($var)
    {
        $this->data_emissao = $var;
    }

    public function insereDataVencimento(string $var)
    {
        $data = new \DateTime($var);

        if ($this->documento == 7 || $this->documento_pagamento == 7) {
            $data = new \DateTime();
            $data->modify('+1 week');
        }

        $this->data_vencimento = $data->format('Y-m-d H:i:s');
    }

    public function insereValor($var)
    {
        $this->valor = $var;
    }

    public function insereJuros($var)
    {
        $this->juros = $var;
    }

    public function insereValorTotal($var)
    {
        $this->valor_total = $var;
    }

    public function insereValorPago(float $var)
    {
        if ($var >= $this->valor) {
            $this->situacao = 2;
        } else {
            $this->situacao = 1;
        }

        $this->valor_pago = $var;
    }

    public function insereNumeroDocumento($var)
    {
        $this->numero_documento = $var;
    }

    public function insereNumeroMovimento($var)
    {
        $this->numero_movimento = $var;
    }

    public function insereIdUsuario($var)
    {
        $this->id_usuario = $var;
    }

    public function insereIdUsuarioPag($var)
    {
        $this->id_usuario_pag = $var;
    }

    public function insereObservacao($var)
    {
        $this->observacao = $var;
    }

    public function insereDataPagamento($var)
    {
        $this->data_pagamento = $var;
    }

    public function inserePares($var)
    {
        $this->pares = $var;
    }

    public function insereAcerto($var)
    {
        $this->acerto = $var;
    }

    public function insereCreditoUsado($var)
    {
        $this->credito_usado = $var;
    }

    public function insereNotaFiscal($var)
    {
        $this->nota_fiscal = $var;
    }

    public function inserePedidoOrigem($var)
    {
        $this->pedido_origem = $var;
    }

    public function inserePedidoDestino($var)
    {
        $this->pedido_destino = $var;
    }

    public function insereIdUsuarioEdicao($var)
    {
        $this->id_usuario_edicao = $var;
    }

    public function insereStatusEstorno($var)
    {
        $this->status_estorno = $var;
    }

    public function insereAtendimento($var)
    {
        $this->atendimento = $var;
    }

    public function insereTaxaPagamento($var)
    {
        $this->taxa_pagamento = $var;
    }

    public function insereCodTransacao($var)
    {
        $this->cod_transacao = $var;
    }

    public function insereNotificacao($var)
    {
        $this->notificacao = $var;
    }

    public function insereBloqueado($var)
    {
        $this->bloqueado = $var;
    }

    public function insereIdSplit($var)
    {
        $this->id_split = $var;
    }

    public function insereDataLiquidacao($var)
    {
        $this->data_liquidacao = $var;
    }

    public function insereLancamentoOrigem($var)
    {
        $this->lancamento_origem = $var;
    }

    public function insereParcelamento($var)
    {
        $this->parcelamento = $var;
    }

    public function insereIdPagador($var)
    {
        $this->id_pagador = $var;
    }

    public function insereIdLancamentoPag($var)
    {
        $this->id_lancamento_pag = $var;
    }
    public function insereFaturamentoCriadoPago($var)
    {
        $this->faturamento_criado_pago = $var;
    }
    public function insereIdRecebedor($var)
    {
        $this->id_recebedor = $var;
    }

    public function insereIdPrioridadeSaque(int $idPrioridadeSaque)
    {
        $this->id_prioridade_saque = $idPrioridadeSaque;
    }

    public function extrair(): array
    {
        //        if ($this->situacao === 2 && !$this->documento_pagamento)
        //            throw new \DomainException('Todo lançamento pago deve ter o campo "documento_pagamento" preenchido');

        return [
            'id' => $this->id,
            'sequencia' => $this->sequencia ?? 1,
            'tipo' => $this->tipo,
            'documento' => $this->documento,
            'documento_pagamento' => $this->situacao == 2 ? $this->documento_pagamento ?? 0 : 0,
            'situacao' => $this->situacao,
            'origem' => $this->origem,
            'id_colaborador' => $this->id_colaborador,
            'data_emissao' => $this->data_emissao,
            'data_vencimento' => $this->data_vencimento,
            'valor' => $this->valor,
            'juros' => $this->juros ?? 0,

            'valor_total' => $this->valor_total ?? $this->valor,
            'valor_pago' => $this->valor_pago,
            'numero_documento' => $this->numero_documento ?? 0,
            'numero_movimento' => $this->numero_movimento ?? 0,
            'id_usuario' => $this->id_usuario,
            'id_usuario_pag' => $this->id_usuario_pag ?? 0,
            'observacao' => $this->observacao,
            'data_pagamento' => $this->data_pagamento,
            'tabela' => $this->tabela ?? 0,
            'pares' => $this->pares ?? 0,
            'nota_fiscal' => $this->nota_fiscal ?? 0,
            'pedido_origem' => $this->pedido_origem ?? 0,
            'pedido_destino' => $this->pedido_destino ?? 0,
            'id_usuario_edicao' => $this->id_usuario_edicao,
            'status_estorno' => $this->status_estorno ?? 0,
            'atendimento' => $this->atendimento,
            'taxa_pagamento' => $this->taxa_pagamento,
            'cod_transacao' => $this->cod_transacao,
            'notificacao' => $this->notificacao,
            'bloqueado' => $this->bloqueado ?? 0,
            'id_split' => $this->id_split,
            'data_liquidacao' => $this->data_liquidacao,
            'lancamento_origem' => $this->lancamento_origem,
            'parcelamento' => $this->parcelamento,
            'id_pagador' => $this->id_pagador ?? ($this->tipo === 'P' ? 1 : $this->id_colaborador),
            'id_recebedor' => $this->id_recebedor ?? ($this->tipo === 'R' ? 1 : $this->id_colaborador),
            //            'id_lancamento_pag' => $this->id_lancamento_pag,
            'faturamento_criado_pago' => $this->faturamento_criado_pago ?? 'F',
            'transacao_origem' => $this->transacao_origem ?? 0,
        ];
    }

    public static function buscaTextoPelaOrigem(string $origem, bool $interno = false)
    {
        switch ($origem) {
            case 'AC':
                return 'Acerto';
            case 'AT':
                return 'Atendimento';
            case 'AU':
                return 'Automático/Diferença';
            case 'CA':
                return 'Cancelado';
            case 'CC':
                return $interno ? 'Comissão Criador da Publicação' : 'Venda de Publicação';
            case 'CE':
                return $interno ? 'Comissão Ponto de Entrega' : 'Venda de Ponto';
            case 'CH':
                return 'Cashback';
            case 'CL':
                return $interno ? 'Comissão Compartilhador do Link' : 'Venda por Link';
            case 'CM_ENTREGA':
                return 'Comissão de entregador';
            case 'CM_LOGISTICA':
                return 'Comissão de logística';
            case 'CM_PONTO_COLETA':
                return 'Comissão de ponto de coleta';
            case 'CM':
                return 'Lançamento de Crédito';
            case 'CP':
                return $interno ? 'Correção de Par(es)' : 'Cancelamento de Venda';
            case 'CV':
                return 'Comissão de Vendedor';
            case 'DR':
                return 'Venda de Devolução';
            case 'EM':
                return 'Adiantamento';
            case 'EP':
                return 'Estorno Look Pay';
            case 'ES':
            case 'EX':
                return 'Estorno de Cancelados';
            case 'FA':
                return $interno ? 'Transação de Pagamento' : 'Valor da Compra';
            case 'FF':
                return 'Faturamento';
            case 'FIM':
                return 'SALDO ANTERIOR';
            case 'LP':
                return 'Lançamento de Pagamento';
            case 'JA':
                return 'Juros/Adiantamento';
            case 'MA':
                return 'Lançamento Manual';
            case 'MK':
                return 'Marketplace';
            case 'MR':
                return 'Prêmio Ranking Meulook';
            case 'PAG':
                return 'Produto Pago';
            case 'PC':
                return 'Pagamento com crédito';
            case 'PD':
                return 'Pagamento de Saldo Negativo';
            case 'DT':
                return 'Taxa de devolução enviada errada ao cliente';
            case 'PF':
                return $interno ? 'Saque' : 'Débito de Transferência';
            case 'PI':
                return 'Transferência Look Pay';
            case 'PT':
                return 'Entrega Meulook';
            case 'RE':
                return 'Reembolso';
            case 'RI':
                return 'Saque Look Pay';
            case 'SC':
                return $interno ? 'Comissão Fornecedor' : 'Venda de Produto';
            case 'SI':
                return '<b>SALDO INICIAL</b>';
            case 'SP':
                return 'Split';
            case 'TC':
                return 'Estorno Criador da Publicação';
            case 'TE':
                return 'Estorno Comissão Entrega';
            case 'TF':
                return $interno ? 'Débito de Troca' : 'Devolução';
            case 'TI':
                return 'Estorno Comissão Influencer';
            case 'TL':
                return 'Estorno Compartilhador do Link';
            case 'TR':
                return 'Devolução bipada';
            case 'TX':
                return 'Taxa de Devolução';
            case 'FA':
                return 'Faturamento do pedido';
            case 'TR_LOGISTICA':
                return 'Estorno de troca logística';
            case 'TR_PONTO_COLETA':
                return 'Estorno de troca ponto de coleta';
            default:
                return 'Origem não Catalogada';
        }
    }
}

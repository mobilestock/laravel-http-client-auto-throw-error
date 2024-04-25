<?php

namespace MobileStock\model;

/**
 * @property int $transacao_origem
 */
class LancamentoPendente implements ModelInterface
{

    private $id,
        $sequencia,
        $tipo,
        $documento,
        $situacao,
        $origem,
        $id_colaborador,
        $data_emissao,
        $data_vencimento,
        $valor,
        $valor_total,
        $valor_pago,
        $id_usuario,
        $id_usuario_pag,
        $observacao,
        $data_pagamento,
        $tabela,
        $pares,
        $transacao_origem,
        $cod_transacao,
        $bloqueado,
        $id_split,
        $parcelamento,
        $taxa_pagamento,
        $juros,
        $id_pagador,
        $id_recebedor,
        $documento_pagamento,
        $numero_documento,
        $numero_movimento;

    /**
     * @var int Esse ID é inutil no sistema.
     * @deprecated
     */
    private int $pedido_origem;

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
        $this->insereDataVencimento(is_null($data_vencimento)
            ? (new \DateTime())->format('Y-m-d H:i:s')
            : $data_vencimento);
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

    public function recuperaTransacaoOrigem(): int
    {
        return $this->transacao_origem;
    }
    public function recuperaTabela(): int
    {
        return $this->tabela;
    }
    public function recuperaId()
    {
        return $this->id;
    }

    public function recuperaSequencia()
    {
        return $this->sequencia;
    }

    public function recuperaTipo()
    {
        return $this->tipo;
    }

    public function recuperaDocumento()
    {
        return $this->documento;
    }


    public function recuperaSituacao()
    {
        return $this->situacao;
    }
    public function recuperaOrigem()
    {
        return $this->origem;
    }
    public function recuperaTaxaPagamento()
    {
        return $this->taxa_pagamento;
    }

    public function recuperaIdColaborador()
    {
        return $this->id_colaborador;
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


    public function recuperaValorTotal()
    {
        return $this->valor_total;
    }

    public function recuperaValorPago()
    {
        return $this->valor_pago;
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

    public function recuperaPedidoOrigem()
    {
        return $this->pedido_origem;
    }


    public function recuperaJuros()
    {
        return $this->juros;
    }

    public function recuperaPedidoDestino()
    {
        return $this->pedido_destino;
    }



    public function recuperaCodTransacao()
    {
        return $this->cod_transacao;
    }



    public function recuperaBloqueado()
    {
        return $this->bloqueado;
    }

    public function recuperaIdSplit()
    {
        return $this->id_split;
    }


    public function recuperaDocumentoPagamento()
    {
        return $this->documento_pagamento;
    }

    public function recuperaParcelamento()
    {
        return $this->parcelamento;
    }

    public function recuperaIdPagador(): int
    {
        return $this->id_pagador;
    }

    public function recuperaIdRecebedor(): int
    {
        return $this->id_recebedor;
    }

    public function recuperaNumeroDocumento(): ?string
    {
        return $this->numero_documento;
    }

    public function recuperaNumeroMovimento()
    {
        return $this->numero_movimento;
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


    public function insereSituacao(int $var)
    {
        $this->situacao = $var;
    }

    public function insereOrigem($var)
    {
        $this->origem = $var;
    }

    public function insereIdColaborador($var)
    {
        $this->id_colaborador = $var;
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


    public function insereValorTotal($var)
    {
        $this->valor_total = $var;
    }

    public function insereValorPago(float $var)
    {
        if ($var >= $this->valor) $this->situacao = 2;
        else $this->situacao = 1;

        $this->valor_pago = $var;
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

    public function inserePedidoOrigem($var)
    {
        $this->pedido_origem = $var;
    }

    public function insereJuros($var)
    {
        $this->juros = $var;
    }

    public function insereCodTransacao($var)
    {
        $this->cod_transacao = $var;
    }


    public function insereBloqueado($var)
    {
        $this->bloqueado = $var;
    }

    public function insereIdSplit($var)
    {
        $this->id_split = $var;
    }

    public function insereParcelamento($var)
    {
        $this->parcelamento = $var;
    }

    public function insereTransacaoOrigem(int $transacao)
    {
        $this->transacao_origem = $transacao;
    }

    public function insereIdPagador(int $idPagador)
    {
        $this->id_pagador = $idPagador;
    }

    public function insereIdRecebedor(int $idRecebedor)
    {
        $this->id_recebedor = $idRecebedor;
    }

    public function insereNumeroDocumento(string $numeroDocumento)
    {
        $this->numero_documento = $numeroDocumento;
    }

    public function insereNumeroMovimento($var)
    {
        $this->numero_movimento = $var;
    }

    public function extrair(): array
    {
        if ($this->situacao === 2 && !$this->documento_pagamento)
            throw new \DomainException('Todo lançamento pago deve ter o campo "documento_pagamento" preenchido');

        return [
            'id' => $this->id,
            'sequencia' => $this->sequencia ?? 1,
            'tipo' => $this->tipo,
            'documento' => $this->documento,
            'situacao' => $this->situacao,
            'origem' => $this->origem,
            'id_colaborador' => $this->id_colaborador,
            'data_emissao' => $this->data_emissao,
            'data_vencimento' => $this->data_vencimento,
            'valor' => $this->valor,
            'valor_total' => $this->valor_total ?? $this->valor,
            'valor_pago' => $this->valor_pago,
            'id_usuario' => $this->id_usuario,
            'id_usuario_pag' => $this->id_usuario_pag ?? 0,
            'observacao' => $this->observacao,
            'numero_documento' => $this->numero_documento ?? null,
            'numero_movimento' => $this->numero_movimento ?? 0,
            'data_pagamento' => $this->data_pagamento,
            'tabela' => $this->tabela ?? 0,
            'pares' => $this->pares ?? 0,
            'pedido_origem' => $this->pedido_origem ?? 0,
            'cod_transacao' => $this->cod_transacao,
            'bloqueado' => $this->bloqueado ?? 0,
            'id_split' => $this->id_split,
            'parcelamento' => $this->parcelamento,
            'juros' =>$this->juros,
            'transacao_origem' => $this->transacao_origem,
            'id_pagador' => $this->id_pagador ?? ($this->tipo === 'P' ? 1 : $this->id_colaborador),
            'id_recebedor' => $this->id_recebedor ?? ($this->tipo === 'R' ? 1 : $this->id_colaborador),
        ];
    }
}

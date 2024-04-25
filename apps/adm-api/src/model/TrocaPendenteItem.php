<?php

namespace MobileStock\model;

class TrocaPendenteItem implements ModelInterface
{
    public $nome_tabela = 'troca_pendente_item';
    protected $id_cliente;
    protected $id_produto;
    protected $sequencia;
    protected $nome_tamanho;
    protected $tipo_cobranca;
    protected $id_tabela;
    protected $id_vendedor;
    protected $preco;
    protected $data_hora;
    protected $uuid;
    protected $cod_barras;
    protected $defeito;
    protected $confirmado;
    protected $troca_pendente;
    protected $descricao_defeito;
    protected $autorizado;
    protected $data_entrega;
    protected $situacao_faturamento_item;
    private $cliente_enviou_errado;
    private $agendada;
    private $pacIndevido;

    public function __construct(
        int $id_cliente,
        int $id_produto,
        string $nomeTamanho,
        int $id_vendedor,
        float $preco,
        string $uuid,
        string $cod_barras,
        string $data_entrega
    ) {
        $this->data_hora = date('Y-m-d H:i:s');
        $this->id_cliente = $id_cliente;
        $this->id_produto = $id_produto;
        $this->nome_tamanho = $nomeTamanho;
        $this->id_vendedor = $id_vendedor;
        $this->preco = $preco;
        $this->uuid = $uuid;
        $this->cod_barras = $cod_barras;
        $this->data_entrega = $data_entrega;
        $this->situacao_faturamento_item = 'DE';
        $this->agendada = false;
        $this->defeito = false;
        $this->descricao_defeito = '';
    }

    public function setPacIndevido(bool $pacIndevido)
    {
        $this->pacIndevido = $pacIndevido;
    }

    public function getPacIndevido(): bool
    {
        return $this->pacIndevido;
    }

    public function getAgendada(): bool
    {
        return $this->agendada;
    }

    public function setAgendada($agendada): self
    {
        $this->agendada = $agendada;
        return $this;
    }

    public function getIdCliente(): int
    {
        return $this->id_cliente;
    }

    public function setIdCliente(int $id_cliente): self
    {
        $this->id_cliente = $id_cliente;
        return $this;
    }

    public function getIdProduto(): int
    {
        return $this->id_produto;
    }

    public function setIdProduto(int $id_produto): self
    {
        $this->id_produto = $id_produto;
        return $this;
    }

    public function getSequencia(): int
    {
        return $this->sequencia;
    }

    public function setSequencia(int $sequencia): self
    {
        $this->sequencia = $sequencia;
        return $this;
    }

    public function getNomeTamanho(): string
    {
        return $this->nome_tamanho;
    }

    // public function setNOmeTamanho(string $nomeTamanho):self
    // {
    //     $this->nome_tamanho = $nomeTamanho;
    //     return $this;
    // }

    public function getTipoCobranca(): int
    {
        return $this->tipo_cobranca;
    }

    public function setTipoCobranca(int $tipo_cobranca): self
    {
        $this->tipo_cobranca = $tipo_cobranca;
        return $this;
    }

    public function getIdTabela(): int
    {
        return $this->id_tabela;
    }

    public function setIdTabela(int $id_tabela): self
    {
        $this->id_tabela = $id_tabela;
        return $this;
    }

    public function getIdVendedor(): int
    {
        return $this->id_vendedor;
    }

    public function setIdVendedor(int $id_vendedor): self
    {
        $this->id_vendedor = $id_vendedor;
        return $this;
    }

    public function getPreco(): float
    {
        return $this->preco;
    }

    public function setPreco(float $preco): self
    {
        $this->preco = $preco;
        return $this;
    }

    public function getDataHora(): string
    {
        return $this->data_hora;
    }

    public function setDataHora(string $data_hora): self
    {
        $this->data_hora = $data_hora;
        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getCodBarras(): string
    {
        return $this->cod_barras;
    }

    public function setCodBarras(string $cod_barras): self
    {
        $this->cod_barras = $cod_barras;
        return $this;
    }

    public function getDefeito(): bool
    {
        return $this->defeito;
    }

    public function setDefeito(bool $defeito): self
    {
        $this->defeito = $defeito;

        if ($this->defeito === true) {
            $this->situacao_faturamento_item = 'DF';
        }

        return $this;
    }

    public function getConfirmado(): int
    {
        return $this->confirmado;
    }

    public function setConfirmado(int $confirmado): self
    {
        $this->confirmado = $confirmado;
        return $this;
    }

    public function getTrocaPendente(): int
    {
        return $this->troca_pendente;
    }

    public function setTrocaPendente(int $troca_pendente): self
    {
        $this->troca_pendente = $troca_pendente;
        return $this;
    }

    public function getDescricaoDefeito(): string
    {
        return $this->descricao_defeito;
    }

    public function setDescricaoDefeito(string $descricao_defeito): self
    {
        $this->descricao_defeito = $descricao_defeito;
        return $this;
    }

    public function getAutorizado(): int
    {
        return $this->autorizado;
    }

    public function setAutorizado(int $autorizado)
    {
        $this->autorizado = $autorizado;
        return $this;
    }

    public function getClienteEnviouErrado()
    {
        return $this->cliente_enviou_errado;
    }

    public function setClienteEnviouErrado($cliente_enviou_errado)
    {
        $this->cliente_enviou_errado = $cliente_enviou_errado;
        return $this;
    }

    public function getSituacaoFaturamentoItem()
    {
        return $this->situacao_faturamento_item;
    }

    public function setSituacaoFaturamentoItem($situacao_faturamento_item): self
    {
        $this->situacao_faturamento_item = $situacao_faturamento_item;
        return $this;
    }

    public static function hidratar(array $dados): ModelInterface
    {
        $obj = new self(0, 0, 0, 0, 0, 0, 0, 0);
        foreach ($dados as $key => $dado) {
            $metodo = 'set';
            $array = explode('_', $key);
            for ($i = 0; $i < sizeof($array); $i++) {
                $metodo .= ucfirst($array[$i]);
            }

            $obj->$metodo($dado);
        }
        return $obj;
    }

    public function extrair(): array
    {
        return [
            'id_cliente' => $this->id_cliente,
            'id_produto' => $this->id_produto,
            'sequencia' => $this->sequencia,
            'nome_tamanho' => $this->nome_tamanho,
            'tipo_cobranca' => $this->tipo_cobranca,
            'id_tabela' => $this->id_tabela,
            'id_vendedor' => $this->id_vendedor,
            'preco' => $this->preco,
            'data_hora' => $this->data_hora,
            'uuid' => $this->uuid,
            'cod_barras' => $this->cod_barras,
            'defeito' => (int) $this->defeito,
            'confirmado' => $this->confirmado,
            'troca_pendente' => $this->troca_pendente,
            'descricao_defeito' => $this->descricao_defeito,
            'autorizado' => $this->autorizado,
        ];
    }

    public function calculaPrecoComTaxa(): float
    {
        if ($this->defeito && !$this->pacIndevido && !$this->cliente_enviou_errado) {
            return $this->agendada === true ? $this->preco : $this->preco - 5.0;
        }

        date_default_timezone_set('America/Sao_Paulo');
        $dataAtual = date_create($this->data_hora);
        $dataEntrega = date_create($this->data_entrega);
        $dias = date_diff($dataAtual, $dataEntrega)->days;

        $taxa = !$this->defeito ? 2.0 : 0;
        $percentual = 100;
        if ($dias > 365) {
            throw new \InvalidArgumentException(
                'Esse produto não pode ser trocado pois já passa de 365 dias da data da entrega. ' .
                    $dataEntrega->format('d/m/Y H:i:s')
            );
        }
        if ($dias >= 90) {
            $percentual = 50;
        }
        if ($this->pacIndevido === true) {
            $taxa += 10.0;
        }
        if ($this->cliente_enviou_errado) {
            $taxa += 8.0;
        }
        if ($this->agendada === false) {
            $taxa += 5.0;
        }

        return ($this->preco / 100) * $percentual - $taxa;
    }

    public function calculaTaxa()
    {
        return $this->preco - $this->calculaPrecoComTaxa();
    }

    public function geraObservacaoLancamento()
    {
        return 'Credito gerado ao realizar troca';
    }
    public function geraObservacaoLancamentoTaxa()
    {
        $detalhes_taxa = '';

        date_default_timezone_set('America/Sao_Paulo');
        $dataAtual = date_create($this->data_hora);
        $dataEntrega = date_create($this->data_entrega);
        $dias = date_diff($dataAtual, $dataEntrega)->days;

        if ($dias >= 90) {
            $detalhes_taxa .= '50% descontado pois passou 90 dias desde a compra do produto, ';
        }

        if ($this->agendada === false && $this->pacIndevido === false) {
            $detalhes_taxa .= "+R$ 5,00 cliente nao agendou troca, ";
        }

        if ($this->cliente_enviou_errado === true && $this->defeito === false && $this->pacIndevido === false) {
            $detalhes_taxa .= "+R$ 2,00 cliente enviou errado, ";
        }

        if ($this->defeito === true && $this->agendada === true && $this->pacIndevido === false) {
            $detalhes_taxa .= 'troca agendada por defeito nao tem taxa';
        } elseif ($this->defeito === false && $this->pacIndevido === false) {
            $detalhes_taxa .= "+R$ 2,00 taxa padrao da troca";
        }

        if ($this->pacIndevido === true) {
            $detalhes_taxa .= "+R$ 10,00 taxa de pac indevido";
        }

        return $detalhes_taxa;
    }
}

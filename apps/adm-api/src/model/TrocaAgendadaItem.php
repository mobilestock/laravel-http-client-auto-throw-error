<?php

namespace MobileStock\model;

class TrocaAgendadaItem extends TrocaPendenteItem
{
    public $nome_tabela = 'troca_pendente_agendamento';
    private $id;
    private $taxa;
    private $defeito_agendamento;
    private $tipo_agendamento;
    private $data_vencimento;
    
    public function __construct(int $id_cliente, int $id_produto, string $nome_tamanho, float $preco, string $uuid, string $data_compra)
    {
        $this->data_hora = date('Y-m-d H:i:s');
        $this->id_cliente = $id_cliente;
        $this->id_produto = $id_produto;
        $this->nome_tamanho = $nome_tamanho;
        $this->preco = $preco;
        $this->uuid = $uuid;
        $this->data_compra = $data_compra;
        $this->taxa = $this->calculaTaxa();
        $this->defeito = false;
        $this->tipo_agendamento = 'MS';
    }

    public function extrair(): array
    {
        return [
            'id' => $this->id,
            'id_produto' => $this->id_produto,
            'id_cliente' => $this->id_cliente,
            'nome_tamanho' => $this->nome_tamanho,
            'preco' => $this->preco,
            'uuid' => $this->uuid,
            'taxa' => $this->taxa,
            'defeito' => $this->defeito_agendamento ? 'T' : 'F',
            'tipo_agendamento' => $this->tipo_agendamento,
            'data_vencimento' => $this->data_vencimento ?? '0000-00-00 00:00:00'
        ];
    }

    public static function hidratar(array $dados): ModelInterface
    {
        $obj = new self(0,0,0,0,'', '');
        foreach ($dados as $key => $dado) {
            $obj->$key = $dado;
        }
        return $obj;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDefeitoAgendamento(): bool
    {
        return $this->defeito_agendamento;
    }

    public function setDefeitoAgendamento(bool $defeito): self
    {
        $this->defeito_agendamento = $defeito;
        return $this;
    }

    public function getTipoAgendamento(): string
    {
        return $this->tipo_agendamento;
    }

    public function getDataVencimento(): string
    {
        return $this->data_vencimento;
    }

    public function setDataVencimento(string $dataVencimento)
    {
        $this->data_vencimento = $dataVencimento;
        return $this;
    }
    
    public function setTipoAgendamento(string $tipoAgendamento): self
    {
        $this->tipo_agendamento = $tipoAgendamento;
        return $this;
    }
}
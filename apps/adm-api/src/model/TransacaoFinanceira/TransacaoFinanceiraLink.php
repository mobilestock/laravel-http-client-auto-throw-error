<?php

namespace MobileStock\model\TransacaoFinanceira;

use MobileStock\model\ModelInterface;

class TransacaoFinanceiraLink implements ModelInterface, \JsonSerializable
{
    public string $nome_tabela = 'transacao_financeiras_links';

    private int $id;
    private int $id_transacao;
    private int $id_cliente;
    private float $valor;
    public string $url;
    private string $nome_consumidor_final;

    public function __construct(int $id_cliente, float $valor, int $id_transacao)
    {
        $this->id = 0;
        $this->id_cliente = $id_cliente;
        $this->valor = $valor;
        $this->id_transacao = $id_transacao;
        $this->nome_consumidor_final = '';
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'id':
                $this->setId($value);
                break;

            default:
                $this->$name = $value;
        }
    }

    private function setId(int $value): void
    {
        $this->id = $value;
        $this->url = $_ENV['URL_LOOKPAY'] . 'link/' . md5($this->id) . '/pagamento?tlk=' . $this->id_transacao;
    }

    public function extrair(): array
    {
        return [
            'id_cliente' => $this->id_cliente,
            'valor' => $this->valor,
            'nome_consumidor_final' => $this->nome_consumidor_final,
            'id_transacao' => $this->id_transacao,
        ];
    }

    public static function hidratar(array $dados): ModelInterface
    {
        return new self($dados['id_cliente'], $dados['valor'], $dados['id_transacao']);
    }

    public function jsonSerialize(): array
    {
        return [
            'id_cliente' => $this->id_cliente,
            'valor' => $this->valor,
            'nome_consumidor_final' => $this->nome_consumidor_final,
            'id_transacao' => $this->id_transacao,
            'url' => $this->url,
        ];
    }
}

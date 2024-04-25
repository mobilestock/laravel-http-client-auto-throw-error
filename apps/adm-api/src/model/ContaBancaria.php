<?php

namespace MobileStock\model;

use Exception;
use MobileStock\model\Colaborador;
use MobileStock\model\ModelInterface;

/**
 * Pertence a um colaborador
 */
class ContaBancaria implements ModelInterface, \JsonSerializable
{
    private $id;
    /**
     * @var Colaborador
     */
    private $colaborador;
    /**
     * Nome do portador
     */
    private $holder_name;

    /**
     * Código do banco
     */
    private $bank_code;

    /**
     * Agencia sem DV (4 digitos)
     */
    private $routing_number;

    /**
     * Conta Bancaria com DV
     */
    private $account_number;

    /**
     * CPF, caso seller seja pessoa física
     */
    private $taxpayer_id;

    /**
     * CNPJ, caso seller seja pessoa juridica
     */
    private $ein;

    private $token_zoop;
    private $prioridade;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * @return mixed
     */
    public function getPrioridade()
    {
        return $this->prioridade;
    }

    /**
     * @param mixed $prioridade
     * @return ContaBancaria
     */
    public function setPrioridade(string $prioridade): self
    {
        $this->prioridade = $prioridade;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHolderName()
    {
        return $this->holder_name;
    }

    /**
     * @param mixed $holder_name
     * @return ContaBancaria
     */
    public function setHolderName($holder_name)
    {
        $this->holder_name = $holder_name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBankCode()
    {
        return $this->bank_code;
    }

    /**
     * @param mixed $bank_code
     * @return ContaBancaria
     */
    public function setBankCode($bank_code)
    {
        $this->bank_code = $bank_code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoutingNumber()
    {
        return $this->routing_number;
    }

    /**
     * @param mixed $routing_number
     * @return ContaBancaria
     */
    public function setRoutingNumber($routing_number)
    {
        $this->routing_number = $routing_number;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    /**
     * @param mixed $account_number
     * @return ContaBancaria
     */
    public function setAccountNumber($account_number)
    {
        $this->account_number = $account_number;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaxpayerId()
    {
        return $this->taxpayer_id;
    }

    /**
     * @param mixed $taxpayer_id
     * @return ContaBancaria
     */
    public function setTaxpayerId($taxpayer_id)
    {
        $this->taxpayer_id = $taxpayer_id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEin()
    {
        return $this->ein;
    }

    /**
     * @param mixed $ein
     * @return ContaBancaria
     */
    public function setEin($ein)
    {
        $this->ein = $ein;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return ContaBancaria
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Conta corrente ou poupança
     */
    private $type;

    public function __construct(
        $holder_name,
        $bank_code,
        $routing_number,
        $account_number,
        $taxpayer_id,
        $ein,
        $type,
        ?Colaborador $colaborador = null
    ) {
        $this->holder_name = $holder_name;
        $this->bank_code = $bank_code;
        $this->routing_number = $routing_number;
        $this->account_number = $account_number;
        $this->taxpayer_id = $taxpayer_id;
        $this->ein = $ein;
        $this->type = $type;
        $this->colaborador = $colaborador;
    }

    /**
     * @return Colaborador
     */
    public function getColaborador(): Colaborador
    {
        return $this->colaborador;
    }

    /**
     * @param Colaborador $colaborador
     */
    public function setColaborador(Colaborador $colaborador): void
    {
        $this->colaborador = $colaborador;
    }

    /**
     * @return mixed
     */
    public function getTokenZoop()
    {
        return $this->token_zoop;
    }

    /**
     * @param mixed $token_zoop
     */
    public function setTokenZoop($token_zoop): void
    {
        $this->token_zoop = $token_zoop;
    }

    public static function hidratar(array $dados): ModelInterface
    {
        $contaBancaria = new self(
            $dados['nome_titular'],
            $dados['id_banco'],
            $dados['agencia'],
            $dados['conta'],
            $dados['cpf_titular'],
            '',
            $dados['tipo']
        );

        foreach ($dados as $key => $dado) {
            $contaBancaria->$key = $dado;
        }
        return $contaBancaria;
    }

    public function extrair(): array
    {
        return get_object_vars($this);
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

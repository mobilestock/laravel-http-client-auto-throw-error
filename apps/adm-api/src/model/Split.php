<?php

class Split
{
    /**
     * @package MobileStock\model
     *
     * @property-read string $trnsaction
     * @property-read string $data
     * @property-read int $amount
     * @property-read string $recipient
     * @property-read int $liable
     * @property-read int $charge
     */

    private $transaction;
    private $data_create;
    private $amount;
    private $recipient;
    private $liable;
    private $charge_processing_fee;

    public function __construct(string $transaction, string $data_create, int $amount, string $recipient, int $liable, int $charge_processing_fee) {
        $this->transaction = $transaction;
        $this->data_create = $data_create;
        $this->amount = $amount;
        $this->recipient = $recipient;
        $this->liable = $liable;
        $this->charge_processing_fee = $charge_processing_fee;
    }

    public function __get($atributo)
    {
        $metodo = 'recupera'.ucfirst($atributo);
        return $this->$metodo();
    }

    public function recuperaTransaction():string
    {
        return $this->transaction;
    }

    public function recuperaData():string
    {
        return $this->data_create;
    }

    public function recuperaAmount():int
    {
        return $this->amount;
    }

    public function recuperaRecipient():string
    {
        return $this->recipient;
    }

    public function recuperaLiable():int
    {
        return $this->liable;
    }

    public function recuperaCharge():int
    {
        return $this->charge_processing_fee;
    }

}
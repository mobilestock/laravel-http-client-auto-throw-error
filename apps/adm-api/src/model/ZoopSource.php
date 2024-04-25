<?php

namespace MobileStock\model;

class ZoopSource
{
    private $usage;
    private $currency;
    private $amount;
    private $type;

    public function __construct(string $usage, string $currency, int $amount, string $type) {
        $this->usage = $usage;
        $this->currency = $currency;
        $this->type = $type;
        $this->amount = $amount;
    }

    public function criaSource(array $source, array $installment, string $customer_id){
        return ["type" => $this->type,
                "usage" => $this->usage,
                "amount" => $this->amount,
                "currency" => $this->currency,
                "capture" => true,
                $this->type => $source,
                "installment_plan" => $installment ,
                "customer" => [ "id" => $customer_id ],
                "token" => $source
        ];
    }
}
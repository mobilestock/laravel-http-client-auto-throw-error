<?php

use PHPUnit\Framework\Assert;

$_POST['lookpay_cc-billing-name'] = 'Teste';
$_POST['lookpay_cc-card-number'] = '1234567890123456';
$_POST['lookpay_cc-card-expiry'] = '12 / 2022';
$_POST['lookpay_cc-card-cvc'] = '123';
$_POST['lookpay_cc-installments'] = 1;

function add_action()
{
}

function woocommerce_form_field()
{
}

function WC()
{
    return new class {
        public $cart;

        public function __construct()
        {
            $this->cart = new class {
                public function empty_cart()
                {
                }
            };
        }
    };
}

function wc_get_order()
{
    return new class {
        public function get_total()
        {
            return 100;
        }

        public function add_meta_data(string $key, string $value)
        {
            if ($key === 'lookpay_id') {
                Assert::assertEquals('ID-MOCK-LOOKPAY', $value);
            } else {
                Assert::assertEquals('Parcelas', $key);
                Assert::assertEquals($_POST['lookpay_cc-installments'], $value);
            }
        }

        public function payment_complete()
        {
        }

        public function save()
        {
        }

        public function get_id()
        {
        }

        public function add_item($item)
        {
        }

        public function calculate_totals(bool $and_taxes = true)
        {
        }
    };
}

<?php

$_POST['lookpay_cc-billing-name'] = 'Teste';
$_POST['lookpay_cc-card-number'] = '1234567890123456';
$_POST['lookpay_cc-card-expiry'] = '12/2022';
$_POST['lookpay_cc-card-cvc'] = '123';
$_POST['lookpay_cc-installments'] = 1;

function __($text)
{
    return $text;
}

function init_settings()
{
    return 'test';
}

function add_action()
{
    return 'test';
}

function woocommerce_form_field()
{
    return 'test';
}

function WC()
{
    return new class {
        public $cart;

        public function __construct()
        {
            $this->cart = new class {
                public $total = 100;

                public function empty_cart()
                {
                    return 'test';
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

        public function add_meta_data()
        {
            return 'teste';
        }

        public function payment_complete()
        {
            return 'test';
        }

        public function save()
        {
            return 'test';
        }
    };
}

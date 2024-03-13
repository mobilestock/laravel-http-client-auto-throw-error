<?php

class WC_Payment_Gateway_CC
{
    public function __construct()
    {
    }

    public function wc_get_order()
    {
        return 'test';
    }

    public function get_option()
    {
        return 'teste';
    }

    public function get_return_url($order)
    {
        return 10;
    }

    public function init_settings()
    {
        return 'test';
    }
}

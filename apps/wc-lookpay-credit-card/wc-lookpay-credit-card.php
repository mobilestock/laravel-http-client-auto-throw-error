<?php

/**
 * Plugin Name: WooCommerce LookPay Credit Card
 * Description: A simple credit card payment gateway for WooCommerce.
 * Version: 1.0.0
 * Author: LookPay
 * Author URI: https://lookpay.com.br
 */

use WcLookPayCC\CreditCardGateway;

if (!defined('ABSPATH')) {
    exit;
}

class CCLP
{
    public function __construct()
    {
        require __DIR__ . '/vendor/autoload.php';

        add_filter('woocommerce_payment_gateways', function (array $methods) {
            $methods[] = CreditCardGateway::class;
            return $methods;
        });
    }
}

add_action('plugins_loaded', fn() => new CCLP());

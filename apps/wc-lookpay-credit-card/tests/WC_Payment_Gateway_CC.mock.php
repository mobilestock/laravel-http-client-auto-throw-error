<?php

class WC_Payment_Gateway_CC
{
    public function get_option(string $key)
    {
        if ($key === 'fees') {
            return json_encode([0, 3.6]);
        }
    }

    public function get_return_url()
    {
    }

    public function init_settings()
    {
    }
}

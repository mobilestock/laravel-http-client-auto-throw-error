<?php

use PHPUnit\Framework\Assert;

class WC_Order_Item_Fee
{
    public function set_name()
    {
    }

    public function set_amount(string $value)
    {
        Assert::assertEquals(3.6, $value);
    }

    public function set_tax_status()
    {
    }

    public function set_total()
    {
    }
}

<?php

namespace App\Enum\Invoice;

enum PaymentMethodsEnum: string
{
    case CREDIT_CARD = 'CREDIT_CARD';

    public static function returnPaymentMethods(): array
    {
        return [self::CREDIT_CARD];
    }
}

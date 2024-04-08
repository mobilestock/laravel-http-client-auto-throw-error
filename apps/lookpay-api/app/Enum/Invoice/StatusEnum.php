<?php

namespace App\Enum\Invoice;

enum StatusEnum: string
{
    case PAID = 'PAID';
    case CREATED = 'CREATED';

    public static function returnStatus(): array
    {
        return [self::PAID, self::CREATED];
    }
}

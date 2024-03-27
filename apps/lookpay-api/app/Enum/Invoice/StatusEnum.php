<?php

namespace App\Enum\Invoice;

enum StatusEnum: string
{
    case CREATED = 'CREATED';
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case REFUNDED = 'REFUNDED';
    case EXPIRED = 'EXPIRED';
}

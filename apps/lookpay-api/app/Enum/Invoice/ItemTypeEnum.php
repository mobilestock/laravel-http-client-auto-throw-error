<?php

namespace App\Enum\Invoice;

enum ItemTypeEnum: string
{
    case ADD_CREDIT = 'ADD_CREDIT';

    public static function returnItemTypes(): array
    {
        return [self::ADD_CREDIT];
    }
}

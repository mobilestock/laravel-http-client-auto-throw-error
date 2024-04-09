<?php

namespace App\Models;

class IuguCreditCardErrorMessage extends Model
{
    public static function getErrorMessageByLrCode(string $lrCode): self
    {
        $errorMessage = self::fromQuery(
            "SELECT iugu_credit_card_error_messages.message
            FROM iugu_credit_card_error_messages
            WHERE lr_code = :lr_code",
            ['lr_code' => $lrCode]
        )->firstOrFail();

        return $errorMessage;
    }
}

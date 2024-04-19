<?php

namespace App\Models;

/**
 * @property string $message
 */
class IuguCreditCardErrorMessage extends Model
{
    public static function getErrorMessageByLrCode(string $lrCode): self
    {
        $errorMessage = self::fromQuery(
            "SELECT iugu_credit_card_error_messages.message
            FROM iugu_credit_card_error_messages
            WHERE iugu_credit_card_error_messages.lr_code = :lr_code",
            ['lr_code' => $lrCode]
        )->firstOrFail();

        return $errorMessage;
    }
}

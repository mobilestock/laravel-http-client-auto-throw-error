<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumberRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (mb_strlen(preg_replace('/[^0-9]/', '', $value)) !== 11) {
            $fail('validation.phone_number')->translate();
        }
    }
}

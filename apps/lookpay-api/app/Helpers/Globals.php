<?php

namespace App\Helpers;

use ReflectionClass;

class Globals
{
    public static function getEnumValues(string $enumClassName)
    {
        $values = [];
        $refClass = new ReflectionClass($enumClassName);
        foreach ($refClass->getConstants() as $constantValue) {
            $values[] = $constantValue;
        }
        return $values;
    }
}

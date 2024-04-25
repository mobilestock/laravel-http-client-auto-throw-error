<?php

namespace MobileStock\service\Cache;

use MobileStock\helper\Validador;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

class JsonMarshaller implements MarshallerInterface
{
    public function marshall(array $values, ?array &$failed): array
    {
        return array_map(function ($item) {
            return is_array($item) ? json_encode($item) : $item;
        }, $values);
    }

    /**
     * @return array|string
     */
    public function unmarshall(string $value)
    {
        if (Validador::validacaoJson($value)) return json_decode($value, true);
        else return $value;
    }
}
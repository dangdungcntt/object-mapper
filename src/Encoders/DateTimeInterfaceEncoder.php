<?php

namespace Nddcoder\ObjectMapper\Encoders;

use Nddcoder\ObjectMapper\Contracts\ObjectMapperEncoder;

class DateTimeInterfaceEncoder implements ObjectMapperEncoder
{
    public function encode(mixed $value, ?string $className = null): string
    {
        return $value->format(DATE_RFC3339_EXTENDED);
    }

    public function decode(mixed $value, ?string $className = null): mixed
    {
        return is_null($className) ? null : new $className($value);
    }
}

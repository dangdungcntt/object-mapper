<?php

namespace Nddcoder\ObjectMapper\Encoders;

use Nddcoder\ObjectMapper\Contracts\ObjectMapperEncoder;

class StdClassEncoder implements ObjectMapperEncoder
{
    public function encode(mixed $value, ?string $className = null): string
    {
        return json_encode($value);
    }

    public function decode(mixed $value, ?string $className = null): mixed
    {
        return json_decode(json_encode($value));
    }
}

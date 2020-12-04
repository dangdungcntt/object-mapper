<?php

namespace Nddcoder\ObjectMapper\Contracts;

interface ObjectMapperEncoder
{
    public function encode(mixed $value, ?string $className = null): string;

    public function decode(mixed $value, ?string $className = null): mixed;
}

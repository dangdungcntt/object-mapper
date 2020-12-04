<?php

namespace Nddcoder\ObjectMapper;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nddcoder\ObjectMapper\ObjectMapper
 * @method static addEncoder(string $targetClass, string $encoderClass): void
 * @method static removeEncoder(string $targetClass): void
 * @method static readValue(array|string $json, string $className): mixed
 * @method static writeValueAsString(mixed $value): string
 */
class ObjectMapperFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ObjectMapper::class;
    }
}

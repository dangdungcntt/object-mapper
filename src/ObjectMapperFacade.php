<?php

namespace Nddcoder\ObjectMapper;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nddcoder\ObjectMapper\ObjectMapper
 * @method static readValue(array|string $json, string $className): mixed
 * @method static writeValueAsString(mixed $value): string
 */
class ObjectMapperFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-object-mapper';
    }
}

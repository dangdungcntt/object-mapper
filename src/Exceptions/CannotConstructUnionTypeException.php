<?php

namespace Nddcoder\ObjectMapper\Exceptions;

use Exception;
use Throwable;

class CannotConstructUnionTypeException extends Exception
{
    public static function make(string $unionType, ?Throwable $previous = null): static
    {
        return new static(sprintf("Cannot construct value for union type %s", $unionType), 0, $previous);
    }
}

<?php

namespace Nddcoder\ObjectMapper\Exceptions;

use Exception;

class AttributeMustNotBeNullException extends Exception
{
    public static function make(string $className, string $propertyName): static
    {
        return new static(sprintf("%s::\$%s must not be null", $className, $propertyName));
    }
}

<?php

namespace Nddcoder\ObjectMapper\Exceptions;

use Exception;

class ClassNotFoundException extends Exception
{
    public static function make(string $className): static
    {
        return new static(sprintf("Class %s not found", $className));
    }
}

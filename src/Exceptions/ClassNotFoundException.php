<?php

namespace Nddcoder\ObjectMapper\Exceptions;

use Exception;

class ClassNotFoundException extends Exception
{
    public static function make(string $className): self
    {
        return new self(sprintf("Class %s not found", $className));
    }
}

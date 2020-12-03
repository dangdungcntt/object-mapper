<?php


namespace Nddcoder\ObjectMapper\Exceptions;


class AttributeMustNotBeNullException extends \Exception
{
    public static function make(string $className, string $propertyName): self
    {
        return new self(sprintf("%s::\$%s must not be null", $className, $propertyName));
    }
}

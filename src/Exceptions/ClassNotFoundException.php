<?php


namespace Nddcoder\ObjectMapper\Exceptions;


class ClassNotFoundException extends \Exception
{
    public static function make(string $className): self
    {
        return new self(sprintf("Class %s not found", $className));
    }
}

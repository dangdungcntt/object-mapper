<?php


namespace Nddcoder\ObjectMapper\Exceptions;


class AttributeMustNotBeNullException extends \Exception
{
    public static function make(string $message): self
    {
        return new static($message);
    }
}

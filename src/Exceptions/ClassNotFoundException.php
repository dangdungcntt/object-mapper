<?php


namespace Nddcoder\ObjectMapper\Exceptions;


class ClassNotFoundException extends \Exception
{
    public static function make(string $message): self
    {
        return new static($message);
    }
}

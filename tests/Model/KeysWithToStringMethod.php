<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


class KeysWithToStringMethod
{
    public string $p256dh;
    public string $auth;

    public function __toString(): string
    {
        return 'masked data';
    }
}

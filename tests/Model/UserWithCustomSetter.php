<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


class UserWithCustomSetter
{
    public ?Keys $keys;
    public int $requestNumber;
    public string $company;

    public function setAuth_key(?Keys $keys)
    {
        $this->keys = $keys;
    }

    public function setRequestNum(string $requestNum)
    {
        $this->requestNumber = (int) $requestNum;
    }
}

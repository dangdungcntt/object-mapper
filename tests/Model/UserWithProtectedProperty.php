<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


class UserWithProtectedProperty
{
    protected string $password;
    public string $company;

    public function setPassword(string $password)
    {
        $this->password = $password;
    }
}

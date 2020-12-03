<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


class ModelWithProtectedProperty
{
    protected string $password;
    public string $company;

    public function setPassword(string $password)
    {
        $this->password = $password;
    }
}

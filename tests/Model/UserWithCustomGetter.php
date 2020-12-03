<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


use JetBrains\PhpStorm\Pure;

class UserWithCustomGetter
{
    public string $company;

    #[Pure]
    public function getCompany(): string
    {
        return strtoupper($this->company);
    }
}

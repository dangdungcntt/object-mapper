<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


use Nddcoder\ObjectMapper\Attributes\AppendJsonOutput;

class ModelWithAppendJsonOutput
{
    public string $firstName;
    public string $lastName;

    #[AppendJsonOutput('full_name')]
    public function getFullName(): string
    {
        return "$this->firstName $this->lastName";
    }
}

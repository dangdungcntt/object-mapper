<?php


namespace Nddcoder\ObjectMapper\Tests\Model;

class ModelImplementJsonSerializable implements \JsonSerializable
{
    public function __construct(
        protected array $items
    ) {
    }

    public function jsonSerialize()
    {
        return $this->items;
    }
}

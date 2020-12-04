<?php

namespace Nddcoder\ObjectMapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonProperty
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $name
    ) {
        //
    }
    // @codeCoverageIgnoreEnd
}

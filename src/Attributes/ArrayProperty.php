<?php

namespace Nddcoder\ObjectMapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ArrayProperty
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $type
    ) {
        //
    }
    // @codeCoverageIgnoreEnd
}

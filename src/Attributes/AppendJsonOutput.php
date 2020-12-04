<?php

namespace Nddcoder\ObjectMapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AppendJsonOutput
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $field
    ) {
        //
    }
    // @codeCoverageIgnoreEnd
}

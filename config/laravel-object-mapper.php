<?php

use Nddcoder\ObjectMapper\Encoders\DateTimeInterfaceEncoder;
use Nddcoder\ObjectMapper\Encoders\StdClassEncoder;

return [
    'encoders' => [
        DateTimeInterface::class => DateTimeInterfaceEncoder::class,
        stdClass::class => StdClassEncoder::class
    ]
];

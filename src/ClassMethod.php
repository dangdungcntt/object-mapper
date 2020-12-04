<?php


namespace Nddcoder\ObjectMapper;


class ClassMethod
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $name,
        public array $params,
    ) {
    }
    // @codeCoverageIgnoreEnd

    public static function fromReflectionMethod(\ReflectionMethod $reflectionMethod): static
    {
        return new static(
            name: $reflectionMethod->getName(),
            params: array_map(
            fn(\ReflectionParameter $parameter) => ClassProperty::fromReflectorProperty($parameter),
            $reflectionMethod->getParameters()
        ),
        );
    }
}

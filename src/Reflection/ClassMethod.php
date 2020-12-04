<?php


namespace Nddcoder\ObjectMapper\Reflection;


use Nddcoder\ObjectMapper\Attributes\AppendJsonOutput;
use ReflectionAttribute;

class ClassMethod
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $name,
        public array $params,
        public ?AppendJsonOutput $appendJsonOutput
    ) {
    }

    // @codeCoverageIgnoreEnd

    public static function fromReflectionMethod(\ReflectionMethod $reflectionMethod): static
    {
        $appendJsonOutputAttribute = $reflectionMethod->getAttributes(AppendJsonOutput::class,
                ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        return new static(
            name: $reflectionMethod->getName(),
            params: array_map(
            fn(\ReflectionParameter $parameter) => ClassProperty::fromReflectorProperty($parameter),
            $reflectionMethod->getParameters()
        ),
            appendJsonOutput: $appendJsonOutputAttribute?->newInstance()
        );
    }
}

<?php

namespace Nddcoder\ObjectMapper\Reflection;

use Nddcoder\ObjectMapper\Attributes\ArrayProperty;
use Nddcoder\ObjectMapper\Attributes\JsonProperty;
use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

/**
 * Class ClassProperty
 * @package Nddcoder\ObjectMapper
 */
class ClassProperty
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $name,
        public ReflectionType|CustomReflectionType|null $type,
        public ?JsonProperty $jsonProperty,
        public ?ArrayProperty $arrayProperty
    ) {

    }
    // @codeCoverageIgnoreEnd

    public static function fromReflectorProperty(ReflectionProperty|ReflectionParameter $reflectionProperty): static
    {
        $jsonPropertyAttribute = $reflectionProperty->getAttributes(
                JsonProperty::class,
                ReflectionAttribute::IS_INSTANCEOF
            )[0] ?? null;

        $arrayPropertyAttribute = $reflectionProperty->getAttributes(ArrayProperty::class,
                ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        return new static(
            name: $reflectionProperty->getName(),
            type: $reflectionProperty->getType(),
            jsonProperty: $jsonPropertyAttribute?->newInstance(),
            arrayProperty: $arrayPropertyAttribute?->newInstance()
        );
    }
}

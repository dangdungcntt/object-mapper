<?php

namespace Nddcoder\ObjectMapper\Reflection;

class CustomReflectionType extends \ReflectionNamedType
{
    // @codeCoverageIgnoreStart
    public function __construct(
        public string $customName,
        public bool $isBuiltin = false
    ) {

    }
    // @codeCoverageIgnoreEnd

    public function getName(): string
    {
        return $this->customName;
    }

    public function isBuiltin(): bool
    {
        return $this->isBuiltin;
    }

    // @codeCoverageIgnoreStart
    //Fix Psalm error
    public function getTypes(): array
    {
        return [];
    }
    // @codeCoverageIgnoreEnd
}

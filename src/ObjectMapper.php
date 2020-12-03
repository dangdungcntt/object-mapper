<?php

namespace Nddcoder\ObjectMapper;

use DateTimeInterface;
use Nddcoder\ObjectMapper\Attributes\JsonProperty;
use Nddcoder\ObjectMapper\Exceptions\AttributeMustNotBeNullException;
use Nddcoder\ObjectMapper\Exceptions\ClassNotFoundException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

class ObjectMapper
{
    protected static array $cachedClassInfo = [];
    protected static array $cachedResolverForClass = [];
    protected static array $cachedConverterForClass = [];

    protected const CLASS_PUBLIC_PROPERTIES = 'public_properties';
    protected const CLASS_PRIVATE_AND_PROTECTED_PROPERTIES = 'private_and_protected_properties';
    protected const CLASS_GETTER_AND_SETTER = 'getter_and_setter';
    protected const CLASS_IS_INTERNAL = 'is_internal';

    /**
     * @param  string|array  $json
     * @param  string  $className
     * @return mixed
     * @throws ClassNotFoundException
     * @throws AttributeMustNotBeNullException
     */
    public function readValue(string|array $json, string $className): mixed
    {
        if (!class_exists($className)) {
            throw ClassNotFoundException::make($className);
        }

        $data = is_string($json) ? json_decode($json, true) : $json;

        $instance = new $className();

        $publicProperties = $this->getClassInfo($className, self::CLASS_PUBLIC_PROPERTIES);
        $getterAndSetter  = $this->getClassInfo($className, self::CLASS_GETTER_AND_SETTER);

        $nulledProperties = [];

        foreach ($publicProperties as [$propertyName, $propertyType, $jsonPropertyName]) {
            $value = $data[$jsonPropertyName ?? StrHelpers::snake($propertyName)] ?? null;

            $resolvedValue = $this->resolveValue($value, $propertyType);

            if (is_null($resolvedValue) && !is_null($propertyType) && !$propertyType->allowsNull()) {
                $nulledProperties[] = $propertyName;
                continue;
            }

            $instance->{$propertyName} = $resolvedValue;
        }

        foreach ($data as $snakeCaseProperty => $value) {
            if (array_key_exists($camelCaseMethod = 'set'.StrHelpers::studly($snakeCaseProperty), $getterAndSetter)) {
                $paramType = $getterAndSetter[$camelCaseMethod][0]?->getType();
                $instance->{$camelCaseMethod}($this->resolveValue($value, $paramType));
                continue;
            }

            if (array_key_exists($snakeCaseMethod = 'set'.ucfirst($snakeCaseProperty), $getterAndSetter)) {
                $paramType = $getterAndSetter[$snakeCaseMethod][0]?->getType();
                $instance->{$snakeCaseMethod}($this->resolveValue($value, $paramType));
                continue;
            }
        }

        foreach ($nulledProperties as $propertyName) {
            if (!isset($instance->{$propertyName})) {
                throw AttributeMustNotBeNullException::make($className, $propertyName);
            }
        }

        return $instance;
    }

    public function writeValueAsString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_null($value)) {
            return '';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (!is_object($value)) {
            return (string) $value;
        }

        if (method_exists($value, 'toArray')) {
            return json_encode($value->toArray());
        }

        if (method_exists($value, 'toJson')) {
            return $value->toJson();
        }

        if (method_exists($value, '__toString')) {
            return $value->__toString();
        }

        $className = $value::class;

        $getterAndSetter = $this->getClassInfo($className, self::CLASS_GETTER_AND_SETTER);

        $publicProperties = $this->getClassInfo($className, self::CLASS_PUBLIC_PROPERTIES);

        $jsonObject = [];

        foreach ($publicProperties as [$propertyName, $_, $jsonPropertyName]) {
            $outputField     = $jsonPropertyName ?? StrHelpers::snake($propertyName);
            $camelCaseMethod = 'get'.ucfirst($propertyName);
            $outputValue     = array_key_exists($camelCaseMethod, $getterAndSetter)
                ? $value->{$camelCaseMethod}()
                : $value->{$propertyName};

            $jsonObject[$outputField] = $this->convertOutputValue($outputValue);
        }

        $privateAndProtectedProperties = $this->getClassInfo($className, self::CLASS_PRIVATE_AND_PROTECTED_PROPERTIES);

        foreach ($privateAndProtectedProperties as [$propertyName, $_, $jsonPropertyName]) {
            $outputField     = $jsonPropertyName ?? StrHelpers::snake($propertyName);
            $camelCaseMethod = 'get'.ucfirst($propertyName);

            if (!array_key_exists($camelCaseMethod, $getterAndSetter)) {
                continue;
            }

            $outputValue = $value->{$camelCaseMethod}();

            $jsonObject[$outputField] = $this->convertOutputValue($outputValue);
        }

        return json_encode((object) $jsonObject);
    }

    /**
     * @param  string  $className
     * @param  string  $field
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getClassInfo(string $className, string $field): mixed
    {
        if (isset(static::$cachedClassInfo[$className])) {
            return static::$cachedClassInfo[$className][$field] ?? null;
        }

        $reflectionClass = new ReflectionClass($className);

        $classInfo = [
            static::CLASS_PUBLIC_PROPERTIES                => $this->getClassProperties(
                $reflectionClass,
                ReflectionProperty::IS_PUBLIC
            ),
            static::CLASS_PRIVATE_AND_PROTECTED_PROPERTIES => $this->getClassProperties(
                $reflectionClass,
                ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED
            ),
            static::CLASS_GETTER_AND_SETTER                => $this->getGetterAndSetterMethods($reflectionClass),
            static::CLASS_IS_INTERNAL                      => $reflectionClass->isInternal()
        ];

        static::$cachedClassInfo[$className] = $classInfo;

        return $classInfo[$field] ?? null;
    }

    protected function getClassProperties(ReflectionClass $reflectionClass, ?int $filter = null): array
    {
        return array_map(
            function (ReflectionProperty $property) {
                $jsonProperty = $property->getAttributes(
                        JsonProperty::class,
                        ReflectionAttribute::IS_INSTANCEOF
                    )[0] ?? null;

                $jsonPropertyName = null;

                if (!is_null($jsonProperty)) {
                    $jsonPropertyName = $jsonProperty->newInstance()->name;
                }

                return [$property->getName(), $property->getType(), $jsonPropertyName];
            },
            $reflectionClass->getProperties($filter)
        );
    }

    protected function getGetterAndSetterMethods(ReflectionClass $reflectionClass): array
    {
        $getterAndSetters = [];
        $methods          = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'get')) {
                $getterAndSetters[$method->getName()] = true;
                continue;
            }

            if (str_starts_with($method->getName(), 'set')) {
                $getterAndSetters[$method->getName()] = $method->getParameters();
            }
        }
        return $getterAndSetters;
    }

    protected function convertOutputValue(mixed $outputValue): mixed
    {
        if (!is_object($outputValue)) {
            return $outputValue;
        }

        if (method_exists($outputValue, '__toString')) {
            return $outputValue->__toString();
        }

        $outputClass = $outputValue::class;

        if ($this->getClassInfo($outputClass, self::CLASS_IS_INTERNAL)) {
            return $this->convertInternalClassInstance($outputClass, $outputValue);
        }

        $stringOutput = $this->writeValueAsString($outputValue);

        return json_decode($stringOutput, true) ?? $stringOutput;
    }

    protected function constructInternalClassInstance(string $className, mixed $value): mixed
    {
        if (isset(static::$cachedResolverForClass[$className])) {
            return (static::$cachedResolverForClass[$className])($value);
        }

        $resolver = fn(): mixed => null;

        $classImplements = class_implements($className) ?: [];

        if (in_array(DateTimeInterface::class, $classImplements)) {
            $resolver = fn($value) => new $className($value);
        }

        static::$cachedResolverForClass[$className] = $resolver;

        return $resolver($value);

    }

    protected function convertInternalClassInstance(string $className, mixed $value): mixed
    {
        if (isset(static::$cachedConverterForClass[$className])) {
            return (static::$cachedConverterForClass[$className])($value);
        }

        $converter = fn($value): mixed => $value;

        $classImplements = class_implements($className) ?: [];

        if (in_array(DateTimeInterface::class, $classImplements)) {
            $converter = fn(DateTimeInterface $value) => $value->format('c');
        }

        static::$cachedConverterForClass[$className] = $converter;

        return $converter($value);
    }

    protected function resolveValue(mixed $value, ?ReflectionType $propertyType): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_null($propertyType)) {
            return $value;
        }

        if ($propertyType instanceof ReflectionNamedType) {
            if ($propertyType->isBuiltin()) {
                try {
                    settype($value, $propertyType->getName());
                    return $value;
                } catch (\Throwable) {
                    return null;
                }
            }

            if ($this->getClassInfo($propertyType->getName(), self::CLASS_IS_INTERNAL)) {
                return $this->constructInternalClassInstance($propertyType->getName(), $value);
            }

            if (is_array($value)) {
                return $this->readValue($value, $propertyType->getName());
            }
        }

        $propertyClassName = $propertyType->getName();

        return $value instanceof $propertyClassName ? $value : null;
    }
}

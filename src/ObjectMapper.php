<?php

namespace Nddcoder\ObjectMapper;

use Nddcoder\ObjectMapper\Attributes\JsonProperty;
use Nddcoder\ObjectMapper\Contracts\ObjectMapperEncoder;
use Nddcoder\ObjectMapper\Exceptions\AttributeMustNotBeNullException;
use Nddcoder\ObjectMapper\Exceptions\CannotConstructUnionTypeException;
use Nddcoder\ObjectMapper\Exceptions\ClassNotFoundException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

class ObjectMapper
{
    protected static array $cachedClassInfo = [];
    protected static array $encoderCache = [];

    protected const CLASS_PUBLIC_PROPERTIES = 'public_properties';
    protected const CLASS_PRIVATE_AND_PROTECTED_PROPERTIES = 'private_and_protected_properties';
    protected const CLASS_GETTER_AND_SETTER = 'getter_and_setter';

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

        $encoder = $this->findEncoder($className);

        if (!is_null($encoder)) {
            return $encoder->encode($value, $className);
        }

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

            $jsonObject[$outputField] = $this->convertOutputValue($value->{$camelCaseMethod}());
        }

        return json_encode((object) $jsonObject);
    }

    /**
     * @param  string  $className
     * @param  string  $field
     * @return mixed
     * @throws ReflectionException
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
                } catch (Throwable) {
                    return null;
                }
            }

            $propertyClassName = $propertyType->getName();

            $encoder = $this->findEncoder($propertyClassName);

            $resolvedValue = null;
            if (!is_null($encoder)) {
                $resolvedValue = $encoder->decode($value, $propertyClassName);
            } elseif (is_array($value)) {
                $resolvedValue = $this->readValue($value, $propertyClassName);
            }

            return $resolvedValue instanceof $propertyClassName ? $resolvedValue : null;
        }

        if ($propertyType instanceof ReflectionUnionType) {
            $exceptions = [];
            $typeNames  = [];

            foreach ($propertyType->getTypes() as $type) {
                try {
                    $typeNames[] = $type->getName();
                    if ($v = $this->resolveValue($value, $type)) {
                        return $v;
                    }

                    if ($type->getName() === 'null') {
                        return null;
                    }
                } catch (Throwable $exception) {
                    $exceptions[] = $exception;
                }
            }

            if (!empty($exceptions)) {
                throw CannotConstructUnionTypeException::make(join('|', $typeNames), $exceptions[0]);
            }
        }
        // @codeCoverageIgnoreStart
        // Never reached here because ReflectionType has only 2 implementations: ReflectionUnionType, ReflectionNamedType
        return null;
        // @codeCoverageIgnoreEnd
    }

    protected function findEncoder(string $className): ?ObjectMapperEncoder
    {
        if (isset(static::$encoderCache[$className])) {
            return static::$encoderCache[$className];
        }

        $encoders = config('laravel-object-mapper.encoders') ?? [];

        foreach ($encoders as $targetClass => $encoderClass) {
            if ($className == $targetClass || is_subclass_of($className, $targetClass)) {
                return static::$encoderCache[$className] = new $encoderClass();
            }
        }

        return static::$encoderCache[$className] = null;
    }

    protected function convertOutputValue(mixed $outputValue): mixed
    {
        if (!is_object($outputValue)) {
            return $outputValue;
        }

        $stringOutput = $this->writeValueAsString($outputValue);

        return json_decode($stringOutput, true) ?? $stringOutput;
    }
}

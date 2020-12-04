<?php

namespace Nddcoder\ObjectMapper;

use DateTimeInterface;
use Nddcoder\ObjectMapper\Contracts\ObjectMapperEncoder;
use Nddcoder\ObjectMapper\Encoders\DateTimeInterfaceEncoder;
use Nddcoder\ObjectMapper\Encoders\StdClassEncoder;
use Nddcoder\ObjectMapper\Exceptions\AttributeMustNotBeNullException;
use Nddcoder\ObjectMapper\Exceptions\CannotConstructUnionTypeException;
use Nddcoder\ObjectMapper\Exceptions\ClassNotFoundException;
use Nddcoder\ObjectMapper\Reflection\ClassMethod;
use Nddcoder\ObjectMapper\Reflection\ClassProperty;
use Nddcoder\ObjectMapper\Reflection\CustomReflectionType;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use stdClass;
use Throwable;

class ObjectMapper
{
    protected static array $classInfoCache = [];
    protected static array $encoderCache = [];
    protected static array $encoders = [
        DateTimeInterface::class => DateTimeInterfaceEncoder::class,
        stdClass::class          => StdClassEncoder::class
    ];

    protected const CLASS_PUBLIC_PROPERTIES = 'public_properties';
    protected const CLASS_PRIVATE_AND_PROTECTED_PROPERTIES = 'private_and_protected_properties';
    protected const CLASS_GETTER_AND_SETTER = 'getter_and_setter';

    public static function addEncoder(string $targetClass, string $encoderClass): void
    {
        static::$encoders[$targetClass] = $encoderClass;
        //clear encoder cache
        static::$encoderCache = [];
    }

    public static function removeEncoder(string $targetClass): void
    {
        unset(static::$encoders[$targetClass]);
        //clear encoder cache
        static::$encoderCache = [];
    }

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

        foreach ($publicProperties as $classProperty) {
            /** @var ClassProperty $classProperty */
            $value = $data[$classProperty->jsonProperty?->name ?? StrHelpers::snake($classProperty->name)] ?? null;

            $resolvedValue = $this->resolveValue($value, $classProperty);

            if (is_null($resolvedValue) && !is_null($classProperty->type) && !$classProperty->type->allowsNull()) {
                $nulledProperties[] = $classProperty->name;
                continue;
            }

            $instance->{$classProperty->name} = $resolvedValue;
        }

        foreach ($data as $snakeCaseProperty => $value) {
            if (array_key_exists($camelCaseMethod = 'set'.StrHelpers::studly($snakeCaseProperty), $getterAndSetter)) {
                /** @var ClassMethod $classMethod */
                $classMethod = $getterAndSetter[$camelCaseMethod];

                $instance->{$camelCaseMethod}($this->resolveValue($value, $classMethod->params[0] ?? null));
                continue;
            }

            if (array_key_exists($snakeCaseMethod = 'set'.ucfirst($snakeCaseProperty), $getterAndSetter)) {
                /** @var ClassMethod $classMethod */
                $classMethod = $getterAndSetter[$snakeCaseMethod];

                $instance->{$snakeCaseMethod}($this->resolveValue($value, $classMethod->params[0] ?? null));
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
            return (string)$value;
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

        foreach ($publicProperties as $classProperty) {
            /** @var ClassProperty $classProperty */
            $outputField     = $classProperty->jsonProperty?->name ?? StrHelpers::snake($classProperty->name);
            $camelCaseMethod = 'get'.ucfirst($classProperty->name);
            $outputValue     = array_key_exists($camelCaseMethod, $getterAndSetter)
                ? $value->{$camelCaseMethod}()
                : $value->{$classProperty->name};

            $jsonObject[$outputField] = $this->convertOutputValue($outputValue);
        }

        $privateAndProtectedProperties = $this->getClassInfo($className, self::CLASS_PRIVATE_AND_PROTECTED_PROPERTIES);

        foreach ($privateAndProtectedProperties as $classProperty) {
            /** @var ClassProperty $classProperty */
            $outputField     = $classProperty->jsonProperty?->name ?? StrHelpers::snake($classProperty->name);
            $camelCaseMethod = 'get'.ucfirst($classProperty->name);

            if (!array_key_exists($camelCaseMethod, $getterAndSetter)) {
                continue;
            }

            $jsonObject[$outputField] = $this->convertOutputValue($value->{$camelCaseMethod}());
        }

        foreach ($getterAndSetter as $methodName => $classMethod) {
            /** @var ClassMethod $classMethod */
            if (!$classMethod->appendJsonOutput) {
                continue;
            }

            $jsonObject[$classMethod->appendJsonOutput->field] = $this->convertOutputValue($value->{$methodName}());
        }

        return json_encode((object)$jsonObject);
    }

    /**
     * @param  string  $className
     * @param  string  $field
     * @return mixed
     * @throws ReflectionException
     */
    protected function getClassInfo(string $className, string $field): mixed
    {
        if (isset(static::$classInfoCache[$className])) {
            return static::$classInfoCache[$className][$field] ?? null;
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

        static::$classInfoCache[$className] = $classInfo;

        return $classInfo[$field] ?? null;
    }

    protected function getClassProperties(ReflectionClass $reflectionClass, ?int $filter = null): array
    {
        $results = [];

        foreach ($reflectionClass->getProperties($filter) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $results[] = ClassProperty::fromReflectorProperty($property);
        }

        return $results;
    }

    protected function getGetterAndSetterMethods(ReflectionClass $reflectionClass): array
    {
        $getterAndSetters = [];
        $methods          = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'get') || str_starts_with($method->getName(), 'set')) {
                $getterAndSetters[$method->getName()] = ClassMethod::fromReflectionMethod($method);
            }
        }
        return $getterAndSetters;
    }

    protected function resolveValue(mixed $value, ClassProperty $classProperty): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_null($classProperty->type)) {
            return $value;
        }

        if ($classProperty->type instanceof ReflectionNamedType) {
            return $this->resolveNamedType($value, $classProperty);
        }

        if ($classProperty->type instanceof ReflectionUnionType) {
            return $this->resolveUnionType($value, $classProperty);
        }
        // @codeCoverageIgnoreStart
        // Never reached here because ReflectionType has only 2 implementations: ReflectionUnionType, ReflectionNamedType
        return null;
        // @codeCoverageIgnoreEnd
    }

    protected function resolveNamedType(mixed $value, ?ClassProperty $classProperty): mixed
    {
        if ($classProperty->type->isBuiltin()) {
            try {
                if (!$classProperty->arrayProperty || $classProperty->type->getName() != 'array') {
                    settype($value, $classProperty->type->getName());
                    return $value;
                }

                if (!is_array($value)) {
                    return null;
                }

                return array_map(
                    function ($item) use ($classProperty) {
                        $itemProperty = new ClassProperty(
                            name: '',
                            type: new CustomReflectionType(
                                      customName: $classProperty->arrayProperty->type,
                                      isBuiltin: $this->isBuiltinType($classProperty->arrayProperty->type)
                                  ),
                            jsonProperty: null,
                            arrayProperty: null,
                        );

                        return $this->resolveValue($item, $itemProperty);
                    },
                    $value
                );
            } catch (Throwable) {
                return null;
            }
        }

        $propertyClassName = $classProperty->type->getName();

        $encoder = $this->findEncoder($propertyClassName);

        $resolvedValue = null;
        if (!is_null($encoder)) {
            $resolvedValue = $encoder->decode($value, $propertyClassName);
        } elseif (is_array($value)) {
            $resolvedValue = $this->readValue($value, $propertyClassName);
        }

        return $resolvedValue instanceof $propertyClassName ? $resolvedValue : null;
    }

    protected function resolveUnionType(mixed $value, ?ClassProperty $classProperty): mixed
    {
        $exceptions = [];
        $typeNames  = [];

        foreach ($classProperty->type->getTypes() as $type) {
            try {
                $typeNames[]      = $type->getName();
                $subUnionProperty = new ClassProperty(
                    name: $classProperty->name,
                    type: $type,
                    jsonProperty: $classProperty->jsonProperty,
                    arrayProperty: $classProperty->arrayProperty
                );
                if (!is_null($v = $this->resolveValue($value, $subUnionProperty))) {
                    return $v;
                }

                if ($type->getName() === 'null') {
                    return null;
                }
            } catch (Throwable $exception) {
                $exceptions[] = $exception;
            }
        }

        throw CannotConstructUnionTypeException::make(join('|', $typeNames), $exceptions[0]);
    }

    protected function findEncoder(string $className): ?ObjectMapperEncoder
    {
        if (isset(static::$encoderCache[$className])) {
            return static::$encoderCache[$className];
        }

        foreach (static::$encoders as $targetClass => $encoderClass) {
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

    protected function isBuiltinType(string $type): bool
    {
        return array_key_exists(
            $type,
            [
                'int'    => 1,
                'bool'   => 1,
                'float'  => 1,
                'string' => 1,
                'array'  => 1,
                'object' => 1,
            ]
        );
    }
}

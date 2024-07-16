<?php

namespace Ingenerator\MicroFramework;

use UnexpectedValueException;

class FactoryFunction
{

    /**
     * Wraps a callable to ensure that it returns an instance of an expected type
     *
     * So for example:
     *
     *   $stdclass = FactoryFunction::call(fn() => new stdClass, stdClass::class)
     *   // stdclass is *guaranteed* to be an instance of StdClass now, and should be detected
     *   // as such by a modern IDE
     *
     *   // Whereas this will throw an UnexpectedValueException
     *   $stdclass = FactoryFunction::call(fn() => new DateTimeImmutable, stdClass::class)
     *
     * @psalm-template T
     * @psalm-param callable():T $callable
     * @psalm-param class-string<T> $expect_class
     * @psalm-param array $arguments that will be passed to the function
     * @psalm-return T
     *
     */
    public static function call(callable $factory, string $expect_class, array $arguments = []): object
    {
        $object = $factory(...$arguments);

        if ( ! $object instanceof $expect_class) {
            throw new UnexpectedValueException(
                sprintf("Factory for %s incorrectly returned %s", $expect_class, get_debug_type($object))
            );
        }

        return $object;
    }
}

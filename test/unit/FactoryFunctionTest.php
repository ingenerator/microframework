<?php

namespace test\unit;

use DateTimeImmutable;
use Ingenerator\MicroFramework\FactoryFunction;
use Ingenerator\PHPUtils\DateTime\DateString;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

class FactoryFunctionTest extends TestCase
{

    public function test_it_calls_function_and_returns_result()
    {
        $instance = new stdClass();
        $this->assertSame($instance, FactoryFunction::call(fn () => $instance, stdClass::class));
    }

    public function test_it_optionally_calls_function_with_provided_args()
    {
        $result = FactoryFunction::call(
            fn ($date, $timezone) => new DateTimeImmutable($date, $timezone),
            DateTimeImmutable::class,
            [
                '2024-03-01 08:23:20',
                new \DateTimeZone('Europe/Paris'),
            ]
        );
        $this->assertSame('2024-03-01T08:23:20.000000+01:00', DateString::isoMS($result));
    }

    public function test_it_allows_factory_to_return_subclass()
    {
        $instance = new class extends DateTimeImmutable { };
        $this->assertSame($instance, FactoryFunction::call(fn () => $instance, DateTimeImmutable::class));

    }

    public function test_it_throws_if_returned_value_is_not_expected_type()
    {
        $fn = fn () => new stdClass();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Factory for DateTimeImmutable incorrectly returned stdClass');
        FactoryFunction::call($fn, DateTimeImmutable::class);
    }

}
<?php

namespace test\unit;

use DateTimeImmutable;
use Ingenerator\MicroFramework\FactoryFunction;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

class FactoryFunctionTest extends TestCase
{
    public function testItCallsFunctionAndReturnsResult()
    {
        $instance = new stdClass();
        $this->assertSame($instance, FactoryFunction::call(fn () => $instance, stdClass::class));
    }

    public function testItAllowsFactoryToReturnSubclass()
    {
        $instance = new class extends DateTimeImmutable { };
        $this->assertSame($instance, FactoryFunction::call(fn () => $instance, DateTimeImmutable::class));
    }

    public function testItThrowsIfReturnedValueIsNotExpectedType()
    {
        $fn = fn () => new stdClass();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains('Factory for DateTimeImmutable incorrectly returned stdClass');
        FactoryFunction::call($fn, DateTimeImmutable::class);
    }
}

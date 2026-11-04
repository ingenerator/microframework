<?php

declare(strict_types=1);

namespace test\unit;

use DateTimeImmutable;
use Ingenerator\MicroFramework\FactoryFunction;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

final class FactoryFunctionTest extends TestCase
{
    public function testItCallsFunctionAndReturnsResult(): void
    {
        $instance = new stdClass();
        $this->assertSame($instance, FactoryFunction::call(fn (): stdClass => $instance, stdClass::class));
    }

    public function testItAllowsFactoryToReturnSubclass(): void
    {
        $instance = new class extends DateTimeImmutable { };
        $this->assertSame($instance, FactoryFunction::call(fn (): object => $instance, DateTimeImmutable::class));
    }

    public function testItThrowsIfReturnedValueIsNotExpectedType(): void
    {
        $fn = fn (): stdClass => new stdClass();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains('Factory for DateTimeImmutable incorrectly returned stdClass');
        FactoryFunction::call($fn, DateTimeImmutable::class);
    }
}

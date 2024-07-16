<?php

namespace test\integration;

use Ingenerator\MicroFramework\DefaultStackdriverLoggerProvider;
use Ingenerator\MicroFramework\LoggerProvider;
use Ingenerator\MicroFramework\StackdriverRequestLogger;
use Ingenerator\PHPUtils\Logging\StackdriverApplicationLogger;
use PHPUnit\Framework\TestCase;

class DefaultStackdriverLoggerProviderTest extends TestCase
{
    public function test_it_is_initialisable_logger_provider()
    {
        $this->assertInstanceOf(LoggerProvider::class, $this->newSubject());
    }

    public function test_it_provides_expected_logger_and_request_logger()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf(StackdriverApplicationLogger::class, $subject->getLogger());
        $this->assertInstanceOf(StackdriverRequestLogger::class, $subject->getRequestLogger());
        $this->assertSame($subject->getLogger(), $subject->getLogger(), 'Logger is a singleton');
    }

    private function newSubject(): DefaultStackdriverLoggerProvider
    {
        // Note - StackdriverApplicationLogger is a global singleton in its own right and does not expose a reset or
        // similar method. It's therefore not possible to guarantee that this test would be the first to initialise it,
        // so we can't test the metadata here (and anyway, there's a lot of metadata beyond the service name and version
        // e.g. the request trace / http context etc). So I'm leaving that behaviour for blackbox testing.
        return new DefaultStackdriverLoggerProvider(
            'any-svc',
            '/path/to/any/file',
        );
    }


}
<?php

namespace test\unit;

use Ingenerator\MicroFramework\RequestLogger;
use Ingenerator\MicroFramework\StackdriverRequestLogger;
use Ingenerator\PHPUtils\Logging\StackdriverApplicationLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class StackdriverRequestLoggerTest extends TestCase
{
    public function test_it_is_initialisable_request_logger()
    {
        $this->assertInstanceOf(RequestLogger::class, $this->newSubject());
    }

    public function test_its_log_request_logs_an_exception_and_returns_on_unknown_logger_type()
    {
        $logger = new TestLogger();
        $this->newSubject()->logRequest($logger, 0);
        $this->assertCount(1, $logger->records);
        $this->assertTrue($logger->hasErrorThatContains('Could not log request details - got a '.$logger::class));
    }

    public static function provider_calculate_request_start(): array
    {
        return [
            '64-bit with int hrtime' => [
                'hrtime_start' => 2993548241208,
                'hrtime_now' => 2993548244583,
                'microtime_now' => 1709456239.781800000,
                'expect_start_time' => 1709456239.781796625,
            ],
            '32-bit with float hrtime (and longer latency)' => [
                'hrtime_start' => 2992348244583.0,
                'hrtime_now' => 2993548244583.0,
                'microtime_now' => 1709456239.7818,
                'expect_start_time' => 1709456238.5818,
            ],
        ];
    }

    /**
     * @dataProvider provider_calculate_request_start
     */
    public function test_it_calculates_request_start_time_from_elapsed_high_res_time(
        int|float $hrtime_start,
        int|float $hrtime_now,
        float $microtime_now,
        float $expect_start_time,
    ) {
        $subject = $this->stubSubjectWithHrTimeAndMicrotime($hrtime_now, $microtime_now);
        $logger = $this->makeLoggerSpy();

        $subject->logRequest($logger, $hrtime_start);

        $this->assertSame(
            ['server' => $_SERVER, 'request_start_time' => $expect_start_time],
            $logger->request_log_calls
        );
    }

    public function test_it_logs_sane_value_for_request_time_with_default_implementation()
    {
        $actual_start_microtime = microtime(true);
        $start_hrtime = hrtime(true);

        $subject = $this->newSubject();
        $logger = $this->makeLoggerSpy();

        $subject->logRequest($logger, $start_hrtime);

        /*
         * Assuming no clock changes while the tests are running, we should be able to fairly accurately guess what the
         * calculated value should be, compared to the time we captured at the start of our test:
         * - We captured the microtime before the hrtime, so the value should always be no earlier than that
         * - And we captured the hrtime on the very next instruction, so even on a slow machine that shouldm't be more
         *   than 1ms later.
         */
        $this->assertGreaterThanOrEqual(
            $actual_start_microtime,
            $logger->request_log_calls['request_start_time'],
            'Calculated start time should be no earlier than the actual start time'
        );
        $this->assertLessThanOrEqual(
            $actual_start_microtime + 0.001,
            $logger->request_log_calls['request_start_time'],
            'Calculated start time should up to 1ms after the start of the test'
        );
    }


    public function stubSubjectWithHrTimeAndMicrotime(
        float|int $hrtime_now,
        float $microtime_now
    ): StackdriverRequestLogger {
        /*
         * As we don't currently have a defined mock for hrtime, and it's not possible to use a callable as a default
         * constructor argument, I decided to stub them directly inline in the test subject.
         */
        $subject = new class extends StackdriverRequestLogger {
            public int|float $hr_time;

            public float $microtime;

            protected function getHrTime(): int|float
            {
                return $this->hr_time;
            }

            protected function getMicrotime(): float
            {
                return $this->microtime;
            }
        };
        $subject->hr_time = $hrtime_now;
        $subject->microtime = $microtime_now;

        return $subject;
    }

    public function makeLoggerSpy(): StackdriverApplicationLogger
    {
        return new class extends StackdriverApplicationLogger {
            public readonly array $request_log_calls;

            public function __construct() { }

            public function logRequest(?array $server, ?float $request_start_time = null): void
            {
                $this->request_log_calls = get_defined_vars();
            }
        };
    }

    private function newSubject(): StackdriverRequestLogger
    {
        return new StackdriverRequestLogger();
    }


}
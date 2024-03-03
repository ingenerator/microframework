<?php

namespace Ingenerator\MicroFramework;

use Closure;
use Ingenerator\PHPUtils\Logging\StackdriverApplicationLogger;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Logs requests using the functionality built into StackdriverApplicationLogger
 */
class StackdriverRequestLogger implements RequestLogger
{
    public function logRequest(LoggerInterface $logger, float|int $start_hr_time): void
    {
        if ( ! $logger instanceof StackdriverApplicationLogger) {
            // This should never happen in any normal usecase, only if there's been an error in the way the
            // dependencies are wired up - e.g. someone has made a LoggerProvider that provides a
            // StackdriverRequestLogger with some other type of LoggerInterface. Just report it as an error
            // but don't interrupt request processing.
            $e = new InvalidArgumentException(
                sprintf(
                    "Could not log request details - got a %s instead of a %s",
                    $logger::class,
                    StackdriverApplicationLogger::class
                )
            );
            $logger->error($e->getMessage(), ['exception' => $e]);

            return;
        }

        $elapsed_nanos = $this->getHrTime() - $start_hr_time;
        $request_start_time = $this->getMicrotime() - ($elapsed_nanos / 1_000_000_000);

        $logger->logRequest($_SERVER, $request_start_time);
    }

    protected function getHrTime(): int|float
    {
        return hrtime(true);
    }

    protected function getMicrotime(): float
    {
        return microtime(true);
    }

}

<?php

namespace Ingenerator\MicroFramework;

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
                    get_debug_type($logger),
                    StackdriverApplicationLogger::class
                )
            );
            $logger->error($e->getMessage(), ['exception' => $e]);

            return;
        }

        $elapsed_nanos = hrtime(true) - $start_hr_time;
        $request_start_time = microtime(true) - ($elapsed_nanos / 1_000_000_000);

        $logger->logRequest($_SERVER, $request_start_time);
    }

}

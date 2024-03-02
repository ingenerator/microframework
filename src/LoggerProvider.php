<?php

namespace Ingenerator\MicroFramework;

use Psr\Log\LoggerInterface;

/**
 * The LoggerProvider is responsible for providing a suitable PSR Logger and a compatible RequestLogger
 *
 * It should be essentially impossible for these services to produce runtime errors under any
 * conditions. If they could produce errors, these must be handled & ideally logged internally.
 * Logging to a stdout / stderr / syslog stream for an external log aggregation stream to pick
 * up is obviously preferred to any runtime network-based log forwarding.
 */
interface LoggerProvider
{
    /**
     * Get a usable LoggerInterface - either by creating, or e.g. accessing from a DI container / singleton / factory etc
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface;

    public function getRequestLogger(): RequestLogger;
}

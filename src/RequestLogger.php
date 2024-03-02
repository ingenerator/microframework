<?php

namespace Ingenerator\MicroFramework;

use Psr\Log\LoggerInterface;

/**
 * Responsible for logging requests on completion
 *
 * This allows for a custom request log decorated with the same metadata etc as any entries in
 * the event log. It is called by MicroFramework at the very end of request execution, after the
 * response has been rendered to the client and the http_status_code is known.
 */
interface RequestLogger
{
    /**
     * @param LoggerInterface $logger destination to log to
     * @param int|float $start_hr_time the high-resolution time that the PHP execution began - useful for calculating latency
     *
     * @return void
     */
    public function logRequest(LoggerInterface $logger, int|float $start_hr_time): void;
}

<?php

namespace Ingenerator\MicroFramework;

use Ingenerator\PHPUtils\Logging\DefaultLogMetadata;
use Ingenerator\PHPUtils\Logging\StackdriverApplicationLogger;
use Psr\Log\LoggerInterface;

class DefaultStackdriverLoggerProvider implements LoggerProvider
{

    public function __construct(
        protected readonly string $service_name,
        protected readonly string $version_file_path,
        protected readonly string $log_destination = 'php://stdout',
    ) {

    }

    public function getLogger(): LoggerInterface
    {
        if ( ! StackdriverApplicationLogger::isInitialised()) {
            StackdriverApplicationLogger::initialise(
                fn () => new StackdriverApplicationLogger(
                    $this->log_destination,
                    ...$this->getMetadataProviders(),
                )
            );
        }

        return StackdriverApplicationLogger::instance();
    }

    protected function getMetadataProviders(): array
    {
        return [
            DefaultLogMetadata::serviceContext($this->service_name, $this->version_file_path),
            DefaultLogMetadata::requestTrace($_SERVER),
            DefaultLogMetadata::httpContext($_SERVER),
        ];
    }

    public function getRequestLogger(): RequestLogger
    {
        return new StackdriverRequestLogger();
    }
}

<?php

namespace Ingenerator\MicroFramework;

use Ingenerator\PHPUtils\Logging\DefaultLogMetadata;
use Ingenerator\PHPUtils\Logging\LogMetadataProvider;
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
        if (!StackdriverApplicationLogger::isInitialised()) {
            StackdriverApplicationLogger::initialise(
                fn (): StackdriverApplicationLogger => new StackdriverApplicationLogger(
                    $this->log_destination,
                    ...$this->getMetadataProviders(),
                )
            );
        }

        return StackdriverApplicationLogger::instance();
    }

    /**
     * @return list<LogMetadataProvider|array<string,mixed>>
     */
    protected function getMetadataProviders(): array
    {
        return [
            DefaultLogMetadata::serviceContext($this->service_name, $this->version_file_path),
            DefaultLogMetadata::requestTrace(),
            DefaultLogMetadata::httpContext(),
        ];
    }

    public function getRequestLogger(): RequestLogger
    {
        return new StackdriverRequestLogger();
    }
}

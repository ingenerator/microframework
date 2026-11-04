<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class HandlerFactoryArgs
{
    public function __construct(
        public LoggerInterface $logger,
        public ServerRequestInterface $request,
    ) {
    }
}

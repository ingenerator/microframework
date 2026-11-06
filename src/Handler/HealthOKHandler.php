<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework\Handler;

use Ingenerator\MicroFramework\Const\RequestMethod;
use Ingenerator\MicroFramework\Handler\Trait\RequestMethodGuardTrait;
use Ingenerator\MicroFramework\RequestHandler;
use Ingenerator\MicroFramework\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple handler that just returns a 200 response with text "OK" on any GET request.
 *
 * Designed for e.g. use servicing a healthcheck endpoint, or when bootstrapping a new
 * project before actual handlers are implemented.
 */
final class HealthOKHandler implements RequestHandler
{
    use RequestMethodGuardTrait;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->guardRequestMethod($request, RequestMethod::GET)
            ?? ResponseFactory::text('OK', 200);
    }
}

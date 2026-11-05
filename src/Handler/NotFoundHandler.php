<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework\Handler;

use Ingenerator\MicroFramework\RequestHandler;
use Ingenerator\MicroFramework\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Just returns a 404 to all requests.
 *
 * Useful if your handler factory function returns different handlers for different
 * requests / paths so that you need a fallback handler for anything that doesn't
 * match.
 */
final class NotFoundHandler implements RequestHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ResponseFactory::text('Not Found', 404);
    }
}

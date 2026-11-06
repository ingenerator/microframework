<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework\Handler\Trait;

use Ingenerator\MicroFramework\Const\RequestMethod;
use Ingenerator\MicroFramework\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Ensures that the incoming request is an allowed method, returns 405 if not.
 *
 * Usage:
 *
 *   class Something implements RequestHandler {
 *       use RequestMethodGuardTrait;
 *
 *       public function handle(ServerRequestInterface $request): ResponseInterface {
 *           return $this->guardRequestMethod($request, RequestMethod::GET)
 *              ?? ResponseFactory::text('Great! This is an allowed method!');
 *       }
 *   }
 */
trait RequestMethodGuardTrait
{
    private function guardRequestMethod(ServerRequestInterface $request, RequestMethod $allowedMethod, RequestMethod ...$allowedMethods): ?ResponseInterface
    {
        array_unshift($allowedMethods, $allowedMethod);
        $allowedMethodStrs = array_map(fn (RequestMethod $method) => $method->value, $allowedMethods);

        if (in_array(strtoupper($request->getMethod()), $allowedMethodStrs)) {
            return null;
        }

        return ResponseFactory::text('Method not allowed', status: 405, headers: ['Allow' => implode(',', $allowedMethodStrs)]);
    }
}

<?php

namespace Ingenerator\MicroFramework;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The RequestHandler is the main implementation of the function / app code
 *
 * As far as MicroFramework is concerned, there is only one RequestHandler class / instance - there
 * is no (and never will be) built-in support for routing etc. If a function needs to route / handle
 * different types of requests, the recommended approach is to implement e.g. a RoutingRequestHandler
 * that does initial parsing and perhaps authentication of the request, then delegates processing to
 * an appropriate specific RequestHandler implementation.
 */
interface RequestHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface;
}

<?php

declare(strict_types=1);

namespace test\unit\Handler;

use GuzzleHttp\Psr7\ServerRequest;
use Ingenerator\MicroFramework\Handler\NotFoundHandler;
use Ingenerator\MicroFramework\RequestHandler;
use PHPUnit\Framework\TestCase;
use test\unit\AssertResponse;

final class NotFoundHandlerTest extends TestCase
{
    public function testItIsInitialisableRequestHandler(): void
    {
        $this->assertInstanceOf(RequestHandler::class, $this->newSubject());
    }

    public function testItReturns404NotFoundResponse(): void
    {
        $request = new ServerRequest('GET', '/any-path');
        $response = $this->newSubject()->handle($request);

        AssertResponse::isExactly(
            404,
            'Not Found',
            ['Content-Type' => ['text/plain']],
            $response
        );
    }

    private function newSubject(): NotFoundHandler
    {
        return new NotFoundHandler();
    }
}

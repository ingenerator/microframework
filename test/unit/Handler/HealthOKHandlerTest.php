<?php

declare(strict_types=1);

namespace test\unit\Handler;

use GuzzleHttp\Psr7\ServerRequest;
use Ingenerator\MicroFramework\Handler\HealthOKHandler;
use Ingenerator\MicroFramework\RequestHandler;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use test\unit\AssertResponse;

final class HealthOKHandlerTest extends TestCase
{
    public function testItIsInitialisableRequestHandler(): void
    {
        $this->assertInstanceOf(RequestHandler::class, $this->newSubject());
    }

    public function testItReturns200OKForGetRequest(): void
    {
        $request = new ServerRequest('GET', '/healthz');

        $response = $this->newSubject()->handle($request);
        AssertResponse::isExactly(200, 'OK', ['Content-Type' => ['text/plain']], $response);
    }

    /**
     * @return Iterator<int, array{string}>
     */
    public static function provider_disallowed_methods(): Iterator
    {
        // Two is enough to prove the point
        yield ['POST'];
        yield ['HEAD'];
    }

    #[DataProvider('provider_disallowed_methods')]
    public function testItReturns405MethodNotAllowedForNonGetRequests(string $method): void
    {
        $request = new ServerRequest($method, '/healthz');
        $response = $this->newSubject()->handle($request);

        AssertResponse::isExactly(
            405,
            'Method not allowed',
            [
                'Content-Type' => ['text/plain'],
                'Allow' => ['GET'],
            ],
            $response
        );
    }

    private function newSubject(): HealthOKHandler
    {
        return new HealthOKHandler();
    }
}

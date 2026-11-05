<?php

declare(strict_types=1);

namespace test\unit;

use Ingenerator\MicroFramework\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase
{
    public function testItCreatesPlainTextResponseWithDefaults(): void
    {
        $response = ResponseFactory::text('Hello world');
        AssertResponse::isExactly(
            200,
            'Hello world',
            ['Content-Type' => ['text/plain']],
            $response
        );
    }

    public function testItCreatesPlainTextResponseWithCustomStatus(): void
    {
        $response = ResponseFactory::text('Created successfully', 201);
        AssertResponse::isExactly(
            201,
            'Created successfully',
            ['Content-Type' => ['text/plain']],
            $response
        );
    }

    public function testItCreatesPlainTextResponseWithCustomHeaders(): void
    {
        $response = ResponseFactory::text('OK', 200, ['X-Custom-Header' => 'custom-value']);
        AssertResponse::isExactly(
            200,
            'OK',
            ['Content-Type' => ['text/plain'], 'X-Custom-Header' => ['custom-value']],
            $response
        );
    }

    public function testItsTextAllowsOverridingContentTypeHeader(): void
    {
        $response = ResponseFactory::text('custom', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        AssertResponse::isExactly(
            200,
            'custom',
            ['Content-Type' => ['text/plain; charset=utf-8']],
            $response
        );
    }
}

<?php

declare(strict_types=1);

namespace test\unit;

use Ingenerator\MicroFramework\ResponseFactory;
use JsonException;
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

    public function testItCreatesJsonResponseWithDefaults(): void
    {
        $response = ResponseFactory::json(['message' => 'Hello world']);
        AssertResponse::isExactly(
            200,
            '{"message":"Hello world"}',
            ['Content-Type' => ['application/json']],
            $response
        );
    }

    public function testItCreatesJsonResponseWithCustomStatus(): void
    {
        $response = ResponseFactory::json(['id' => 123], 201);
        AssertResponse::isExactly(
            201,
            '{"id":123}',
            ['Content-Type' => ['application/json']],
            $response
        );
    }

    public function testItCreatesJsonResponseWithCustomHeaders(): void
    {
        $response = ResponseFactory::json(['ok' => true], 200, ['X-Custom-Header' => 'custom-value']);
        AssertResponse::isExactly(
            200,
            '{"ok":true}',
            ['Content-Type' => ['application/json'], 'X-Custom-Header' => ['custom-value']],
            $response
        );
    }

    public function testItsJsonAllowsOverridingContentTypeHeader(): void
    {
        $response = ResponseFactory::json(['error' => 'Not Found'], 404, ['Content-Type' => 'application/problem+json']);
        AssertResponse::isExactly(
            404,
            '{"error":"Not Found"}',
            ['Content-Type' => ['application/problem+json']],
            $response
        );
    }

    public function testItDoesNotEscapeSlashesInJsonResponse(): void
    {
        $response = ResponseFactory::json(['url' => 'https://example.com/api/v1/resource']);
        AssertResponse::isExactly(
            200,
            '{"url":"https://example.com/api/v1/resource"}',
            ['Content-Type' => ['application/json']],
            $response
        );
    }

    public function testItThrowsJsonExceptionOnJsonError(): void
    {
        $this->expectException(JsonException::class);
        ResponseFactory::json(NAN);
    }
}

<?php

declare(strict_types=1);

namespace test\blackbox;

use Ingenerator\PHPUtils\StringEncoding\JSON;
use PHPUnit\Framework\Attributes\DataProvider;

class RequestParsingTest extends BaseBlackboxTestCase
{
    private static string $handler_url;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response(
                        200, 
                        body: json_encode([
                            'method' => $request->getMethod(),
                            'body' => $request->getBody()->getContents(),
                            'parsed_body' => $request->getParsedBody(),
                            'headers' => $request->getHeaders(),
                        ]),
                    );
                }
            }
            PHP,
        );
    }

    public static function provider_request_bodies(): array
    {
        return [
            'no body' => [
                'GET',
                [],
                [
                    'method' => 'GET',
                    'body' => '',
                    'parsed_body' => [],
                ],
            ],
            'POST with no body' => [
                'POST',
                [],
                [
                    'method' => 'POST',
                    'body' => '',
                    'parsed_body' => [],
                ],
            ],
            'POST with form_params' => [
                'POST',
                [
                    'form_params' => ['foo' => 'bar', 'boo' => ['bax', 3]],
                ],
                [
                    'method' => 'POST',
                    'body' => 'foo=bar&boo%5B0%5D=bax&boo%5B1%5D=3',
                    'parsed_body' => ['foo' => 'bar', 'boo' => ['bax', '3']],
                ],
            ],
            'POST with JSON (not natively parsed)' => [
                'POST',
                [
                    'json' => ['foo' => 'bar', 'boo' => ['bax', 3]],
                ],
                [
                    'method' => 'POST',
                    'body' => '{"foo":"bar","boo":["bax",3]}',
                    'parsed_body' => [],
                ],
            ],
            'PUT with no body' => [
                'PUT',
                [],
                [
                    'method' => 'PUT',
                    'body' => '',
                    'parsed_body' => [],
                ],
            ],
            'PUT with form_params (not natively parsed)' => [
                'PUT',
                [
                    'form_params' => ['foo' => 'bar', 'boo' => ['bax', 3]],
                ],
                [
                    'method' => 'PUT',
                    'body' => 'foo=bar&boo%5B0%5D=bax&boo%5B1%5D=3',
                    'parsed_body' => [],
                ],
            ],
            'PUT with JSON (not natively parsed)' => [
                'PUT',
                [
                    'json' => ['foo' => 'bar', 'boo' => ['bax', 3]],
                ],
                [
                    'method' => 'PUT',
                    'body' => '{"foo":"bar","boo":["bax",3]}',
                    'parsed_body' => [],
                ],
            ],
        ];
    }

    #[DataProvider('provider_request_bodies')]
    public function testItCanReceiveIncomingRequestBodiesInExpectedFormats(string $method, array $options, array $expect): void
    {
        $response = $this->guzzle->request($method, self::$handler_url, $options);
        $received_payload = JSON::decodeArray($response->getBody()->getContents());
        unset($received_payload['headers']);
        $this->assertSame($expect, $received_payload);
    }

    public static function provider_request_headers(): array
    {
        return [
            'no custom headers' => [
                [],
                [
                    'Host' => ['test_subject'],
                    'User-Agent' => [self::class],
                ],
            ],
            'with custom headers' => [
                [
                    'User-Agent' => 'Us and Me',
                    'Content-Type' => 'application/json',
                ],
                [
                    'Host' => ['test_subject'],
                    'User-Agent' => ['Us and Me'],
                    'Content-Type' => ['application/json'],
                ],
            ],
            'with custom repeated headers' => [
                [
                    'X-My-Header' => ['test1', 'test2'],
                ],
                [
                    'Host' => ['test_subject'],
                    'User-Agent' => [self::class],
                    'X-My-Header' => ['test1, test2'],
                ],
            ],
        ];
    }

    #[DataProvider('provider_request_headers')]
    public function testItCanReceiveIncomingHeaders(array $send_headers, array $expect): void
    {
        $response = $this->guzzle->get(self::$handler_url, ['headers' => [
            'User-Agent' => self::class,
            ...$send_headers,
        ]]);
        $received_payload = JSON::decodeArray($response->getBody()->getContents());
        $this->assertSame($expect, $received_payload['headers']);
    }

    public function testItCanDetectIncomingUrlsWhenRewriting(): void
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response(
                        200, 
                        body: (string) $request->getUri(),
                    );
                }
            }
            PHP,
        );

        $this->assertResponseMatches(
            200,
            'http://test_subject'.$handler_url,
            $this->guzzle->get($handler_url),
        );
    }
}

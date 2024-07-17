<?php

namespace test\blackbox;

use GuzzleHttp\RequestOptions;

class HttpHeadersTest extends BaseBlackboxTestCase
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
                    $body = \Ingenerator\PHPUtils\StringEncoding\JSON::decodeArray($request->getBody()->getContents());
                    return new Response(202, headers: $body, body: 'Headers from POST');
                }
            }
            PHP,
        );
    }

    public static function provider_headers(): array
    {
        return [
            'no headers specified, just the defaults' => [
                [],
                [
                    'Content-Length' => ['17'],
                    'Content-Type' => ['text/html; charset=UTF-8'],
                ],
            ],
            'custom content type' => [
                ['Content-Type' => 'application/json'],
                [
                    'Content-Length' => ['17'],
                    'Content-Type' => ['application/json'],
                ],
            ],
            'custom content type in different case' => [
                ['content-type' => 'application/json'],
                [
                    'Content-Length' => ['17'],
                    'Content-Type' => ['application/json'],
                ],
            ],
            'custom headers' => [
                [
                    'Content-Type' => 'application/json',
                    'X-My-Custom' => 'Value',
                ],
                [
                    'Content-Length' => ['17'],
                    'Content-Type' => ['application/json'],
                    'X-My-Custom' => ['Value'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provider_headers
     */
    public function test_it_returns_expected_headers(array $specified_headers, array $expected_headers)
    {
        $response = $this->guzzle->post(
            self::$handler_url,
            [RequestOptions::JSON => $specified_headers],
        );

        $this->assertResponseMatches(202, 'Headers from POST', $response);
        $all_headers = $response->getHeaders();
        // These are set by Apache / PHP depending on config and we can't override them
        unset($all_headers['Server']);
        unset($all_headers['Date']);
        unset($all_headers['X-Powered-By']);
        ksort($all_headers);
        ksort($expected_headers);
        $this->assertSame($expected_headers, $all_headers);
    }

}

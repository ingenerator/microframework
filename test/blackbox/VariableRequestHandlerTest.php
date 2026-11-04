<?php

declare(strict_types=1);

namespace test\blackbox;

use PHPUnit\Framework\Attributes\TestWith;

final class VariableRequestHandlerTest extends BaseBlackboxTestCase
{
    private static string $handler_url;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (HandlerFactoryArgs $args) => match ($args->request->getMethod()) {
                'POST' => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new Response(201, body: 'You made a new thing');
                    }
                },
                'GET' => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new Response(200, body: 'You got a thing');
                    }
                } 
            }
            PHP,
        );
    }

    #[TestWith(['post', 201, 'You made a new thing'])]
    #[TestWith(['get', 200, 'You got a thing'])]
    public function testItPassesRequestToHandlerFactoryForRouting(string $method, int $expectedStatus, string $expectedBody): void
    {
        $this->assertResponseMatches($expectedStatus, $expectedBody, $this->guzzle->request($method, self::$handler_url));
    }
}

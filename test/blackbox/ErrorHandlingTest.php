<?php

declare(strict_types=1);

namespace test\blackbox;

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;

final class ErrorHandlingTest extends BaseBlackboxTestCase
{
    /**
     * @return Iterator<string, list<string>>
     */
    public static function provider_error_handling(): Iterator
    {
        yield 'exception in handler factory' => [
            <<<'PHP'
                fn () => throw new RuntimeException('No handler')
                PHP,
        ];
        yield 'error in handler factory' => [
            <<<'PHP'
                fn () => ['no'=> 'thing']['undefined']
                PHP,
        ];
        yield 'factory does not return a handler instance' => [
            <<<'PHP'
                fn () => new class extends stdClass {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new Response(200, body: 'Should not get here, it is the wrong instance type');
                    }
                }
                PHP,
        ];
        yield 'factory references undefined class' => [
            <<<'PHP'
                fn () => new \My\Undefined\RequestHandlerClass()
                PHP,
        ];
        yield 'uncaught exception during handler' => [
            <<<'PHP'
                fn () => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        throw new \RuntimeException('You broke it');
                    }
                }
                PHP,
        ];
        yield 'php error in handler' => [
            <<<'PHP'
                fn () => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        $map = ['no' => 'thing'];
                        return new Response(200, body: $map['wrong']);
                    }
                }
                PHP,
        ];
        yield 'TypeError in handler' => [
            <<<'PHP'
                fn () => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return $this->doThings($request);
                    }
                    
                    private function doThings(string $uhoh): ResponseInterface {
                        return $uhoh.'ho';
                    }
                }
                PHP,
        ];
    }

    #[DataProvider('provider_error_handling')]
    public function testItCapturesErrorsAndExceptions(string $handler_code): void
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap($handler_code);

        $this->assertResponseMatches(
            500,
            "Unexpected fatal error\n",
            $this->guzzle->get($handler_url),
        );
    }
}

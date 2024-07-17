<?php

namespace test\blackbox;

class ErrorHandlingTest extends BaseBlackboxTestCase
{

    public static function provider_error_handling(): array
    {
        return [
            'exception in handler factory' => [
                <<<'PHP'
                fn () => throw new RuntimeException('No handler')
                PHP,
            ],
            'error in handler factory' => [
                <<<'PHP'
                fn () => ['no'=> 'thing']['undefined']
                PHP,
            ],
            'factory does not return a handler instance' => [
                <<<'PHP'
                fn (LoggerInterface $logger) => new class extends stdClass {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new Response(200, body: 'Should not get here, it is the wrong instance type');
                    }
                }
                PHP,
            ],
            'factory references undefined class' => [
                <<<'PHP'
                fn (LoggerInterface $logger) => new \My\Undefined\RequestHandlerClass()
                PHP,
            ],
            'uncaught exception during handler' => [
                <<<'PHP'
                fn (LoggerInterface $logger) => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        throw new \RuntimeException('You broke it');
                    }
                }
                PHP,
            ],
            'php error in handler' => [
                <<<'PHP'
                fn (LoggerInterface $logger) => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        $map = ['no' => 'thing'];
                        return new Response(200, body: $map['wrong']);
                    }
                }
                PHP,
            ],
            'TypeError in handler' => [
                <<<'PHP'
                fn (LoggerInterface $logger) => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return $this->doThings($request);
                    }
                    
                    private function doThings(string $uhoh): ResponseInterface {
                        return $uhoh.'ho';
                    }
                }
                PHP,
            ],
        ];
    }

    /**
     * @dataProvider provider_error_handling
     */
    public function test_it_captures_errors_and_exceptions(string $handler_code)
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap($handler_code);

        $this->assertResponseMatches(
            500,
            "Unexpected fatal error\n",
            $this->guzzle->get($handler_url),
        );
    }

}

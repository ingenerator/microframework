<?php

namespace blackbox;

use test\blackbox\BaseBlackboxTestCase;

class OutputBufferingTest extends BaseBlackboxTestCase
{

    public function test_it_handles_error_even_if_output_began_before_framework()
    {
        $handler_url = $this->provisionDynamicHandler(
            <<<'PHP'
            <?php
            use GuzzleHttp\Psr7\Response;
            use Ingenerator\MicroFramework\DefaultStackdriverLoggerProvider;
            use Ingenerator\MicroFramework\MicroFramework;
            use Ingenerator\MicroFramework\RequestHandler;
            use Psr\Http\Message\ResponseInterface;
            use Psr\Http\Message\ServerRequestInterface;
            use Psr\Log\LoggerInterface;

            echo "I am a badly behaving dependency\n";
            require_once('/var/www/vendor/autoload.php');
            
            (new MicroFramework)->execute(
                logger_provider_factory: fn () => new DefaultStackdriverLoggerProvider(
                    service_name: 'ig-micro',
                    version_file_path: '/var/www/version.php',
                ),
                handler_factory: fn() => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new Response(200, body: 'OK');
                    }
                },
            );
            PHP,
        );

        $this->assertResponseMatches(
            200,
            "I am a badly behaving dependency\nUnexpected fatal error\n",
            $this->guzzle->get($handler_url),
        );
    }

    public function test_it_returns_error_without_output_if_any_code_writes_output_during_request_handling()
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    echo "You did something";
                    return new Response(200, body: 'You did not');
                }
            }
            PHP,
        );

        $this->assertResponseMatches(
            500,
            "Unexpected fatal error\n",
            $this->guzzle->get($handler_url),
        );
    }

    public function test_it_treats_as_error_if_user_leaves_output_buffers_open()
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    ob_start();
                    echo "You did something";
                    return new Response(200, body: 'You didn\'t');
                }
            }
            PHP,
        );

        $this->assertResponseMatches(
            500,
            "Unexpected fatal error\n",
            $this->guzzle->get($handler_url),
        );
    }

    public function test_it_treats_as_error_if_user_flushes_output_buffers()
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    ob_start();
                    echo "You did something";
                    $response = ob_get_flush();
                    return new Response(200, body: 'buffered='.$response);
                }
            }
            PHP,
        );

        $this->assertResponseMatches(
            500,
            "Unexpected fatal error\n",
            $this->guzzle->get($handler_url),
        );
    }

    public function test_it_does_not_treat_as_error_if_user_handles_output_buffering_correctly()
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    ob_start();
                    echo "You did something";
                    return new Response(200, body: ob_get_clean());
                }
            }
            PHP,
        );

        $this->assertResponseMatches(
            200,
            'You did something',
            $this->guzzle->get($handler_url),
        );
    }

    public function test_it_does_not_treat_as_error_if_user_handles_output_buffering_correctly_without_rendering()
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    ob_start();
                    echo "You did something";
                    ob_end_clean();
                    return new Response(200, body: 'Buffered');
                }
            }
            PHP,
        );

        $this->assertResponseMatches(
            200,
            'Buffered',
            $this->guzzle->get($handler_url),
        );
    }

}

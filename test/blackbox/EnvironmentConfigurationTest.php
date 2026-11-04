<?php

namespace test\blackbox;

class EnvironmentConfigurationTest extends BaseBlackboxTestCase
{

    public function test_can_configure_custom_timezone_in_bootstrap()
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

            require_once('/var/www/vendor/autoload.php');
            
            (new MicroFramework)->execute(
                logger_provider_factory: fn () => new DefaultStackdriverLoggerProvider(
                    service_name: 'ig-micro',
                    version_file_path: '/var/www/version.php',
                ),
                default_timezone: 'Europe/Paris',
                handler_factory: fn () => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface {
                        return new Response(200, body: date_default_timezone_get());
                    } 
                },
            );
            PHP,
        );

        $this->assertResponseMatches(
            200,
            'Europe/Paris',
            $this->guzzle->get($handler_url),
        );
    }

    public function test_can_configure_locale_in_bootstrap()
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

            require_once('/var/www/vendor/autoload.php');
            
            (new MicroFramework)->execute(
                logger_provider_factory: fn () => new DefaultStackdriverLoggerProvider(
                    service_name: 'ig-micro',
                    version_file_path: '/var/www/version.php',
                ),
                locale: 'fr_FR.utf8',
                handler_factory: fn () => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface {
                        return new Response(200, body: setlocale(LC_ALL, 0));
                    } 
                },
            );
            PHP,
        );

        $this->assertResponseMatches(
            200,
            'fr_FR.utf8',
            $this->guzzle->get($handler_url),
        );
    }

}

<?php

namespace test\blackbox;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

class BaseBlackboxTestCase extends TestCase
{
    protected static string $dynamic_handler_path = __DIR__.'/implementation/htdocs/dynamic';
    protected static Filesystem $filesystem;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$filesystem ??= new Filesystem();
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->guzzle = new Client([
            'base_uri' => $this->getTestSubjectBaseUrl(),
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);
    }

    protected Client $guzzle;

    private function getTestSubjectBaseUrl(): string
    {
        $subject_url = getenv('TEST_SUBJECT_BASE_URI');
        Assert::assertNotEmpty($subject_url, 'Expect an environment variable TEST_SUBJECT_BASE_URI');
        return $subject_url;
    }


    protected function assertResponseMatches(
        int               $expect_code,
        string            $expect_body,
        ResponseInterface $response
    ): void
    {
        $this->assertSame(
            [
                'status' => $expect_code,
                'body' => $expect_body,
            ],
            [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
            ],
        );
    }

    protected static function provisionDynamicHandlerFactoryWithDefaultBootstrap(string $handler_factory_implementation): string
    {
        $template = <<<'PHP'
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
                handler_factory: HANDLER_FACTORY_IMPLEMENTATION,
            );
            PHP;

        return self::provisionDynamicHandler(
            strtr($template, ['HANDLER_FACTORY_IMPLEMENTATION' => $handler_factory_implementation]),
        );
    }

    protected static function provisionDynamicHandler(string $code): string
    {
        $id = uniqid();
        $file_path = self::$dynamic_handler_path.'/'.$id;
        self::$filesystem->mkdir($file_path);
        self::$filesystem->dumpFile($file_path.'/index.php', $code);

        return '/dynamic/'.$id.'/';
    }

}

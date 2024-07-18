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
    protected static string $test_subject_logs_file = __DIR__.'/logging/test_subject.log';

    protected static Filesystem $filesystem;

    protected Client $guzzle;

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
        $this->truncateTestSubjectLogs();
    }

    private function getTestSubjectBaseUrl(): string
    {
        $subject_url = getenv('TEST_SUBJECT_BASE_URI');
        Assert::assertNotEmpty($subject_url, 'Expect an environment variable TEST_SUBJECT_BASE_URI');
        return $subject_url;
    }

    protected function truncateTestSubjectLogs(): void
    {
        if (is_file(self::$test_subject_logs_file)) {
            // NB, can't use filesystem->dump as we need to modify the content of the file *in place* because
            // the log-receiving netcat is just redirected to the file and already has it open, so will keep
            // writing to the original file handle even if we move or delete it.
            $this->assertSame(0, file_put_contents(self::$test_subject_logs_file, ''));
        }
    }

    protected function getTestSubjectLogEntriesDuringTest(): array
    {
        if (!is_file(self::$test_subject_logs_file)) {
            return [];
        }

        // Each line in the file is in syslog RFC3164 format. The details of the message header not relevant to us, we
        // are only using syslog format to be able to capture the underlying container STDOUT/STDERR in a docker compose
        // environment. So we just split the lines on the end of the HEADER and return the CONTENT
        return array_map(
            self::extractSyslogRFC3164MessageBody(...),
            file(self::$test_subject_logs_file, FILE_IGNORE_NEW_LINES),
        );
    }

    private function extractSyslogRFC3164MessageBody(string $line): string
    {
        // https://datatracker.ietf.org/doc/html/rfc3164
        if (!preg_match('/^<\d+>\w+ \d+ \d\d:\d\d:\d\d [^ ]+ \w+\[\w+\]:(.+)$/', $line, $matches)) {
            throw new \UnexpectedValueException('Unexpected log line format: `'.$line.'`');
        }
        return $matches[1];
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
        $class_parts = explode('\\', static::class);
        $id = array_pop($class_parts).'_'.sha1($code);
        $file_path = self::$dynamic_handler_path.'/'.$id;
        self::$filesystem->mkdir($file_path);
        self::$filesystem->dumpFile($file_path.'/index.php', $code);

        return '/dynamic/'.$id.'/';
    }

}

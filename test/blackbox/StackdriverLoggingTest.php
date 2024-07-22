<?php

namespace test\blackbox;

use Ingenerator\PHPUtils\StringEncoding\JSON;

class StackdriverLoggingTest extends BaseBlackboxTestCase
{

    // Note, these tests are necessarily verbose and fairly tightly coupled to the code structure, because I think for
    // a blackbox test we should be as explicit as we can be about the detail of the log messages that are written -
    // but the log messages include filenames, line numbers, and other semi-dynamic content.

    public function test_it_logs_requests_on_completion_in_stackdriver_format()
    {
        $handler_url = self::provisionDynamicHandler(
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

            // Note, wouldn't normally write this here obviously, normally the version file would be generated during
            // build. But writing it dynamically is the easiest way to keep this test setup all in the same place.
            $version_file_path = tempnam(sys_get_temp_dir(), 'app-version');
            file_put_contents($version_file_path, "<?php\nreturn 'ab28372';\n");
            
            (new MicroFramework)->execute(
                logger_provider_factory: fn () => new DefaultStackdriverLoggerProvider(
                    service_name: 'my-service',
                    version_file_path: $version_file_path,
                ),
                handler_factory: fn (LoggerInterface $logger) => new class implements RequestHandler {
                    public function handle(ServerRequestInterface $request): ResponseInterface
                    {
                        return new Response(203, body: 'OK');
                    }
                },
            );
            PHP,
        );

        $this->assertResponseMatches(
            203,
            'OK',
            $this->guzzle->get($handler_url, ['headers' => ['User-Agent' => 'My Test UA']]),
        );

        $messages = $this->getTestSubjectLogEntriesDuringTest();
        $this->assertCount(1, $messages, 'Should log a single entry');
        $request_log_msg = JSON::decodeArray($messages[0]);

        // Assert things that are dynamic and can't be exactly asserted
        $this->assertMatchesRegularExpression('/^0.00\d{4}s$/', $request_log_msg['httpRequest']['latency']);
        unset($request_log_msg['httpRequest']['latency']);

        $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $request_log_msg['context']['req']);
        unset($request_log_msg['context']['req']);

        $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $request_log_msg['logging.googleapis.com/trace']);
        unset($request_log_msg['logging.googleapis.com/trace']);

        // Currently the test handler is taking around 2.1Mb, just assert it's close to that
        $this->assertSame(2.0, round($request_log_msg['context']['mem_mb']));
        unset($request_log_msg['context']['mem_mb']);

        $this->assertSame(
            [
                'severity' => 'INFO',
                '@ingenType' => 'rqst',
                'httpRequest' => [
                    'requestMethod' => 'GET',
                    'requestUrl' => parse_url($handler_url, PHP_URL_PATH),
                    'remoteIp' => gethostbyname(gethostname()),
                    'status' => 203,
                    'userAgent' => 'My Test UA',
                    // 'latency' => '0.002811s',
                ],
                'context' => [
                    // 'mem_mb' => 2.10,
                    // 'req' => '66991537308e16.07573263',
                ],
                'serviceContext' => [
                    'service' => 'my-service',
                    'version' => 'ab28372',
                ],
                //'logging.googleapis.com/trace' => '66991537308e16.07573263',
            ],
            $request_log_msg,
        );
    }

    public function test_it_logs_uncaught_exceptions_and_request_in_stackdriver_format()
    {
        $handler_url = self::provisionDynamicHandler(
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
            // Note: defining this as a named class to make log output assertions easier to follow (anonymous classes
            // render happily but include a null character in the JSON which makes phpunit render a diff as a binary
            // string)
            class MyHandler implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    throw new RuntimeException('I broke');
                }
            }

            (new MicroFramework)->execute(
                logger_provider_factory: fn () => new DefaultStackdriverLoggerProvider(
                    service_name: 'my-service',
                    version_file_path: '/var/www/version.php',
                ),
                handler_factory: fn (LoggerInterface $logger) => new MyHandler($logger),
            );
            PHP,
        );

        $this->assertResponseMatches(
            500,
            "Unexpected fatal error\n",
            $this->guzzle->post($handler_url, ['headers' => ['User-Agent' => 'My Test UA']]),
        );

        $messages = $this->getTestSubjectLogEntriesDuringTest();
        $this->assertCount(2, $messages, 'Should log 2 entries');

        $error_log_msg = JSON::decodeArray($messages[0]);
        $request_log_msg = JSON::decodeArray($messages[1]);

        // Assert the things that vary and can't easily be asserted directly
        $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $error_log_msg['context']['req']);
        $this->assertSame(
            $error_log_msg['context']['req'],
            $request_log_msg['context']['req'],
            'Same request ID in both error & request log',
        );
        unset($error_log_msg['context']['req']);
        unset($request_log_msg['context']['req']);

        $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $request_log_msg['logging.googleapis.com/trace']);
        $this->assertSame(
            $error_log_msg['logging.googleapis.com/trace'],
            $request_log_msg['logging.googleapis.com/trace'],
            'Same google trace ID in error & request log',
        );
        unset($error_log_msg['logging.googleapis.com/trace']);
        unset($request_log_msg['logging.googleapis.com/trace']);

        $this->assertMatchesRegularExpression('/^0.00\d{4}s$/', $request_log_msg['httpRequest']['latency']);
        unset($request_log_msg['httpRequest']['latency']);

        // Currently the test handler is taking around 2.1Mb, just assert it's close to that
        $this->assertSame(2.0, round($request_log_msg['context']['mem_mb']));
        unset($request_log_msg['context']['mem_mb']);

        $this->assertSame(
            [
                'severity' => 'EMERGENCY',
                'message' => 'Uncaught RuntimeException: I broke at /var/www/htdocs/dynamic/StackdriverLoggingTest_54a57cd69e4889f9c6cfdde12f4607f1461dec3e/index.php:/var/www/htdocs/dynamic/StackdriverLoggingTest_54a57cd69e4889f9c6cfdde12f4607f1461dec3e/index.php',
                '@ingenType' => 'app',
                'serviceContext' => [
                    'service' => 'my-service',
                    'version' => 'a-version',
                ],
                'context' => [
                    // 'req' => '66991537308e16.07573263',
                    'httpRequest' => [
                        'requestMethod' => 'POST',
                        'requestUrl' => parse_url($handler_url, PHP_URL_PATH),
                        'remoteIp' => gethostbyname(gethostname()),
                    ],
                    'reportLocation' => [
                        'filePath' => '/var/www/vendor/ingenerator/microframework/src/MicroFramework.php',
                        'lineNumber' => 153,
                        'functionName' => 'Ingenerator\MicroFramework\MicroFramework->logUncaughtException',
                    ],
                ],
                //'logging.googleapis.com/trace' => '66991537308e16.07573263',
                'exception' => [
                    'class' => 'RuntimeException',
                    'msg' => 'I broke',
                    'code' => 0,
                    'file' => '/var/www/htdocs/dynamic/StackdriverLoggingTest_54a57cd69e4889f9c6cfdde12f4607f1461dec3e/index.php',
                    'line' => 17,
                    'trace' => <<<'TEXT'
                        #0 /var/www/vendor/ingenerator/microframework/src/MicroFramework.php(81): MyHandler->handle()
                        #1 /var/www/htdocs/dynamic/StackdriverLoggingTest_54a57cd69e4889f9c6cfdde12f4607f1461dec3e/index.php(21): Ingenerator\MicroFramework\MicroFramework->execute()
                        #2 {main}
                        TEXT,
                ],
                'logging.googleapis.com/sourceLocation' => [
                    'file' => '/var/www/vendor/ingenerator/microframework/src/MicroFramework.php',
                    'line' => 153,
                    'function' => 'Ingenerator\MicroFramework\MicroFramework->logUncaughtException',
                ],
                '@type' => 'type.googleapis.com/google.devtools.clouderrorreporting.v1beta1.ReportedErrorEvent',
                'stack_trace' => <<<'TEXT'
                    PHP Warning: RuntimeException: I broke in /var/www/htdocs/dynamic/StackdriverLoggingTest_54a57cd69e4889f9c6cfdde12f4607f1461dec3e/index.php:17
                    Stack trace:
                    #0 /var/www/vendor/ingenerator/microframework/src/MicroFramework.php(81): MyHandler->handle()
                    #1 /var/www/htdocs/dynamic/StackdriverLoggingTest_54a57cd69e4889f9c6cfdde12f4607f1461dec3e/index.php(21): Ingenerator\MicroFramework\MicroFramework->execute()
                    #2 {main}
                    TEXT,
            ],
            $error_log_msg,
        );

        $this->assertSame(
            [
                'severity' => 'ERROR',
                '@ingenType' => 'rqst',
                'httpRequest' => [
                    'requestMethod' => 'POST',
                    'requestUrl' => parse_url($handler_url, PHP_URL_PATH),
                    'remoteIp' => gethostbyname(gethostname()),
                    'status' => 500,
                    'userAgent' => 'My Test UA',
                    // 'latency' => '0.002811s',
                ],
                'context' => [
                    // 'mem_mb' => 2.10,
                    // 'req' => '66991537308e16.07573263',
                ],
                'serviceContext' => [
                    'service' => 'my-service',
                    'version' => 'a-version',
                ],
                //'logging.googleapis.com/trace' => '66991537308e16.07573263',
            ],
            $request_log_msg,
        );
    }

    public function test_it_outputs_custom_app_logs_in_stackdriver_format()
    {
        $handler_url = self::provisionDynamicHandler(
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
            // Note: defining this as a named class to make log output assertions easier to follow (anonymous classes
            // render happily but include a null character in the JSON which makes phpunit render a diff as a binary
            // string)
            class MyHandler implements RequestHandler {
                public function __construct(private readonly LoggerInterface $logger) {}
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $this->logger->notice('Notice me', ['some' => 'data']);
                    return new Response(200, body: 'OK');
                }                              
            }


            (new MicroFramework)->execute(
                logger_provider_factory: fn () => new DefaultStackdriverLoggerProvider(
                    service_name: 'my-service',
                    version_file_path: '/var/www/version.php',
                ),
                handler_factory: fn (LoggerInterface $logger) => new MyHandler($logger),
            );
            PHP,
        );

        $this->assertResponseMatches(
            200,
            "OK",
            $this->guzzle->post($handler_url, ['headers' => ['User-Agent' => 'My Test UA']]),
        );

        $messages = $this->getTestSubjectLogEntriesDuringTest();
        $this->assertCount(2, $messages, 'Should log 2 entries');

        $error_log_msg = JSON::decodeArray($messages[0]);
        $request_log_msg = JSON::decodeArray($messages[1]);

        // Assert the things that vary and can't easily be asserted directly
        $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $error_log_msg['context']['req']);
        $this->assertSame(
            $error_log_msg['context']['req'],
            $request_log_msg['context']['req'],
            'Same request ID in both error & request log',
        );
        unset($error_log_msg['context']['req']);
        unset($request_log_msg['context']['req']);

        $this->assertMatchesRegularExpression('/^\w+\.\w+$/', $request_log_msg['logging.googleapis.com/trace']);
        $this->assertSame(
            $error_log_msg['logging.googleapis.com/trace'],
            $request_log_msg['logging.googleapis.com/trace'],
            'Same google trace ID in error & request log',
        );
        unset($error_log_msg['logging.googleapis.com/trace']);
        unset($request_log_msg['logging.googleapis.com/trace']);

        $this->assertMatchesRegularExpression('/^0.00\d{4}s$/', $request_log_msg['httpRequest']['latency']);
        unset($request_log_msg['httpRequest']['latency']);

        // Currently the test handler is taking around 2.1Mb, just assert it's close to that
        $this->assertSame(2.0, round($request_log_msg['context']['mem_mb']));
        unset($request_log_msg['context']['mem_mb']);

        $this->assertSame(
            [
                'severity' => 'NOTICE',
                'message' => 'Notice me',
                '@ingenType' => 'app',
                'serviceContext' => [
                    'service' => 'my-service',
                    'version' => 'a-version',
                ],
                'context' => [
                    // 'req' => '66991537308e16.07573263',
                    'httpRequest' => [
                        'requestMethod' => 'POST',
                        'requestUrl' => parse_url($handler_url, PHP_URL_PATH),
                        'remoteIp' => gethostbyname(gethostname()),
                    ],
                ],
                //'logging.googleapis.com/trace' => '66991537308e16.07573263',
                'logging.googleapis.com/sourceLocation' => [
                    'file' => '/var/www/htdocs/dynamic/StackdriverLoggingTest_0ba32a129fbc45c0d394a95f76d7bdf405c84692/index.php',
                    'line' => 18,
                    'function' => 'MyHandler->handle',
                ],
                'custom_context' => [
                    'some' => 'data',
                ],
            ],
            $error_log_msg,
        );

        $this->assertSame(
            [
                'severity' => 'INFO',
                '@ingenType' => 'rqst',
                'httpRequest' => [
                    'requestMethod' => 'POST',
                    'requestUrl' => parse_url($handler_url, PHP_URL_PATH),
                    'remoteIp' => gethostbyname(gethostname()),
                    'status' => 200,
                    'userAgent' => 'My Test UA',
                    // 'latency' => '0.002811s',
                ],
                'context' => [
                    // 'mem_mb' => 2.10,
                    // 'req' => '66991537308e16.07573263',
                ],
                'serviceContext' => [
                    'service' => 'my-service',
                    'version' => 'a-version',
                ],
                //'logging.googleapis.com/trace' => '66991537308e16.07573263',
            ],
            $request_log_msg,
        );
    }

}

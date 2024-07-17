<?php
declare(strict_types=1);


namespace Ingenerator\MicroFramework;


use ErrorException;
use GuzzleHttp\Psr7\ServerRequest;
use Ingenerator\PHPUtils\Logging\StackdriverApplicationLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function date_default_timezone_set;
use function get_class;
use function header;
use function headers_sent;
use function http_response_code;
use function ob_get_clean;
use function ob_get_level;
use function set_error_handler;
use function setlocale;
use function sprintf;
use function substr;
use const LC_ALL;

class MicroFramework
{
    public static function throwingErrorHandler($code, $error, $file = null, $line = null): bool
    {
        if (\error_reporting() & $code) {
            // This error is not suppressed by current error reporting settings
            // Convert the error into an ErrorException
            throw new ErrorException($error, $code, 0, $file, $line);
        }

        // Do not execute the PHP error handler for errors that are not suppressed
        return true;
    }

    /**
     * Executes a request
     *
     * @param callable():LoggerProvider $logger_provider_factory function that returns a LoggerProvider to get/create a suitable logger
     * @param callable(LoggerInterface $logger):RequestHandler $handler_factory function that returns a class containing the application code
     * @param null|callable():ServerRequestInterface $request_factory callable that provides a ServerRequest - defaults to guzzle/psr7's ServerRequest::fromGlobals()
     * @param null|int|float $start_hr_time the hrtime() at the start of the request - ideally captured as the very first thing in index.php. Used for logging latency
     * @param string $default_timezone default timezone to set before execution
     * @param string $locale default locale to set before execution
     *
     * @return void
     */
    public function execute(
        callable $logger_provider_factory,
        callable $handler_factory,
        null|int|float $start_hr_time = null,
        ?callable $request_factory = null,
        string $default_timezone = 'Europe/London',
        string $locale = 'en_UK.utf-8',
    ): void {
        $start_hr_time ??= hrtime(as_number: true);
        ob_start();
        try {
            set_error_handler(static::throwingErrorHandler(...));

            date_default_timezone_set($default_timezone);
            setlocale(LC_ALL, $locale);

            // The logger is created as early as possible. It should be virtually impossible to fail before or
            // during logger initialisation, if the container has been built correctly and passed smoketests
            $logger_provider = FactoryFunction::call($logger_provider_factory, LoggerProvider::class);
            $logger = $logger_provider->getLogger();

            $request = $request_factory
                ? FactoryFunction::call($request_factory, ServerRequest::class)
                : ServerRequest::fromGlobals();

            // Hand off to the application for execution
            $response = FactoryFunction::call($handler_factory, RequestHandler::class, [$logger])
                ->handle($request);

            // Render the application result
            $this->resetOutputBuffersAssertingNoOutput();
            $this->renderResponseToClient($response);
        } catch (Throwable $e) {
            // A well-implemented logger shouldn't fail to initialise even if there are errors loading metadata etc.
            // However, just in case, it should *always* be possible to create and use a StackdriverApplicationLogger
            // with no metadata providers, which is better than nothing.
            $logger ??= new StackdriverApplicationLogger('php://stderr');
            $this->logUncaughtException($logger, $e);

            $this->renderErrorResponse();
        } finally {
            // NOTE: This runs after everything and outside the output buffering and error handling so:
            // a) it must not produce any output or errors, under any circumstances
            // b) if something went wrong in the course of creating a logger, we will never log the
            //    request either.
            if (isset($logger_provider, $logger)) {
                $logger_provider->getRequestLogger()->logRequest($logger, $start_hr_time);
            }
        }
    }


    private function resetOutputBuffersAssertingNoOutput(): void
    {
        if (headers_sent($file, $line)) {
            throw new \RuntimeException('Headers already sent (output started at '.$file.':'.$line);
        }

        while (ob_get_level()) {
            $buffer = ob_get_clean();
            if ( ! empty($buffer)) {
                throw new \RuntimeException('Unexpected buffered output during request ('.substr($buffer, 0, 50));
            }
        }
    }

    private function renderResponseToClient(ResponseInterface $response): void
    {
        if (empty($response->getReasonPhrase())) {
            // When behind apache, at least, it is not valid to send an 'HTTP/xx {code}' without a reason phrase for a
            // custom response code - apache will attempt to look up the reason phrase for the code and will convert the
            // response to a 500 if it doesn't find anything (despite logging in the request log with the custom code).
            throw new \UnexpectedValueException(
                'HTTP reason phrase cannot be empty and there is no default for status "'.$response->getStatusCode().
                '"',
            );
        }

        header(
            sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ),
        );

        foreach ($response->getHeaders() as $header => $header_values) {
            if (count($header_values) > 1) {
                throw new \UnexpectedValueException('Cannot specify multiple values for the '.$header.' header');
            }
            header($header.': '.array_shift($header_values), replace: true);
        }

        echo $response->getBody()->getContents();
    }

    private function logUncaughtException(LoggerInterface $logger, Throwable $e): void
    {
        $logger->emergency(
            sprintf(
                'Uncaught %s: %s at %s:%s',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getFile(),
            ),
            ['exception' => $e],
        );
    }

    private function renderErrorResponse(): void
    {
        if ( ! headers_sent()) {
            // We can't send these if they've already been sent...
            http_response_code(500);
            header('Content-Type: text/plain');
        }
        echo "Unexpected fatal error\n";
    }

}

microframework provides an extremely lightweight, opinionated, framework for simple HTTP request handlers
(e.g. Cloud Run / Lambda / Cloud Functions style endpoints).

It provides:

* Basic bootstrapping including setting timezone & locale.
* Error / Exception handling including logging and rendering a generic error response.
* Detection of unexpected output during execution / early header-sending.
* Request logging (including high-resolution latency).
* Rendering a PSR `ResponseInterface` object back to the client (including status code, headers & body).

It is designed & tested to run under apache v2.4 with mod_php, mod_prefork, and mod_rewrite. Other 
runtime configurations may work but are not officially supported.

It does not - and will not - provide routing, runtime configuration management, dependency injection, HTML templating, 
middlewares, event dispatch or any similar features of more full-featured frameworks. 


# Getting started

`composer require ingenerator/microframework`

## Handling requests

Provision a PHP file as your entrypoint. This could be at `{Apache DocumentRoot}/index.php` or
at any other location (e.g. you could place it in a subdirectory of a larger / more complex 
project alongside code that uses a different framework).

```php
<?php
// index.php
require_once(__DIR__.'/../vendor/autoload.php');

// *NO CODE HERE*
// You should ideally not have any code that runs outside the functions passed into execute() below. This makes sure
// that all code is wrapped in the output detection, error handling, and logging provided by microframework. 

(new Ingenerator\MicroFramework\MicroFramework)->execute(
    /*
     * --- REQUIRED CODE ---
     */
    // You must provide a callable that returns a `LoggerProvider`
    //
    // The DefaultStackdriverLoggerProvider returns a logger that will log requests and custom entries to STDOUT in a
    // format suitable for ingestion into Google Stackdriver (including tagging exceptions for Google Error Reporting).
    //
    // You can alternatively provide a custom LoggerProvider that returns any PSR\Log\LoggerInterface along with a thin
    // RequestLogger class that will be called automatically to log the request itself.
    logger_provider_factory: fn () => new \Ingenerator\MicroFramework\DefaultStackdriverLoggerProvider(
        service_name: 'my-function',
        // For the DefaultStackdriverLoggerProvider, you need to give a path to a file that will return the current 
        // version of your service - e.g. `<?php return 'ab9237723'` which you would usually write during docker build.
        // This will be included in the metadata for all log entries.
        version_file_path: __DIR__.'/../version.php' 
    ),
    // Your actual implementation sits within a RequestHandler class.
    // - For the simplest functions, you can define this inline as an anonymous class - as in the example below.
    // - For more complex functions, you will probably want to define a normal PHP class in a separate file (e.g.
    //   autoloadable by composer) and potentially a factory function to create it with any services / config it
    //   requires.
    //
    // The only service / dependency that microframework provides to your code is the Logger - this is passed to
    // your factory function for you to use as required.
    handler_factory: fn(\Psr\Log\LoggerInterface $logger) => new class implements Ingenerator\MicroFramework\RequestHandler {
        
        // If you wish to write log entries from your own code, capture the logger as a constructor argument.
        public function __construct(private readonly $logger) {}
        
        public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
            // Do whatever you want with the request here.
            // Microframework does not provide any routing - if you need to handle different requests with different
            // code, it is up to you to dispatch them appropriately.           
            // When you are finished, return a PSR ResponseInterface.
            $this->logger->info('Got a request');
            return new Response(200, headers: ['Content-Type'=> 'application/json'], body: '{"ok": true}');
        }
    },
    /*
     * 
     * --- OPTIONAL CODE ---
     * 
     */
     // For ultra-precise latency measurement, you could capture start_hr_time as the very first line of PHP in the file
     // e.g. before requiring the composer autoloader. If not provided, it will default to when ->execute() is called.
     start_hr_time: hrtime(as_number: true),
     // Set a custom locale, if required (defaults to en_UK.utf-8)
     locale: 'en_US.utf-8',
     // Set a custom timezone (defaults to Europe/London)
     default_timezone: 'Europe/Paris',
     // Customise how the incoming request is parsed - e.g. if your runtime environment does not provide the request
     // in a structure that the default GuzzleHttp\Psr7\ServerRequest::fromGlobals() can understand. Provide a factory
     // function that returns a ServerRequestInterface
     request_factory: fn() => new \GuzzleHttp\Psr7\ServerRequest(/*request values from somewhere*/)
);

// *NO CODE HERE*
// Again, you should not have code that runs outside the `execute` call above.
// If absolutely required and you e.g. need to run cleanup after the response has been sent / streamed to the client,
// you could do that here - but be aware that it will not be covered by error handling or logging & will not be able to
// modify response headers or output.
```

# Versioning policy

The package follows semver. For ease of maintenance, any given package version will only support a single PHP version 
and one minor version of each of the (small number of) composer dependencies.

It is expected that you may need to update your function code to the latest supported PHP / composer dependency version
to be able to upgrade to a newer minor version of this package.

We may occasionally publish bugfix versions against an older version of the package to deal with security or severe 
bugs, but the majority of changes will only be applied to the current minor version.

# Running tests

The package has some unit & integration tests which can be run with phpunit in the normal way. 

We also provide a suite of blackbox tests to verify the overall behaviour of the package by making HTTP requests against
a distribution version of the package in a supported runtime environment. The test environment for this is quite 
specific and is provisioned with uses `docker compose` and some custom scripts to ensure that the system under test
has an exact copy of (only) the production code. See [test/blackbox/README.md](test/blackbox/README.md) for more details
on how this works.

# Contributing

Contributions are welcome but please contact us before you start work on anything : this is an opinionated package and 
we may have particular requirements / opinions that differ from yours.

# Contributors

This package has been sponsored by [inGenerator Ltd](https://www.ingenerator.com/)

# Licence

Licensed under the [BSD-3-Clause-Licence](LICENSE).

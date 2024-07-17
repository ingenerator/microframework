<?php

use GuzzleHttp\Psr7\Response;
use Ingenerator\MicroFramework\DefaultStackdriverLoggerProvider;
use Ingenerator\MicroFramework\MicroFramework;
use Ingenerator\MicroFramework\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$start_time = hrtime(as_number: true);

require_once(__DIR__.'/../vendor/autoload.php');

(new MicroFramework)->execute(
    logger_provider_factory: fn() => new DefaultStackdriverLoggerProvider(
        service_name: 'ig-micro',
        version_file_path: __DIR__.'/../version.php',
    ),
    handler_factory: fn() => new class implements RequestHandler {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return new Response(200, body: 'Blackbox is alive');
        }
    },
    start_hr_time: $start_time,
);

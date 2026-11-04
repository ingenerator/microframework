<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/test',
    ])
    ->withRootFiles()
    ->withSkipPath(__DIR__.'/test/blackbox/implementation/htdocs/dynamic')
    ->withComposerBased(phpunit: true)
    ->withImportNames();

<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework;

use GuzzleHttp\Psr7\Response;

final class ResponseFactory
{
    private function __construct()
    {
    }

    /**
     * Shorthand to create a text/plain response.
     *
     * @param array<string, array<string>|string> $headers
     */
    public static function text(string $content, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'text/plain', ...$headers],
            $content
        );
    }
}

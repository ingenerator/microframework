<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework;

use GuzzleHttp\Psr7\Response;
use JsonException;

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

    /**
     * Shorthand to create an application/json response.
     *
     * @param array<string, array<string>|string> $headers
     *
     * @throws JsonException
     */
    public static function json(mixed $content, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json', ...$headers],
            json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
        );
    }
}

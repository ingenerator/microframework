<?php

declare(strict_types=1);

namespace test\unit;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

final class AssertResponse
{
    /**
     * @param array<string, array<string>> $expectHeaders
     */
    public static function isExactly(
        int $expectStatus,
        string $expectBody,
        array $expectHeaders,
        ResponseInterface $actual,
    ): void {
        // Ensure body has not been read elsewhere
        $actual->getBody()->rewind();
        Assert::assertSame(
            [
                'status' => $expectStatus,
                'body' => $expectBody,
                'headers' => $expectHeaders,
            ],
            [
                'status' => $actual->getStatusCode(),
                'body' => $actual->getBody()->getContents(),
                'headers' => $actual->getHeaders(),
            ]
        );
        // Rewind for any further reads
        $actual->getBody()->rewind();
    }
}

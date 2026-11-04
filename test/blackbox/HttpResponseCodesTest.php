<?php

declare(strict_types=1);

namespace test\blackbox;

use PHPUnit\Framework\Attributes\TestWith;

final class HttpResponseCodesTest extends BaseBlackboxTestCase
{
    private static string $handler_url;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $query = $request->getQueryParams();
                    return new Response((int) $query['code'], body: 'Status code from query');
                }
            }
            PHP,
        );
    }

    #[TestWith([200])]
    #[TestWith([202])]
    #[TestWith([404])]
    #[TestWith([500])]
    #[TestWith([502])]
    public function testItReturnsStatusCodeFromHandler(int $status_code): void
    {
        $this->assertResponseMatches(
            $status_code,
            'Status code from query',
            $this->guzzle->get(self::$handler_url.'?code='.$status_code),
        );
    }

    public function testItCanOptionallyUseCustomStatusCodesIfReasonPhraseProvided(): void
    {
        $handler_url = self::provisionDynamicHandlerFactoryWithDefaultBootstrap(
            <<<'PHP'
            fn (LoggerInterface $logger) => new class implements RequestHandler {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    $query = $request->getQueryParams();
                    return new Response(
                        status: 599,
                        reason: 'Unusually broken',                        
                    );
                }
            }
            PHP,
        );

        $this->assertResponseMatches(599, '', $this->guzzle->get($handler_url));
    }
}

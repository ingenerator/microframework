<?php

namespace test\blackbox;

class HttpResponseCodesTest extends BaseBlackboxTestCase
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

    /**
     * @testWith [200]
     *           [202]
     *           [404]
     *           [500]
     *           [502]
     */
    public function test_it_returns_status_code_from_handler(int $status_code)
    {
        $this->assertResponseMatches(
            $status_code,
            'Status code from query',
            $this->guzzle->get(self::$handler_url.'?code='.$status_code),
        );
    }

    public function test_it_can_optionally_use_custom_status_codes_if_reason_phrase_provided()
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

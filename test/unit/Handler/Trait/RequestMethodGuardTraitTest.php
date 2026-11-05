<?php

declare(strict_types=1);

namespace test\unit\Handler\Trait;

use GuzzleHttp\Psr7\ServerRequest;
use Ingenerator\MicroFramework\Const\RequestMethod;
use Ingenerator\MicroFramework\Handler\Trait\RequestMethodGuardTrait;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use test\unit\AssertResponse;

final class RequestMethodGuardTraitTest extends TestCase
{
    /**
     * @return Iterator<string, array{string, list<RequestMethod>}>
     */
    public static function provider_matching_methods(): Iterator
    {
        yield 'matches single method' => ['GET', [RequestMethod::GET]];
        yield 'matches one of a list of methods (first)' => ['GET', [RequestMethod::GET, RequestMethod::POST]];
        yield 'matches one of a list of methods (second)' => ['POST', [RequestMethod::GET, RequestMethod::POST]];
        yield 'lowercase get' => ['get', [RequestMethod::GET]];
        yield 'mixed case post' => ['pOsT', [RequestMethod::POST]];
        yield 'uppercase delete' => ['DELETE', [RequestMethod::DELETE]];
    }

    /**
     * @param list<RequestMethod> $allowedMethods
     */
    #[DataProvider('provider_matching_methods')]
    public function testItReturnsNullWhenMethodMatchesAllowedMethod(string $requestMethod, array $allowedMethods): void
    {
        $subject = $this->newSubject();
        $request = new ServerRequest($requestMethod, '/');

        $this->assertNull($subject->guardRequestMethod($request, ...$allowedMethods));
    }

    /**
     * @return Iterator<string, array{string, list<RequestMethod>, string}>
     */
    public static function provider_not_matching(): Iterator
    {
        yield 'does not match single method' => ['POST', [RequestMethod::GET], 'GET'];
        yield 'arbitrary unknown method' => ['JUNK', [RequestMethod::GET], 'GET'];
        yield 'not in a list of methods (comma-separated Allow response header)' => [
            'POST',
            [RequestMethod::GET, RequestMethod::DELETE],
            'GET,DELETE',
        ];
    }

    /**
     * @param list<RequestMethod> $allowedMethods
     */
    #[DataProvider('provider_not_matching')]
    public function testItReturns405ResponseWhenRequestMethodNotAllowed(
        string $requestMethod,
        array $allowedMethods,
        string $expectAllowHeader,
    ): void {
        $request = new ServerRequest($requestMethod, '/');

        $response = $this->newSubject()->guardRequestMethod($request, ...$allowedMethods);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        AssertResponse::isExactly(
            405,
            'Method not allowed',
            ['Content-Type' => ['text/plain'], 'Allow' => [$expectAllowHeader]],
            $response,
        );
    }

    private function newSubject(): RequestMethodGuardTestSubject
    {
        return new RequestMethodGuardTestSubject();
    }
}

final class RequestMethodGuardTestSubject
{
    use RequestMethodGuardTrait {
        guardRequestMethod as public;
    }
}

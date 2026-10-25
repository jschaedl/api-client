<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Exception;

use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface as Psr7RequestInterface;

#[CoversClass(TransportException::class)]
#[UsesClass(RequestDescription::class)]
#[UsesClass(Request::class)]
#[Group('unit')]
final class TransportExceptionTest extends TestCase
{
    public function test_it_chains_the_cause_but_not_its_message(): void
    {
        $request = new Request(Method::GET, 'installations?accessToken=secret');
        $cause = new \RuntimeException('Could not resolve host for "https://api.example.com/installations?accessToken=secret"');

        $exception = new TransportException($request, $cause);

        self::assertSame('Request "GET installations" failed before a complete response was received.', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame($request, $exception->request());
        self::assertTrue($exception->isNetworkError());
    }

    public function test_a_rejected_request_is_no_network_error(): void
    {
        $cause = new class extends \InvalidArgumentException implements RequestExceptionInterface {
            public function getRequest(): Psr7RequestInterface
            {
                throw new \LogicException('not needed');
            }
        };

        self::assertFalse((new TransportException(new Request(Method::GET, 'x'), $cause))->isNetworkError());
    }
}

<?php

declare(strict_types=1);

namespace Api\Exception;

use Api\Request\Method;
use Api\Request\Request;
use Api\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientException::class)]
#[UsesClass(ResponseException::class)]
#[UsesClass(RequestDescription::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[Group('unit')]
final class ClientExceptionTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $exception = new ClientException(new Request(Method::GET, 'items'), new Response('', 400));

        self::assertSame('HTTP 400 returned for "GET items".', $exception->getMessage());
        self::assertSame(400, $exception->getCode());
    }

    #[DataProvider('provideInvalidCodes')]
    public function test_it_throws_InvalidArgumentException_on_non_4xx_code(int $invalidCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Api\Exception\ClientException" requires a response with a 4xx status code');

        new ClientException(new Request(Method::GET, 'items'), new Response('', $invalidCode));
    }

    /**
     * @return iterable<array{int}>
     */
    public static function provideInvalidCodes(): iterable
    {
        yield [500];
        yield [300];
        yield [200];
        yield [100];
    }
}

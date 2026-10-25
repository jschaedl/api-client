<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Exception;

use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerException::class)]
#[UsesClass(ResponseException::class)]
#[UsesClass(RequestDescription::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[Group('unit')]
final class ServerExceptionTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $exception = new ServerException(new Request(Method::GET, 'items'), new Response('', 500));

        self::assertSame('HTTP 500 returned for "GET items".', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
    }

    #[DataProvider('provideInvalidCodes')]
    public function test_it_throws_InvalidArgumentException_on_non_5xx_code(int $invalidCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Jschaedl\ApiClient\Exception\ServerException" requires a response with a 5xx status code');

        new ServerException(new Request(Method::GET, 'items'), new Response('', $invalidCode));
    }

    /**
     * @return iterable<array{int}>
     */
    public static function provideInvalidCodes(): iterable
    {
        yield [400];
        yield [600];
        yield [300];
        yield [200];
        yield [100];
    }
}

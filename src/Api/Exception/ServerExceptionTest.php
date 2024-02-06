<?php

declare(strict_types=1);

namespace Api\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Api\Exception\ServerException
 * @covers \Api\Exception\ResponseException
 *
 * @group unit
 */
final class ServerExceptionTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $exception = new ServerException(500, 'Server Error');
        self::assertSame('500 Server Error', $exception->getMessage());
    }

    /**
     * @dataProvider provideInvalidCodes
     */
    public function test_it_throws_InvalidArgumentException_on_non_4xx_code(int $invalidCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Api\Exception\ServerException" constructor argument $code must be a 5xx status code');

        new ServerException($invalidCode, 'invalid code');
    }

    public function provideInvalidCodes(): iterable
    {
        yield [400];
        yield [300];
        yield [200];
        yield [100];
    }
}

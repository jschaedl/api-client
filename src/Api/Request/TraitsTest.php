<?php

declare(strict_types=1);

namespace Api\Request;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Api\Request\IsGet
 * @covers \Api\Request\IsPost
 * @covers \Api\Request\IsPut
 * @covers \Api\Request\IsPatch
 * @covers \Api\Request\IsDelete
 * @covers \Api\Request\HasNoAdditionalHeaders
 */
final class TraitsTest extends TestCase
{
    public function test_traits(): void
    {
        self::assertSame(Method::GET->value, (new GetRequest())->method());
        self::assertSame(null, (new GetRequest())->body());

        self::assertSame(Method::POST->value, (new PostRequest())->method());

        self::assertSame(Method::PUT->value, (new PutRequest())->method());

        self::assertSame(Method::PATCH->value, (new PatchRequest())->method());

        self::assertSame(Method::DELETE->value, (new DeleteRequest())->method());
        self::assertSame(null, (new DeleteRequest())->body());

        self::assertEmpty((new HasNoRequestSpecificHeadersRequest())->headers());
    }
}

class GetRequest
{
    use IsGet;
}

class PostRequest
{
    use IsPost;
}

class PutRequest
{
    use IsPut;
}

class PatchRequest
{
    use IsPatch;
}

class DeleteRequest
{
    use IsDelete;
}

class HasNoRequestSpecificHeadersRequest
{
    use HasNoAdditionalHeaders;
}

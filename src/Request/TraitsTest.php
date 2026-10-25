<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversTrait(IsGet::class)]
#[CoversTrait(IsPost::class)]
#[CoversTrait(IsPut::class)]
#[CoversTrait(IsPatch::class)]
#[CoversTrait(IsDelete::class)]
#[UsesClass(Method::class)]
#[Group('unit')]
final class TraitsTest extends TestCase
{
    public function test_traits(): void
    {
        self::assertSame(Method::GET->value, (new GetRequest())->method());

        self::assertSame(Method::POST->value, (new PostRequest())->method());

        self::assertSame(Method::PUT->value, (new PutRequest())->method());

        self::assertSame(Method::PATCH->value, (new PatchRequest())->method());

        self::assertSame(Method::DELETE->value, (new DeleteRequest())->method());
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

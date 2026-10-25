<?php

declare(strict_types=1);

namespace Api\Request\Encoder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonRequestBodyEncoder::class)]
#[CoversClass(FormRequestBodyEncoder::class)]
#[Group('unit')]
final class EncodersTest extends TestCase
{
    public function test_json(): void
    {
        $encoder = new JsonRequestBodyEncoder();

        self::assertSame('{"url":"https://x.test/ä"}', $encoder->encode(['url' => 'https://x.test/ä']));
        self::assertSame('application/json', $encoder->contentType());
    }

    public function test_json_throws_on_invalid_input(): void
    {
        $this->expectException(\JsonException::class);

        (new JsonRequestBodyEncoder())->encode(['invalid' => "\xB1"]);
    }

    public function test_form(): void
    {
        $encoder = new FormRequestBodyEncoder();

        self::assertSame('grant_type=client_credentials&scope=read+write&ids%5B0%5D=1', $encoder->encode(['grant_type' => 'client_credentials', 'scope' => 'read write', 'ids' => [1]]));
        self::assertSame('application/x-www-form-urlencoded', $encoder->contentType());
    }
}

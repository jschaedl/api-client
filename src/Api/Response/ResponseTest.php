<?php

declare(strict_types=1);

namespace Api\Response;

use Api\Exception\DecodingException;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
#[UsesClass(JsonResponseBodyDecoder::class)]
#[UsesClass(DecodingException::class)]
#[Group('unit')]
final class ResponseTest extends TestCase
{
    public function test_without_decoder_the_raw_body_is_returned(): void
    {
        self::assertSame('plain', (new Response('plain', 200))->body());
    }

    public function test_an_empty_body_is_not_decoded(): void
    {
        self::assertSame('', (new Response('', 204, [], new JsonResponseBodyDecoder()))->body());
    }

    public function test_the_body_is_decoded_once(): void
    {
        $decoder = new class implements ResponseBodyDecoderInterface {
            public int $calls = 0;

            public function decode(string $responseBody): mixed
            {
                ++$this->calls;

                return ['decoded' => $responseBody];
            }
        };

        $response = new Response('x', 200, [], $decoder);

        self::assertSame(0, $decoder->calls);
        self::assertSame(['decoded' => 'x'], $response->body());
        self::assertSame(['decoded' => 'x'], $response->body());
        self::assertSame(1, $decoder->calls);
    }

    public function test_a_decoding_failure_is_wrapped_without_the_body(): void
    {
        try {
            (new Response('<html>secret</html>', 502, [], new JsonResponseBodyDecoder()))->body();
            self::fail('No exception thrown.');
        } catch (DecodingException $exception) {
            self::assertSame(502, $exception->getCode());
            self::assertStringNotContainsString('secret', $exception->getMessage());
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        }
    }

    public function test_headers_are_looked_up_case_insensitively(): void
    {
        $response = new Response('', 200, ['Retry-After' => ['5', '6']]);

        self::assertSame('5', $response->header('retry-after'));
        self::assertNull($response->header('X-Missing'));
    }
}

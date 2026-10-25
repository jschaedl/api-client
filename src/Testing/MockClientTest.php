<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Testing;

use Jschaedl\ApiClient\Exception\ClientException;
use Jschaedl\ApiClient\Exception\RequestDescription;
use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Request\RequestInterface;
use Jschaedl\ApiClient\Response\Decoder\JsonResponseBodyDecoder;
use Jschaedl\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MockClient::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(JsonResponseBodyDecoder::class)]
#[UsesClass(ResponseException::class)]
#[UsesClass(ClientException::class)]
#[UsesClass(RequestDescription::class)]
#[Group('unit')]
final class MockClientTest extends TestCase
{
    public function test_it_answers_in_order_and_records_requests(): void
    {
        $client = new MockClient([
            MockClient::json(['id' => 1]),
            static fn (RequestInterface $request): Response => new Response($request->uri(), 200),
        ]);

        self::assertSame(['id' => 1], $client->request(new Request(Method::GET, 'a'))->body());
        self::assertSame('b', $client->request(new Request(Method::GET, 'b'))->rawBody());
        self::assertCount(2, $client->requests());
        self::assertFalse($client->hasPendingResponses());
    }

    public function test_error_responses_and_exceptions_are_thrown(): void
    {
        $client = (new MockClient())->append(MockClient::json(['error' => 'nope'], 404), new \RuntimeException('boom'));

        try {
            $client->request(new Request(Method::GET, 'a'));
            self::fail('No exception thrown.');
        } catch (ClientException $exception) {
            self::assertSame(404, $exception->getCode());
        }

        $this->expectExceptionMessage('boom');
        $client->request(new Request(Method::GET, 'b'));
    }

    public function test_it_fails_loudly_when_nothing_is_queued(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No response queued for request #1 "GET a"');

        (new MockClient())->request(new Request(Method::GET, 'a'));
    }
}

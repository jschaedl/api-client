<?php

declare(strict_types=1);

namespace Api\Exception;

use Api\Request\Method;
use Api\Request\Request;
use Api\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseException::class)]
#[CoversClass(RequestDescription::class)]
#[UsesClass(ClientException::class)]
#[UsesClass(ServerException::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[Group('unit')]
final class ResponseExceptionTest extends TestCase
{
    public function test_create_picks_the_class_by_status_code(): void
    {
        $request = new Request(Method::GET, 'items');

        self::assertInstanceOf(ClientException::class, ResponseException::create($request, new Response('', 404)));
        self::assertInstanceOf(ServerException::class, ResponseException::create($request, new Response('', 500)));
        self::assertSame(ResponseException::class, ResponseException::create($request, new Response('', 302))::class);
    }

    public function test_it_carries_request_response_and_status_but_no_body_or_query(): void
    {
        $request = new Request(Method::POST, '/members?token=secret', ['name' => 'Jane']);
        $response = new Response('{"name":"Jane"}', 409);

        $exception = ResponseException::create($request, $response);

        self::assertSame('HTTP 409 returned for "POST /members".', $exception->getMessage());
        self::assertSame(409, $exception->getCode());
        self::assertSame(409, $exception->statusCode());
        self::assertSame($request, $exception->request());
        self::assertSame($response, $exception->response());
    }

    public function test_the_cause_can_be_chained(): void
    {
        $cause = new \RuntimeException('cause');

        $exception = new ResponseException(new Request(Method::GET, ''), new Response('', 302), $cause);

        self::assertSame($cause, $exception->getPrevious());
        self::assertSame('HTTP 302 returned for "GET /".', $exception->getMessage());
    }
}

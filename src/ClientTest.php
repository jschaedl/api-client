<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient;

use Jschaedl\ApiClient\Exception\ClientException;
use Jschaedl\ApiClient\Exception\DecodingException;
use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\ServerException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\Encoder\FormRequestBodyEncoder;
use Jschaedl\ApiClient\Request\Encoder\JsonRequestBodyEncoder;
use Jschaedl\ApiClient\Request\Handler\AddHeaderHandler;
use Jschaedl\ApiClient\Request\Header;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Request\RequestHandlerInterface;
use Jschaedl\ApiClient\Request\RequestInterface;
use Jschaedl\ApiClient\Response\Decoder\JsonResponseBodyDecoder;
use Jschaedl\ApiClient\Response\ResponseHandlerInterface;
use Jschaedl\ApiClient\Response\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(Client::class)]
#[UsesClass(Request::class)]
#[UsesClass(Header::class)]
#[UsesClass(AddHeaderHandler::class)]
#[UsesClass(JsonRequestBodyEncoder::class)]
#[UsesClass(FormRequestBodyEncoder::class)]
#[UsesClass(JsonResponseBodyDecoder::class)]
#[UsesClass(Response\Response::class)]
#[UsesClass(ResponseException::class)]
#[UsesClass(ClientException::class)]
#[UsesClass(ServerException::class)]
#[UsesClass(TransportException::class)]
#[UsesClass(DecodingException::class)]
#[UsesClass(Exception\RequestDescription::class)]
#[Group('integration')]
final class ClientTest extends TestCase
{
    /** @var array{method: string, url: string, headers: array<mixed>, body: mixed}|null */
    private ?array $sent = null;

    public function test_it_joins_base_uri_and_request_uri(): void
    {
        $this->client(new MockResponse('{}'))->request(new Request(Method::GET, '/items?page=2'));

        self::assertSame('GET', $this->sent['method'] ?? null);
        self::assertSame('https://api.example.com/rest/v1/items?page=2', $this->sent['url'] ?? null);
    }

    public function test_it_encodes_an_array_body_and_sets_the_encoders_content_type(): void
    {
        $this->client(new MockResponse('{}'))->request(new Request(Method::POST, 'items', ['name' => 'Ball/Racket']));

        self::assertSame('{"name":"Ball/Racket"}', $this->sent['body'] ?? null);
        self::assertSame(['Content-Type: application/json'], $this->sent['headers']['content-type'] ?? null);
    }

    public function test_a_request_can_bring_its_own_encoder(): void
    {
        $request = new Request(Method::POST, 'token', ['grant_type' => 'client_credentials', 'scope' => 'a b'], bodyEncoder: new FormRequestBodyEncoder());

        $this->client(new MockResponse('{}'))->request($request);

        self::assertSame('grant_type=client_credentials&scope=a+b', $this->sent['body'] ?? null);
        self::assertSame(['Content-Type: application/x-www-form-urlencoded'], $this->sent['headers']['content-type'] ?? null);
    }

    public function test_a_string_body_is_sent_as_it_is(): void
    {
        $request = (new Request(Method::PUT, 'files/1', "%PDF-1.7\x00\x01"))->addHeader('Content-Type', 'application/pdf');

        $this->client(new MockResponse('', ['http_code' => 204]))->request($request);

        self::assertSame("%PDF-1.7\x00\x01", $this->sent['body'] ?? null);
        // Header normalizes names to lower case
        self::assertSame(['content-type: application/pdf'], $this->sent['headers']['content-type'] ?? null);
    }

    public function test_request_headers_override_handler_and_encoder_headers(): void
    {
        $request = (new Request(Method::POST, 'items', ['a' => 1]))->addHeader('Content-Type', 'application/vnd.api+json');

        $this->client(new MockResponse('{}'), new AddHeaderHandler('Accept', 'application/json'))->request($request);

        self::assertSame(['content-type: application/vnd.api+json'], $this->sent['headers']['content-type'] ?? null);
        self::assertSame(['accept: application/json'], $this->sent['headers']['accept'] ?? null);
    }

    public function test_an_array_body_without_encoder_is_rejected(): void
    {
        $client = new Client(...$this->psr18(new MockResponse('{}')), baseUri: 'https://api.example.com');

        $this->expectException(\LogicException::class);

        $client->request(new Request(Method::POST, 'items', ['a' => 1]));
    }

    public function test_it_returns_a_lazily_decoded_response(): void
    {
        $response = $this->client(new MockResponse('{"id":7}', ['response_headers' => ['X-Request-Id' => 'abc']]))
            ->request(new Request(Method::GET, 'items/7'));

        self::assertSame(200, $response->statusCode());
        self::assertSame(['id' => 7], $response->body());
        self::assertSame('{"id":7}', $response->rawBody());
        self::assertSame('abc', $response->header('x-request-id'));
    }

    public function test_a_binary_body_is_only_decoded_when_asked_for(): void
    {
        $response = $this->client(new MockResponse("%PDF-1.7\x00"))->request(new Request(Method::GET, 'files/1'));

        self::assertSame("%PDF-1.7\x00", $response->rawBody());

        $this->expectException(DecodingException::class);
        $response->body();
    }

    public function test_a_4xx_response_throws_a_client_exception_without_the_body(): void
    {
        $client = $this->client(new MockResponse('{"email":"jane@example.com"}', ['http_code' => 422]));

        try {
            $client->request(new Request(Method::POST, 'members?api_key=secret', ['email' => 'jane@example.com']));
            self::fail('No exception thrown.');
        } catch (ClientException $exception) {
            self::assertSame(422, $exception->getCode());
            self::assertSame('HTTP 422 returned for "POST members".', $exception->getMessage());
            self::assertSame(['email' => 'jane@example.com'], $exception->response()->body());
        }
    }

    public function test_a_5xx_response_throws_a_server_exception(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionCode(503);

        $this->client(new MockResponse('<html>busy</html>', ['http_code' => 503]))->request(new Request(Method::GET, 'items'));
    }

    public function test_error_statuses_can_be_returned_instead_of_thrown(): void
    {
        $client = new Client(
            ...$this->psr18(new MockResponse('', ['http_code' => 404])),
            baseUri: 'https://api.example.com',
            throwOnErrorStatus: false,
        );

        self::assertSame(404, $client->request(new Request(Method::GET, 'items/1'))->statusCode());
    }

    public function test_response_handlers_run_before_the_status_check(): void
    {
        $notFound = new class implements ResponseHandlerInterface {
            public function handle(ResponseInterface $response, RequestInterface $request): ResponseInterface
            {
                if (404 === $response->statusCode()) {
                    throw new \DomainException('Member not found');
                }

                return $response;
            }
        };

        $client = new Client(...$this->psr18(new MockResponse('', ['http_code' => 404])), baseUri: 'https://api.example.com', responseHandler: $notFound);

        $this->expectException(\DomainException::class);

        $client->request(new Request(Method::GET, 'members/1'));
    }

    public function test_a_network_failure_throws_a_transport_exception_with_the_cause_chained(): void
    {
        try {
            $this->client(new MockResponse('', ['error' => 'Idle timeout reached for "https://api.example.com/items?token=secret".']))
                ->request(new Request(Method::GET, 'items?token=secret'));
            self::fail('No exception thrown.');
        } catch (TransportException $exception) {
            self::assertSame('Request "GET items" failed before a complete response was received.', $exception->getMessage());
            self::assertStringNotContainsString('secret', $exception->getMessage());
            self::assertInstanceOf(NetworkExceptionInterface::class, $exception->getPrevious());
            self::assertTrue($exception->isNetworkError());
        }
    }

    private function client(MockResponse $response, ?RequestHandlerInterface $requestHandler = null): Client
    {
        return new Client(
            ...$this->psr18($response),
            baseUri: 'https://api.example.com/rest/v1/',
            requestHandler: $requestHandler,
            requestBodyEncoder: new JsonRequestBodyEncoder(),
            responseBodyDecoder: new JsonResponseBodyDecoder(),
        );
    }

    /**
     * @return array{httpClient: Psr18Client, requestFactory: Psr18Client, streamFactory: Psr18Client}
     */
    private function psr18(MockResponse $response): array
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use ($response): MockResponse {
            $headers = $options['normalized_headers'] ?? [];

            $this->sent = [
                'method' => $method,
                'url' => $url,
                'headers' => \is_array($headers) ? $headers : [],
                'body' => $options['body'] ?? null,
            ];

            return $response;
        });

        $psr18 = new Psr18Client($mock);

        return ['httpClient' => $psr18, 'requestFactory' => $psr18, 'streamFactory' => $psr18];
    }
}

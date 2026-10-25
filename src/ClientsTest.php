<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient;

use GuzzleHttp\Psr7\HttpFactory;
use Jschaedl\ApiClient\Request\Encoder\JsonRequestBodyEncoder;
use Jschaedl\ApiClient\Request\Handler\AddHeaderHandler;
use Jschaedl\ApiClient\Request\Handler\ChainRequestHandler;
use Jschaedl\ApiClient\Request\Header;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Response\Decoder\JsonResponseBodyDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\Psr18Client;

#[CoversClass(Client::class)]
#[UsesClass(Request::class)]
#[UsesClass(ChainRequestHandler::class)]
#[UsesClass(AddHeaderHandler::class)]
#[UsesClass(JsonRequestBodyEncoder::class)]
#[UsesClass(JsonResponseBodyDecoder::class)]
#[UsesClass(Response\Response::class)]
#[UsesClass(Header::class)]
#[Group('e2e')]
final class ClientsTest extends TestCase
{
    /**
     * @return \Generator<string, array{ClientInterface, RequestFactoryInterface, StreamFactoryInterface}>
     */
    public static function httpClientProvider(): \Generator
    {
        $symfonyClient = new Psr18Client();
        yield 'Symfony HttpClient' => [$symfonyClient, $symfonyClient, $symfonyClient];

        $guzzleHttpFactory = new HttpFactory();
        yield 'Guzzle Client' => [new \GuzzleHttp\Client(), $guzzleHttpFactory, $guzzleHttpFactory];
    }

    #[DataProvider('httpClientProvider')]
    public function test_it_works_using_different_http_clients(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ): void {
        $apiClient = new Client(
            $httpClient,
            $requestFactory,
            $streamFactory,
            'https://httpbin.org/',
            new ChainRequestHandler([
                new AddHeaderHandler('Authorization', 'Bearer api_key'),
                new AddHeaderHandler('Accept', 'application/json'),
            ]),
            null,
            new JsonRequestBodyEncoder(),
            new JsonResponseBodyDecoder(),
        );

        $request = new AnythingRequest();
        $response = $apiClient->request($request);

        self::assertSame(200, $response->statusCode());

        $body = $response->body();
        self::assertIsArray($body);
        self::assertIsString($body['url'] ?? null);
        self::assertIsArray($body['headers'] ?? null);
        self::assertStringContainsString($request->uri(), $body['url']);
        self::assertSame($request->method(), $body['method'] ?? null);
        self::assertSame('Bearer api_key', $body['headers']['Authorization'] ?? null);
        self::assertSame('application/json', $body['headers']['Accept'] ?? null);
    }
}

class AnythingRequest extends Request
{
    public function __construct()
    {
        parent::__construct(
            Method::GET,
            '/anything'
        );
    }
}

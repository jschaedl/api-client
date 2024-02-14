<?php

declare(strict_types=1);

namespace Api;

use Api\Request\Encoder\JsonRequestBodyEncoder;
use Api\Request\Handler\AddHeaderHandler;
use Api\Request\Handler\ChainRequestHandler;
use Api\Request\Method;
use Api\Request\Request;
use Api\Response\Decoder\JsonResponseBodyDecoder;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * @covers \Api\Client
 *
 * @uses \Api\Request\Request
 * @uses \Api\Request\Handler\ChainRequestHandler
 * @uses \Api\Request\Handler\AddHeaderHandler
 * @uses \Api\Request\Encoder\JsonRequestBodyEncoder
 * @uses \Api\Response\Decoder\JsonResponseBodyDecoder
 * @uses \Api\Response\Response
 * @uses \Api\Request\Header
 *
 * @group e2e
 */
final class ClientsTest extends TestCase
{
    public static function httpClientProvider(): \Generator
    {
        $symfonyClient = new Psr18Client();
        yield 'Symfony HttpClient' => [$symfonyClient, $symfonyClient, $symfonyClient];

        $guzzleHttpFactory = new HttpFactory();
        yield 'Guzzle Client' => [new \GuzzleHttp\Client(), $guzzleHttpFactory, $guzzleHttpFactory];
    }

    /**
     * @dataProvider httpClientProvider
     */
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
                new AddHeaderHandler('Content-Type', 'application/json'),
            ]),
            null,
            new JsonRequestBodyEncoder(),
            new JsonResponseBodyDecoder(),
        );

        $request = new AnythingRequest();
        $response = $apiClient->request($request);

        self::assertSame(200, $response->statusCode());

        $body = $response->body();
        self::assertStringContainsString($request->uri(), $body['url']); /* @phpstan-ignore-line */
        self::assertSame($request->method(), $body['method']); /* @phpstan-ignore-line */
        self::assertSame('Bearer api_key', $body['headers']['Authorization']); /* @phpstan-ignore-line */
        self::assertSame('application/json', $body['headers']['Accept']); /* @phpstan-ignore-line */
        self::assertSame('application/json', $body['headers']['Content-Type']); /* @phpstan-ignore-line */
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

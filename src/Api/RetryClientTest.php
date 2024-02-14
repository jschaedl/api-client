<?php

declare(strict_types=1);

namespace Api;

use Api\Exception\ServerException;
use Api\Request\Method;
use Api\Request\Request;
use Api\Request\RequestInterface;
use Api\Response\ResponseInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Api\RetryClient
 *
 * @uses \Api\Exception\ResponseException
 * @uses \Api\Exception\ServerException
 *
 * @group integration
 */
final class RetryClientTest extends TestCase
{
    public function test_client_is_called_three_times(): void
    {
        $testRequest = new TestRequest();
        $traceableClient = new TestClient();
        $retryClient = new RetryClient($traceableClient, 3, true);

        try {
            $retryClient->request($testRequest);
        } catch (\Exception) {
            self::assertCount(3, $traceableClient->requests);
        }
    }
}

final class TestClient implements ClientInterface
{
    public array $requests = [];

    public function request(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        throw new ServerException(500, 'retry client test');
    }
}

final class TestRequest extends Request
{
    public function __construct()
    {
        parent::__construct(
            Method::GET,
            '/test'
        );
    }
}

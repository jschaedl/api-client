<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Retry;

use Jschaedl\ApiClient\Clock\MockClock;
use Jschaedl\ApiClient\Exception\ClientException;
use Jschaedl\ApiClient\Exception\RequestDescription;
use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\ServerException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface as Psr7RequestInterface;

#[CoversClass(GenericRetryStrategy::class)]
#[UsesClass(MockClock::class)]
#[UsesClass(Method::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(ResponseException::class)]
#[UsesClass(ClientException::class)]
#[UsesClass(ServerException::class)]
#[UsesClass(TransportException::class)]
#[UsesClass(RequestDescription::class)]
#[Group('unit')]
final class GenericRetryStrategyTest extends TestCase
{
    /**
     * @return iterable<string, array{Method, int, bool}>
     */
    public static function defaultStatusCodes(): iterable
    {
        yield 'GET 503' => [Method::GET, 503, true];
        yield 'PUT 500' => [Method::PUT, 500, true];
        yield 'DELETE 423' => [Method::DELETE, 423, true];
        yield 'POST 503' => [Method::POST, 503, false];
        yield 'PATCH 502' => [Method::PATCH, 502, false];
        yield 'POST 429' => [Method::POST, 429, true];
        yield 'GET 429' => [Method::GET, 429, true];
        yield 'GET 404' => [Method::GET, 404, false];
        yield 'GET 501' => [Method::GET, 501, false];
    }

    #[DataProvider('defaultStatusCodes')]
    public function test_default_status_codes_respect_idempotency(Method $method, int $statusCode, bool $retried): void
    {
        $delay = $this->strategy()->getDelay(...$this->failure($method, $statusCode));

        self::assertSame($retried, null !== $delay);
    }

    public function test_delays_grow_exponentially_and_are_capped(): void
    {
        $strategy = $this->strategy(delay: 1.0, multiplier: 3.0, maxDelay: 5.0);
        [$request, $failure] = $this->failure(Method::GET, 503);

        self::assertSame(1.0, $strategy->getDelay($request, $failure, 1));
        self::assertSame(3.0, $strategy->getDelay($request, $failure, 2));
        self::assertSame(5.0, $strategy->getDelay($request, $failure, 3));
    }

    public function test_a_flat_delay_is_possible(): void
    {
        $strategy = $this->strategy(delay: 20.0, multiplier: 1.0, maxDelay: 20.0);
        [$request, $failure] = $this->failure(Method::GET, 503);

        self::assertSame(20.0, $strategy->getDelay($request, $failure, 3));
    }

    public function test_jitter_stays_within_bounds(): void
    {
        $strategy = new GenericRetryStrategy(delay: 10.0, maxDelay: 100.0, jitter: 0.1);
        [$request, $failure] = $this->failure(Method::GET, 503);

        for ($i = 0; $i < 50; ++$i) {
            $delay = $strategy->getDelay($request, $failure, 1);
            self::assertNotNull($delay);
            self::assertGreaterThanOrEqual(9.0, $delay);
            self::assertLessThanOrEqual(11.0, $delay);
        }
    }

    public function test_retry_after_in_seconds_extends_the_delay(): void
    {
        self::assertSame(7.0, $this->strategy()->getDelay(...$this->failure(Method::GET, 503, '7')));
    }

    public function test_retry_after_shorter_than_the_backoff_does_not_shorten_it(): void
    {
        self::assertSame(4.0, $this->strategy()->getDelay(...$this->failure(Method::GET, 503, '0', retryCount: 3)));
    }

    public function test_retry_after_as_http_date(): void
    {
        self::assertSame(12.0, $this->strategy()->getDelay(...$this->failure(Method::GET, 429, 'Sun, 25 Oct 2026 12:00:12 GMT')));
    }

    public function test_unreadable_retry_after_falls_back_to_the_backoff(): void
    {
        self::assertSame(1.0, $this->strategy()->getDelay(...$this->failure(Method::GET, 429, 'soon')));
    }

    public function test_retry_after_beyond_max_delay_gives_up(): void
    {
        self::assertNull($this->strategy()->getDelay(...$this->failure(Method::GET, 429, '31')));
    }

    public function test_custom_status_codes_with_and_without_method_restriction(): void
    {
        $strategy = new GenericRetryStrategy([409 => GenericRetryStrategy::IDEMPOTENT_METHODS, 429], jitter: 0.0);

        self::assertNotNull($strategy->getDelay(...$this->failure(Method::PUT, 409)));
        self::assertNull($strategy->getDelay(...$this->failure(Method::POST, 409)));
        self::assertNotNull($strategy->getDelay(...$this->failure(Method::POST, 429)));
        self::assertNull($strategy->getDelay(...$this->failure(Method::GET, 503)));
    }

    public function test_network_errors_are_retried_for_idempotent_methods_only(): void
    {
        $get = new Request(Method::GET, 'items');
        $post = new Request(Method::POST, 'items');

        self::assertSame(1.0, $this->strategy()->getDelay($get, new TransportException($get, new \RuntimeException()), 1));
        self::assertNull($this->strategy()->getDelay($post, new TransportException($post, new \RuntimeException()), 1));
        self::assertNull((new GenericRetryStrategy(retryNetworkErrors: false))->getDelay($get, new TransportException($get, new \RuntimeException()), 1));
    }

    public function test_malformed_requests_are_never_retried(): void
    {
        $get = new Request(Method::GET, 'items');
        $malformed = new class extends \InvalidArgumentException implements RequestExceptionInterface {
            public function getRequest(): Psr7RequestInterface
            {
                throw new \LogicException('not needed');
            }
        };

        self::assertNull($this->strategy()->getDelay($get, new TransportException($get, $malformed), 1));
    }

    public function test_invalid_configuration_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new GenericRetryStrategy(delay: 10.0, maxDelay: 5.0);
    }

    private function strategy(float $delay = 1.0, float $multiplier = 2.0, float $maxDelay = 30.0): GenericRetryStrategy
    {
        return new GenericRetryStrategy(
            delay: $delay,
            multiplier: $multiplier,
            maxDelay: $maxDelay,
            jitter: 0.0,
            clock: new MockClock('2026-10-25 12:00:00 UTC'),
        );
    }

    /**
     * @return array{Request, ResponseException, int}
     */
    private function failure(Method $method, int $statusCode, ?string $retryAfter = null, int $retryCount = 1): array
    {
        $request = new Request($method, 'items');
        $headers = null === $retryAfter ? [] : ['Retry-After' => [$retryAfter]];

        return [$request, ResponseException::create($request, new Response('', $statusCode, $headers)), $retryCount];
    }
}

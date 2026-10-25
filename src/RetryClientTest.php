<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient;

use Jschaedl\ApiClient\Clock\MockClock;
use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\ServerException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\Request;
use Jschaedl\ApiClient\Response\Response;
use Jschaedl\ApiClient\Retry\GenericRetryStrategy;
use Jschaedl\ApiClient\Testing\MockClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

#[CoversClass(RetryClient::class)]
#[UsesClass(GenericRetryStrategy::class)]
#[UsesClass(MockClient::class)]
#[UsesClass(MockClock::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(Method::class)]
#[UsesClass(ResponseException::class)]
#[UsesClass(ServerException::class)]
#[UsesClass(Exception\ClientException::class)]
#[UsesClass(TransportException::class)]
#[UsesClass(Exception\RequestDescription::class)]
#[Group('unit')]
final class RetryClientTest extends TestCase
{
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-10-25 12:00:00');
    }

    public function test_a_get_is_retried_on_503_until_it_succeeds(): void
    {
        $inner = new MockClient([new Response('', 503), new Response('', 503), new Response('ok', 200)]);

        $response = $this->retryClient($inner)->request(new Request(Method::GET, 'items'));

        self::assertSame('ok', $response->rawBody());
        self::assertCount(3, $inner->requests());
        self::assertSame([1.0, 2.0], $this->clock->sleeps());
    }

    public function test_a_post_is_not_retried_on_503(): void
    {
        $inner = new MockClient([new Response('', 503), new Response('ok', 200)]);

        try {
            $this->retryClient($inner)->request(new Request(Method::POST, 'invoices', ['a' => 1]));
            self::fail('No exception thrown.');
        } catch (ServerException) {
            self::assertCount(1, $inner->requests());
            self::assertSame([], $this->clock->sleeps());
        }
    }

    public function test_a_post_is_retried_on_429_honouring_retry_after(): void
    {
        $inner = new MockClient([new Response('', 429, ['Retry-After' => ['5']]), new Response('ok', 200)]);

        $this->retryClient($inner)->request(new Request(Method::POST, 'invoices', ['a' => 1]));

        self::assertCount(2, $inner->requests());
        self::assertSame([5.0], $this->clock->sleeps());
    }

    public function test_a_retry_after_beyond_max_delay_is_not_waited_for(): void
    {
        $inner = new MockClient([new Response('', 429, ['Retry-After' => ['3600']]), new Response('ok', 200)]);

        $this->expectException(ResponseException::class);
        $this->expectExceptionCode(429);

        $this->retryClient($inner)->request(new Request(Method::GET, 'items'));
    }

    public function test_network_errors_are_retried_for_idempotent_methods_only(): void
    {
        $failure = static fn (Request $request): TransportException => new TransportException($request, new \RuntimeException('connection reset'));

        $get = new Request(Method::GET, 'items');
        $inner = new MockClient([$failure($get), new Response('ok', 200)]);
        self::assertSame('ok', $this->retryClient($inner)->request($get)->rawBody());

        $post = new Request(Method::POST, 'items', ['a' => 1]);
        $inner = new MockClient([$failure($post), new Response('ok', 200)]);
        $this->expectException(TransportException::class);
        $this->retryClient($inner)->request($post);
    }

    public function test_the_last_failure_is_thrown_once_max_retries_is_reached(): void
    {
        $inner = new MockClient([new Response('', 502), new Response('', 503), new Response('', 504)]);

        try {
            $this->retryClient($inner, maxRetries: 2)->request(new Request(Method::GET, 'items'));
            self::fail('No exception thrown.');
        } catch (ServerException $exception) {
            self::assertSame(504, $exception->getCode());
            self::assertCount(3, $inner->requests());
        }
    }

    public function test_no_retry_is_attempted_past_the_deadline(): void
    {
        $inner = new MockClient([new Response('', 503), new Response('', 503), new Response('ok', 200)]);

        try {
            // first wait 1s fits into 2.5s, the second (2s, ending at 3s) does not
            $this->retryClient($inner, maxDuration: 2.5)->request(new Request(Method::GET, 'items'));
            self::fail('No exception thrown.');
        } catch (ServerException) {
            self::assertCount(2, $inner->requests());
            self::assertSame([1.0], $this->clock->sleeps());
        }
    }

    public function test_retries_are_logged_without_the_query_string(): void
    {
        $logger = new RecordingLogger();
        $inner = new MockClient([new Response('', 503), new TransportException(new Request(Method::GET, 'items'), new \RuntimeException()), new Response('ok', 200)]);

        $this->retryClient($inner, logger: $logger)->request(new Request(Method::GET, 'items?token=secret'));

        self::assertSame([
            ['info', 'Retrying "{request}" after {failure} in {delay}s (retry {retry} of {max_retries}).', ['request' => 'GET items', 'failure' => 'HTTP 503', 'status_code' => 503, 'retry' => 1, 'max_retries' => 3, 'delay' => 1.0, 'max_duration' => null]],
            ['info', 'Retrying "{request}" after {failure} in {delay}s (retry {retry} of {max_retries}).', ['request' => 'GET items', 'failure' => 'a network error', 'status_code' => null, 'retry' => 2, 'max_retries' => 3, 'delay' => 2.0, 'max_duration' => null]],
        ], $logger->records);
    }

    public function test_a_retry_prevented_by_the_deadline_is_logged(): void
    {
        $logger = new RecordingLogger();
        $inner = new MockClient([new Response('', 503), new Response('', 503)]);

        try {
            $this->retryClient($inner, maxDuration: 2.5, logger: $logger)->request(new Request(Method::GET, 'items'));
            self::fail('No exception thrown.');
        } catch (ServerException) {
            self::assertCount(2, $logger->records);
            self::assertSame('Not retrying "{request}" after {failure}: waiting {delay}s would pass the {max_duration}s deadline.', $logger->records[1][1]);
            self::assertSame(2.5, $logger->records[1][2]['max_duration']);
        }
    }

    public function test_requests_that_are_not_retried_are_not_logged(): void
    {
        $logger = new RecordingLogger();
        $inner = new MockClient([new Response('', 503)]);

        try {
            $this->retryClient($inner, logger: $logger)->request(new Request(Method::POST, 'items', ['a' => 1]));
            self::fail('No exception thrown.');
        } catch (ServerException) {
            self::assertSame([], $logger->records);
        }
    }

    public function test_other_exceptions_are_not_retried(): void
    {
        $inner = new MockClient([new \LogicException('bug'), new Response('ok', 200)]);

        $this->expectException(\LogicException::class);

        $this->retryClient($inner)->request(new Request(Method::GET, 'items'));
    }

    public function test_negative_max_retries_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maxRetries cannot be negative');

        $this->retryClient(new MockClient(), maxRetries: -1);
    }

    private function retryClient(MockClient $inner, int $maxRetries = 3, ?float $maxDuration = null, ?RecordingLogger $logger = null): RetryClient
    {
        return new RetryClient(
            $inner,
            new GenericRetryStrategy(jitter: 0.0, clock: $this->clock),
            $maxRetries,
            $maxDuration,
            $this->clock,
            $logger ?? new RecordingLogger(),
        );
    }
}

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{mixed, string, array<mixed>}> */
    public array $records = [];

    /**
     * $message stays untyped: psr/log 1.x declares it without a type, and a typed override would be incompatible.
     *
     * @param string|\Stringable $message
     * @param array<mixed>       $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [$level, (string) $message, $context];
    }
}

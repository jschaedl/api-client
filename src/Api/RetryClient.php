<?php

declare(strict_types=1);

namespace Api;

use Api\Clock\ClockInterface;
use Api\Clock\NativeClock;
use Api\Exception\RequestDescription;
use Api\Exception\ResponseException;
use Api\Exception\TransportException;
use Api\Request\RequestInterface;
use Api\Response\ResponseInterface;
use Api\Retry\GenericRetryStrategy;
use Api\Retry\RetryStrategyInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Webmozart\Assert\Assert;

/**
 * Sends a failed request again when the retry strategy allows it.
 *
 * The decorated client must throw on error status codes (Client does by default); only
 * ResponseException and TransportException are considered, everything else is rethrown.
 *
 * maxDuration bounds the whole sequence, waits included: a retry whose wait would end past
 * the deadline is not attempted and the last failure is thrown instead. Each single attempt
 * is bounded by the timeout of the underlying PSR-18 client.
 *
 * Every retry, and every retry the deadline prevents, is logged at info level. Log entries
 * name the method and path only, never the query string or a body.
 *
 * @see RetryClientTest
 */
final readonly class RetryClient implements ClientInterface
{
    public function __construct(
        private ClientInterface $client,
        private RetryStrategyInterface $strategy = new GenericRetryStrategy(),
        private int $maxRetries = 3,
        private ?float $maxDuration = null,
        private ClockInterface $clock = new NativeClock(),
        private LoggerInterface $logger = new NullLogger(),
    ) {
        Assert::greaterThanEq($maxRetries, 0, 'maxRetries cannot be negative, %s given.');
        Assert::nullOrGreaterThan($maxDuration, 0.0, 'maxDuration must be greater than 0, %s given.');
    }

    public function request(RequestInterface $request): ResponseInterface
    {
        $deadline = null === $this->maxDuration ? null : $this->now() + $this->maxDuration;
        $retryCount = 0;

        while (true) {
            try {
                return $this->client->request($request);
            } catch (ResponseException|TransportException $failure) {
                $delay = $this->delayBeforeRetry($request, $failure, ++$retryCount, $deadline);

                if (null === $delay) {
                    throw $failure;
                }

                $this->clock->sleep($delay);
            }
        }
    }

    /**
     * @return float|null seconds to wait, null when the failure must be thrown
     */
    private function delayBeforeRetry(RequestInterface $request, ResponseException|TransportException $failure, int $retryCount, ?float $deadline): ?float
    {
        if ($retryCount > $this->maxRetries) {
            return null;
        }

        $delay = $this->strategy->getDelay($request, $failure, $retryCount);

        if (null === $delay) {
            return null;
        }

        if (null !== $deadline && $this->now() + $delay >= $deadline) {
            $this->log('Not retrying "{request}" after {failure}: waiting {delay}s would pass the {max_duration}s deadline.', $request, $failure, $retryCount, $delay);

            return null;
        }

        $this->log('Retrying "{request}" after {failure} in {delay}s (retry {retry} of {max_retries}).', $request, $failure, $retryCount, $delay);

        return $delay;
    }

    private function log(string $message, RequestInterface $request, ResponseException|TransportException $failure, int $retryCount, float $delay): void
    {
        $statusCode = $failure instanceof ResponseException ? $failure->statusCode() : null;

        $this->logger->info($message, [
            'request' => RequestDescription::of($request),
            'failure' => null === $statusCode ? 'a network error' : sprintf('HTTP %d', $statusCode),
            'status_code' => $statusCode,
            'retry' => $retryCount,
            'max_retries' => $this->maxRetries,
            'delay' => round($delay, 3),
            'max_duration' => $this->maxDuration,
        ]);
    }

    private function now(): float
    {
        return (float) $this->clock->now()->format('U.u');
    }
}

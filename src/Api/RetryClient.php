<?php

declare(strict_types=1);

namespace Api;

use Api\Clock\ClockInterface;
use Api\Clock\NativeClock;
use Api\Exception\ResponseException;
use Api\Exception\TransportException;
use Api\Request\RequestInterface;
use Api\Response\ResponseInterface;
use Api\Retry\GenericRetryStrategy;
use Api\Retry\RetryStrategyInterface;
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
                if ($retryCount >= $this->maxRetries) {
                    throw $failure;
                }

                $delay = $this->strategy->getDelay($request, $failure, ++$retryCount);

                if (null === $delay || (null !== $deadline && $this->now() + $delay >= $deadline)) {
                    throw $failure;
                }

                $this->clock->sleep($delay);
            }
        }
    }

    private function now(): float
    {
        return (float) $this->clock->now()->format('U.u');
    }
}

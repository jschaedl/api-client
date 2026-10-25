<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Retry;

use Jschaedl\ApiClient\Clock\ClockInterface;
use Jschaedl\ApiClient\Clock\NativeClock;
use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\Method;
use Jschaedl\ApiClient\Request\RequestInterface;
use Webmozart\Assert\Assert;

/**
 * Retries only what was provably not processed, or what is safe to repeat.
 *
 * A POST that failed with a 5xx or a timeout may have been processed anyway, and sending it
 * again can create a second order, invoice or notification. So by default:
 *
 * - 429 is retried for every method: rate limiters reject a request before processing it.
 * - 423, 425 and 500/502/503/504 are retried for idempotent methods only.
 * - Network failures (timeouts, DNS, connection resets) are retried for idempotent methods only.
 *
 * A status code configured with a list of methods is retried for those methods only; a status
 * code configured as a bare value is retried for every method:
 *
 *     new GenericRetryStrategy([409 => GenericRetryStrategy::IDEMPOTENT_METHODS, 429])
 *
 * Delays grow exponentially (delay * multiplier^(retry-1)), get +/- jitter and are capped at
 * maxDelay. A Retry-After header is honoured; when it asks for longer than maxDelay the request
 * is not retried, since retrying earlier than the server asked would only fail again.
 *
 * @see GenericRetryStrategyTest
 */
final readonly class GenericRetryStrategy implements RetryStrategyInterface
{
    public const IDEMPOTENT_METHODS = ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];

    public const DEFAULT_RETRY_STATUS_CODES = [
        423 => self::IDEMPOTENT_METHODS,
        425 => self::IDEMPOTENT_METHODS,
        429,
        500 => self::IDEMPOTENT_METHODS,
        502 => self::IDEMPOTENT_METHODS,
        503 => self::IDEMPOTENT_METHODS,
        504 => self::IDEMPOTENT_METHODS,
    ];

    /** @var array<int, list<string>|null> status code => methods, null for every method */
    private array $statusCodes;

    /**
     * @param array<int, int|list<string>> $statusCodes
     * @param float                        $delay       seconds before the first retry
     * @param float                        $jitter      0.1 = each delay varies by up to +/- 10%
     */
    public function __construct(
        array $statusCodes = self::DEFAULT_RETRY_STATUS_CODES,
        private bool $retryNetworkErrors = true,
        private float $delay = 1.0,
        private float $multiplier = 2.0,
        private float $maxDelay = 30.0,
        private float $jitter = 0.1,
        private ClockInterface $clock = new NativeClock(),
    ) {
        Assert::greaterThanEq($delay, 0.0);
        Assert::greaterThanEq($multiplier, 1.0);
        Assert::greaterThanEq($maxDelay, $delay);
        Assert::range($jitter, 0.0, 1.0);

        $normalized = [];
        foreach ($statusCodes as $key => $value) {
            if (\is_int($value)) {
                $normalized[$value] = null;
            } else {
                $normalized[$key] = array_map(strtoupper(...), $value);
            }
        }
        $this->statusCodes = $normalized;
    }

    public function getDelay(RequestInterface $request, ResponseException|TransportException $failure, int $retryCount): ?float
    {
        $method = strtoupper($request->method());

        if ($failure instanceof TransportException) {
            $retryable = $this->retryNetworkErrors && $failure->isNetworkError() && $this->isIdempotent($method);

            return $retryable ? $this->backoff($retryCount) : null;
        }

        if (!$this->isRetryableStatus($failure->statusCode(), $method)) {
            return null;
        }

        $delay = $this->backoff($retryCount);
        $retryAfter = $this->retryAfter($failure->response()->header('Retry-After'));

        if (null === $retryAfter) {
            return $delay;
        }

        return $retryAfter > $this->maxDelay ? null : max($delay, $retryAfter);
    }

    private function isRetryableStatus(int $statusCode, string $method): bool
    {
        if (!\array_key_exists($statusCode, $this->statusCodes)) {
            return false;
        }

        $methods = $this->statusCodes[$statusCode];

        return null === $methods || \in_array($method, $methods, true);
    }

    private function isIdempotent(string $method): bool
    {
        return Method::tryFrom($method)?->isIdempotent() ?? false;
    }

    private function backoff(int $retryCount): float
    {
        $delay = $this->delay * $this->multiplier ** max(0, $retryCount - 1);

        if ($this->jitter > 0) {
            // uniformly in [-jitter, +jitter]
            $delay *= 1 + $this->jitter * random_int(-1_000_000, 1_000_000) / 1_000_000;
        }

        return min($delay, $this->maxDelay);
    }

    /**
     * Retry-After is either delay-seconds or an HTTP-date (RFC 9110, section 10.2.3).
     */
    private function retryAfter(?string $value): ?float
    {
        $value = trim($value ?? '');

        if ('' === $value) {
            return null;
        }

        if (ctype_digit($value)) {
            return (float) $value;
        }

        // IMF-fixdate, always GMT: "Sun, 06 Nov 1994 08:49:37 GMT"
        $date = \DateTimeImmutable::createFromFormat('!D, d M Y H:i:s \G\M\T', $value, new \DateTimeZone('UTC'));

        if (false === $date) {
            return null;
        }

        $seconds = (float) $date->format('U.u') - (float) $this->clock->now()->format('U.u');

        return max(0.0, $seconds);
    }
}

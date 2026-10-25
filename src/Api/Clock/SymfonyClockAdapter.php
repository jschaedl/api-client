<?php

declare(strict_types=1);

namespace Api\Clock;

use Symfony\Component\Clock\ClockInterface as SymfonyClockInterface;

/**
 * Lets RetryClient and GenericRetryStrategy use a Symfony clock, e.g. the app's `clock`
 * service, so retry delays follow Symfony's MockClock in tests. Requires symfony/clock.
 *
 * @see SymfonyClockAdapterTest
 */
final readonly class SymfonyClockAdapter implements ClockInterface
{
    public function __construct(
        private SymfonyClockInterface $clock,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function sleep(float $seconds): void
    {
        $this->clock->sleep($seconds);
    }
}

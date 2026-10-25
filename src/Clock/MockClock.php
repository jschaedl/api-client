<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Clock;

/**
 * A clock for tests: sleep() advances the time instead of waiting, and every sleep is recorded.
 *
 * @see MockClockTest
 */
final class MockClock implements ClockInterface
{
    private \DateTimeImmutable $now;

    /** @var list<float> */
    private array $sleeps = [];

    public function __construct(\DateTimeImmutable|string $now = 'now')
    {
        $this->now = \is_string($now) ? new \DateTimeImmutable($now) : $now;
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function sleep(float $seconds): void
    {
        $this->sleeps[] = $seconds;
        $this->now = $this->now->modify(sprintf('%+d microseconds', (int) round($seconds * 1_000_000)));
    }

    /**
     * @return list<float>
     */
    public function sleeps(): array
    {
        return $this->sleeps;
    }
}

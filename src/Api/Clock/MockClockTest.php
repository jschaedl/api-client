<?php

declare(strict_types=1);

namespace Api\Clock;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(MockClock::class)]
#[CoversClass(NativeClock::class)]
#[Group('unit')]
final class MockClockTest extends TestCase
{
    public function test_sleep_advances_the_time_and_is_recorded(): void
    {
        $clock = new MockClock('2026-10-25 12:00:00.000000');

        $clock->sleep(1.5);
        $clock->sleep(0.25);

        self::assertSame('2026-10-25 12:00:01.750000', $clock->now()->format('Y-m-d H:i:s.u'));
        self::assertSame([1.5, 0.25], $clock->sleeps());
    }

    public function test_native_clock(): void
    {
        $clock = new NativeClock();
        $before = $clock->now();

        $clock->sleep(0.01);
        $clock->sleep(0.0);

        // both ends from the same clock, so microsecond truncation cannot make this flaky
        $elapsedMicroseconds = (int) $clock->now()->format('Uu') - (int) $before->format('Uu');
        self::assertGreaterThanOrEqual(10_000, $elapsedMicroseconds);
    }
}

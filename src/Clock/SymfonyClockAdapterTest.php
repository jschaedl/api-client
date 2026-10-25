<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Clock;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock as SymfonyMockClock;

#[CoversClass(SymfonyClockAdapter::class)]
#[Group('unit')]
final class SymfonyClockAdapterTest extends TestCase
{
    public function test_it_delegates_to_the_symfony_clock(): void
    {
        $symfonyClock = new SymfonyMockClock('2026-10-25 12:00:00');
        $clock = new SymfonyClockAdapter($symfonyClock);

        $clock->sleep(1.5);

        self::assertSame('2026-10-25 12:00:01.500000', $clock->now()->format('Y-m-d H:i:s.u'));
        self::assertEquals($symfonyClock->now(), $clock->now());
    }
}

<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Clock;

/**
 * A PSR-20 clock that can also wait, so retry delays and deadlines can be tested without sleeping.
 */
interface ClockInterface extends \Psr\Clock\ClockInterface
{
    public function sleep(float $seconds): void;
}

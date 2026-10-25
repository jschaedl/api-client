<?php

declare(strict_types=1);

namespace Api\Exception;

/**
 * Marker for every exception thrown by this library, so callers can catch them all at once.
 */
interface ApiExceptionInterface extends \Throwable
{
}

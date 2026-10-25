<?php

declare(strict_types=1);

namespace Api\Exception;

/**
 * Thrown when a response body cannot be decoded. The body itself is never part of the message.
 */
final class DecodingException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(int $statusCode, \Throwable $previous)
    {
        parent::__construct(sprintf('The body of the HTTP %d response could not be decoded.', $statusCode), $statusCode, $previous);
    }
}

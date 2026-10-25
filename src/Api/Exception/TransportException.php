<?php

declare(strict_types=1);

namespace Api\Exception;

use Api\Request\RequestInterface;
use Psr\Http\Client\RequestExceptionInterface;

/**
 * Thrown when no complete response was received: DNS, connect, TLS, timeout or a body
 * that broke off mid-read. The exception code is always 0, the cause is always chained.
 *
 * The cause's message is not repeated here, because PSR-18 clients usually put the full
 * URL - query string included - into it.
 *
 * @see TransportExceptionTest
 */
final class TransportException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        \Throwable $previous,
    ) {
        parent::__construct(
            sprintf('Request "%s" failed before a complete response was received.', RequestDescription::of($request)),
            0,
            $previous,
        );
    }

    public function request(): RequestInterface
    {
        return $this->request;
    }

    /**
     * False when the PSR-18 client rejected the request itself (malformed request):
     * sending it again cannot succeed.
     */
    public function isNetworkError(): bool
    {
        return !$this->getPrevious() instanceof RequestExceptionInterface;
    }
}

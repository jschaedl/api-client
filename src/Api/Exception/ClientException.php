<?php

declare(strict_types=1);

namespace Api\Exception;

use Api\Request\RequestInterface;
use Api\Response\ResponseInterface;

/**
 * @see ClientExceptionTest
 */
final class ClientException extends ResponseException
{
    public function __construct(RequestInterface $request, ResponseInterface $response, ?\Throwable $previous = null)
    {
        $code = $response->statusCode();

        if (400 > $code || $code >= 500) {
            throw new \InvalidArgumentException(sprintf('Class "%s" requires a response with a 4xx status code, "%d" given.', self::class, $code));
        }

        parent::__construct($request, $response, $previous);
    }
}

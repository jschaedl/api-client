<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Exception;

use Jschaedl\ApiClient\Request\RequestInterface;
use Jschaedl\ApiClient\Response\ResponseInterface;

/**
 * Thrown when the API answered with an error status code.
 *
 * The exception code is the HTTP status code. The message never contains the response body,
 * because bodies of error responses often echo back submitted (personal) data; use response()
 * when the body is really needed.
 *
 * @see ResponseExceptionTest
 */
class ResponseException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('HTTP %d returned for "%s".', $response->statusCode(), RequestDescription::of($request)),
            $response->statusCode(),
            $previous,
        );
    }

    public static function create(RequestInterface $request, ResponseInterface $response): self
    {
        $statusCode = $response->statusCode();

        return match (true) {
            $statusCode >= 500 => new ServerException($request, $response),
            $statusCode >= 400 => new ClientException($request, $response),
            default => new self($request, $response),
        };
    }

    public function request(): RequestInterface
    {
        return $this->request;
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public function statusCode(): int
    {
        return $this->response->statusCode();
    }
}

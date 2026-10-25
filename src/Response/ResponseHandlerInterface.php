<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Response;

use Jschaedl\ApiClient\Request\RequestInterface;

/**
 * Runs before the client checks the status code, so a handler can turn an expected
 * error response (e.g. 404 = "member not found") into a domain exception of its own.
 */
interface ResponseHandlerInterface
{
    public function handle(ResponseInterface $response, RequestInterface $request): ResponseInterface;
}

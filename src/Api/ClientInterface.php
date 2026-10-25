<?php

declare(strict_types=1);

namespace Api;

use Api\Exception\ApiExceptionInterface;
use Api\Exception\ResponseException;
use Api\Exception\TransportException;
use Api\Request\RequestInterface;
use Api\Response\ResponseInterface;

interface ClientInterface
{
    /**
     * @throws ResponseException     on a 4xx/5xx response (unless the client is told not to)
     * @throws TransportException    when no complete response was received
     * @throws ApiExceptionInterface
     */
    public function request(RequestInterface $request): ResponseInterface;
}

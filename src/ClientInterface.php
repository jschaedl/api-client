<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient;

use Jschaedl\ApiClient\Exception\ApiExceptionInterface;
use Jschaedl\ApiClient\Exception\ResponseException;
use Jschaedl\ApiClient\Exception\TransportException;
use Jschaedl\ApiClient\Request\RequestInterface;
use Jschaedl\ApiClient\Response\ResponseInterface;

interface ClientInterface
{
    /**
     * @throws ResponseException     on a 4xx/5xx response (unless the client is told not to)
     * @throws TransportException    when no complete response was received
     * @throws ApiExceptionInterface
     */
    public function request(RequestInterface $request): ResponseInterface;
}

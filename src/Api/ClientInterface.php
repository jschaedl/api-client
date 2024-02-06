<?php

declare(strict_types=1);

namespace Api;

use Api\Exception\ResponseException;
use Api\Request\RequestInterface;
use Api\Response\ResponseInterface;

interface ClientInterface
{
    /**
     * @throws ResponseException
     */
    public function request(RequestInterface $request): ResponseInterface;
}

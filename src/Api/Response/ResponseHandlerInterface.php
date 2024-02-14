<?php

declare(strict_types=1);

namespace Api\Response;

use Api\Exception\ResponseException;

interface ResponseHandlerInterface
{
    /**
     * @throws ResponseException
     */
    public function handle(Response $response): Response;
}

<?php

declare(strict_types=1);

namespace Api\Response;

use Api\Exception\ResponseException;
use Psr\Http\Message\ResponseInterface as Psr7Response;

interface ResponseHandlerInterface
{
    /**
     * @throws ResponseException
     */
    public function handle(Psr7Response $psr7Response): Psr7Response;
}

<?php

declare(strict_types=1);

namespace Api\Request\Handler;

use Api\Request\HeadersAwareRequestInterface;
use Api\Request\RequestHandlerInterface;
use Api\Request\RequestInterface;

final readonly class AddHeaderHandler implements RequestHandlerInterface
{
    public function __construct(
        private string $name,
        private string $value,
    ) {
    }

    public function handle(RequestInterface $request): RequestInterface
    {
        if (!$request instanceof HeadersAwareRequestInterface) {
            return $request;
        }

        return $request->addHeader($this->name, $this->value);
    }
}

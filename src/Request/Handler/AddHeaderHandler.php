<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request\Handler;

use Jschaedl\ApiClient\Request\HeadersAwareRequestInterface;
use Jschaedl\ApiClient\Request\RequestHandlerInterface;
use Jschaedl\ApiClient\Request\RequestInterface;

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

<?php

declare(strict_types=1);

namespace Api\Request\Handler;

use Api\Request\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface as Psr7Request;

final class AddHeaderHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $value,
    ) {
    }

    public function handle(Psr7Request $psr7Request): Psr7Request
    {
        return $psr7Request->withHeader($this->name, $this->value);
    }
}

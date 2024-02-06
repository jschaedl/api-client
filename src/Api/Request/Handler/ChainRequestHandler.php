<?php

declare(strict_types=1);

namespace Api\Request\Handler;

use Api\Request\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface as Psr7Request;
use Webmozart\Assert\Assert;

final class ChainRequestHandler implements RequestHandlerInterface
{
    /**
     * @param RequestHandlerInterface[] $requestHandlers
     */
    public function __construct(
        private readonly iterable $requestHandlers
    ) {
        Assert::allIsInstanceOf($requestHandlers, RequestHandlerInterface::class);
    }

    public function handle(Psr7Request $psr7Request): Psr7Request
    {
        foreach ($this->requestHandlers as $requestHandler) {
            $psr7Request = $requestHandler->handle($psr7Request);
        }

        return $psr7Request;
    }
}

<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request\Handler;

use Jschaedl\ApiClient\Request\RequestHandlerInterface;
use Jschaedl\ApiClient\Request\RequestInterface;
use Webmozart\Assert\Assert;

final readonly class ChainRequestHandler implements RequestHandlerInterface
{
    /**
     * @param RequestHandlerInterface[] $requestHandlers
     */
    public function __construct(
        private iterable $requestHandlers
    ) {
        Assert::allIsInstanceOf($requestHandlers, RequestHandlerInterface::class);
    }

    public function handle(RequestInterface $request): RequestInterface
    {
        foreach ($this->requestHandlers as $requestHandler) {
            $request = $requestHandler->handle($request);
        }

        return $request;
    }
}

<?php

declare(strict_types=1);

namespace Api\Response\Handler;

use Api\Response\ResponseHandlerInterface;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Webmozart\Assert\Assert;

final class ChainResponseHandler implements ResponseHandlerInterface
{
    /**
     * @param ResponseHandlerInterface[] $responseHandlers
     */
    public function __construct(
        private iterable $responseHandlers
    ) {
        Assert::allIsInstanceOf($responseHandlers, ResponseHandlerInterface::class);
    }

    public function handle(Psr7Response $psr7Response): Psr7Response
    {
        foreach ($this->responseHandlers as $responseHandler) {
            $psr7Response = $responseHandler->handle($psr7Response);
        }

        return $psr7Response;
    }
}

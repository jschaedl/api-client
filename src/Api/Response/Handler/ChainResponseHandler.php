<?php

declare(strict_types=1);

namespace Api\Response\Handler;

use Api\Response\Response;
use Api\Response\ResponseHandlerInterface;
use Webmozart\Assert\Assert;

final readonly class ChainResponseHandler implements ResponseHandlerInterface
{
    /**
     * @param ResponseHandlerInterface[] $responseHandlers
     */
    public function __construct(
        private iterable $responseHandlers
    ) {
        Assert::allIsInstanceOf($responseHandlers, ResponseHandlerInterface::class);
    }

    public function handle(Response $response): Response
    {
        foreach ($this->responseHandlers as $responseHandler) {
            $response = $responseHandler->handle($response);
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Api\Response\Handler;

use Api\Request\RequestInterface;
use Api\Response\ResponseHandlerInterface;
use Api\Response\ResponseInterface;
use Webmozart\Assert\Assert;

final readonly class ChainResponseHandler implements ResponseHandlerInterface
{
    /**
     * @param iterable<ResponseHandlerInterface> $responseHandlers
     */
    public function __construct(
        private iterable $responseHandlers,
    ) {
        Assert::allIsInstanceOf($responseHandlers, ResponseHandlerInterface::class);
    }

    public function handle(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        foreach ($this->responseHandlers as $responseHandler) {
            $response = $responseHandler->handle($response, $request);
        }

        return $response;
    }
}

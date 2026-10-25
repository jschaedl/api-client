<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Response\Handler;

use Jschaedl\ApiClient\Request\RequestInterface;
use Jschaedl\ApiClient\Response\ResponseHandlerInterface;
use Jschaedl\ApiClient\Response\ResponseInterface;
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

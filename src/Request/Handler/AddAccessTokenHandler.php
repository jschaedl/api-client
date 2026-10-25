<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request\Handler;

use Jschaedl\ApiClient\Request\HeadersAwareRequestInterface;
use Jschaedl\ApiClient\Request\RequestHandlerInterface;
use Jschaedl\ApiClient\Request\RequestInterface;

final readonly class AddAccessTokenHandler implements RequestHandlerInterface
{
    public function __construct(
        private AccessTokenProviderInterface $tokenProvider,
        private string $headerName = 'Authorization',
    ) {
    }

    public function handle(RequestInterface $request): RequestInterface
    {
        if (!$request instanceof HeadersAwareRequestInterface) {
            return $request;
        }

        $token = $this->tokenProvider->provideToken();

        return $request->addHeader($this->headerName, sprintf('%s %s', $token->type, $token->token));
    }
}

<?php

declare(strict_types=1);

namespace Api\Request\Handler;

use Api\Request\RequestHandlerInterface;
use Api\Request\RequestInterface;

final readonly class AddAccessTokenHandler implements RequestHandlerInterface
{
    public function __construct(
        private AccessTokenProviderInterface $tokenProvider,
        private string $headerName = 'Authorization',
    ) {
    }

    public function handle(RequestInterface $request): RequestInterface
    {
        $token = $this->tokenProvider->provideToken();

        return $request->addHeader($this->headerName, sprintf('%s %s', $token->type, $token->token));
    }
}

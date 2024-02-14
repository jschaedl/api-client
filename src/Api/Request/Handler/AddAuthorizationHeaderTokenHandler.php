<?php

declare(strict_types=1);

namespace Api\Request\Handler;

use Api\Request\RequestHandlerInterface;
use Api\Request\RequestInterface;

final readonly class AddAuthorizationHeaderTokenHandler implements RequestHandlerInterface
{
    public function __construct(
        private TokenProviderInterface $tokenProvider,
    ) {
    }

    public function handle(RequestInterface $request): RequestInterface
    {
        $token = $this->tokenProvider->provideToken();

        return $request->addHeader('Authorization', sprintf('%s %s', $token->type, $token->token));
    }
}

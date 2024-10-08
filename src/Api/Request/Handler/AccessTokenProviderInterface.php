<?php

declare(strict_types=1);

namespace Api\Request\Handler;

interface AccessTokenProviderInterface
{
    public function provideToken(): AccessToken;
}

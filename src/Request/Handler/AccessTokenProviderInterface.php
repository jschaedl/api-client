<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request\Handler;

interface AccessTokenProviderInterface
{
    public function provideToken(): AccessToken;
}

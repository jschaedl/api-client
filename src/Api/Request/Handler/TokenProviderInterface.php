<?php

declare(strict_types=1);

namespace Api\Request\Handler;

interface TokenProviderInterface
{
    public function provideToken(): Token;
}

<?php

declare(strict_types=1);

namespace Api\Request\Handler;

final readonly class Token
{
    public function __construct(
        public string $type,
        public string $token,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Api\Request\Handler;

final readonly class AccessToken
{
    public function __construct(
        public string $type,
        public string $token,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now->getTimestamp() > $this->expiresAt->getTimestamp();
    }
}

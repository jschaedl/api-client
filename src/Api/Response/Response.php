<?php

declare(strict_types=1);

namespace Api\Response;

final class Response implements ResponseInterface
{
    public function __construct(
        private readonly mixed $body,
        private readonly int $statusCode,
        private readonly array $headers = []
    ) {
    }

    public function body(): mixed
    {
        return $this->body;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return mixed[]
     */
    public function headers(): array
    {
        return $this->headers;
    }
}

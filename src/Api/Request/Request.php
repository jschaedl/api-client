<?php

declare(strict_types=1);

namespace Api\Request;

final class Request implements RequestInterface
{
    private array $headers;
    private ?array $body;

    public function __construct(
        public readonly Method $method,
        public readonly string $uri
    ) {
        $this->headers = [];
        $this->body = null;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withBody(array $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function method(): string
    {
        return $this->method->value;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?array
    {
        return $this->body;
    }
}

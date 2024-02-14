<?php

declare(strict_types=1);

namespace Api\Request;

class Request implements RequestInterface
{
    /**
     * @param Header[] $headers
     */
    public function __construct(
        public readonly Method $method,
        public readonly string $uri,
        private array $headers = [],
        private ?array $body = null,
    ) {
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[] = new Header($name, $value);

        return $this;
    }

    public function setBody(array $body): self
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

    /**
     * @return Header[]
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?array
    {
        return $this->body;
    }
}

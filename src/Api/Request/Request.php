<?php

declare(strict_types=1);

namespace Api\Request;

class Request implements HeadersAwareRequestInterface
{
    use HeadersAwareRequestTrait;

    /**
     * @param Header[] $headers
     */
    public function __construct(
        public readonly Method $method,
        public readonly string $uri,
        private ?array $body = null,
        array $headers = [],
    ) {
        $this->headers = $headers;
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

    public function body(): ?array
    {
        return $this->body;
    }
}

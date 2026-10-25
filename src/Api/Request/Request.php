<?php

declare(strict_types=1);

namespace Api\Request;

class Request implements HeadersAwareRequestInterface, BodyEncoderAwareRequestInterface
{
    use HeadersAwareRequestTrait;

    /**
     * @param array<mixed>|string|null $body
     * @param Header[]                 $headers
     */
    public function __construct(
        public readonly Method $method,
        public readonly string $uri,
        private array|string|null $body = null,
        array $headers = [],
        private ?RequestBodyEncoderInterface $bodyEncoder = null,
    ) {
        $this->headers = $headers;
    }

    /**
     * @param array<mixed>|string $body
     */
    public function setBody(array|string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function setBodyEncoder(RequestBodyEncoderInterface $bodyEncoder): self
    {
        $this->bodyEncoder = $bodyEncoder;

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

    public function body(): array|string|null
    {
        return $this->body;
    }

    public function bodyEncoder(): ?RequestBodyEncoderInterface
    {
        return $this->bodyEncoder;
    }
}

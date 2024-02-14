<?php

declare(strict_types=1);

namespace Api\Request;

interface RequestInterface
{
    public function method(): string;

    public function uri(): string;

    /**
     * @return Header[]
     */
    public function headers(): array;

    public function body(): ?array;

    // public function setMethod(string $method): self;
    // public function setUri(string $uri): self;
    public function addHeader(string $name, string $value): self;
    // public function setBody(array $body): self;
}

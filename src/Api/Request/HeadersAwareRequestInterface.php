<?php

declare(strict_types=1);

namespace Api\Request;

interface HeadersAwareRequestInterface extends RequestInterface
{
    /**
     * @return Header[]
     */
    public function headers(): array;

    public function addHeader(string $name, string $value): self;
}

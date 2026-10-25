<?php

declare(strict_types=1);

namespace Jschaedl\ApiClient\Request;

interface HeadersAwareRequestInterface extends RequestInterface
{
    /**
     * @return Header[]
     */
    public function headers(): array;

    public function addHeader(string $name, string $value): self;
}
